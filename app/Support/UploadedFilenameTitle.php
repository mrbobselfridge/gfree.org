<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class UploadedFilenameTitle
{
    /**
     * @return array<int, string>
     */
    private const MONTH_NAMES = [
        1 => 'jan(?:uary)?',
        2 => 'feb(?:ruary)?',
        3 => 'mar(?:ch)?',
        4 => 'apr(?:il)?',
        5 => 'may',
        6 => 'jun(?:e)?',
        7 => 'jul(?:y)?',
        8 => 'aug(?:ust)?',
        9 => 'sep(?:t(?:ember)?)?',
        10 => 'oct(?:ober)?',
        11 => 'nov(?:ember)?',
        12 => 'dec(?:ember)?',
    ];

    public static function fromStem(string $stem, ?int $defaultYear = null): ?string
    {
        $defaultYear ??= (int) now()->year;
        $dateMatch = self::firstDateMatch($stem, $defaultYear);
        $title = self::formatTitleText($dateMatch ? self::removeMatch($stem, $dateMatch['offset'], $dateMatch['length']) : $stem);

        if (! $dateMatch) {
            return filled($title) ? $title : null;
        }

        return trim(collect([$title, $dateMatch['date']->format('F j, Y')])->filter()->implode(' '));
    }

    public static function dateFromStem(string $stem, ?int $defaultYear = null): ?CarbonImmutable
    {
        $defaultYear ??= (int) now()->year;

        return self::firstDateMatch($stem, $defaultYear)['date'] ?? null;
    }

    /**
     * @return array{date: CarbonImmutable, offset: int, length: int}|null
     */
    private static function firstDateMatch(string $stem, int $defaultYear): ?array
    {
        $matches = [];

        foreach (self::datePatterns() as $pattern) {
            if (! preg_match_all($pattern, $stem, $patternMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                continue;
            }

            foreach ($patternMatches as $match) {
                $date = self::dateFromMatch($match, $defaultYear);

                if (! $date) {
                    continue;
                }

                $matches[] = [
                    'date' => $date,
                    'offset' => $match[0][1],
                    'length' => strlen($match[0][0]),
                ];
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, fn (array $a, array $b): int => $a['offset'] <=> $b['offset']);

        return $matches[0];
    }

    /**
     * @return array<int, string>
     */
    private static function datePatterns(): array
    {
        $separator = '[\s_.\/-]+';
        $monthNames = implode('|', self::MONTH_NAMES);

        return [
            '/(?<!\d)(?<year>\d{4})'.$separator.'(?<month>\d{1,2})'.$separator.'(?<day>\d{1,2})(?!\d)/i',
            '/(?<!\d)(?<first>\d{1,2})'.$separator.'(?<second>\d{1,2})'.$separator.'(?<year>\d{2}|\d{4})(?!\d)/i',
            '/(?<!\d)(?<first>\d{1,2})'.$separator.'(?<second>\d{1,2})(?![\d._\/-])/i',
            '/(?<![a-z])(?<month_name>'.$monthNames.')'.$separator.'(?<day>\d{1,2})(?:'.$separator.'(?<year>\d{2}|\d{4}))?(?!\d)/i',
            '/(?<!\d)(?<day>\d{1,2})'.$separator.'(?<month_name>'.$monthNames.')(?:'.$separator.'(?<year>\d{2}|\d{4}))?(?![a-z])/i',
        ];
    }

    /**
     * @param  array<string|int, array{0: string, 1: int}>  $match
     */
    private static function dateFromMatch(array $match, int $defaultYear): ?CarbonImmutable
    {
        $year = self::normalizeYear(self::matchValue($match, 'year'), $defaultYear);
        $monthName = self::matchValue($match, 'month_name');

        if ($monthName !== null) {
            return self::validDate(
                $year,
                self::monthNumber($monthName),
                self::integerValue(self::matchValue($match, 'day')),
            );
        }

        $month = self::integerValue(self::matchValue($match, 'month'));
        $day = self::integerValue(self::matchValue($match, 'day'));

        if ($month !== null && $day !== null) {
            return self::validDate($year, $month, $day);
        }

        $first = self::integerValue(self::matchValue($match, 'first'));
        $second = self::integerValue(self::matchValue($match, 'second'));

        if ($first === null || $second === null) {
            return null;
        }

        if ($first > 12 && $second <= 12) {
            return self::validDate($year, $second, $first);
        }

        return self::validDate($year, $first, $second);
    }

    private static function validDate(?int $year, ?int $month, ?int $day): ?CarbonImmutable
    {
        if ($year === null || $month === null || $day === null || ! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::create($year, $month, $day);
    }

    private static function normalizeYear(?string $year, int $defaultYear): int
    {
        if ($year === null || $year === '') {
            return $defaultYear;
        }

        if (strlen($year) === 2) {
            $value = (int) $year;

            return $value >= 70 ? 1900 + $value : 2000 + $value;
        }

        return (int) $year;
    }

    private static function monthNumber(?string $monthName): ?int
    {
        if ($monthName === null) {
            return null;
        }

        $monthName = strtolower($monthName);

        foreach (self::MONTH_NAMES as $number => $pattern) {
            if (preg_match('/^'.$pattern.'$/i', $monthName) === 1) {
                return $number;
            }
        }

        return null;
    }

    /**
     * @param  array<string|int, array{0: string, 1: int}>  $match
     */
    private static function matchValue(array $match, string $key): ?string
    {
        if (! isset($match[$key]) || $match[$key][1] < 0 || $match[$key][0] === '') {
            return null;
        }

        return $match[$key][0];
    }

    private static function integerValue(?string $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private static function removeMatch(string $stem, int $offset, int $length): string
    {
        return substr($stem, 0, $offset).' '.substr($stem, $offset + $length);
    }

    private static function formatTitleText(string $value): ?string
    {
        $title = Str::of($value)
            ->replaceMatches('/[\s_.-]+/', ' ')
            ->trim()
            ->headline()
            ->toString();

        return filled($title) ? $title : null;
    }
}
