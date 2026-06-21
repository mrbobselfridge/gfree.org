<?php

namespace App\Support;

use App\Filament\Admin\Forms\RichContentBlocks\EmbedBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

class RichContent
{
    public static function hasRenderableContent(mixed $content): bool
    {
        if (blank($content)) {
            return false;
        }

        $content = (string) $content;

        if (str_contains($content, 'data-type="customBlock"')) {
            return true;
        }

        return self::plainText($content) !== '';
    }

    public static function nullable(mixed $content): ?string
    {
        if (! self::hasRenderableContent($content)) {
            return null;
        }

        return trim((string) $content);
    }

    public static function plainText(mixed $content): string
    {
        $text = html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\u{00A0}", "\u{200B}", "\u{200C}", "\u{200D}", "\u{FEFF}"], ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    public static function render(mixed $content): string
    {
        if (! self::hasRenderableContent($content)) {
            return '';
        }

        $content = (string) $content;

        if (! str_contains($content, 'data-type="customBlock"')) {
            return SiteVariables::renderHtml($content);
        }

        $html = RichContentRenderer::make($content)
            ->customBlocks([
                EmbedBlock::class,
            ])
            ->toUnsafeHtml();

        return SiteVariables::renderHtml($html);
    }
}
