<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\RichEditorDefaults;
use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Filament\Admin\Resources\Concerns\ManagesListingPageSettings;
use App\Support\YoutubeFeedUrl;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
            TextInput::make('sermons_youtube_channel_url')
                ->label('Sermons YouTube channel URL')
                ->helperText('Optional. Used for the View on YouTube link when the feed source changes. The RSS feed URL is filled automatically when a channel ID can be found.')
                ->url()
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    $feedUrl = YoutubeFeedUrl::fromChannelUrl($state);

                    if ($feedUrl) {
                        $set('sermons_youtube_feed_url', $feedUrl);
                    }
                }),
            TextInput::make('sermons_youtube_feed_url')
                ->label('Sermons YouTube feed URL')
                ->helperText('Optional. Paste a YouTube RSS feed URL to replace the default sermon channel feed.')
                ->url(),
            TextInput::make('sermons_youtube_link_label')
                ->label('View on YouTube text')
                ->maxLength(255),
            ImageUpload::make('sermons_image_path', 'site-settings/sermons', 'Sermons image'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getListingSettingsContentComponent(),
            ]);
    }
}
