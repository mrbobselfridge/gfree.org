<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Bulletin;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenAiBulletinAnnouncementReviewer
{
    public function review(Bulletin $bulletin): string
    {
        $apiKey = OpenAiSiteSettings::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured. Add it in Site Settings under AI Settings.');
        }

        $content = $this->requestContent($bulletin);

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->post('https://api.openai.com/v1/responses', [
                'model' => OpenAiSiteSettings::bulletinModel(),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ...$content,
                            [
                                'type' => 'input_text',
                                'text' => $this->prompt($bulletin, $this->announcementContext()),
                            ],
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('OpenAI rejected the announcement review request: '.$this->responseSummary($exception));
        }

        return $this->cleanText($this->extractOutputText($response->json()));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function requestContent(Bulletin $bulletin): array
    {
        if (filled($bulletin->extracted_html)) {
            return [[
                'type' => 'input_text',
                'text' => "Current extracted bulletin HTML:\n{$bulletin->extracted_html}",
            ]];
        }

        if (blank($bulletin->pdf_path)) {
            throw new RuntimeException('Extract the bulletin content or upload a PDF before reviewing announcements.');
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($bulletin->pdf_path)) {
            throw new RuntimeException('The uploaded bulletin PDF could not be found.');
        }

        return [[
            'type' => 'input_file',
            'filename' => basename($bulletin->pdf_path),
            'file_data' => 'data:application/pdf;base64,'.base64_encode($disk->get($bulletin->pdf_path)),
        ]];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function announcementContext(): Collection
    {
        $now = now();

        return Announcement::query()
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', $now->copy()->subWeek()))
            ->orderByRaw('publish_at IS NULL')
            ->orderByDesc('publish_at')
            ->orderBy('title')
            ->limit(40)
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'title' => $announcement->title,
                'summary' => $announcement->summary,
                'body' => $this->plainText($announcement->body),
                'content_blocks' => $announcement->content_blocks,
                'publish_at' => $announcement->publish_at?->toDateTimeString(),
                'expires_at' => $announcement->expires_at?->toDateTimeString(),
                'featured_at' => $announcement->featured_at?->toDateTimeString(),
                'feature_expires_at' => $announcement->feature_expires_at?->toDateTimeString(),
                'is_featured' => $announcement->is_featured,
                'is_published' => $announcement->is_published,
                'public_url' => $announcement->publicUrl(),
            ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $announcements
     */
    private function prompt(Bulletin $bulletin, Collection $announcements): string
    {
        $announcementJson = json_encode($announcements->values()->all(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $bulletinDate = $bulletin->bulletin_date?->toDateString() ?? 'No bulletin date set';

        return <<<PROMPT
You are helping a church website editor review weekly workflow consistency.

Bulletin title: {$bulletin->title}
Bulletin date: {$bulletinDate}

Compare the bulletin content against these current, upcoming, or recently expired announcement records from the CMS:
{$announcementJson}

Return a concise plain-text review for an admin user. Use these headings exactly:
Missing announcements
Possible mismatches
Possibly stale online announcements
Looks OK

Focus on practical website work:
- Items in the bulletin that likely need an announcement but do not appear in the announcement records.
- Items that appear in both places but have conflicting dates, names, times, registration instructions, or links.
- Online announcement records that may be stale because they are not reflected in the bulletin.

Do not create or update content. Do not invent facts. If there are no concerns in a section, write "None found." Include announcement titles when referencing existing records.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractOutputText(array $payload): string
    {
        $outputText = data_get($payload, 'output_text');

        if (filled($outputText)) {
            return $outputText;
        }

        $text = collect(data_get($payload, 'output', []))
            ->flatMap(fn (array $item): array => data_get($item, 'content', []))
            ->map(fn (array $content): ?string => data_get($content, 'text'))
            ->filter()
            ->implode("\n");

        if (blank($text)) {
            throw new RuntimeException('OpenAI returned an empty response for the announcement review.');
        }

        return $text;
    }

    private function cleanText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:text|markdown)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        return trim($text);
    }

    private function plainText(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))) ?? '');
    }

    private function responseSummary(RequestException $exception): string
    {
        $response = $exception->response;

        if (! $response) {
            return $exception->getMessage();
        }

        $message = data_get($response->json(), 'error.message');

        if (filled($message)) {
            return $message;
        }

        return 'HTTP '.$response->status();
    }
}
