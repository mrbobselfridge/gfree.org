<?php

namespace App\Support;

use App\Models\SlideDeckSlide;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenAiSlideAnalyzer implements SlideAnalyzerInterface
{
    public function analyze(SlideDeckSlide $slide): SlideAnalysisResult
    {
        $apiKey = OpenAiSiteSettings::apiKey();

        if (blank($apiKey)) {
            throw new SlideAnalysisException(
                'OpenAI API key is not configured. Add it in Site Settings under AI Settings.',
                'openai_not_configured',
            );
        }

        $disk = Storage::disk('local');

        if (blank($slide->image_path) || ! $disk->exists($slide->image_path)) {
            throw new SlideAnalysisException('The slide image could not be found.', 'slide_image_missing');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->post('https://api.openai.com/v1/responses', [
                'model' => OpenAiSiteSettings::contentModel(),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $this->promptFor($slide),
                            ],
                            [
                                'type' => 'input_image',
                                'image_url' => 'data:image/png;base64,'.base64_encode($disk->get($slide->image_path)),
                                'detail' => 'high',
                            ],
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            if ($this->isBalanceOrQuotaError($exception)) {
                throw new SlideAnalysisException(
                    'OpenAI API balance or quota issue: the slide analyzer could not run because the configured OpenAI project has no available API credits or has reached its spend limit. Add API credits or raise the project or organization limit, then re-run analysis.',
                    'openai_quota_exceeded',
                    $exception,
                );
            }

            throw new SlideAnalysisException(
                'OpenAI rejected the slide analysis request: '.$this->responseSummary($exception),
                'openai_request_failed',
                $exception,
            );
        }

        return SlideAnalysisResult::fromArray($this->decodeAnalysisJson($this->extractOutputText($response->json())));
    }

    public function promptFor(SlideDeckSlide $slide): string
    {
        $slideNumber = $slide->slide_number ?: 'unknown';

        return <<<PROMPT
You are analyzing one exported PNG slide from a church announcement slide deck.

Slide number: {$slideNumber}

Classify the slide into exactly one slide_type:
- announcement: anything announcing an event, meeting, class, signup, date, location, time, contact, deadline, or ministry opportunity.
- general: a general instruction, reminder, action, policy, worship/service note, welcome slide, offering reminder, prayer request prompt, online bulletin prompt, connection card prompt, or similar non-event slide.
- unknown: only when the slide cannot be confidently understood.

Extract visible text from the slide as accurately as practical. Infer useful metadata only when supported by visible text or obvious context. Do not invent dates, times, locations, contacts, scripture, or details that are not visible or strongly implied.

Return only valid JSON with this exact shape:
{
  "slide_type": "announcement",
  "suggested_name": "Short useful name",
  "extracted_text": "Visible text from the slide",
  "summary": "One or two sentence summary",
  "scripture_reference": null,
  "scripture_text": null,
  "event_title": null,
  "event_date": null,
  "event_time": null,
  "event_location": null,
  "event_audience": null,
  "contact_person": null,
  "announcement_details": null,
  "confidence_score": 0.0
}

Rules:
- Use null for unknown fields.
- confidence_score must be a number from 0 to 1.
- For general slides, put useful purpose or usage notes in summary.
- For announcement slides, put event details in announcement_details when there is more than the structured fields can hold.
- Do not include Markdown fences, comments, or explanatory text outside the JSON.
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
            throw new RuntimeException('OpenAI returned an empty response for the slide.');
        }

        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAnalysisJson(string $text): array
    {
        $json = trim($text);
        $json = preg_replace('/^```(?:json)?\s*/i', '', $json) ?? $json;
        $json = preg_replace('/\s*```$/', '', $json) ?? $json;
        $json = trim($json);

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid slide analysis JSON.');
        }

        return $decoded;
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

    private function isBalanceOrQuotaError(RequestException $exception): bool
    {
        $response = $exception->response;

        if (! $response) {
            return false;
        }

        $code = strtolower((string) data_get($response->json(), 'error.code'));
        $type = strtolower((string) data_get($response->json(), 'error.type'));
        $message = strtolower((string) data_get($response->json(), 'error.message'));

        return $code === 'insufficient_quota'
            || str_contains($type, 'quota')
            || str_contains($message, 'exceeded your current quota')
            || str_contains($message, 'billing details')
            || str_contains($message, 'run out of credits')
            || str_contains($message, 'no balance');
    }
}
