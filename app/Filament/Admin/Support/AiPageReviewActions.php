<?php

namespace App\Filament\Admin\Support;

use App\Models\SiteSetting;
use App\Support\AiContentPrompt;
use App\Support\OpenAiPageReviewer;
use App\Support\PageReviewSnapshot;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AiPageReviewActions
{
    public static function make(Model $record, Closure $save): ?Action
    {
        $snapshotBuilder = app(PageReviewSnapshot::class);

        if (! $snapshotBuilder->isEligible($record)) {
            return null;
        }

        return IconOnlyAction::make(
            Action::make('aiPageReview')
                ->label('AI Page Review')
                ->color('info')
                ->modalHeading('AI page review')
                ->modalDescription('This saves the current form, builds a draft-safe CMS snapshot of the page, and asks AI for page-level review notes.')
                ->modalWidth(Width::Screen)
                ->modalSubmitActionLabel('Run AI Review')
                ->modalCancelActionLabel('Close')
                ->stickyModalHeader()
                ->closeModalByClickingAway(false)
                ->fillForm(fn (): array => self::initialFormData($record, $snapshotBuilder))
                ->schema([
                    Textarea::make('prompt')
                        ->label('AI Content Prompt')
                        ->helperText('Defaults from Site Settings. Adjust it here for this page review only.')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                    Hidden::make('snapshot_json'),
                    Textarea::make('snapshot_preview')
                        ->label('Page Snapshot')
                        ->helperText('Draft-safe CMS content that will be sent for review. It includes page fields, content blocks, and image references.')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(12)
                        ->columnSpanFull(),
                    Textarea::make('review')
                        ->label('AI Review')
                        ->helperText('Review these notes, then manually update the fields that should change.')
                        ->rows(18)
                        ->columnSpanFull(),
                ])
                ->action(function (
                    Action $action,
                    array $data,
                    OpenAiPageReviewer $reviewer,
                    PageReviewSnapshot $snapshotBuilder,
                    Schema $schema,
                ) use ($record, $save): void {
                    $save();

                    $record->refresh();
                    $snapshot = $snapshotBuilder->forRecord($record);

                    try {
                        $review = $reviewer->review($snapshot, (string) ($data['prompt'] ?? ''));
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('AI page review failed')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $snapshotJson = $snapshotBuilder->toPromptContext($snapshot);

                    $schema->fill([
                        ...$data,
                        'snapshot_json' => $snapshotJson,
                        'snapshot_preview' => $snapshotJson,
                        'review' => $review,
                    ]);

                    Notification::make()
                        ->title('AI page review ready')
                        ->success()
                        ->send();

                    $action->halt();
                }),
            Heroicon::OutlinedSparkles,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function initialFormData(Model $record, PageReviewSnapshot $snapshotBuilder): array
    {
        $snapshotJson = $snapshotBuilder->toPromptContext($snapshotBuilder->forRecord($record));

        return [
            'prompt' => SiteSetting::query()->value('ai_content_prompt') ?: AiContentPrompt::DEFAULT,
            'snapshot_json' => $snapshotJson,
            'snapshot_preview' => $snapshotJson,
            'review' => null,
        ];
    }
}
