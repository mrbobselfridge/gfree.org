<?php

namespace App\Filament\Admin\Resources\NavigationLinks\Schemas;

use App\Support\NavigationDestinationSuggestions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class NavigationLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                TextInput::make('label')
                    ->label('Link text')
                    ->required()
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'The text visitors see in the header or dropdown.'
                    )
                    ->hintColor('gray'),
                Select::make('parent_id')
                    ->label('Parent link')
                    ->relationship('parent', 'label')
                    ->searchable()
                    ->preload()
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Choose a top-level link to make this link appear inside that link\'s dropdown.'
                    )
                    ->hintColor('gray'),


                ToggleButtons::make('is_published')
                    ->label('Link is live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Controls whether this link can appear publicly, subject to publish and expiration dates.'
                    )
                    ->hintColor('gray')
                    ->required(),

                TextInput::make('url')
                    ->label('Destination')
                    ->required()
                    ->maxLength(255)
                    ->datalist(fn(): array => NavigationDestinationSuggestions::optionValues())
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Use a local path like /new-here, choose a suggested page, file, or media path after typing /, or enter a full https:// URL.'
                    )
                    ->hintColor('gray'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Lower numbers appear earlier within the header or within the selected parent dropdown.'
                    )
                    ->hintColor('gray'),

                ToggleButtons::make('opens_in_new_tab')
                    ->label('Open in new tab')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Use mostly for external websites or documents. Internal site links usually stay in the same tab.'
                    )
                    ->hintColor('gray')
                    ->required(),

                Hidden::make('location')
                    ->default('header')
                    ->dehydrateStateUsing(fn(): string => 'header'),
                DateTimePicker::make('publish_at')
                    ->label('Publish at')
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Leave empty to allow the link to appear immediately when live.'
                    )
                    ->hintColor('gray'),
                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->afterOrEqual(fn(Get $get): ?string => $get('publish_at'))
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Use for seasonal links that should disappear automatically.'
                    )
                    ->hintColor('gray'),
            ]);
    }
}
