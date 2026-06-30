<?php

namespace App\Filament\Admin\Resources\SiteAlerts\Schemas;

use App\Filament\Admin\Forms\HtmlCodeTextarea;
use App\Models\SiteAlert;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteAlertForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Select::make('tone')
                    ->label('Alert notification level')
                    ->options(SiteAlert::toneOptions())
                    ->default(SiteAlert::TONE_CRITICAL)
                    ->required()
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Choose the visual importance level for this alert.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(2),
                ToggleButtons::make('is_published')
                    ->label('Alert is live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Controls whether this alert can appear publicly, subject to publish and expiration dates.'
                    )
                    ->hintColor('gray')
                    ->required(),
                TextInput::make('label')
                    ->label('Alert label')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional short label shown before the alert message, such as News Alert.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(2),
                ToggleButtons::make('is_dismissible')
                    ->label('Visitors can dismiss')
                    ->boolean()
                    ->inline()
                    ->default(true)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'When enabled, visitors can hide this alert in their browser until it is edited.'
                    )
                    ->hintColor('gray')
                    ->required(),
                HtmlCodeTextarea::html(Textarea::make('message'))
                    ->label('Alert message')
                    ->required()
                    ->rows(1)
                    ->columnSpan(3)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Main alert text. Keep this brief so stacked alerts remain compact on mobile.'
                    )
                    ->hintColor('gray'),

                TextInput::make('link_label')
                    ->label('Link text')
                    ->maxLength(255)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional call-to-action text shown after the alert message.'
                    )
                    ->hintColor('gray'),
                TextInput::make('link_url')
                    ->label('Link destination')
                    ->maxLength(255)
                    ->rules(SiteAlert::validationRules()['link_url'])
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional destination. Use a local path like /events or a full https:// URL.'
                    )
                    ->hintColor('gray')
                    ->columnSpan(2),
                    
                DateTimePicker::make('publish_at')
                    ->label('Publish at')
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Leave empty to allow the alert to appear immediately once Alert is live is enabled.'
                    )
                    ->hintColor('gray'),
                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->afterOrEqual(fn (Get $get): ?string => $get('publish_at'))
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Optional. Use when an alert should stop appearing automatically.'
                    )
                    ->hintColor('gray'),

                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->hintIcon(
                        Heroicon::OutlinedInformationCircle,
                        'Lower numbers appear first when multiple alerts are live.'
                    )
                    ->hintColor('gray'),
            ]);
    }
}
