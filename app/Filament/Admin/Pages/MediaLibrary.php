<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Concerns\RequiresAdminPageAccess;
use App\Support\MediaLibrary as MediaLibrarySupport;
use App\Support\MediaUsage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MediaLibrary extends Page
{
    use RequiresAdminPageAccess;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?string $title = 'Media Library';

    protected static ?string $slug = 'media-library';

    protected string $view = 'filament.admin.pages.media-library';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getImages(): Collection
    {
        return MediaLibrarySupport::images();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadImages')
                ->label('Upload new')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('success')
                ->schema([
                    FileUpload::make('images')
                        ->label('Images')
                        ->image()
                        ->multiple()
                        ->disk('public')
                        ->directory('media-library')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $count = count($data['images'] ?? []);

                    Notification::make()
                        ->title($count === 1 ? 'Image uploaded' : "{$count} images uploaded")
                        ->success()
                        ->send();
                }),
            $this->replaceImageAction(),
            $this->deleteImageAction(),
        ];
    }

    protected function replaceImageAction(): Action
    {
        return Action::make('replaceImage')
            ->label('Replace image')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->modalHeading('Replace image')
            ->modalDescription('Upload a replacement image. Every tracked place using the selected image will be updated to the new image.')
            ->modalSubmitActionLabel('Replace image')
            ->schema([
                FileUpload::make('replacement_image')
                    ->label('Replacement image')
                    ->image()
                    ->disk('public')
                    ->directory('media-library/replacements')
                    ->required(),
            ])
            ->action(function (array $arguments, array $data): void {
                $oldPath = (string) ($arguments['path'] ?? '');
                $replacement = $data['replacement_image'] ?? null;
                $newPath = is_array($replacement) ? collect($replacement)->first() : $replacement;

                if (blank($oldPath) || blank($newPath)) {
                    return;
                }

                $updated = MediaUsage::replaceImagePath($oldPath, (string) $newPath);
                Storage::disk('public')->delete($oldPath);

                Notification::make()
                    ->title('Image replaced')
                    ->body("Updated {$updated} tracked ".str('location')->plural($updated).'.')
                    ->success()
                    ->send();
            });
    }

    protected function deleteImageAction(): Action
    {
        return Action::make('deleteImage')
            ->label('Delete image')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete image')
            ->modalDescription(fn (array $arguments): string => $this->deleteImageDescription((string) ($arguments['path'] ?? '')))
            ->modalSubmitActionLabel('Delete image')
            ->action(function (array $arguments): void {
                $path = (string) ($arguments['path'] ?? '');

                if (blank($path)) {
                    return;
                }

                Storage::disk('public')->delete($path);

                Notification::make()
                    ->title('Image deleted')
                    ->success()
                    ->send();
            });
    }

    private function deleteImageDescription(string $path): string
    {
        $usage = MediaUsage::forImages([$path])[$path] ?? [];

        if ($usage === []) {
            return 'This image is not currently used in any tracked image field or content block.';
        }

        $usedIn = collect($usage)
            ->map(fn (array $item): string => "{$item['label']} ({$item['detail']})")
            ->implode(', ');

        return "Warning: this image is currently used in {$usedIn}. Deleting it will leave those places without this file.";
    }
}
