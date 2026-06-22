<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

class SiteVariables
{
    private const TOKEN_PATTERN = '/\[\[([a-z0-9](?:[a-z0-9-]*[a-z0-9])?)\]\]/i';

    private static ?string $cacheKey = null;

    /**
     * @var array<string, string>|null
     */
    private static ?array $tokenMap = null;

    /**
     * @param  array<int, mixed>|null  $variables
     * @return array<int, array{name: string, variable: string, value: string}>
     */
    public static function normalizeRows(mixed $variables): array
    {
        return collect(is_array($variables) ? $variables : [])
            ->filter(fn (mixed $row): bool => is_array($row))
            ->map(function (array $row): ?array {
                $name = trim((string) ($row['name'] ?? ''));
                $variable = self::normalizeKey($row['variable'] ?? $name);
                $value = trim((string) ($row['value'] ?? ''));

                if ($name === '' || $variable === '' || $value === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'variable' => $variable,
                    'value' => $value,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function normalizeKey(mixed $value): string
    {
        $value = trim((string) $value);
        $value = trim($value, '[]');
        $value = preg_replace('/^\[\[|\]\]$/', '', $value) ?? $value;

        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();
    }

    public static function tokenFor(mixed $variable): ?string
    {
        $variable = self::normalizeKey($variable);

        return $variable === '' ? null : "[[{$variable}]]";
    }

    public static function renderHtml(mixed $content, ?SiteSetting $settings = null): string
    {
        $content = (string) $content;

        if ($content === '') {
            return '';
        }

        return self::replaceTokens($content, $settings);
    }

    public static function renderText(mixed $content, ?SiteSetting $settings = null): string
    {
        $content = (string) $content;

        if ($content === '') {
            return '';
        }

        $tokens = [];
        $placeholderText = preg_replace_callback(self::TOKEN_PATTERN, function (array $match) use (&$tokens): string {
            $placeholder = "\u{E000}".count($tokens)."\u{E001}";
            $tokens[$placeholder] = '[['.self::normalizeKey($match[1]).']]';

            return $placeholder;
        }, $content) ?? $content;

        $escaped = e($placeholderText);

        foreach ($tokens as $placeholder => $token) {
            $escaped = str_replace(e($placeholder), $token, $escaped);
        }

        return self::replaceTokens($escaped, $settings);
    }

    public static function renderTextWithLineBreaks(mixed $content, ?SiteSetting $settings = null): string
    {
        return nl2br(self::renderText($content, $settings));
    }

    public static function renderPlainText(mixed $content, ?SiteSetting $settings = null): string
    {
        $content = (string) $content;

        if ($content === '') {
            return '';
        }

        return self::replaceTokens($content, $settings, plainTextValues: true);
    }

    public static function variableValue(string $variable, ?SiteSetting $settings = null): ?string
    {
        $variable = self::normalizeKey($variable);

        return self::tokenMap($settings)[$variable] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private static function tokenMap(?SiteSetting $settings = null): array
    {
        $settings ??= SiteSetting::query()->first();

        if (! $settings) {
            return [];
        }

        $cacheKey = implode(':', [
            $settings->getKey() ?: 'new',
            (string) $settings->updated_at,
            md5(json_encode($settings->site_variables ?? []) ?: ''),
        ]);

        if (self::$cacheKey === $cacheKey && self::$tokenMap !== null) {
            return self::$tokenMap;
        }

        self::$cacheKey = $cacheKey;
        self::$tokenMap = collect(self::normalizeRows($settings->site_variables))
            ->mapWithKeys(fn (array $row): array => [$row['variable'] => $row['value']])
            ->all();

        return self::$tokenMap;
    }

    private static function replaceTokens(string $content, ?SiteSetting $settings = null, bool $plainTextValues = false): string
    {
        $tokens = self::tokenMap($settings);

        if ($tokens === [] || ! str_contains($content, '[[')) {
            return $content;
        }

        return preg_replace_callback(self::TOKEN_PATTERN, function (array $match) use ($tokens, $plainTextValues): string {
            $variable = self::normalizeKey($match[1]);

            if (! array_key_exists($variable, $tokens)) {
                return $match[0];
            }

            return $plainTextValues ? self::plainTextValue($tokens[$variable]) : $tokens[$variable];
        }, $content) ?? $content;
    }

    private static function plainTextValue(mixed $value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00A0}", "\u{200B}", "\u{200C}", "\u{200D}", "\u{FEFF}"], ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
