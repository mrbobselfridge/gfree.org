<?php

namespace App\Filament\Admin\Resources\StaffMembers\Schemas;

use App\Filament\Admin\Forms\ContentBlockBuilder;
use App\Filament\Admin\Forms\RichEditorDefaults;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class StaffMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, ?string $operation) => $operation === 'create'
                        ? $set('slug', Str::slug($state))
                        : null)
                    ->maxLength(255),
                ToggleButtons::make('is_published')
                    ->label('Make Leader Live')
                    ->boolean()
                    ->inline()
                    ->default(false)
                    ->required(),
                TextInput::make('role')
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->prefix('/leadership/')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Section::make('Leadership Content Blocks')
                    ->description('Build the visible leadership detail page here. New profiles start with one text block.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->iconColor('success')
                    ->extraAttributes([
                        'class' => 'rounded-xl border border-success-500/30 bg-success-50/40 p-6 dark:bg-success-950/10',
                    ])
                    ->schema([
                        ContentBlockBuilder::make('content_blocks', 'leadership/content-images', 'Leadership Content', true),
                    ])
                    ->columnSpanFull(),
                RichEditorDefaults::configure(RichEditor::make('bio'))
                    ->label('Legacy bio fallback')
                    ->helperText('Used only when no content blocks have been added.')
                    ->columnSpanFull(),
                FileUpload::make('photo_path')
                    ->label('Leadership Image')
                    ->image()
                    ->disk('public')
                    ->directory('leadership')
                    ->columnSpanFull(),
            ]);
    }
}
