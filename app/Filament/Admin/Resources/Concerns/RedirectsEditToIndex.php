<?php

namespace App\Filament\Admin\Resources\Concerns;

trait RedirectsEditToIndex
{
    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl();
    }
}
