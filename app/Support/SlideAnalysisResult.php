<?php

namespace App\Support;

use App\Models\SlideDeckSlide;

class SlideAnalysisResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $slideType,
        public readonly ?string $suggestedName = null,
        public readonly ?string $extractedText = null,
        public readonly ?string $summary = null,
        public readonly ?string $scriptureReference = null,
        public readonly ?string $scriptureText = null,
        public readonly ?string $eventTitle = null,
        public readonly ?string $eventDate = null,
        public readonly ?string $eventTime = null,
        public readonly ?string $eventLocation = null,
        public readonly ?string $eventAudience = null,
        public readonly ?string $contactPerson = null,
        public readonly ?string $announcementDetails = null,
        public readonly ?float $confidenceScore = null,
        public readonly array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $slideType = in_array($data['slide_type'] ?? null, array_keys(SlideDeckSlide::types()), true)
            ? (string) $data['slide_type']
            : SlideDeckSlide::TYPE_UNKNOWN;

        return new self(
            slideType: $slideType,
            suggestedName: self::stringOrNull($data['suggested_name'] ?? null),
            extractedText: self::stringOrNull($data['extracted_text'] ?? null),
            summary: self::stringOrNull($data['summary'] ?? null),
            scriptureReference: self::stringOrNull($data['scripture_reference'] ?? null),
            scriptureText: self::stringOrNull($data['scripture_text'] ?? null),
            eventTitle: self::stringOrNull($data['event_title'] ?? null),
            eventDate: self::stringOrNull($data['event_date'] ?? null),
            eventTime: self::stringOrNull($data['event_time'] ?? null),
            eventLocation: self::stringOrNull($data['event_location'] ?? null),
            eventAudience: self::stringOrNull($data['event_audience'] ?? null),
            contactPerson: self::stringOrNull($data['contact_person'] ?? null),
            announcementDetails: self::stringOrNull($data['announcement_details'] ?? null),
            confidenceScore: is_numeric($data['confidence_score'] ?? null) ? (float) $data['confidence_score'] : null,
            raw: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toSlideAttributes(): array
    {
        return [
            'slide_type' => $this->slideType,
            'suggested_name' => $this->suggestedName,
            'extracted_text' => $this->extractedText,
            'summary' => $this->summary,
            'scripture_reference' => $this->scriptureReference,
            'scripture_text' => $this->scriptureText,
            'event_title' => $this->eventTitle,
            'event_date' => $this->eventDate,
            'event_time' => $this->eventTime,
            'event_location' => $this->eventLocation,
            'event_audience' => $this->eventAudience,
            'contact_person' => $this->contactPerson,
            'announcement_details' => $this->announcementDetails,
            'confidence_score' => $this->confidenceScore,
            'raw_analysis_json' => $this->raw,
        ];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return filled($value) ? $value : null;
    }
}
