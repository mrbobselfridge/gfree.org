<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\MediaLibrary as MediaLibraryPage;
use App\Filament\Admin\Pages\Sermons;
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

    protected function rows(): array
    {
        $rows = [
            ...$this->siteSettingsRows(),
            ...$this->navigationRows(),
            ...$this->sermonRows(),
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
            $rows[] = $this->row('AI Settings', 'OpenAI API key', 'AI rewrite and bulletin extraction need an API key in Site Settings.', $url, 'Missing', 'danger');
        }

        $missingLandingImages = collect([
            'announcements' => $settings->announcements_image_path,
            'ministries' => $settings->ministry_image_path,
            'leaders' => $settings->leadership_image_path,
            'sermons' => $settings->sermons_image_path,
            'bulletins' => $settings->bulletins_image_path,
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
                $this->row('Navigation Links', 'Header navigation', "{$activeHeaderLinks} active header links are available.", $this->resourceUrl(NavigationLinkResource::class), 'Good', 'success'),
            ];
        }

        return [
            $this->row('Navigation Links', 'Header navigation', 'No active header navigation links are currently available.', $this->resourceUrl(NavigationLinkResource::class), 'Missing', 'danger'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sermonRows(): array
    {
        if (! $this->canAccessTool(AdminAccess::SERMONS)) {
            return [];
        }

        $settings = SiteSetting::query()->first();

        if ($settings && (filled($settings->sermons_youtube_channel_url) || filled($settings->sermons_youtube_feed_url))) {
            return [
                $this->row('Sermons', 'Sermons YouTube source', 'A YouTube channel or feed URL is configured.', Sermons::getUrl(), 'Good', 'success'),
            ];
        }

        return [
            $this->row('Sermons', 'Sermons YouTube source', 'No sermon YouTube channel or RSS feed URL is configured.', Sermons::getUrl(), 'Review', 'warning'),
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
