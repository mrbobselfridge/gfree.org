<?php

namespace App\Support;

use App\Models\FileCategory;
use App\Models\FileDocument;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenAiFileDocumentExtractor
{
    public function extract(FileDocument $document): string
    {
        $apiKey = OpenAiSiteSettings::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured. Add it in Site Settings under AI Settings.');
        }

        $document->loadMissing('currentVersion');
        $version = $document->currentVersion;

        if (! $version) {
            throw new RuntimeException('Save a file before extracting content.');
        }

        $disk = Storage::disk($version->disk);

        if (! $disk->exists($version->path)) {
            throw new RuntimeException('The saved file could not be found.');
        }

        $mimeType = $version->mime_type ?: ($disk->mimeType($version->path) ?: 'application/octet-stream');

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
                                'type' => 'input_file',
                                'filename' => $version->original_name ?: basename($version->path),
                                'file_data' => "data:{$mimeType};base64,".base64_encode($disk->get($version->path)),
                            ],
                            [
                                'type' => 'input_text',
                                'text' => $this->promptFor($document),
                            ],
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('OpenAI rejected the file extraction request: '.$this->responseSummary($exception));
        }

        return $this->cleanHtml($this->extractOutputText($response->json()));
    }

    public function promptFor(FileDocument $document): string
    {
        $instructions = FileCategory::query()
            ->where('name', $document->category)
            ->value('extraction_instructions');

        $instructions = filled($instructions)
            ? (string) $instructions
            : FileCategoryExtractionInstructions::forCategory($document->category);

        $title = $document->title ?: 'Untitled file';
        $category = $document->category ?: FileCategory::DEFAULT_NAME;

        return <<<PROMPT
You are extracting content from a saved File Library document for a church website editor.

File title: {$title}
File category: {$category}

Follow these category-specific extraction instructions:
{$instructions}

Return only clean semantic HTML suitable for a rich text editor. Use headings, paragraphs, bullet lists, tables, and links when appropriate. Do not include Markdown fences, scripts, styles, full HTML documents, or explanatory notes outside the HTML. Do not invent facts that are not in the document.
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
            throw new RuntimeException('OpenAI returned an empty response for the file.');
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
