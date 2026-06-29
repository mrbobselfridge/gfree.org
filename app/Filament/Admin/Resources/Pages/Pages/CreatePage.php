<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Models\SlideDeckSlide;
use App\Support\CodeBlockAccess;
use App\Support\SlideAnnouncementPageLink;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use UsesStandardCreateActions;

    protected static string $resource = PageResource::class;

    protected function afterFill(): void
    {
        $slideId = request()->query('slide_deck_slide');

        if (blank($slideId)) {
            return;
        }

        $slide = SlideDeckSlide::query()->find($slideId);

        if (! $slide) {
            return;
        }

        $this->form->fill([
            ...($this->data ?? []),
            ...app(SlideAnnouncementPageLink::class)->createPageDefaults($slide),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! CodeBlockAccess::canManage()) {
            unset($data['seo_title'], $data['seo_description'], $data['noindex_nofollow']);
        }

        return $data;
    }
}
