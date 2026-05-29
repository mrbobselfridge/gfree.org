<?php

namespace App\Support;

use App\Filament\Admin\Forms\RichContentBlocks\EmbedBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

class RichContent
{
    public static function render(mixed $content): string
    {
        if (blank($content)) {
            return '';
        }

        $content = (string) $content;

        if (! str_contains($content, 'data-type="customBlock"')) {
            return $content;
        }

        return RichContentRenderer::make($content)
            ->customBlocks([
                EmbedBlock::class,
            ])
            ->toUnsafeHtml();
    }
}
