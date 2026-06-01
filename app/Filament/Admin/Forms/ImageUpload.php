<?php

namespace App\Filament\Admin\Forms;

use App\Filament\Admin\Forms\Components\ImageGalleryPicker;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ImageUpload
{
    public static function make(string $name, string $directory, ?string $label = null): FileUpload
    {
        $upload = FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory($directory);

        if ($label) {
            $upload->label($label);
        }

        return self::configure($upload);
    }

    public static function configure(FileUpload $upload): FileUpload
    {
        return $upload
            ->openable()
            ->downloadable()
            ->hintActions([
                Action::make('chooseExistingImage')
                    ->label('Choose existing')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->modalHeading('Choose an existing image')
                    ->modalSubmitActionLabel('Use selected image')
                    ->modalWidth(Width::Screen)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->schema([
                        ImageGalleryPicker::make('existing_image_path')
                            ->label('Images')
                            ->required(),
                    ])
                    ->fillForm(fn (FileUpload $component): array => [
                        'existing_image_path' => $component->getState(),
                    ])
                    ->action(function (array $data, FileUpload $component): void {
                        $component->state($data['existing_image_path'] ?? null);
                        $component->callAfterStateUpdated();
                    }),
            ]);
    }
}
