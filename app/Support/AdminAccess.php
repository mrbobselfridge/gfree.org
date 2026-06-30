<?php

namespace App\Support;

use App\Filament\Admin\Pages\Analytics;
use App\Filament\Admin\Pages\Backups;
use App\Filament\Admin\Pages\HomepageContent;
use App\Filament\Admin\Pages\MediaLibrary;
use App\Filament\Admin\Resources\FileCategories\FileCategoryResource;
use App\Filament\Admin\Resources\FileDocuments\FileDocumentResource;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\SlideDecks\SlideDeckResource;
use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Admin\Resources\SiteAlerts\SiteAlertResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Resources\WorkflowNotificationRules\WorkflowNotificationRuleResource;
use App\Models\FileCategory;
use App\Models\FileDocument;
use App\Models\HomepageBanner;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SlideDeck;
use App\Models\SiteAlert;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WorkflowNotificationRule;
use Filament\Pages\Page as FilamentPage;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class AdminAccess
{
    public const HOMEPAGE_CONTENT = 'homepage_content';

    public const HOMEPAGE_BANNERS = 'homepage_banners';

    public const SITE_ALERTS = 'site_alerts';

    public const PAGES = 'pages';

    public const CODE_BLOCKS = 'code_blocks';

    public const SITE_SETTINGS = 'site_settings';

    public const ANALYTICS = 'analytics';

    public const MEDIA_LIBRARY = 'media_library';

    public const FILE_LIBRARY = 'file_library';

    public const SLIDE_DECK_IMPORT = 'slide_deck_import';

    public const NAVIGATION_LINKS = 'navigation_links';

    public const USERS = 'users';

    public const WORKFLOW_NOTIFICATIONS = 'workflow_notifications';

    public const BACKUPS = 'backups';

    public static function toolDefinitions(): array
    {
        return [
            self::HOMEPAGE_CONTENT => [
                'label' => 'Homepage',
                'group' => 'Content',
                'page' => HomepageContent::class,
            ],
            self::HOMEPAGE_BANNERS => [
                'label' => 'Banners',
                'group' => 'Content',
                'model' => HomepageBanner::class,
                'resource' => HomepageBannerResource::class,
            ],
            self::SITE_ALERTS => [
                'label' => 'Site Alerts',
                'group' => 'Content',
                'model' => SiteAlert::class,
                'resource' => SiteAlertResource::class,
            ],
            self::PAGES => [
                'label' => 'Pages',
                'group' => 'Content',
                'model' => Page::class,
                'resource' => PageResource::class,
            ],
            self::CODE_BLOCKS => [
                'label' => 'Code Blocks',
                'group' => 'Content',
            ],
            self::SITE_SETTINGS => [
                'label' => 'Site Settings',
                'group' => 'Site Tools',
                'model' => SiteSetting::class,
                'resource' => SiteSettingResource::class,
            ],
            self::ANALYTICS => [
                'label' => 'Analytics',
                'group' => 'Site Tools',
                'page' => Analytics::class,
            ],
            self::MEDIA_LIBRARY => [
                'label' => 'Media Library',
                'group' => 'Content',
                'page' => MediaLibrary::class,
            ],
            self::FILE_LIBRARY => [
                'label' => 'File Library',
                'group' => 'Content',
                'model' => FileDocument::class,
                'models' => [FileDocument::class, FileCategory::class],
                'resource' => FileDocumentResource::class,
                'resources' => [FileDocumentResource::class, FileCategoryResource::class],
            ],
            self::SLIDE_DECK_IMPORT => [
                'label' => 'Slide Deck Import',
                'group' => 'Content',
                'model' => SlideDeck::class,
                'resource' => SlideDeckResource::class,
            ],
            self::NAVIGATION_LINKS => [
                'label' => 'Navigation',
                'group' => 'Content',
                'model' => NavigationLink::class,
                'resource' => NavigationLinkResource::class,
            ],
            self::USERS => [
                'label' => 'Users',
                'group' => 'Site Tools',
                'model' => User::class,
                'resource' => UserResource::class,
            ],
            self::WORKFLOW_NOTIFICATIONS => [
                'label' => 'Notifications',
                'group' => 'Site Tools',
                'model' => WorkflowNotificationRule::class,
                'resource' => WorkflowNotificationRuleResource::class,
            ],
            self::BACKUPS => [
                'label' => 'Backups',
                'group' => 'Site Tools',
                'page' => Backups::class,
            ],
        ];
    }

    public static function groupedToolOptions(): array
    {
        return collect(self::toolDefinitions())
            ->groupBy('group', preserveKeys: true)
            ->map(fn($definitions) => $definitions->mapWithKeys(
                fn(array $definition, string $key): array => [$key => $definition['label']]
            )->all())
            ->all();
    }

    public static function toolOptionsForGroup(string $group): array
    {
        return self::groupedToolOptions()[$group] ?? [];
    }

    public static function recordLimitedTools(): array
    {
        return [
            self::PAGES => [
                'label' => ' - Individual Page Entries',
                'model' => Page::class,
                'title' => 'title',
            ],
        ];
    }

    public static function additionalToolOptions(): array
    {
        return collect(self::additionalToolDefinitions())
            ->mapWithKeys(fn(array $definition, string $key): array => [$key => $definition['label']])
            ->all();
    }

    public static function additionalToolDefinitions(): array
    {
        $knownResourceClasses = collect(self::toolDefinitions())
            ->flatMap(fn(array $definition): array => array_filter([
                $definition['resource'] ?? null,
                ...($definition['resources'] ?? []),
            ]))
            ->filter()
            ->all();

        $knownPageClasses = collect(self::toolDefinitions())
            ->pluck('page')
            ->filter()
            ->all();

        $resources = collect(self::classesIn(app_path('Filament/Admin/Resources'), 'App\\Filament\\Admin\\Resources'))
            ->filter(fn(string $class): bool => is_subclass_of($class, Resource::class))
            ->reject(fn(string $class): bool => in_array($class, $knownResourceClasses, true))
            ->mapWithKeys(fn(string $class): array => [
                self::classPermissionKey($class) => [
                    'label' => method_exists($class, 'getNavigationLabel') ? $class::getNavigationLabel() : Str::headline(class_basename($class)),
                    'group' => method_exists($class, 'getNavigationGroup') ? (string) $class::getNavigationGroup() : 'Additional Tools',
                    'model' => method_exists($class, 'getModel') ? $class::getModel() : null,
                    'resource' => $class,
                ],
            ]);

        $pages = collect(self::classesIn(app_path('Filament/Admin/Pages'), 'App\\Filament\\Admin\\Pages'))
            ->filter(fn(string $class): bool => is_subclass_of($class, FilamentPage::class))
            ->reject(fn(string $class): bool => in_array($class, $knownPageClasses, true))
            ->mapWithKeys(fn(string $class): array => [
                self::classPermissionKey($class) => [
                    'label' => method_exists($class, 'getNavigationLabel') ? $class::getNavigationLabel() : Str::headline(class_basename($class)),
                    'group' => method_exists($class, 'getNavigationGroup') ? (string) $class::getNavigationGroup() : 'Additional Tools',
                    'page' => $class,
                ],
            ]);

        return $resources->merge($pages)->all();
    }

    public static function canAccessTool(?User $user, string $toolKey): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return in_array($toolKey, self::toolKeys($user), true);
    }

    public static function canAccessToolOrAssignedRecords(?User $user, string $toolKey): bool
    {
        if (self::canAccessTool($user, $toolKey)) {
            return true;
        }

        return count(self::recordIds($user, $toolKey)) > 0;
    }

    public static function canAccessRecord(?User $user, string $toolKey, Model $record): bool
    {
        if (self::canAccessTool($user, $toolKey)) {
            return true;
        }

        return in_array((string) $record->getKey(), self::recordIds($user, $toolKey), true);
    }

    public static function canAccessPage(?User $user, string $pageClass): bool
    {
        $toolKey = self::toolKeyForPage($pageClass);

        return $toolKey ? self::canAccessTool($user, $toolKey) : false;
    }

    public static function authorizeModelAbility(User $user, string $ability, mixed $subject): ?bool
    {
        $modelClass = self::modelClass($subject);

        if (!$modelClass) {
            return null;
        }

        $toolKey = self::toolKeyForModel($modelClass);

        if (!$toolKey) {
            return null;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($ability === 'viewAny') {
            return self::canAccessToolOrAssignedRecords($user, $toolKey);
        }

        if (is_string($subject)) {
            return in_array($ability, ['create', 'deleteAny', 'forceDeleteAny', 'restoreAny', 'reorder'], true)
                ? self::canAccessTool($user, $toolKey)
                : self::canAccessToolOrAssignedRecords($user, $toolKey);
        }

        if ($subject instanceof Model) {
            return self::canAccessRecord($user, $toolKey, $subject);
        }

        return self::canAccessTool($user, $toolKey);
    }

    public static function scopeQuery(Builder $query, ?User $user, string $modelClass): Builder
    {
        $toolKey = self::toolKeyForModel($modelClass);

        if (!$toolKey || self::canAccessTool($user, $toolKey)) {
            return $query;
        }

        if (!array_key_exists($toolKey, self::recordLimitedTools())) {
            return $query->whereRaw('1 = 0');
        }

        $ids = self::recordIds($user, $toolKey);

        return count($ids) > 0
            ? $query->whereKey($ids)
            : $query->whereRaw('1 = 0');
    }

    public static function toolKeyForModel(string $modelClass): ?string
    {
        foreach ([...self::toolDefinitions(), ...self::additionalToolDefinitions()] as $key => $definition) {
            if (
                ($definition['model'] ?? null) === $modelClass
                || in_array($modelClass, $definition['models'] ?? [], true)
            ) {
                return $key;
            }
        }

        return null;
    }

    public static function toolKeyForPage(string $pageClass): ?string
    {
        foreach ([...self::toolDefinitions(), ...self::additionalToolDefinitions()] as $key => $definition) {
            if (($definition['page'] ?? null) === $pageClass) {
                return $key;
            }
        }

        return null;
    }

    public static function recordOptions(string $toolKey): array
    {
        $definition = self::recordLimitedTools()[$toolKey] ?? null;

        if (!$definition) {
            return [];
        }

        return $definition['model']::query()
            ->orderBy($definition['title'])
            ->pluck($definition['title'], 'id')
            ->mapWithKeys(fn(string $label, int|string $id): array => [(string) $id => $label])
            ->all();
    }

    public static function accessSummary(User $user): string
    {
        if ($user->isAdmin()) {
            return 'Full admin access.';
        }

        $definitions = [...self::toolDefinitions(), ...self::additionalToolDefinitions()];
        $toolKeys = self::toolKeys($user);

        $lines = collect($toolKeys)
            ->filter(fn(string $toolKey): bool => array_key_exists($toolKey, $definitions))
            ->groupBy(fn(string $toolKey): string => (string) ($definitions[$toolKey]['group'] ?? 'Additional Tools'))
            ->map(function ($groupToolKeys, string $group) use ($definitions): string {
                $labels = $groupToolKeys
                    ->map(fn(string $toolKey): string => (string) $definitions[$toolKey]['label'])
                    ->unique()
                    ->values()
                    ->all();

                return " - " . $group . ': ' . implode(', ', $labels);
            })
            ->values();

        foreach (self::recordLimitedTools() as $toolKey => $definition) {
            $labels = self::recordLabels($user, $toolKey);

            if ($labels === []) {
                continue;
            }

            $lines->push($definition['label'] . ': ' . implode(', ', $labels));
        }

        if ($lines->isEmpty()) {
            return 'No admin access has been assigned.';
        }

        return $lines->implode("\n");
    }

    private static function toolKeys(User $user): array
    {
        return collect(data_get($user->adminPermissionData(), 'tools', []))
            ->filter()
            ->map(fn(mixed $value): string => (string) $value)
            ->values()
            ->all();
    }

    private static function recordIds(?User $user, string $toolKey): array
    {
        if (!$user) {
            return [];
        }

        return collect(data_get($user->adminPermissionData(), "records.{$toolKey}", []))
            ->filter()
            ->map(fn(mixed $value): string => (string) $value)
            ->values()
            ->all();
    }

    private static function recordLabels(User $user, string $toolKey): array
    {
        $ids = self::recordIds($user, $toolKey);

        if ($ids === []) {
            return [];
        }

        return collect(self::recordOptions($toolKey))
            ->only($ids)
            ->values()
            ->all();
    }

    private static function modelClass(mixed $subject): ?string
    {
        if ($subject instanceof Model) {
            return $subject::class;
        }

        if (is_string($subject) && is_subclass_of($subject, Model::class)) {
            return $subject;
        }

        return null;
    }

    private static function classPermissionKey(string $class): string
    {
        return 'class:' . str_replace('\\', '.', $class);
    }

    private static function classesIn(string $path, string $namespace): array
    {
        if (!is_dir($path)) {
            return [];
        }

        return collect(File::allFiles($path))
            ->filter(fn($file): bool => $file->getExtension() === 'php')
            ->map(function ($file) use ($path, $namespace): string {
                $relative = Str::of($file->getPathname())
                    ->after(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                    ->beforeLast('.php')
                    ->replace(DIRECTORY_SEPARATOR, '\\');

                return "{$namespace}\\{$relative}";
            })
            ->filter(fn(string $class): bool => class_exists($class) && !(new ReflectionClass($class))->isAbstract())
            ->values()
            ->all();
    }
}
