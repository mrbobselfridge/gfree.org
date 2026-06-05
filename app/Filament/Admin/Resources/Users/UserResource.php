<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Concerns\AppliesAdminAccess;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    use AppliesAdminAccess;

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
                Section::make('User Details')
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
                Section::make('Approved Admin Areas')
                    ->description('Admins always have access to every admin area. Editor permissions are applied here.')
                    ->visible(fn (Get $get): bool => $get('role') === User::ROLE_EDITOR)
                    ->schema([
                        CheckboxList::make('admin_permissions.tools')
                            ->label('Previously saved full access')
                            ->options([])
                            ->hidden()
                            ->dehydrated(false),
                        CheckboxList::make('admin_permissions.tool_groups.homepage')
                            ->label(self::permissionGroupLabel('Homepage'))
                            ->options(AdminAccess::toolOptionsForGroup('Homepage'))
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                        CheckboxList::make('admin_permissions.tool_groups.content')
                            ->label(self::permissionGroupLabel('Content'))
                            ->options(AdminAccess::toolOptionsForGroup('Content'))
                            ->helperText('Selecting Ministries, Pages, or Leaders here grants access to all current and future entries in that area.')
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                        CheckboxList::make('admin_permissions.tool_groups.sitewide')
                            ->label(self::permissionGroupLabel('Sitewide'))
                            ->options(AdminAccess::toolOptionsForGroup('Sitewide'))
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                        CheckboxList::make('admin_permissions.tool_groups.additional')
                            ->label(self::permissionGroupLabel('Additional Tools'))
                            ->options(AdminAccess::additionalToolOptions())
                            ->visible(fn (): bool => count(AdminAccess::additionalToolOptions()) > 0)
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                        CheckboxList::make('admin_permissions.records.ministries')
                            ->label(self::permissionGroupLabel('Individual Ministry Entries'))
                            ->options(fn (): array => AdminAccess::recordOptions(AdminAccess::MINISTRIES))
                            ->helperText('Leave blank if the user has full Ministries access above.')
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                        CheckboxList::make('admin_permissions.records.pages')
                            ->label(self::permissionGroupLabel('Individual Page Entries'))
                            ->options(fn (): array => AdminAccess::recordOptions(AdminAccess::PAGES))
                            ->helperText('Leave blank if the user has full Pages access above.')
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                        CheckboxList::make('admin_permissions.records.leaders')
                            ->label(self::permissionGroupLabel('Individual Leader Entries'))
                            ->options(fn (): array => AdminAccess::recordOptions(AdminAccess::LEADERS))
                            ->helperText('Leave blank if the user has full Leaders access above.')
                            ->extraAlpineAttributes(self::permissionListAttributes())
                            ->bulkToggleable()
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function permissionGroupLabel(string $label): HtmlString
    {
        return new HtmlString('<span class="text-base font-semibold leading-6 text-gray-950 dark:text-white">'.$label.'</span>');
    }

    private static function permissionListAttributes(): array
    {
        return [
            'class' => 'gfree-user-permission-list',
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(fn (User $record): mixed => app(WorkflowNotificationService::class)->automaticForRecord(
                        $record,
                        WorkflowNotificationRule::TRIGGER_DELETED,
                    )),
            ])
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
