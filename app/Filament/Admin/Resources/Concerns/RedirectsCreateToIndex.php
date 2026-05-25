<?php

namespace App\Filament\Admin\Resources\Concerns;

trait RedirectsCreateToIndex
{
    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}
