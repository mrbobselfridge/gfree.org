<?php

namespace App\Filament\Admin\Resources\SlideDecks\RelationManagers;

use App\Filament\Admin\Support\IconOnlyAction;
use App\Jobs\AnalyzeSlideDeckSlideJob;
use App\Models\Page;
use App\Models\SlideDeck;
use App\Models\SlideDeckSlide;
use App\Support\SlideAnnouncementPageLink;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class SlidesRelationManager extends RelationManager
{
    protected static string $relationship = 'slides';

    protected static ?string $title = 'Slide Review';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof SlideDeck && $ownerRecord->exists;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('preview')
                    ->label('Slide')
                    ->html()
                    ->state(fn (SlideDeckSlide $record): HtmlString => new HtmlString(
                        '<img src="'.e(route('admin.slide-decks.image', ['slideDeckSlide' => $record, 'thumbnail' => 1])).'" alt="Slide '.e((string) $record->slide_number).'" style="width: 12rem; max-width: 100%; border-radius: 0.375rem; border: 1px solid #e5e7eb;">'
                    )),
                TextColumn::make('slide_number')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('suggested_name')
                    ->label('Suggested name')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('analysis_status')
                    ->label('Analysis')
                    ->badge()
                    ->state(fn (SlideDeckSlide $record): string => $this->analysisStatus($record))
                    ->description(fn (SlideDeckSlide $record): ?string => $this->analysisDescription($record))
                    ->color(fn (SlideDeckSlide $record): string => $this->analysisColor($record))
                    ->wrap(),
                TextColumn::make('announcement_page')
                    ->label('Page?')
                    ->html()
                    ->state(fn (SlideDeckSlide $record): HtmlString => app(SlideAnnouncementPageLink::class)->statusHtml($record)),
                TextColumn::make('slide_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SlideDeckSlide::types()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        SlideDeckSlide::TYPE_ANNOUNCEMENT => 'success',
                        SlideDeckSlide::TYPE_GENERAL => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('event_date')
                    ->label('Date')
                    ->placeholder('None')
                    ->toggleable(),
                TextColumn::make('event_time')
                    ->label('Time')
                    ->placeholder('None')
                    ->toggleable(),
                TextColumn::make('event_location')
                    ->label('Location')
                    ->placeholder('None')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('extracted_text')
                    ->label('Visible text')
                    ->limit(120)
                    ->placeholder('None')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('slide_number')
            ->recordActions([
                IconOnlyAction::make(
                    EditAction::make()
                        ->label('Edit slide')
                        ->modalContent(fn (SlideDeckSlide $record): ?HtmlString => $this->slideModalImage($record))
                        ->schema($this->slideFormSchema()),
                    Heroicon::OutlinedPencilSquare,
                ),
                IconOnlyAction::make(
                    Action::make('rerunSlideAnalysis')
                        ->label('Re-run slide analysis')
                        ->action(function (SlideDeckSlide $record): void {
                            AnalyzeSlideDeckSlideJob::dispatch($record)->afterResponse();

                            Notification::make()
                                ->title('Slide analysis queued')
                                ->success()
                                ->send();
                        }),
                    Heroicon::OutlinedSparkles,
                ),
                IconOnlyAction::make(
                    DeleteAction::make()
                        ->label('Delete slide')
                        ->modalHeading('Delete this slide?')
                        ->modalDescription('This removes the slide, generated slide image, thumbnail, and copied media library image.'),
                    Heroicon::OutlinedTrash,
                ),
                IconOnlyAction::make(
                    Action::make('editAnnouncementPage')
                        ->label('Edit existing page')
                        ->url(fn (SlideDeckSlide $record): ?string => ($page = $this->matchingAnnouncementPage($record))
                            ? app(SlideAnnouncementPageLink::class)->editPageUrl($page)
                            : null, true)
                        ->disabled(fn (SlideDeckSlide $record): bool => $this->matchingAnnouncementPage($record) === null),
                    Heroicon::OutlinedDocumentText,
                    'Edit existing page',
                )->extraAttributes([
                    'style' => 'margin-left: .75rem; padding-left: .75rem; border-left: 1px solid #d1d5db;',
                ], merge: true),
                IconOnlyAction::make(
                    Action::make('viewAnnouncementPage')
                        ->label('View existing page')
                        ->url(fn (SlideDeckSlide $record): ?string => $this->matchingAnnouncementPage($record)?->publicUrl(), true)
                        ->disabled(fn (SlideDeckSlide $record): bool => $this->matchingAnnouncementPage($record) === null),
                    Heroicon::OutlinedArrowTopRightOnSquare,
                    'View existing page',
                ),
                IconOnlyAction::make(
                    Action::make('createAnnouncementPage')
                        ->label('Create missing page')
                        ->url(fn (SlideDeckSlide $record): string => app(SlideAnnouncementPageLink::class)->createPageUrl($record), true)
                        ->disabled(fn (SlideDeckSlide $record): bool => $this->matchingAnnouncementPage($record) !== null),
                    Heroicon::OutlinedPlus,
                    'Create missing page',
                ),
            ], position: RecordActionsPosition::BeforeColumns);
    }

    private function analysisStatus(SlideDeckSlide $record): string
    {
        if (data_get($record->raw_analysis_json, 'error_type') === 'openai_quota_exceeded') {
            return 'OpenAI balance issue';
        }

        if (data_get($record->raw_analysis_json, 'analyzer_failed')) {
            return 'Analysis failed';
        }

        if (filled($record->summary) || filled($record->extracted_text) || filled($record->confidence_score)) {
            return 'Analyzed';
        }

        return 'Pending';
    }

    private function analysisDescription(SlideDeckSlide $record): ?string
    {
        $error = data_get($record->raw_analysis_json, 'error');

        return filled($error) ? str($error)->limit(160)->toString() : null;
    }

    private function analysisColor(SlideDeckSlide $record): string
    {
        return match ($this->analysisStatus($record)) {
            'Analyzed' => 'success',
            'Pending' => 'gray',
            default => 'danger',
        };
    }

    private function slideModalImage(SlideDeckSlide $record): ?HtmlString
    {
        $imageUrl = $record->imageUrl();

        if (blank($imageUrl)) {
            return null;
        }

        return new HtmlString(
            '<img src="'.e($imageUrl).'" alt="Slide '.e((string) $record->slide_number).'" style="display: block; width: 100%; height: auto; max-height: 28rem; object-fit: contain; border-radius: 0.5rem; border: 1px solid #e5e7eb;">'
        );
    }

    private function matchingAnnouncementPage(SlideDeckSlide $record): ?Page
    {
        return app(SlideAnnouncementPageLink::class)->matchingPage($record);
    }

    private function slideFormSchema(): array
    {
        return [
            TextInput::make('suggested_name')
                ->label('Suggested name')
                ->maxLength(255)
                ->columnSpanFull(),
            Select::make('slide_type')
                ->label('Slide type')
                ->options(SlideDeckSlide::types())
                ->required(),
            TextInput::make('confidence_score')
                ->label('Confidence score')
                ->numeric()
                ->minValue(0)
                ->maxValue(1),
            Textarea::make('extracted_text')
                ->label('Visible text')
                ->rows(4)
                ->columnSpanFull(),
            Textarea::make('summary')
                ->label('Intro Text')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('event_title')
                ->maxLength(255),
            TextInput::make('event_date')
                ->maxLength(255),
            TextInput::make('event_time')
                ->maxLength(255),
            TextInput::make('event_location')
                ->maxLength(255),
            TextInput::make('event_audience')
                ->maxLength(255),
            TextInput::make('contact_person')
                ->maxLength(255),
            Textarea::make('announcement_details')
                ->rows(4)
                ->columnSpanFull(),
        ];
    }
}
