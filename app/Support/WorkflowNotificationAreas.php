<?php

namespace App\Support;

use App\Filament\Admin\Pages\HomepageContent as HomepageContentPage;
use App\Filament\Admin\Pages\MediaLibrary;
use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\Bulletins\BulletinResource;
use App\Filament\Admin\Resources\HomepageBanners\HomepageBannerResource;
use App\Filament\Admin\Resources\Ministries\MinistryResource;
use App\Filament\Admin\Resources\NavigationLinks\NavigationLinkResource;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\Admin\Resources\SiteSettings\SiteSettingResource;
use App\Filament\Admin\Resources\StaffMembers\StaffMemberResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Announcement;
use App\Models\Bulletin;
use App\Models\HomepageBanner;
use App\Models\HomepageContent;
use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\WorkflowNotificationRule;
use Illuminate\Database\Eloquent\Model;

class WorkflowNotificationAreas
{
    public static function options(): array
    {
        return [
            AdminAccess::HOMEPAGE_CONTENT => 'Homepage Content',
            AdminAccess::HOMEPAGE_BANNERS => 'Homepage Banners',
            AdminAccess::PAGES => 'Pages',
            AdminAccess::ANNOUNCEMENTS => 'Announcements',
            AdminAccess::BULLETINS => 'Bulletins',
            AdminAccess::MINISTRIES => 'Ministries',
            AdminAccess::LEADERS => 'Leaders',
            AdminAccess::NAVIGATION_LINKS => 'Navigation Links',
            AdminAccess::MEDIA_LIBRARY => 'Media Library',
            AdminAccess::SITE_SETTINGS => 'Site Settings',
            AdminAccess::USERS => 'Users',
        ];
    }

    public static function triggerOptions(): array
    {
        return [
            WorkflowNotificationRule::TRIGGER_CREATED => 'Created',
            WorkflowNotificationRule::TRIGGER_UPDATED => 'Updated',
            WorkflowNotificationRule::TRIGGER_DELETED => 'Deleted',
            WorkflowNotificationRule::TRIGGER_MANUAL => 'Manual',
        ];
    }

    public static function delayOptions(): array
    {
        return [
            0 => 'Immediately',
            15 => '15 minutes after the last change',
            30 => '30 minutes after the last change',
            60 => '60 minutes after the last change',
        ];
    }

    public static function areaForModel(Model|string $model): ?string
    {
        $class = is_string($model) ? $model : $model::class;

        return match ($class) {
            HomepageContent::class => AdminAccess::HOMEPAGE_CONTENT,
            HomepageBanner::class => AdminAccess::HOMEPAGE_BANNERS,
            Page::class => AdminAccess::PAGES,
            Announcement::class => AdminAccess::ANNOUNCEMENTS,
            Bulletin::class => AdminAccess::BULLETINS,
            Ministry::class => AdminAccess::MINISTRIES,
            StaffMember::class => AdminAccess::LEADERS,
            NavigationLink::class => AdminAccess::NAVIGATION_LINKS,
            SiteSetting::class => AdminAccess::SITE_SETTINGS,
            User::class => AdminAccess::USERS,
            default => null,
        };
    }

    public static function labelForRecord(Model $record): string
    {
        foreach (['title', 'name', 'label', 'church_name', 'email'] as $field) {
            if (array_key_exists($field, $record->getAttributes()) && filled($record->{$field})) {
                return (string) $record->{$field};
            }
        }

        return class_basename($record).' #'.$record->getKey();
    }

    public static function adminUrlForRecord(Model $record): ?string
    {
        return match ($record::class) {
            HomepageContent::class => HomepageContentPage::getUrl(),
            HomepageBanner::class => HomepageBannerResource::getUrl('edit', ['record' => $record]),
            Page::class => PageResource::getUrl('edit', ['record' => $record]),
            Announcement::class => AnnouncementResource::getUrl('edit', ['record' => $record]),
            Bulletin::class => BulletinResource::getUrl('edit', ['record' => $record]),
            Ministry::class => MinistryResource::getUrl('edit', ['record' => $record]),
            StaffMember::class => StaffMemberResource::getUrl('edit', ['record' => $record]),
            NavigationLink::class => NavigationLinkResource::getUrl('edit', ['record' => $record]),
            SiteSetting::class => SiteSettingResource::getUrl('edit', ['record' => $record]),
            User::class => UserResource::getUrl('edit', ['record' => $record]),
            default => null,
        };
    }

    public static function mediaLibraryUrl(): string
    {
        return MediaLibrary::getUrl();
    }
}
