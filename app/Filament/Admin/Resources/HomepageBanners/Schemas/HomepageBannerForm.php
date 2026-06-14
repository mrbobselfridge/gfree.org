<?php

namespace App\Filament\Admin\Resources\HomepageBanners\Schemas;

use App\Filament\Admin\Forms\ImageUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class HomepageBannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Internal banner title used in the admin list and to identify this homepage slide.'
                    )
                    ->hintColor('gray'),
                ToggleButtons::make('is_published')
                    ->label('Make Banner Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Controls whether this banner can appear on the homepage, subject to start and end dates.'
                    )
                    ->hintColor('gray')
                    ->required(),
                TextInput::make('eyebrow')
                    ->label('Small label')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional short text shown above the main homepage banner message.'
                    )
                    ->hintColor('gray'),
                Textarea::make('subtitle')
                    ->rows(1)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Main supporting banner message. Keep this short so it fits well on mobile.'
                    )
                    ->hintColor('gray'),
                DateTimePicker::make('starts_at')
                    ->label('Starts at')
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Leave empty to allow the banner to appear immediately once Make Banner Live is Yes.'
                    )
                    ->hintColor('gray'),
                DateTimePicker::make('ends_at')
                    ->label('Ends at')
                    ->afterOrEqual(fn (Get $get): ?string => $get('starts_at'))
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Use when a seasonal or event banner should stop appearing automatically.'
                    )
                    ->hintColor('gray'),
                TextInput::make('button_label')
                    ->label('Primary button label')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional text for the main banner button. Add a matching URL if this is filled in.'
                    )
                    ->hintColor('gray'),
                TextInput::make('button_url')
                    ->label('Primary button URL')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional destination for the main button. Use a local path like /new-here or a full https:// URL.'
                    )
                    ->hintColor('gray'),
                TextInput::make('secondary_button_label')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional text for a second banner button. Add a matching URL if this is filled in.'
                    )
                    ->hintColor('gray'),
                TextInput::make('secondary_button_url')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional destination for the second button. Use a local path or a full https:// URL.'
                    )
                    ->hintColor('gray'),
                ...ImageUpload::make(
                    'image_path',
                    'homepage-banners',
                    'Banner Image',
                    fn (FileUpload $upload): FileUpload => $upload
                        ->hintIcon(
                            Heroicon::OutlinedInformationCircle,
                            'Primary homepage banner image. Use a wide, high-quality image that still works when cropped on mobile.'
                        )
                        ->hintColor('gray')
                        ->columnSpanFull(),
                ),

            ]);
    }
}
