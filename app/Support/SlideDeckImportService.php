<?php

namespace App\Support;

use App\Jobs\AnalyzeSlideDeckSlideJob;
use App\Models\SlideDeck;
use App\Models\SlideDeckSlide;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SlideDeckImportService
{
    public function __construct(
        private readonly PowerPointToPdfService $powerPointToPdf,
        private readonly PdfToSlideImagesService $pdfToSlideImages,
    ) {}

    public function convertDeckToImages(SlideDeck $deck): void
    {
        $deck->forceFill([
            'status' => SlideDeck::STATUS_PROCESSING,
            'error_message' => null,
            'processed_slides' => 0,
        ])->save();

        $workDirectory = storage_path('app/tmp/slide-decks/'.$deck->getKey().'-'.uniqid());

        try {
            $pdf = $this->powerPointToPdf->convert($deck, $workDirectory);
            $images = $this->pdfToSlideImages->convert($deck, $pdf, $workDirectory);

            $deck->slides()->delete();
            Storage::disk(SlideDeck::DISK)->deleteDirectory($deck->directory('images'));
            Storage::disk(SlideDeck::DISK)->deleteDirectory($deck->directory('thumbnails'));

            foreach ($images as $image) {
                Storage::disk(SlideDeck::DISK)->put($image['image_path'], file_get_contents($image['local_image_path']));
                Storage::disk(SlideDeck::DISK)->put($image['thumbnail_path'], file_get_contents($image['local_thumbnail_path']));

                $deck->slides()->create([
                    'slide_number' => $image['slide_number'],
                    'image_path' => $image['image_path'],
                    'thumbnail_path' => $image['thumbnail_path'],
                    'slide_type' => SlideDeckSlide::TYPE_UNKNOWN,
                    'suggested_name' => 'Slide '.$image['slide_number'],
                ]);
            }

            $deck->forceFill([
                'total_slides' => count($images),
                'processed_slides' => 0,
                'error_message' => null,
            ])->save();
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    public function analyzeDeck(SlideDeck $deck): void
    {
        $deck->slides()
            ->get()
            ->each(fn (SlideDeckSlide $slide): mixed => AnalyzeSlideDeckSlideJob::dispatchSync($slide));

        $deck->forceFill([
            'status' => SlideDeck::STATUS_COMPLETED,
            'processed_slides' => $deck->slides()->count(),
        ])->save();
    }

    public function failDeck(SlideDeck $deck, Throwable|string $error): void
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        $deck->forceFill([
            'status' => SlideDeck::STATUS_FAILED,
            'error_message' => $message,
        ])->save();
    }
}
