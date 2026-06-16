<?php

namespace App\Support;

class FileCategoryExtractionInstructions
{
    public const DEFAULT = 'Extract the useful public-facing content from this document for a church website editor. Preserve headings, dates, deadlines, requirements, contact information, URLs, and action steps when available. Return concise formatted HTML using headings, paragraphs, and bullet lists where helpful.';

    /**
     * @return array<string, string>
     */
    public static function starterInstructions(): array
    {
        return [
            'Bulletin' => 'Extract the important public bulletin content for the church website from both the PDF text layer and visible page layout. Preserve announcement headings, worship details, dates, times, locations, contacts, phone numbers, emails, sign-up or connection options, boxed sections, and next steps. Bulletins may be multi-column and may include small text, tables, boxes, checkboxes, and a connection card. Do not transcribe blank form lines, empty prayer request areas, unchecked boxes by themselves, page numbers, repeated headers, footers, or decorative filler.',
            'Newsletter' => 'Extract newsletter stories, announcements, ministry updates, dates, deadlines, links, and contact details. Organize the result into clear sections with concise headings.',
            'Consent Form' => 'Extract the purpose of the consent form, who it applies to, deadlines, required guardian or participant information, signature requirements, contact details, and submission instructions. Do not invent legal language.',
            'Marketing Form' => 'Extract the public offer, event or ministry name, dates, audience, location, call to action, links, and contact details. Keep promotional language concise and clear for a website page.',
            'Form' => 'Extract what the form is for, who should use it, required information, deadlines, submission instructions, contact details, and links.',
            'Poster' => 'Extract the event or announcement title, date, time, location, audience, call to action, links, and contact details. Ignore decorative poster text that does not help a visitor act.',
            'Policy' => 'Extract a concise policy summary, key requirements, dates, responsible contacts, and any steps a visitor or church member needs to follow.',
            'Ministry Resource' => 'Extract useful ministry resource content, including the audience, purpose, key instructions, dates, contact details, and next steps.',
            'Event Handout' => 'Extract the event name, date, time, location, schedule, important instructions, contact details, and next steps.',
            'Spreadsheet' => 'Summarize the spreadsheet content into useful website-editor notes. Preserve table headings, important labels, dates, totals, links, and action items when available.',
            'Other' => self::DEFAULT,
        ];
    }

    public static function forCategory(?string $category): string
    {
        return self::starterInstructions()[$category ?: ''] ?? self::DEFAULT;
    }
}
