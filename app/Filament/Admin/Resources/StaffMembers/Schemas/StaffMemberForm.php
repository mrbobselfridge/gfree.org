<?php

namespace App\Filament\Admin\Resources\StaffMembers\Schemas;

use App\Filament\Admin\Forms\RichEditorDefaults;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
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
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state)))
                    ->maxLength(255),
                ToggleButtons::make('is_published')
                    ->label('Make Announcement Live')
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
                RichEditorDefaults::configure(RichEditor::make('bio'))
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
