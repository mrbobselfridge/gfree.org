<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CLAY = [
        'key' => 'clay',
        'name' => 'Clay',
        'hex' => '#c96f5a',
    ];

    private const BLUE = [
        'key' => 'blue',
        'name' => 'Blue',
        'hex' => '#3066ff',
    ];

    public function up(): void
    {
        $this->replacePaletteColor('clay', self::BLUE);
        $this->replaceContentBlockBackgrounds('clay', 'blue');
    }

    public function down(): void
    {
        $this->replacePaletteColor('blue', self::CLAY);
        $this->replaceContentBlockBackgrounds('blue', 'clay');
    }

    /**
     * @param  array{key: string, name: string, hex: string}  $replacement
     */
    private function replacePaletteColor(string $oldKey, array $replacement): void
    {
        if (! Schema::hasTable('site_settings') || ! Schema::hasColumn('site_settings', 'design_background_colors')) {
            return;
        }

        DB::table('site_settings')
            ->select(['id', 'design_background_colors'])
            ->whereNotNull('design_background_colors')
            ->orderBy('id')
            ->chunkById(100, function ($settings) use ($oldKey, $replacement): void {
                foreach ($settings as $setting) {
                    $colors = json_decode((string) $setting->design_background_colors, true);

                    if (! is_array($colors)) {
                        continue;
                    }

                    $hasReplacement = collect($colors)->contains(
                        fn (mixed $color): bool => is_array($color) && ($color['key'] ?? null) === $replacement['key']
                    );

                    $changed = false;
                    $colors = collect($colors)
                        ->map(function (mixed $color) use ($oldKey, $replacement, $hasReplacement, &$changed): ?array {
                            if (! is_array($color)) {
                                return null;
                            }

                            if (($color['key'] ?? null) !== $oldKey) {
                                return $color;
                            }

                            $changed = true;

                            return $hasReplacement ? null : $replacement;
                        })
                        ->filter()
                        ->values()
                        ->all();

                    if (! $changed) {
                        continue;
                    }

                    DB::table('site_settings')
                        ->where('id', $setting->id)
                        ->update(['design_background_colors' => json_encode($colors)]);
                }
            });
    }

    private function replaceContentBlockBackgrounds(string $from, string $to): void
    {
        foreach (['pages', 'homepage_contents'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'content_blocks')) {
                continue;
            }

            DB::table($table)
                ->select(['id', 'content_blocks'])
                ->whereNotNull('content_blocks')
                ->orderBy('id')
                ->chunkById(100, function ($records) use ($table, $from, $to): void {
                    foreach ($records as $record) {
                        $blocks = json_decode((string) $record->content_blocks, true);

                        if (! is_array($blocks)) {
                            continue;
                        }

                        $changed = false;
                        $blocks = $this->replaceBackgroundValue($blocks, $from, $to, $changed);

                        if (! $changed) {
                            continue;
                        }

                        DB::table($table)
                            ->where('id', $record->id)
                            ->update(['content_blocks' => json_encode($blocks)]);
                    }
                });
        }
    }

    private function replaceBackgroundValue(mixed $value, string $from, string $to, bool &$changed): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $childValue) {
            if ($key === 'background' && $childValue === $from) {
                $value[$key] = $to;
                $changed = true;

                continue;
            }

            $value[$key] = $this->replaceBackgroundValue($childValue, $from, $to, $changed);
        }

        return $value;
    }
};
