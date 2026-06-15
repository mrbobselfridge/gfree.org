<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\MediaLibrary as MediaLibraryPage;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Models\NavigationLink;
use App\Models\SiteSetting;
use App\Support\AdminAccess;
use App\Support\MediaLibrary;

class QuickSiteHealthWidget extends CmsDashboardWidget
{
    protected static ?int $sort = 50;

    protected function heading(): string
    {
        return 'Quick Site Health';
    }

    protected function description(): ?string
    {
        return 'Small setup checks that commonly affect the public website.';
    }

    protected function emptyMessage(): string
    {
        return 'No health checks are available for your assigned admin areas.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{value: int, tone: string, label: string}>
     */
    protected function countBadges(array $rows): array
    {
        $counts = [
            'danger' => 0,
            'warning' => 0,
            'success' => 0,
        ];

        foreach ($rows as $row) {
            $tone = $this->healthTone($row);

            if ($tone) {
                $counts[$tone]++;
            }
        }

        return collect([
            [
                'value' => $counts['danger'],
                'tone' => 'danger',
                'label' => $counts['danger'].' high priority '.str('item')->plural($counts['danger']),
            ],
            [
                'value' => $counts['warning'],
                'tone' => 'warning',
                'label' => $counts['warning'].' review '.str('item')->plural($counts['warning']),
            ],
            [
                'value' => $counts['success'],
                'tone' => 'success',
                'label' => $counts['success'].' good '.str('item')->plural($counts['success']),
            ],
        ])
            ->filter(fn (array $badge): bool => $badge['value'] > 0)
            ->values()
            ->all();
    }

    protected function rows(): array
    {
        $rows = [
            ...$this->siteSettingsRows(),
            ...$this->navigationRows(),
            ...$this->mediaRows(),
        ];

        if ($rows === [] && $this->canAccessTool(AdminAccess::SITE_SETTINGS)) {
            $rows[] = $this->row(
                type: 'Site Health',
                title: 'CMS setup',
                meta: 'The baseline site settings checks look good.',
                url: $this->resourceUrl(SiteSettingResource::class),
                status: 'Good',
                statusColor: 'success',
            );
        }

        return array_slice($rows, 0, 8);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function healthTone(array $row): ?string
    {
        $statusColor = str((string) ($row['statusColor'] ?? ''))->lower()->toString();
        $status = str((string) ($row['status'] ?? ''))->lower()->toString();

        if (in_array($statusColor, ['danger', 'error'], true) || str($status)->contains(['missing', 'error', 'concern', 'high'])) {
            return 'danger';
        }

        if (in_array($statusColor, ['warning'], true) || str($status)->contains(['review', 'warning', 'warn', 'medium'])) {
            return 'warning';
        }

        if (in_array($statusColor, ['success'], true) || str($status)->contains(['good', 'ok', 'okay'])) {
            return 'success';
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function siteSettingsRows(): array
    {
        if (! $this->canAccessTool(AdminAccess::SITE_SETTINGS)) {
            return [];
        }

        $settings = SiteSetting::query()->first();
        $url = $settings
            ? $this->editUrl(SiteSettingResource::class, $settings)
            : $this->resourceUrl(SiteSettingResource::class);

        if (! $settings) {
            return [
                $this->row('Site Settings', 'Site Settings record', 'Create the site settings record before relying on public defaults.', $url, 'Missing', 'danger'),
            ];
        }

        $rows = [];
        $missingContact = collect([
            'church name' => $settings->church_name,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'service times' => $settings->sunday_service_times,
        ])
            ->filter(fn (mixed $value): bool => blank(strip_tags((string) $value)))
            ->keys()
            ->implode(', ');

        if (filled($missingContact)) {
            $rows[] = $this->row('Site Settings', 'Organizational information', 'Missing '.$missingContact.'.', $url, 'Review', 'warning');
        }

        $hasSocialOrVideoUrl = collect([
            $settings->livestream_url,
            $settings->giving_url,
            $settings->one_church_url,
            $settings->facebook_url,
            $settings->instagram_url,
            $settings->youtube_url,
        ])->contains(fn (?string $value): bool => filled($value));

        if (! $hasSocialOrVideoUrl) {
            $rows[] = $this->row('Site Settings', 'Social and video URLs', 'No social, giving, livestream, or video links are filled in.', $url, 'Review', 'warning');
        }

        if (blank($settings->openai_api_key)) {
            $rows[] = $this->row('AI Settings', 'OpenAI API key', 'AI rewrite tools need an API key in Site Settings.', $url, 'Missing', 'danger');
        }

        $missingLandingImages = collect([
            'default page header' => $settings->default_page_header_image_path,
        ])
            ->filter(fn (?string $value): bool => blank($value))
            ->keys()
            ->implode(', ');

        if (filled($missingLandingImages)) {
            $rows[] = $this->row('Site Settings', 'Landing page images', 'Missing images for '.$missingLandingImages.'.', $url, 'Review', 'warning');
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function navigationRows(): array
    {
        if (! $this->canAccessTool(AdminAccess::NAVIGATION_LINKS)) {
            return [];
        }

        $activeHeaderLinks = $this->queryFor(NavigationLink::class)
            ->active()
            ->where('location', 'header')
            ->count();

        if ($activeHeaderLinks > 0) {
            return [
                $this->row('Navigation', 'Header navigation', "{$activeHeaderLinks} active header links are available.", $this->resourceUrl(NavigationLinkResource::class), 'Good', 'success'),
            ];
        }

        return [
            $this->row('Navigation', 'Header navigation', 'No active header navigation links are currently available.', $this->resourceUrl(NavigationLinkResource::class), 'Missing', 'danger'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mediaRows(): array
    {
        if (! $this->canAccessTool(AdminAccess::MEDIA_LIBRARY)) {
            return [];
        }

        $images = MediaLibrary::images();
        $unusedCount = $images->where('usage_count', 0)->count();

        if ($unusedCount === 0) {
            return [
                $this->row('Media Library', 'Media usage', 'All tracked images are currently used somewhere.', MediaLibraryPage::getUrl(), 'Good', 'success'),
            ];
        }

        return [
            $this->row('Media Library', 'Unused images', "{$unusedCount} uploaded images are not currently used in tracked content.", MediaLibraryPage::getUrl(), 'Review', 'warning'),
        ];
    }
}
