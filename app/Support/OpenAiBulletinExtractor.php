<?php

namespace App\Support;

use App\Models\Bulletin;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenAiBulletinExtractor
{
    public function extract(Bulletin $bulletin): string
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured. Add OPENAI_API_KEY to your .env file.');
        }

        if (blank($bulletin->pdf_path)) {
            throw new RuntimeException('Upload a bulletin PDF before extracting content.');
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($bulletin->pdf_path)) {
            throw new RuntimeException('The uploaded bulletin PDF could not be found.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.bulletin_model', 'gpt-5-mini'),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_file',
                                'filename' => basename($bulletin->pdf_path),
                                'file_data' => 'data:application/pdf;base64,'.base64_encode($disk->get($bulletin->pdf_path)),
                            ],
                            [
                                'type' => 'input_text',
                                'text' => $this->prompt($bulletin),
                            ],
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('OpenAI rejected the PDF extraction request: '.$this->responseSummary($exception));
        }

        return $this->cleanHtml($this->extractOutputText($response->json()));
    }

    private function prompt(Bulletin $bulletin): string
    {
        $instructions = filled($bulletin->extraction_prompt)
            ? $bulletin->extraction_prompt
            : 'Extract the important public bulletin content for the church website.';

        return <<<PROMPT
You are preparing a church bulletin for a website editor.

Follow these admin instructions:
{$instructions}

Return only clean semantic HTML suitable for a rich text editor. Use headings, paragraphs, bullet lists, and links when appropriate. Do not include Markdown fences, scripts, styles, full HTML documents, or explanatory notes outside the HTML.
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
            throw new RuntimeException('OpenAI returned an empty response for the bulletin PDF.');
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
