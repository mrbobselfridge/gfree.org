<?php

namespace App\Filament\Admin\Forms\RichContentPlugins;

use App\Models\FileDocument;
use App\Support\FileLibrary;
use App\Support\RichTextFileLibrary;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Tiptap\Core\Extension;

class FileLibraryLinkPlugin implements RichContentPlugin
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
            RichEditorTool::make('fileLibrary')
                ->label('File')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->action(),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [
            Action::make('fileLibrary')
                ->label('File')
                ->modalHeading('Insert file link')
                ->modalDescription('Choose an existing public File Library document, or upload a new file that will be added to the File Library.')
                ->modalWidth(Width::Large)
                ->fillForm([
                    'new_category' => 'Other',
                    'open_in_new_tab' => true,
                ])
                ->schema([
                    Section::make('Link Text')
                        ->schema([
                            TextInput::make('link_label')
                                ->label('Link text')
                                ->helperText('Optional. If empty, the file title is used.')
                                ->maxLength(255),
                            ToggleButtons::make('open_in_new_tab')
                                ->label('Open in new tab')
                                ->boolean()
                                ->inline()
                                ->default(true)
                                ->required(),
                        ])
                        ->columns(2),
                    Tabs::make('File source')
                        ->tabs([
                            Tab::make('Upload New File')
                                ->schema([
                                    TextInput::make('new_title')
                                        ->label('Title')
                                        ->helperText('Optional. If empty, the uploaded file name is used.')
                                        ->maxLength(255),
                                    Select::make('new_category')
                                        ->label('Category')
                                        ->options(fn (): array => FileDocument::categoryOptions())
                                        ->searchable()
                                        ->createOptionForm([
                                            TextInput::make('category')
                                                ->label('Category')
                                                ->required()
                                                ->maxLength(255),
                                        ])
                                        ->createOptionUsing(fn (array $data): string => trim((string) $data['category']))
                                        ->default('Other')
                                        ->required(fn (Get $get): bool => filled($get('upload'))),
                                    FileUpload::make('upload')
                                        ->label('File')
                                        ->helperText('Uploading here creates a public File Library record and inserts a link to it.')
                                        ->acceptedFileTypes(FileLibrary::allowedMimeTypes())
                                        ->disk(FileLibrary::DISK)
                                        ->directory(FileLibrary::DIRECTORY)
                                        ->storeFileNamesIn('upload_original_name')
                                        ->downloadable()
                                        ->columnSpanFull(),
                                    TextInput::make('upload_original_name')
                                        ->hidden(),
                                ])
                                ->columns(2),
                            Tab::make('Existing File')
                                ->schema([
                                    Select::make('document_id')
                                        ->label('File Library document')
                                        ->helperText('Only public, unexpired files are available for public content links.')
                                        ->searchable()
                                        ->preload()
                                        ->options(fn (): array => RichTextFileLibrary::publicDocumentOptions())
                                        ->getSearchResultsUsing(fn (string $search): array => RichTextFileLibrary::publicDocumentOptions($search))
                                        ->getOptionLabelUsing(fn (mixed $value): ?string => RichTextFileLibrary::publicDocumentOptionLabel(
                                            FileDocument::query()->with('currentVersion')->find($value),
                                        )),
                                ]),
                        ])
                        ->activeTab(1)
                        ->columnSpanFull(),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $document = RichTextFileLibrary::resolveDocument($data, Filament::auth()->user());

                    if (! $document?->publicUrl()) {
                        Notification::make()
                            ->title('Choose or upload a file')
                            ->body('Select an existing public file or upload a new file before inserting the link.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $component->runCommands([
                        EditorCommand::make('insertContent', arguments: [[
                            'type' => 'text',
                            'text' => RichTextFileLibrary::linkLabel($document, $data['link_label'] ?? null),
                            'marks' => [
                                [
                                    'type' => 'link',
                                    'attrs' => [
                                        'href' => $document->publicUrl(),
                                        'target' => ($data['open_in_new_tab'] ?? true) ? '_blank' : null,
                                    ],
                                ],
                            ],
                        ]]),
                    ], editorSelection: $arguments['editorSelection'] ?? null);

                    Notification::make()
                        ->title('File link inserted')
                        ->success()
                        ->send();
                }),
        ];
    }
}
