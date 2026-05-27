<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                ToggleButtons::make('is_published')
                    ->label('Make Announcement Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->required(),
                Textarea::make('summary')
                    ->rows(1),
                ToggleButtons::make('is_featured')
                    ->label('Featured on homepage')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->required(),
                TextInput::make('cta_label')
                    ->label('Button label')
                    ->maxLength(255),
                TextInput::make('cta_url')
                    ->label('Button URL')
                    ->maxLength(255),
                TextInput::make('slug')
                    ->prefix('/announcements/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('background')
                    ->options([
                        'white' => 'White',
                        'black' => 'Black',
                        'teal' => 'Teal',
                        'gold' => 'Gold',
                        'forest' => 'Forest',
                        'clay' => 'Clay',
                    ])
                    ->default('white')
                    ->required(),
                RichEditorDefaults::configure(RichEditor::make('body'))
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Announcement Image')
                    ->image()
                    ->disk('public')
                    ->directory('announcements')
                    ->columnSpanFull(),
                DateTimePicker::make('publish_at'),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('featured_at')
                    ->label('Featured at')
                    ->helperText('Optional. If empty, the homepage uses Publish at.')
                    ->afterOrEqual(fn (Get $get): ?string => $get('publish_at')),
                DateTimePicker::make('feature_expires_at')
                    ->label('Featured expires at')
                    ->helperText('Optional. If empty, the homepage uses Expires at.')
                    ->afterOrEqual(fn (Get $get): ?string => $get('featured_at'))
                    ->beforeOrEqual(fn (Get $get): ?string => $get('expires_at')),
            ]);
    }
}
