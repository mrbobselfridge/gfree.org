<?php

namespace App\Filament\Admin\Forms\RichContentBlocks;

use App\Filament\Admin\Forms\HtmlCodeTextarea;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;

class EmbedBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'embed';
    }

    public static function getLabel(): string
    {
        return 'Embed';
    }

    public static function getPreviewLabel(array $config): string
    {
        return filled($config['label'] ?? null) ? $config['label'] : 'Embed';
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $label = e(static::getPreviewLabel($config));

        return <<<HTML
        <div class="text-sm font-medium text-gray-700 dark:text-gray-200">{$label}</div>
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Embedded content</div>
        HTML;
    }

    public static function toHtml(array $config, array $data): ?string
    {
        $embedCode = trim((string) ($config['embed_code'] ?? ''));

        if ($embedCode === '') {
            return null;
        }

        return '<div class="page-rich-embed">'.$embedCode.'</div>';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Paste embed code for a video, form, map, or another trusted third-party embed.')
            ->modalWidth(Width::Large)
            ->schema([
                TextInput::make('label')
                    ->label('Label')
                    ->helperText('Only used as the editor preview label.')
                    ->maxLength(120),
                HtmlCodeTextarea::html(Textarea::make('embed_code'))
                    ->label('Embed code')
                    ->required()
                    ->rows(8)
                    ->helperText('Use trusted embed code only.')
                    ->dehydrateStateUsing(fn (?string $state): string => Str::of($state ?? '')->trim()->toString())
                    ->columnSpanFull(),
            ]);
    }
}
