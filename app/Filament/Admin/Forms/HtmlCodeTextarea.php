<?php

namespace App\Filament\Admin\Forms;

use Filament\Forms\Components\Textarea;

class HtmlCodeTextarea
{
    public static function html(Textarea $textarea): Textarea
    {
        return self::configure($textarea, 'html');
    }

    public static function css(Textarea $textarea): Textarea
    {
        return self::configure($textarea, 'css');
    }

    public static function javascript(Textarea $textarea): Textarea
    {
        return self::configure($textarea, 'javascript');
    }

    public static function mixed(Textarea $textarea): Textarea
    {
        return self::configure($textarea, 'mixed');
    }

    private static function configure(Textarea $textarea, string $language): Textarea
    {
        return $textarea
            ->extraInputAttributes([
                'data-twyxtco-code-textarea' => 'true',
                'data-twyxtco-code-language' => $language,
                'spellcheck' => 'false',
                'autocapitalize' => 'off',
                'autocomplete' => 'off',
                'data-gramm' => 'false',
                'data-gramm_editor' => 'false',
                'data-enable-grammarly' => 'false',
            ], merge: true);
    }
}
