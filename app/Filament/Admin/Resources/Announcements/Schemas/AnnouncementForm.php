<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\ImageUpload;
use App\Filament\Admin\Forms\SlugRebuildAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug($state))
                        : null),
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
                    ->label('Path')
                    ->prefix('/announcements/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->suffixAction(SlugRebuildAction::make('title'))
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
                Section::make('Announcement Content Blocks')
                    ->description('Build the visible announcement detail page here. New announcements start with one text block.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'announcements/content-images', 'Announcement Content', true),
                    ])
                    ->columnSpanFull(),
                ...ImageUpload::make(
                    'image_path',
                    'announcements',
                    'Announcement Image',
                    fn (FileUpload $upload): FileUpload => $upload->columnSpanFull(),
                ),
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
