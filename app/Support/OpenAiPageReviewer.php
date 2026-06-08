<?php

namespace App\Support;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiPageReviewer
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function review(array $snapshot, string $prompt, ?PageVisualSnapshotResult $visualSnapshot = null): string
    {
        $apiKey = OpenAiSiteSettings::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured. Add it in Site Settings under AI Settings.');
        }

        if ($snapshot === []) {
            throw new RuntimeException('This record type is not available for page-level AI review.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->post('https://api.openai.com/v1/responses', [
                'model' => OpenAiSiteSettings::contentModel(),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => $this->requestContent($snapshot, $prompt, $visualSnapshot),
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('OpenAI rejected the page review request: '.$this->responseSummary($exception));
        }

        return $this->cleanText($this->extractOutputText($response->json()));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, string>>
     */
    private function requestContent(array $snapshot, string $prompt, ?PageVisualSnapshotResult $visualSnapshot): array
    {
        $content = [
            [
                'type' => 'input_text',
                'text' => $this->buildPrompt($snapshot, $prompt, $visualSnapshot),
            ],
        ];

        if ($visualSnapshot) {
            $content[] = [
                'type' => 'input_image',
                'image_url' => $this->imageDataUrl($visualSnapshot),
                'detail' => 'auto',
            ];
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function buildPrompt(array $snapshot, string $prompt, ?PageVisualSnapshotResult $visualSnapshot): string
    {
        $instructions = filled($prompt) ? $prompt : AiContentPrompt::DEFAULT;
        $context = app(PageReviewSnapshot::class)->toPromptContext($snapshot);
        $visualContext = $visualSnapshot
            ? "A desktop full-page screenshot is attached for visual review. It was captured at {$visualSnapshot->width}px wide by {$visualSnapshot->height}px viewport height before full-page capture."
            : 'No visual screenshot is attached. Review the structured CMS snapshot only.';

        return <<<PROMPT
You are reviewing a church website page for an admin editor.

Follow these admin instructions:
{$instructions}

Review this full page CMS snapshot. It includes the resulting page URL, editable fields, rendered content outline, and image references:
{$context}

Visual context:
{$visualContext}

Important constraints:
- Ignore site navigation and footer unless the snapshot says this is the Homepage.
- Do not suggest changes to fields or page areas that are not represented in editable_fields.
- Treat image URLs and image metadata as visual context; do not invent unseen image details.
- When a screenshot is attached, use it to evaluate visible layout, visual hierarchy, spacing, image presentation, and obvious rendering issues.
- Keep recommendations practical for a church website editor.

Return concise plain text using these headings exactly:
Overall assessment
Recommended content edits
Recommended image or layout edits
Suggested field updates
Things to verify before publishing

For Suggested field updates, name the exact editable field or content block when possible. Do not return JSON, Markdown fences, scripts, styles, or full HTML documents.
PROMPT;
    }

    private function imageDataUrl(PageVisualSnapshotResult $visualSnapshot): string
    {
        if (! is_file($visualSnapshot->absolutePath) || ! is_readable($visualSnapshot->absolutePath)) {
            throw new RuntimeException('The page visual snapshot image could not be read.');
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($visualSnapshot->absolutePath));
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
            throw new RuntimeException('OpenAI returned an empty response for the page review.');
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
