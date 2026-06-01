<?php

namespace App\Filament\Admin\Resources\Ministries\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\RichEditorDefaults;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class MinistryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->maxLength(255)
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug($state))
                        : null),
                ToggleButtons::make('is_published')
                    ->label('Make Ministry Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->required(),
                Textarea::make('short_summary')
                    ->rows(2),
                TextInput::make('slug')
                    ->prefix('/ministry/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state) => Str::slug($state))
                    ->maxLength(255),
                Section::make('Ministry Content Blocks')
                    ->description('Build the visible ministry detail page here. New ministries start with one text block.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'ministries/content-images', 'Ministry Content', true),
                    ])
                    ->columnSpanFull(),
                RichEditorDefaults::configure(RichEditor::make('description'))
                    ->label('Legacy description fallback')
                    ->helperText('Used only when no content blocks have been added.')
                    ->columnSpanFull(),
                FileUpload::make('hero_image_path')
                    ->label('Hero image')
                    ->image()
                    ->disk('public')
                    ->directory('ministries/hero-images'),
                FileUpload::make('card_image_path')
                    ->label('Card image')
                    ->image()
                    ->disk('public')
                    ->directory('ministries/card-images'),
                TextInput::make('category')
                    ->maxLength(255),
                TextInput::make('meeting_time')
                    ->maxLength(255),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('leader_name')
                    ->maxLength(255),
                TextInput::make('leader_email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('leader_phone')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('one_church_url')
                    ->label('One Church URL')
                    ->url()
                    ->maxLength(255),
                Textarea::make('embed_code')
                    ->label('Embed code')
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
