<?php

namespace App\Support;

use App\Models\HomepageBanner;
use App\Models\HomepageContent;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class PageReviewSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public function forRecord(Model $record): array
    {
        return match (true) {
            $record instanceof Page => $this->page($record),
            $record instanceof HomepageContent => $this->homepage($record),
            default => [],
        };
    }

    public function isEligible(Model $record): bool
    {
        return $this->forRecord($record) !== [];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function toPromptContext(array $snapshot): string
    {
        return json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function page(Page $page): array
    {
        return $this->snapshot('Page', $page->title, $page->publicUrl(), $page, [
            'title',
            'slug',
            'hero_label',
            'intro',
            'message',
            'content_blocks',
            'hero_image_path',
            'card_image_path',
            'seo_title',
            'seo_description',
            'publish_at',
            'expires_at',
            'show_site_chrome',
            'show_page_header',
            'is_published',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function homepage(HomepageContent $content): array
    {
        $settings = SiteSetting::query()->first();
        $banners = HomepageBanner::query()
            ->orderByDesc('is_published')
            ->orderBy('title')
            ->get()
            ->map(fn (HomepageBanner $banner): array => [
                'title' => $banner->title,
                'eyebrow' => $banner->eyebrow,
                'subtitle' => $this->plainText($banner->subtitle),
                'image_path' => $banner->image_path,
                'image_url' => ContentBlocks::imageUrl($banner->image_path),
                'button_label' => $banner->button_label,
                'button_url' => $banner->button_url,
                'secondary_button_label' => $banner->secondary_button_label,
                'secondary_button_url' => $banner->secondary_button_url,
                'is_published' => $banner->is_published,
                'starts_at' => $banner->starts_at?->toDateTimeString(),
                'ends_at' => $banner->ends_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        $snapshot = $this->snapshot('Homepage', $content->seo_title ?: $settings?->church_name, route('home'), $content, [
            'seo_title',
            'seo_description',
            'content_blocks',
        ]);

        $snapshot['homepage_banners'] = $banners;
        $snapshot['site_context'] = [
            'church_name' => $settings?->church_name,
            'tagline' => $this->plainText($settings?->tagline),
            'service_times' => $this->plainText($settings?->siteVariableValue('service-times')),
            'address' => $this->plainText($settings?->siteVariableValue('address')),
        ];

        return $snapshot;
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function snapshot(string $type, ?string $title, ?string $url, Model $record, array $fields): array
    {
        $editable = Arr::only($record->attributesToArray(), $fields);
        $blocks = $record->getAttribute('content_blocks');

        return [
            'page_type' => $type,
            'title' => $title,
            'public_url' => $url,
            'is_published' => (bool) ($record->getAttribute('is_published') ?? true),
            'editable_fields' => $this->normalizeFields($editable),
            'rendered_content_outline' => $this->contentOutline($editable, is_array($blocks) ? $blocks : []),
            'images' => $this->images($editable),
            'review_notes' => [
                'source' => 'Admin CMS record snapshot. This can include draft/unpublished content.',
                'footer_and_navigation' => $type === 'Homepage'
                    ? 'Homepage review may consider the header/footer as visitor context, but suggested edits must stay limited to editable homepage fields.'
                    : 'Ignore site navigation and footer. Suggested edits must stay limited to this record.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function normalizeFields(array $fields): array
    {
        return collect($fields)
            ->map(fn (mixed $value): mixed => $this->normalizeValue($value))
            ->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): mixed => $this->normalizeValue($item))
                ->all();
        }

        if (is_string($value)) {
            return $this->plainText($value);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function contentOutline(array $fields, array $blocks): array
    {
        $outline = collect($fields)
            ->reject(fn (mixed $value, string $key): bool => $key === 'content_blocks' || str_contains($key, 'image_path'))
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value, string $key): array => [
                'field' => $key,
                'text' => is_string($value) ? $this->plainText($value) : $value,
            ])
            ->values();

        $blockOutline = collect($blocks)
            ->map(fn (array $block, int $index): array => [
                'block' => $index + 1,
                'type' => $block['type'] ?? null,
                'content' => $this->normalizeValue($block['data'] ?? []),
            ]);

        return $outline
            ->merge($blockOutline)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function images(array $fields): array
    {
        $images = [];

        foreach ($fields as $key => $value) {
            if (filled($value) && (str_contains($key, 'image_path') || str_contains($key, 'photo_path'))) {
                $images[] = [
                    'field' => $key,
                    'path' => $value,
                    'url' => ContentBlocks::imageUrl($value),
                ];
            }
        }

        foreach ($fields['content_blocks'] ?? [] as $index => $block) {
            $data = $block['data'] ?? [];

            if (filled($data['image_path'] ?? null)) {
                $images[] = [
                    'field' => 'content_blocks.'.($index + 1).'.image_path',
                    'path' => $data['image_path'],
                    'url' => ContentBlocks::imageUrl($data['image_path']),
                    'alt' => $data['image_alt'] ?? null,
                ];
            }
        }

        return $images;
    }

    private function plainText(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
