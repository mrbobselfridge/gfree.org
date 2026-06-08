<?php

namespace App\Filament\Admin\Support;

use App\Models\SiteSetting;
use App\Support\AiContentPrompt;
use App\Support\OpenAiPageReviewer;
use App\Support\PageReviewSnapshot;
use App\Support\PageVisualSnapshot;
use App\Support\PageVisualSnapshotResult;
use App\Support\WorkflowNotificationAreas;
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
                        ->columnSpanFull(),
                    Textarea::make('review')
                        ->label('AI Review')
                        ->helperText('Review these notes, then manually update the fields that should change.')
                        ->rows(18)
                        ->visible(fn (Get $get): bool => (bool) $get('review_completed'))
                        ->columnSpanFull(),
                    View::make('filament.admin.forms.components.ai-page-review-email-actions')
                        ->viewData([
                            'emailArguments' => ['email' => true],
                        ])
                        ->visible(fn (Get $get): bool => (bool) $get('review_completed'))
                        ->columnSpanFull(),
                ])
                ->action(function (
                    Action $action,
                    array $arguments,
                    array $data,
                    OpenAiPageReviewer $reviewer,
                    PageReviewSnapshot $snapshotBuilder,
                    PageVisualSnapshot $visualSnapshot,
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
                    $visualSnapshotResult = self::captureVisualSnapshot($visualSnapshot, $record);

                    try {
                        $review = $reviewer->review($snapshot, (string) ($data['prompt'] ?? ''), $visualSnapshotResult);
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
                        ->body($visualSnapshotResult
                            ? 'The review includes a desktop visual snapshot.'
                            : 'The review ran without a visual snapshot because screenshot capture is not available.')
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

    private static function captureVisualSnapshot(PageVisualSnapshot $visualSnapshot, Model $record): ?PageVisualSnapshotResult
    {
        try {
            return $visualSnapshot->capture($record);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Visual snapshot unavailable')
                ->body($exception->getMessage())
                ->warning()
                ->send();

            return null;
        }
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

        $publicUrl = self::publicUrlForRecord($record);
        $subject = 'AI Review: '.$publicUrl;
        $body = self::emailBody($record, $review, $publicUrl);

        Mail::raw($body, function (Message $message) use ($email, $subject): void {
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

    private static function emailBody(Model $record, string $review, string $publicUrl): string
    {
        $settings = SiteSetting::query()->first();
        $churchName = $settings?->church_name ?: config('app.name', 'Church Website');
        $timestamp = now()->format('F j, Y g:i A T');
        $recordTitle = WorkflowNotificationAreas::labelForRecord($record);
        $adminUrl = WorkflowNotificationAreas::adminUrlForRecord($record) ?: url('/admin');

        return <<<BODY
Reviewed @ {$timestamp} for {$churchName}
Page Reviewed: {$recordTitle} - {$publicUrl}
Edit Content: {$adminUrl}

{$review}
BODY;
    }

    private static function publicUrlForRecord(Model $record): string
    {
        if (method_exists($record, 'publicUrl')) {
            $url = $record->publicUrl();

            if (filled($url)) {
                return $url;
            }
        }

        return route('home');
    }
}
