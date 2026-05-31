<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Filament\Admin\Resources\Concerns\ManagesListingPageSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Http;

class Sermons extends Page
{
    use ManagesListingPageSettings;
    use RequiresAdminPageAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'Sermons';

    protected static ?string $title = 'Sermons';

    protected static ?string $slug = 'sermons';

    public function mount(): void
    {
        $this->mountListingSettings();
    }

    protected function getListingSettingsPrefix(): string
    {
        return 'sermons';
    }

    protected function getListingSettingsLabelPrefix(): string
    {
        return 'Sermons';
    }

    protected function isListingSettingsCollapsedByDefault(): bool
    {
        return false;
    }

    protected function shouldPersistListingSettingsCollapsedState(): bool
    {
        return false;
    }

    protected function getListingSettingsFieldNames(): array
    {
        return [
            'sermons_small_label',
            'sermons_title',
            'sermons_subtitle',
            'sermons_text',
            'sermons_youtube_link_label',
            'sermons_youtube_feed_url',
            'sermons_youtube_channel_url',
            'sermons_image_path',
        ];
    }

    protected function getListingSettingsFields(): array
    {
        return [
            TextInput::make('sermons_small_label')
                ->label('Sermons small label')
                ->maxLength(255),
            TextInput::make('sermons_title')
                ->label('Sermons title')
                ->maxLength(255),
            RichEditorDefaults::configure(RichEditor::make('sermons_subtitle'))
                ->label('Sermons subtitle'),
            RichEditorDefaults::configure(RichEditor::make('sermons_text'))
                ->label('Sermons text'),
            TextInput::make('sermons_youtube_feed_url')
                ->label('Sermons YouTube feed URL')
                ->helperText('Optional. Paste a YouTube RSS feed URL to replace the default sermon channel feed.')
                ->url(),
            TextInput::make('sermons_youtube_channel_url')
                ->label('Sermons YouTube channel URL')
                ->helperText('Optional. Used for the View on YouTube link when the feed source changes. The RSS feed URL is filled automatically when a channel ID can be found.')
                ->url()
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    $feedUrl = $this->youtubeFeedUrlFromChannelUrl($state);

                    if ($feedUrl) {
                        $set('sermons_youtube_feed_url', $feedUrl);
                    }
                }),
            TextInput::make('sermons_youtube_link_label')
                ->label('View on YouTube text')
                ->maxLength(255),
            FileUpload::make('sermons_image_path')
                ->label('Sermons image')
                ->image()
                ->disk('public')
                ->directory('site-settings/sermons'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getListingSettingsContentComponent(),
            ]);
    }

    private function youtubeFeedUrlFromChannelUrl(?string $channelUrl): ?string
    {
        $channelId = $this->youtubeChannelIdFromUrl($channelUrl);

        if (! $channelId && filled($channelUrl)) {
            $channelId = $this->youtubeChannelIdFromPage((string) $channelUrl);
        }

        return $channelId ? "https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}" : null;
    }

    private function youtubeChannelIdFromUrl(?string $channelUrl): ?string
    {
        if (blank($channelUrl)) {
            return null;
        }

        $path = parse_url((string) $channelUrl, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        return preg_match('#/channel/([A-Za-z0-9_-]+)#', $path, $matches)
            ? $matches[1]
            : null;
    }

    private function youtubeChannelIdFromPage(string $channelUrl): ?string
    {
        $response = Http::timeout(8)->get($channelUrl);

        if (! $response->successful()) {
            return null;
        }

        return $this->youtubeChannelIdFromHtml($response->body());
    }

    private function youtubeChannelIdFromHtml(string $html): ?string
    {
        foreach ([
            '/<meta[^>]+itemprop=["\']channelId["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/["\']channelId["\']\s*:\s*["\']([^"\']+)["\']/i',
            '/["\']browseId["\']\s*:\s*["\'](UC[A-Za-z0-9_-]+)["\']/i',
        ] as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
