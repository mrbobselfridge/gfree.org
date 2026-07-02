<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Schemas;

use App\Filament\Admin\Forms\HtmlCodeTextarea;
use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\InternalNotes;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HomepageBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('title')
                    ->label('Banner title')
                    ->required()
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Internal banner title used in the admin list and to identify this homepage slide.'
                    )
                    ->hintColor('gray'),
                TextInput::make('eyebrow')
                    ->label('Small label')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional short text shown above the main homepage banner message.'
                    )
                    ->hintColor('gray'),
                ToggleButtons::make('is_published')
                    ->label('Banner is live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Controls whether this banner can appear on the homepage, subject to start and end dates.'
                    )
                    ->hintColor('gray')
                    ->required(),
                ...ImageUpload::make(
                    'image_path',
                    'homepage-banners',
                    'Banner image',
                    fn (ViewField $upload): ViewField => $upload
                        ->hintIcon(
                            Heroicon::OutlinedInformationCircle,
                            'Primary homepage banner image. Use a wide, high-quality image that still works when cropped on mobile.'
                        )
                    ->columnSpan(1)
                        ->hintColor('gray'),
                ),

                HtmlCodeTextarea::html(Textarea::make('subtitle'))
                    ->label('Banner message')
                    ->rows(3)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Main supporting banner message. Keep this short so it fits well on mobile.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(2),
                TextInput::make('button_label')
                    ->label('Primary button text')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional text for the main banner button. Add a matching URL if this is filled in.'
                    )
                    ->hintColor('gray'),
                TextInput::make('button_url')
                    ->label('Primary button destination')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional destination for the main button. Use a local path like /new-here or a full https:// URL.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(2),
                TextInput::make('secondary_button_label')
                    ->label('Secondary button text')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional text for a second banner button. Add a matching URL if this is filled in.'
                    )
                    ->hintColor('gray'),
                TextInput::make('secondary_button_url')
                    ->label('Secondary button destination')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional destination for the second button. Use a local path or a full https:// URL.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(2),
                DateTimePicker::make('starts_at')
                    ->label('Publish at')
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Leave empty to allow the banner to appear immediately once Banner is live is enabled.'
                    )
                    ->hintColor('gray'),
                DateTimePicker::make('ends_at')
                    ->label('Expires at')
                    ->afterOrEqual(fn (Get $get): ?string => $get('starts_at'))
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Use when a seasonal or event banner should stop appearing automatically.'
                    )
                    ->hintColor('gray'),
                InternalNotes::field(),

            ]);
    }
}
