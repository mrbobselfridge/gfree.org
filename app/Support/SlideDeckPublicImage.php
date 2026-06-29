<?php

namespace App\Support;

use App\Models\MediaImageMetadata;
use App\Models\SlideDeck;
use App\Models\SlideDeckSlide;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SlideDeckPublicImage
{
    public function ensure(SlideDeckSlide $slide): ?string
    {
        if (filled($slide->public_image_path) && Storage::disk('public')->exists($slide->public_image_path)) {
            return $slide->public_image_path;
        }

        if (blank($slide->image_path) || ! Storage::disk(SlideDeck::DISK)->exists($slide->image_path)) {
            return null;
        }

        $path = $this->publicPath($slide);

        Storage::disk('public')->put(
            $path,
            Storage::disk(SlideDeck::DISK)->get($slide->image_path),
        );

        $title = filled($slide->suggested_name)
            ? (string) $slide->suggested_name
            : 'Slide '.$slide->slide_number;

        $metadata = MediaImageMetadata::query()->firstOrNew(['path' => $path]);
        $metadata->fill([
            'title' => $title,
            'slug' => MediaImageMetadata::normalizeSlug('slide-decks/'.$slide->deck?->name.'/'.$title.'-'.$slide->getKey()),
            'tags' => MediaImageMetadata::mergeAutoTags(['slide deck', 'announcement'], $title),
            'source' => 'slide_deck',
            'source_id' => (string) $slide->getKey(),
        ])->save();

        MediaLibrary::clearImageIndexCache();

        $slide->forceFill(['public_image_path' => $path])->save();

        return $path;
    }

    public function publicPath(SlideDeckSlide $slide): string
    {
        $deckId = $slide->slide_deck_id ?: $slide->deck?->getKey();
        $slideNumber = str_pad((string) ($slide->slide_number ?: $slide->getKey()), 3, '0', STR_PAD_LEFT);
        $name = Str::slug((string) ($slide->suggested_name ?: 'slide-'.$slideNumber)) ?: 'slide-'.$slideNumber;

        if (blank($deckId)) {
            throw new RuntimeException('The slide must belong to a deck before it can be copied to the media library.');
        }

        return "slide-decks/{$deckId}/media/{$slideNumber}-{$name}.png";
    }
}
