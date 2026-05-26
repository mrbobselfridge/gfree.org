<?php

namespace App\Filament\Admin\Forms;

use Filament\Forms\Components\RichEditor;

class RichEditorDefaults
{
    public static function configure(RichEditor $editor): RichEditor
    {
        return $editor
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link', 'clearFormatting'],
                ['h2', 'h3', 'h4', 'paragraph', 'lead', 'small'],
                ['alignStart', 'alignCenter', 'alignEnd'],
                ['blockquote', 'bulletList', 'orderedList'],
                ['table', 'horizontalRule'],
                ['undo', 'redo'],
            ]);
    }
}
