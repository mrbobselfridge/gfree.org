<?php

namespace App\Filament\Admin\Forms\RichContentPlugins;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Tiptap\Core\Extension;

class HtmlSourcePlugin implements RichContentPlugin
{
    public const TOOL = 'htmlSource';

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make(self::TOOL)
                ->label('Source')
                ->icon(Heroicon::OutlinedCodeBracketSquare)
                ->action(arguments: '{ content: $getEditor().getHTML() }'),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [
            Action::make(self::TOOL)
                ->label('Source')
                ->modalHeading('Rich text source')
                ->modalDescription('View-only HTML source for this rich text box.')
                ->modalWidth(Width::Screen)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->fillForm(fn (array $arguments): array => self::formDataFromArguments($arguments))
                ->schema([
                    Textarea::make('source_html')
                        ->label('HTML source')
                        ->helperText('View-only. Copy from here if you need to inspect or reuse the markup.')
                        ->readOnly()
                        ->dehydrated(false)
                        ->rows(22)
                        ->extraInputAttributes([
                            'class' => 'font-mono text-xs',
                            'spellcheck' => 'false',
                            'wrap' => 'off',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{source_html: string}
     */
    public static function formDataFromArguments(array $arguments): array
    {
        return [
            'source_html' => (string) ($arguments['content'] ?? ''),
        ];
    }
}
