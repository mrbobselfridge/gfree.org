<?php

namespace App\Filament\Admin\Forms\RichContentPlugins;

use App\Models\SiteSetting;
use App\Support\AiContentPrompt;
use App\Support\OpenAiContentRewriter;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Tiptap\Core\Extension;

class AiContentRewritePlugin implements RichContentPlugin
{
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
            RichEditorTool::make('aiRewrite')
                ->label('AI rewrite')
                ->icon(Heroicon::OutlinedSparkles)
                ->action(arguments: '{ content: $getEditor().getHTML() }'),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [
            Action::make('aiRewrite')
                ->label('AI rewrite')
                ->modalHeading('AI content rewrite')
                ->modalDescription('Review or adjust the prompt, generate a suggested rewrite, then accept it to replace this rich text content.')
                ->modalWidth(Width::Screen)
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->stickyModalHeader()
                ->extraModalWindowAttributes(['class' => 'twyxtco-ai-rewrite-modal'], merge: true)
                ->closeModalByClickingAway(false)
                ->fillForm(fn (array $arguments): array => [
                    'prompt' => SiteSetting::query()->value('ai_content_prompt') ?: AiContentPrompt::DEFAULT,
                    'source_html' => $arguments['content'] ?? '',
                    'source_preview_html' => $arguments['content'] ?? '',
                    'source_compare_html' => $arguments['content'] ?? '',
                    'suggested_html' => null,
                    'rewrite_completed' => false,
                ])
                ->schema([
                    Textarea::make('prompt')
                        ->label('Default Prompt')
                        ->helperText('You can change this prompt below to fine-tune the rewrite for your specific use case.')
                        ->rows(4)
                        ->required()
                        ->extraFieldWrapperAttributes(['class' => 'twyxtco-ai-rewrite-prompt-field'])
                        ->columnSpanFull(),
                    Hidden::make('source_html'),
                    Hidden::make('rewrite_completed'),
                    View::make('filament.admin.forms.components.ai-rewrite-actions')
                        ->viewData([
                            'acceptArguments' => ['accept' => true],
                        ])
                        ->columnSpanFull(),
                    RichEditor::make('source_preview_html')
                        ->label('Current Content')
                        ->helperText('Current content. This side is shown for comparison and is not changed here.')
                        ->disabled()
                        ->dehydrated(false)
                        ->toolbarButtons([])
                        ->extraInputAttributes([
                            'class' => 'twyxtco-ai-rewrite-comparison-editor',
                        ])
                        ->visible(fn (Get $get): bool => ! (bool) $get('rewrite_completed'))
                        ->columnSpanFull(),
                    Grid::make([
                        'default' => 1,
                        'lg' => 2,
                    ])
                        ->schema([
                            RichEditor::make('source_compare_html')
                                ->label('Current Content')
                                ->helperText('Current content. This side is shown for comparison and is not changed here.')
                                ->disabled()
                                ->dehydrated(false)
                                ->toolbarButtons([])
                                ->extraInputAttributes([
                                    'class' => 'twyxtco-ai-rewrite-comparison-editor',
                                ]),
                            RichEditor::make('suggested_html')
                                ->label('Suggested Content Rewrite')
                                ->helperText('Review and tweak this version, then choose Accept to place it into the original rich text box.')
                                ->extraFieldWrapperAttributes(['class' => 'twyxtco-ai-rewrite-suggestion-field'])
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'strike', 'link', 'clearFormatting'],
                                    ['h2', 'h3', 'h4', 'paragraph', 'lead', 'small'],
                                    ['alignStart', 'alignCenter', 'alignEnd'],
                                    ['blockquote', 'bulletList', 'orderedList'],
                                    ['table', 'horizontalRule'],
                                    ['undo', 'redo'],
                                ])
                                ->extraInputAttributes([
                                    'class' => 'twyxtco-ai-rewrite-comparison-editor',
                                ]),
                        ])
                        ->visible(fn (Get $get): bool => (bool) $get('rewrite_completed'))
                        ->columnSpanFull(),
                ])
                ->action(function (
                    Action $action,
                    array $arguments,
                    array $data,
                    RichEditor $component,
                    OpenAiContentRewriter $rewriter,
                    Schema $schema,
                ): void {
                    if ($arguments['accept'] ?? false) {
                        $suggestedHtml = (string) ($data['suggested_html'] ?? '');

                        if (blank(strip_tags($suggestedHtml))) {
                            Notification::make()
                                ->title('Generate a suggestion first')
                                ->warning()
                                ->send();

                            $action->halt();
                        }

                        $component->runCommands([
                            EditorCommand::make('setContent', [$suggestedHtml]),
                        ]);

                        Notification::make()
                            ->title('AI rewrite accepted')
                            ->success()
                            ->send();

                        return;
                    }

                    $sourceHtml = (string) ($data['source_html'] ?? $arguments['content'] ?? '');

                    try {
                        $suggestedHtml = $rewriter->rewrite(
                            html: $sourceHtml,
                            prompt: (string) ($data['prompt'] ?? ''),
                        );
                    } catch (RuntimeException $exception) {
                        Notification::make()
                            ->title('AI rewrite failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        $action->halt();
                    }

                    $schema->fill([
                        ...$data,
                        'source_html' => $sourceHtml,
                        'source_preview_html' => $sourceHtml,
                        'source_compare_html' => $sourceHtml,
                        'suggested_html' => $suggestedHtml,
                        'rewrite_completed' => true,
                    ]);

                    Notification::make()
                        ->title('AI rewrite ready')
                        ->success()
                        ->send();

                    $action->halt();
                }),
        ];
    }
}
