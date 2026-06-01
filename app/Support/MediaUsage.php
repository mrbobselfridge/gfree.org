<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\HomepageBanner;
use App\Models\HomepageContent;
use App\Models\Ministry;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MediaUsage
{
    /**
     * @param  array<int, string>  $paths
     * @return array<string, array<int, array<string, string>>>
     */
    public static function forImages(array $paths): array
    {
        $paths = collect($paths)->filter()->unique()->values();

        if ($paths->isEmpty()) {
            return [];
        }

        $usage = $paths->mapWithKeys(fn (string $path): array => [$path => []])->all();

        foreach (self::directImageFieldDefinitions() as $definition) {
            self::addDirectImageFieldUsages($usage, $paths, $definition);
        }

        foreach (self::contentBlockDefinitions() as $definition) {
            self::addContentBlockUsages($usage, $paths, $definition);
        }

        return $usage;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function directImageFieldDefinitions(): array
    {
        return [
            [
                'model' => HomepageBanner::class,
                'record_label' => 'Homepage Banner',
                'title' => 'title',
                'fields' => [
                    'image_path' => 'Banner image',
                ],
            ],
            [
                'model' => Announcement::class,
                'record_label' => 'Announcement',
                'title' => 'title',
                'fields' => [
                    'image_path' => 'Announcement image',
                ],
            ],
            [
                'model' => Ministry::class,
                'record_label' => 'Ministry',
                'title' => 'name',
                'fields' => [
                    'hero_image_path' => 'Hero image',
                    'card_image_path' => 'Card image',
                ],
            ],
            [
                'model' => Page::class,
                'record_label' => 'Page',
                'title' => 'title',
                'fields' => [
                    'hero_image_path' => 'Header image',
                ],
            ],
            [
                'model' => StaffMember::class,
                'record_label' => 'Leader',
                'title' => 'name',
                'fields' => [
                    'photo_path' => 'Leadership image',
                ],
            ],
            [
                'model' => SiteSetting::class,
                'record_label' => 'Site Settings',
                'title' => 'church_name',
                'fields' => [
                    'announcements_image_path' => 'Announcements landing image',
                    'leadership_image_path' => 'Leadership landing image',
                    'ministry_image_path' => 'Ministries landing image',
                    'sermons_image_path' => 'Sermons landing image',
                    'bulletins_image_path' => 'Bulletins landing image',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function contentBlockDefinitions(): array
    {
        return [
            [
                'model' => HomepageContent::class,
                'record_label' => 'Homepage Content',
                'title' => 'intro_title',
                'field' => 'content_blocks',
            ],
            [
                'model' => Page::class,
                'record_label' => 'Page',
                'title' => 'title',
                'field' => 'content_blocks',
            ],
            [
                'model' => Announcement::class,
                'record_label' => 'Announcement',
                'title' => 'title',
                'field' => 'content_blocks',
            ],
            [
                'model' => Ministry::class,
                'record_label' => 'Ministry',
                'title' => 'name',
                'field' => 'content_blocks',
            ],
            [
                'model' => StaffMember::class,
                'record_label' => 'Leader',
                'title' => 'name',
                'field' => 'content_blocks',
            ],
        ];
    }

    /**
     * @param  array<string, array<int, array<string, string>>>  $usage
     * @param  Collection<int, string>  $paths
     * @param  array<string, mixed>  $definition
     */
    private static function addDirectImageFieldUsages(array &$usage, Collection $paths, array $definition): void
    {
        /** @var class-string<Model> $model */
        $model = $definition['model'];
        $fields = array_keys($definition['fields']);
        $titleField = $definition['title'];

        $model::query()
            ->where(function ($query) use ($fields, $paths): void {
                foreach ($fields as $field) {
                    $query->orWhereIn($field, $paths);
                }
            })
            ->get()
            ->each(function (Model $record) use (&$usage, $definition, $fields, $titleField): void {
                foreach ($fields as $field) {
                    foreach (self::pathValues($record->getAttribute($field)) as $path) {
                        if (! array_key_exists($path, $usage)) {
                            continue;
                        }

                        $usage[$path][] = self::usageItem(
                            $definition['record_label'],
                            self::recordTitle($record, $titleField),
                            $definition['fields'][$field],
                        );
                    }
                }
            });
    }

    /**
     * @param  array<string, array<int, array<string, string>>>  $usage
     * @param  Collection<int, string>  $paths
     * @param  array<string, mixed>  $definition
     */
    private static function addContentBlockUsages(array &$usage, Collection $paths, array $definition): void
    {
        /** @var class-string<Model> $model */
        $model = $definition['model'];
        $field = $definition['field'];
        $titleField = $definition['title'];

        $model::query()
            ->whereNotNull($field)
            ->get()
            ->each(function (Model $record) use (&$usage, $paths, $definition, $field, $titleField): void {
                $usedPaths = self::contentBlockImagePaths($record->getAttribute($field))
                    ->intersect($paths)
                    ->unique();

                foreach ($usedPaths as $path) {
                    $usage[$path][] = self::usageItem(
                        $definition['record_label'],
                        self::recordTitle($record, $titleField),
                        'Content image',
                    );
                }
            });
    }

    /**
     * @return Collection<int, string>
     */
    private static function contentBlockImagePaths(mixed $blocks): Collection
    {
        if (is_string($blocks)) {
            $blocks = json_decode($blocks, true);
        }

        return collect(self::findImagePaths(is_array($blocks) ? $blocks : []))
            ->filter()
            ->values();
    }

    /**
     * @param  array<mixed>  $value
     * @return array<int, string>
     */
    private static function findImagePaths(array $value): array
    {
        $paths = [];

        foreach ($value as $key => $item) {
            if ($key === 'image_path') {
                array_push($paths, ...self::pathValues($item));
            }

            if (is_array($item)) {
                array_push($paths, ...self::findImagePaths($item));
            }
        }

        return $paths;
    }

    /**
     * @return array<int, string>
     */
    private static function pathValues(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->flatMap(fn (mixed $item): array => self::pathValues($item))
                ->all();
        }

        return filled($value) ? [(string) $value] : [];
    }

    private static function recordTitle(Model $record, string $titleField): string
    {
        return (string) ($record->getAttribute($titleField) ?: class_basename($record));
    }

    /**
     * @return array{label: string, detail: string}
     */
    private static function usageItem(string $recordLabel, string $recordTitle, string $fieldLabel): array
    {
        return [
            'label' => "{$recordLabel}: {$recordTitle}",
            'short_label' => self::shortRecordLabel($recordLabel).": {$recordTitle}",
            'detail' => $fieldLabel,
        ];
    }

    private static function shortRecordLabel(string $recordLabel): string
    {
        return match ($recordLabel) {
            'Ministry' => 'Mn',
            'Announcement' => 'An',
            'Leader' => 'Ld',
            'Site Settings' => 'Ss',
            'Homepage Banner' => 'Hb',
            'Homepage Content' => 'Hc',
            'Page' => 'Pg',
            default => $recordLabel,
        };
    }
}
