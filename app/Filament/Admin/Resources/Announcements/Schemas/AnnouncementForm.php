<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('summary')
                    ->rows(3)
                    ->columnSpanFull(),
                RichEditorDefaults::configure(RichEditor::make('body'))
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('announcements'),
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
                TextInput::make('cta_label')
                    ->label('Button label')
                    ->maxLength(255),
                TextInput::make('cta_url')
                    ->label('Button URL')
                    ->maxLength(255),
                DateTimePicker::make('publish_at'),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('featured_at')
                    ->label('Featured at')
                    ->helperText('Optional. If empty, the homepage uses Publish at.')
                    ->afterOrEqual(fn (Get $get): ?string => $get('publish_at')),
                DateTimePicker::make('feature_expires_at')
                    ->label('Feature expires at')
                    ->helperText('Optional. If empty, the homepage uses Expires at.')
                    ->afterOrEqual(fn (Get $get): ?string => $get('featured_at'))
                    ->beforeOrEqual(fn (Get $get): ?string => $get('expires_at')),
                Toggle::make('is_featured')
                    ->label('Featured on homepage')
                    ->default(false)
                    ->required(),
                Toggle::make('is_published')
                    ->default(false)
                    ->required(),
            ]);
    }
}
