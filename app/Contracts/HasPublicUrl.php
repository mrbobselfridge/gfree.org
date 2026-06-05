<?php

namespace App\Contracts;

interface HasPublicUrl
{
    public function publicUrl(): ?string;
}
