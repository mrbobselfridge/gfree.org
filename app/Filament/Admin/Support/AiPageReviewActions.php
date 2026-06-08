<?php

namespace App\Filament\Admin\Support;

use App\Models\SiteSetting;
use App\Support\AiContentPrompt;
use App\Support\OpenAiPageReviewer;
use App\Support\PageReviewSnapshot;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
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
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->stickyModalHeader()
                ->extraModalWindowAttributes(['class' => 'twyxtco-ai-page-review-modal'], merge: true)
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
                    Hidden::make('review_completed'),
                    View::make('filament.admin.forms.components.ai-page-review-actions')
                        ->viewData([
                            'emailArguments' => ['email' => true],
                        ])
                        ->columnSpanFull(),
                    Textarea::make('review')
                        ->label('AI Review')
                        ->helperText('Review these notes, then manually update the fields that should change.')
                        ->rows(18)
                        ->visible(fn (Get $get): bool => (bool) $get('review_completed'))
                        ->columnSpanFull(),
                ])
                ->action(function (
                    Action $action,
                    array $arguments,
                    array $data,
                    OpenAiPageReviewer $reviewer,
                    PageReviewSnapshot $snapshotBuilder,
                    Schema $schema,
                ) use ($record, $save): void {
                    if ($arguments['email'] ?? false) {
                        self::emailReview((string) ($data['review'] ?? ''), $record);
                        $action->halt();

                        return;
                    }

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
                        'review' => $review,
                        'review_completed' => true,
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
            'review' => null,
            'review_completed' => false,
        ];
    }

    private static function emailReview(string $review, Model $record): void
    {
        if (blank($review)) {
            Notification::make()
                ->title('Run AI review first')
                ->warning()
                ->send();

            return;
        }

        $user = Filament::auth()->user();
        $email = $user?->email;

        if (blank($email)) {
            Notification::make()
                ->title('No email address found')
                ->body('Your user account does not have an email address.')
                ->danger()
                ->send();

            return;
        }

        $subject = 'AI page review results';
        $recordTitle = (string) ($record->getAttribute('title') ?? $record->getAttribute('name') ?? 'Page');

        Mail::raw("AI page review results for {$recordTitle}:\n\n{$review}", function (Message $message) use ($email, $subject): void {
            $message
                ->to($email)
                ->subject($subject);
        });

        Notification::make()
            ->title('AI review emailed')
            ->body('The review results were sent to '.$email.'.')
            ->success()
            ->send();
    }
}
