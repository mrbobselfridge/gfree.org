<?php

namespace App\Rules;

use App\Support\PageSlugs;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PageSlugPath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            $fail('The :attribute field must be a valid page path.');

            return;
        }

        $slug = trim((string) $value);
        $reservedPrefix = PageSlugs::reservedPrefix($slug);

        if ($reservedPrefix !== null) {
            $fail("The :attribute field cannot start with /{$reservedPrefix} because that area is reserved.");

            return;
        }

        if (! PageSlugs::isValidPath($slug)) {
            $fail('The :attribute field must use letters, numbers, hyphens, and parentheses, with single slashes between path segments.');
        }
    }
}
