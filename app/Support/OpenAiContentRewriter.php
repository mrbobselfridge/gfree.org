<?php

namespace App\Support;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiContentRewriter
{
    public function rewrite(string $html, string $prompt): string
    {
        $apiKey = OpenAiSiteSettings::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured. Add it in Site Settings under AI Settings.');
        }

        if (blank(strip_tags($html))) {
            throw new RuntimeException('Add some rich text content before asking AI to rewrite it.');
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
                                'text' => $this->buildPrompt($html, $prompt),
                            ],
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('OpenAI rejected the rewrite request: '.$this->responseSummary($exception));
        }

        return $this->cleanHtml($this->extractOutputText($response->json()));
    }

    private function buildPrompt(string $html, string $prompt): string
    {
        $instructions = filled($prompt) ? $prompt : AiContentPrompt::DEFAULT;

        return <<<PROMPT
You are rewriting church website content for an editor.

Follow these admin instructions:
{$instructions}

Rewrite this current rich text content:
{$html}

Return only clean semantic HTML suitable for a rich text editor. Use headings, paragraphs, bold text, bullet lists, numbered lists, and links when appropriate. Do not include Markdown fences, scripts, styles, full HTML documents, or explanatory notes outside the HTML.
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
            throw new RuntimeException('OpenAI returned an empty response for the rewrite request.');
        }

        return $text;
    }

    private function cleanHtml(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
        $html = preg_replace('/\s*```$/', '', $html) ?? $html;

        return trim($html);
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
