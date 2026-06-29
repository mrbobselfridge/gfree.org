<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiUsageSummary
{
    /**
     * @return array{status: string, title: string, body: string, formatted_total?: string, currency?: string, period?: string}
     */
    public function currentMonth(): array
    {
        $adminApiKey = OpenAiSiteSettings::adminApiKey();
        $apiKeyId = OpenAiSiteSettings::apiKeyId();

        if (blank($adminApiKey)) {
            return [
                'status' => 'missing',
                'title' => 'OpenAI usage spend unavailable',
                'body' => 'Add an OpenAI Admin API key to show current-month API usage spend for this app key. Normal project API keys cannot read organization cost data.',
            ];
        }

        if (blank($apiKeyId)) {
            return [
                'status' => 'missing',
                'title' => 'OpenAI usage spend unavailable',
                'body' => 'Add the OpenAI API key ID for the project key this app uses. The spend panel will not show organization-wide totals without a specific key ID.',
            ];
        }

        $now = CarbonImmutable::now('UTC');
        $startsAt = $now->startOfMonth();
        $cacheKey = 'openai-usage-costs:'.hash('sha256', $adminApiKey.'|'.$apiKeyId.'|'.$startsAt->timestamp);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($adminApiKey, $apiKeyId, $startsAt, $now): array {
            try {
                $response = Http::withToken($adminApiKey)
                    ->acceptJson()
                    ->timeout(20)
                    ->get('https://api.openai.com/v1/organization/costs', [
                        'start_time' => $startsAt->timestamp,
                        'end_time' => $now->timestamp,
                        'bucket_width' => '1d',
                        'group_by' => ['api_key_id'],
                    ]);
            } catch (Throwable $exception) {
                report($exception);

                return [
                    'status' => 'error',
                    'title' => 'OpenAI usage spend unavailable',
                    'body' => 'OpenAI usage spend could not be loaded right now. Check server network access and try again.',
                ];
            }

            if ($response->failed()) {
                return [
                    'status' => 'error',
                    'title' => 'OpenAI usage spend unavailable',
                    'body' => match ($response->status()) {
                        401, 403 => 'OpenAI rejected the Admin API key. Confirm this is an organization Admin API key with usage/cost access.',
                        default => 'OpenAI did not return usage spend data. HTTP '.$response->status().'.',
                    },
                ];
            }

            $total = collect($response->json('data', []))
                ->flatMap(fn (array $bucket): array => $bucket['results'] ?? [])
                ->filter(fn (array $result): bool => data_get($result, 'api_key_id') === $apiKeyId)
                ->sum(fn (array $result): float => (float) data_get($result, 'amount.value', 0));

            $currency = strtoupper((string) collect($response->json('data', []))
                ->flatMap(fn (array $bucket): array => $bucket['results'] ?? [])
                ->filter(fn (array $result): bool => data_get($result, 'api_key_id') === $apiKeyId)
                ->map(fn (array $result): ?string => data_get($result, 'amount.currency'))
                ->filter()
                ->first() ?: 'USD');

            return [
                'status' => 'ok',
                'title' => 'OpenAI API usage spend for this app key',
                'body' => $this->formatCurrency($total, $currency).' from '.$startsAt->format('M j').' through '.$now->format('M j, Y').'. Cached for 15 minutes. Billing top-ups and prepaid credit purchases are separate from usage spend.',
                'formatted_total' => $this->formatCurrency($total, $currency),
                'currency' => $currency,
                'period' => $startsAt->format('M j').' - '.$now->format('M j, Y'),
            ];
        });
    }

    public function toSiteSettingsHtml(): string
    {
        $summary = $this->currentMonth();
        $colorClass = match ($summary['status']) {
            'ok' => 'text-success-700 dark:text-success-300',
            'missing' => 'text-gray-600 dark:text-gray-300',
            default => 'text-danger-700 dark:text-danger-300',
        };

        return sprintf(
            '<div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900"><div class="font-medium %s">%s</div><div class="mt-1 text-gray-600 dark:text-gray-300">%s</div></div>',
            e($colorClass),
            e($summary['title']),
            e($summary['body']),
        );
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        if ($currency === 'USD') {
            return '$'.number_format($amount, 2);
        }

        return number_format($amount, 2).' '.$currency;
    }
}
