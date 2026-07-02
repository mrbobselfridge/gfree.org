<?php

namespace App\Filament\Admin\Forms;

use App\Support\RichContent;
use Filament\Forms\Components\RichEditor;
use Filament\Support\Icons\Heroicon;

class InternalNotes
{
    public static function field(bool $visibleOnEditOnly = true): RichEditor
    {
        $field = RichEditorDefaults::configure(RichEditor::make('notes'), withAiRewrite: false)
            ->label('Notes')
            ->dehydrateStateUsing(fn (mixed $state): ?string => RichContent::nullable($state))
            ->hintIcon(
                Heroicon::OutlinedInformationCircle,
                'Suggested format: date, name, status or decision, owner, next step, and any context another editor should know.'
            )
            ->hintColor('gray')
            ->extraFieldWrapperAttributes([
                'id' => 'notes',
                'style' => 'scroll-margin-top: 7rem;',
            ])
            ->columnSpanFull();

        return $visibleOnEditOnly
            ? $field->visible(fn (?string $operation): bool => $operation === 'edit')
            : $field;
    }
}
