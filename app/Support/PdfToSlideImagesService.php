<?php

namespace App\Support;

use App\Models\SlideDeck;
use RuntimeException;
use Symfony\Component\Process\Process;

class PdfToSlideImagesService
{
    /**
     * @return array<int, array{slide_number: int, image_path: string, thumbnail_path: string}>
     */
    public function convert(SlideDeck $deck, string $pdfPath, string $workDirectory): array
    {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('The converted PDF could not be found.');
        }

        $binary = $this->binary();

        if ($binary === null) {
            throw new RuntimeException('ImageMagick is not installed or is not available on the server PATH.');
        }

        $imagesDirectory = $workDirectory.'/images';
        $thumbnailsDirectory = $workDirectory.'/thumbnails';

        foreach ([$imagesDirectory, $thumbnailsDirectory] as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException('Could not create the slide image conversion workspace.');
            }
        }

        $pattern = $imagesDirectory.'/slide-%03d.png';
        $process = new Process([
            $binary,
            '-density',
            '150',
            $pdfPath,
            '-background',
            'white',
            '-alpha',
            'remove',
            '-quality',
            '90',
            $pattern,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('ImageMagick could not convert the PDF to slide images: '.$this->cleanOutput($process));
        }

        $files = collect(glob($imagesDirectory.'/slide-*.png') ?: [])
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            throw new RuntimeException('No slide images were created from the PDF.');
        }

        return $files
            ->map(function (string $image, int $index) use ($deck, $binary, $thumbnailsDirectory): array {
                $slideNumber = $index + 1;
                $imagePath = $deck->directory('images').'/slide-'.str_pad((string) $slideNumber, 3, '0', STR_PAD_LEFT).'.png';
                $thumbnailPath = $deck->directory('thumbnails').'/slide-'.str_pad((string) $slideNumber, 3, '0', STR_PAD_LEFT).'.png';
                $thumbnail = $thumbnailsDirectory.'/'.basename($thumbnailPath);

                $this->createThumbnail($binary, $image, $thumbnail);

                return [
                    'slide_number' => $slideNumber,
                    'image_path' => $imagePath,
                    'thumbnail_path' => $thumbnailPath,
                    'local_image_path' => $image,
                    'local_thumbnail_path' => $thumbnail,
                ];
            })
            ->all();
    }

    private function createThumbnail(string $binary, string $image, string $thumbnail): void
    {
        $process = new Process([
            $binary,
            $image,
            '-thumbnail',
            '640x360>',
            '-background',
            'white',
            '-alpha',
            'remove',
            $thumbnail,
        ]);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('ImageMagick could not create a slide thumbnail: '.$this->cleanOutput($process));
        }
    }

    private function binary(): ?string
    {
        foreach (['magick', 'convert'] as $binary) {
            $process = Process::fromShellCommandline('command -v '.escapeshellarg($binary));
            $process->run();

            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        }

        return null;
    }

    private function cleanOutput(Process $process): string
    {
        return trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'unknown image conversion error';
    }
}
