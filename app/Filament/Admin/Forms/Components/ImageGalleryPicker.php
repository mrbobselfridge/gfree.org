<?php

namespace App\Filament\Admin\Forms\Components;

use App\Support\MediaLibrary;
use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;

class ImageGalleryPicker extends Field
{
    protected string $view = 'filament.admin.forms.components.image-gallery-picker';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getImages(): Collection
    {
        return MediaLibrary::images();
    }
}
