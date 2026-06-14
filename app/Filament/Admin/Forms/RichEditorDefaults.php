<?php

namespace App\Filament\Admin\Forms;

use App\Filament\Admin\Forms\RichContentBlocks\EmbedBlock;
use App\Filament\Admin\Forms\RichContentPlugins\AiContentRewritePlugin;
use App\Filament\Admin\Forms\RichContentPlugins\FileLibraryLinkPlugin;
use App\Filament\Admin\Forms\RichContentPlugins\HtmlSourcePlugin;
use App\Support\CodeBlockAccess;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Icons\Heroicon;

class RichEditorDefaults
{
    public static function configure(RichEditor $editor, bool $withAiRewrite = true): RichEditor
    {
        $plugins = [
            new FileLibraryLinkPlugin,
        ];

        $toolbarButtons = [
            ['bold', 'italic', 'underline', 'strike', 'link', 'clearFormatting'],
            ['h2', 'h3', 'h4', 'paragraph', 'lead', 'small'],
            ['alignStart', 'alignCenter', 'alignEnd'],
            ['blockquote', 'bulletList', 'orderedList'],
            ['table', 'horizontalRule', 'embed', 'fileLibrary'],
            ['undo', 'redo'],
        ];

        if ($withAiRewrite) {
            array_unshift($plugins, new AiContentRewritePlugin);
            $toolbarButtons[4][] = 'aiRewrite';
        }

        if (CodeBlockAccess::canManage()) {
            $plugins[] = new HtmlSourcePlugin;
            $toolbarButtons[4][] = HtmlSourcePlugin::TOOL;
        }

        return $editor
            ->plugins($plugins)
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
            ->toolbarButtons($toolbarButtons);
    }

    /**
     * @param  array<int, array<int, string>>  $toolbarButtons
     * @return array<int, array<int, string>>
     */
    public static function configureSourceViewer(RichEditor $editor, array $toolbarButtons): RichEditor
    {
        if (! CodeBlockAccess::canManage()) {
            return $editor->toolbarButtons($toolbarButtons);
        }

        $toolbarButtons[4][] = HtmlSourcePlugin::TOOL;

        return $editor
            ->plugins([
                new HtmlSourcePlugin,
            ])
            ->toolbarButtons($toolbarButtons);
    }
}
