<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Support\IconOnlyAction;
use App\Models\User;
use App\Models\WorkflowNotificationRule;
use App\Support\AdminAccess;
use App\Support\WorkflowNotificationService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use AppliesAdminAccess;

    private const SECTION_IDS = [
        'users-content-tools',
        'users-sitewide-tools',
        'users-additional-tools',
        'users-individual-ministry-entries',
        'users-individual-page-entries',
    ];

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Sitewide';

    protected static ?int $navigationSort = 300;

    protected static ?string $modelLabel = 'user';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                CheckboxList::make('admin_permissions.tools')
                    ->label('Previously saved full access')
                    ->options([])
                    ->hidden()
                    ->dehydrated(false),
                View::make('filament.admin.site-settings-section-controls')
                    ->viewData([
                        'sectionIds' => self::SECTION_IDS,
                    ])
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_EDITOR)
                    ->key('users-section-controls')
                    ->columnSpanFull(),
                Section::make('User Details')
                    ->id('users-user-details')
                    ->key('users-user-details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                        Select::make('role')
                            ->options([
                                User::ROLE_ADMIN => 'Admin',
                                User::ROLE_EDITOR => 'Editor',
                            ])
                            ->default(User::ROLE_EDITOR)
                            ->live()
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                self::section('Content', 'users-content-tools')
                    ->description('Choose which content tools this editor can use.')
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_EDITOR)
                    ->schema([
                        CheckboxList::make('admin_permissions.tool_groups.content')
                            ->label('')
                            ->options(AdminAccess::toolOptionsForGroup('Content'))
                            ->helperText('Selecting Ministries or Pages here grants access to all current and future entries in that area.')
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
                self::section('Sitewide', 'users-sitewide-tools')
                    ->description('Choose which sitewide tools this editor can use.')
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_EDITOR)
                    ->schema([
                        CheckboxList::make('admin_permissions.tool_groups.sitewide')
                            ->label('')
                            ->options(AdminAccess::toolOptionsForGroup('Sitewide'))
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
                self::section('Additional Tools', 'users-additional-tools')
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_EDITOR && count(AdminAccess::additionalToolOptions()) > 0)
                    ->schema([
                        CheckboxList::make('admin_permissions.tool_groups.additional')
                            ->label('')
                            ->options(AdminAccess::additionalToolOptions())
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
                self::section('Individual Ministry Entries', 'users-individual-ministry-entries')
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_EDITOR)
                    ->schema([
                        CheckboxList::make('admin_permissions.records.ministries')
                            ->label('')
                            ->options(fn (): array => AdminAccess::recordOptions(AdminAccess::MINISTRIES))
                            ->helperText('Leave blank if the user has full Ministries access above.')
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
                self::section('Individual Page Entries', 'users-individual-page-entries')
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_EDITOR)
                    ->schema([
                        CheckboxList::make('admin_permissions.records.pages')
                            ->label('')
                            ->options(fn (): array => AdminAccess::recordOptions(AdminAccess::PAGES))
                            ->helperText('Leave blank if the user has full Pages access above.')
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function section(string $heading, string $id): Section
    {
        return Section::make($heading)
            ->id($id)
            ->key($id)
            ->collapsible()
            ->collapsed()
            ->persistCollapsed();
    }

    private static function permissionListAttributes(): array
    {
        return [
            'class' => 'twyxtco-user-permission-list',
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString())
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                //
            ])
            ->recordActions(
                [
                    IconOnlyAction::make(
                        EditAction::make()
                            ->label('Edit'),
                        Heroicon::OutlinedPencilSquare,
                    ),
                    IconOnlyAction::make(
                        DeleteAction::make()
                            ->label('Delete')
                            ->after(fn (User $record): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                                $record,
                                WorkflowNotificationRule::TRIGGER_DELETED,
                            )),
                        Heroicon::OutlinedTrash,
                    ),
                ],
                position: RecordActionsPosition::BeforeColumns,
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
