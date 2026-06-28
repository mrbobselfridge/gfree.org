<?php

namespace App\Filament\Admin\Resources\SlideDecks\Pages;

use App\Filament\Admin\Resources\Concerns\UsesStandardCreateActions;
use App\Filament\Admin\Resources\SlideDecks\SlideDeckResource;
use App\Jobs\ProcessSlideDeckJob;
use App\Models\FileDocument;
use App\Models\SlideDeck;
use App\Support\FileLibrary;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateSlideDeck extends CreateRecord
{
    use UsesStandardCreateActions {
        afterCreate as standardAfterCreate;
    }

    protected static string $resource = SlideDeckResource::class;

    private ?string $pendingUpload = null;

    private ?string $pendingOriginalName = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingUpload = FileLibrary::normalizeUploadPath($data['deck_upload'] ?? null);
        $this->pendingOriginalName = FileLibrary::normalizeOriginalName($data['original_filename'] ?? null);

        unset($data['deck_upload']);

        $data['original_filename'] = $this->pendingOriginalName ?: basename((string) $this->pendingUpload);
        $data['stored_file_path'] = $this->pendingUpload ?: '';
        $data['status'] = SlideDeck::STATUS_PENDING;
        $data['created_by_user_id'] = Filament::auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $deck = $this->getRecord();

        if ($this->pendingUpload) {
            $finalPath = $deck->directory('original').'/'.basename($this->pendingUpload);
            Storage::disk(SlideDeck::DISK)->makeDirectory($deck->directory('original'));
            Storage::disk(SlideDeck::DISK)->move($this->pendingUpload, $finalPath);

            $document = FileDocument::query()->create([
                'title' => $deck->name,
                'file_name' => FileDocument::makeUniqueFileName($deck->name),
                'category' => 'Slide Deck',
                'is_published' => true,
                'visibility' => FileDocument::VISIBILITY_PRIVATE,
                'tags' => ['Slide Deck'],
                'uploaded_by_id' => Filament::auth()->id(),
                'updated_by_id' => Filament::auth()->id(),
            ]);

            FileLibrary::createVersion($document, $finalPath, $this->pendingOriginalName, Filament::auth()->user());

            $deck->forceFill([
                'stored_file_path' => $finalPath,
                'file_document_id' => $document->getKey(),
            ])->save();
        }

        ProcessSlideDeckJob::dispatch($deck)->afterResponse();

        $this->standardAfterCreate();
    }
}
