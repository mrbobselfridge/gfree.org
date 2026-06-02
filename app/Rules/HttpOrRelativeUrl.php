<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HttpOrRelativeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            $fail('The :attribute field must be a valid URL or local page path.');

            return;
        }

        $value = trim((string) $value);

        if ($this->isHttpUrl($value) || $this->isRelativePath($value)) {
            return;
        }

        $fail('The :attribute field must be a valid http:// or https:// URL, or a local path like /page.');
    }

    private function isHttpUrl(string $value): bool
    {
        $scheme = parse_url($value, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true)
            && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function isRelativePath(string $value): bool
    {
        return str_starts_with($value, '/')
            && ! str_starts_with($value, '//')
            && ! preg_match('/\s/', $value);
    }
}
