<?php

namespace App\Rules;

use App\Models\Page;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPageParent implements ValidationRule
{
    public function __construct(
        private readonly ?int $pageId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $parentPageId = (int) $value;

        if (! Page::query()->whereKey($parentPageId)->exists()) {
            $fail('The selected parent page does not exist.');

            return;
        }

        if (Page::wouldCreateParentLoop($parentPageId, $this->pageId)) {
            $fail('The parent page must be another page and cannot be one of this page\'s subpages.');
        }
    }
}
