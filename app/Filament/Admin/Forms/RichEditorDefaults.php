<?php

namespace App\Filament\Admin\Forms;

use App\Filament\Admin\Forms\RichContentBlocks\EmbedBlock;
use App\Filament\Admin\Forms\RichContentPlugins\AiContentRewritePlugin;
use App\Filament\Admin\Forms\RichContentPlugins\FileLibraryLinkPlugin;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Icons\Heroicon;

class RichEditorDefaults
{
    public static function configure(RichEditor $editor): RichEditor
    {
        return $editor
            ->plugins([
                new AiContentRewritePlugin,
                new FileLibraryLinkPlugin,
            ])
            ->customBlocks([
                EmbedBlock::class,
            ])
            ->tools([
                RichEditorTool::make('embed')
                    ->label('Embed')
                    ->jsHandler('togglePanel(\'customBlocks\')')
                    ->activeJsExpression('isPanelActive(\'customBlocks\')')
                    ->icon(Heroicon::OutlinedCodeBracketSquare),
            ])
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link', 'clearFormatting'],
                ['h2', 'h3', 'h4', 'paragraph', 'lead', 'small'],
                ['alignStart', 'alignCenter', 'alignEnd'],
                ['blockquote', 'bulletList', 'orderedList'],
                ['table', 'horizontalRule', 'embed', 'fileLibrary', 'aiRewrite'],
                ['undo', 'redo'],
            ]);
    }
}
