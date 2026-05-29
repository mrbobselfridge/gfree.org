<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class YoutubeSermonFeed
{
    public function latest(int $limit = 12, ?string $feedUrl = null, ?string $channelId = null): array
    {
        $feedUrl = filled($feedUrl) ? trim((string) $feedUrl) : null;
        $channelId = filled($channelId)
            ? trim((string) $channelId)
            : (string) config('services.youtube.sermons_channel_id');

        if (blank($feedUrl) && blank($channelId)) {
            return [];
        }

        $source = $feedUrl ?: "channel:{$channelId}";

        return Cache::remember(
            'youtube-sermons-feed-v3-'.sha1($source)."-{$limit}",
            now()->addMinutes(30),
            fn (): array => $this->fetchLatest($limit, $feedUrl, $channelId),
        );
    }

    private function fetchLatest(int $limit, ?string $feedUrl, ?string $channelId): array
    {
        $request = Http::timeout(8)->accept('application/xml');

        $response = filled($feedUrl)
            ? $request->get($feedUrl)
            : $request->get('https://www.youtube.com/feeds/videos.xml', [
                'channel_id' => $channelId,
            ]);

        if (! $response->successful()) {
            return [];
        }

        return $this->parseFeed($response->body(), $limit);
    }

    private function parseFeed(string $xml, int $limit): array
    {
        $feed = simplexml_load_string($xml);

        if (! $feed instanceof SimpleXMLElement) {
            return [];
        }

        $sermons = [];

        foreach ($feed->entry as $entry) {
            $sermon = $this->parseEntry($entry);

            if (filled($sermon['video_id'])) {
                $sermons[] = $sermon;
            }

            if (count($sermons) >= $limit) {
                break;
            }
        }

        return $sermons;
    }

    private function parseEntry(SimpleXMLElement $entry): array
    {
        $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
        $media = $entry->children('http://search.yahoo.com/mrss/');
        $group = $media->group;
        $thumbnail = $group?->thumbnail?->attributes()?->url;
        $published = filled((string) $entry->published)
            ? CarbonImmutable::parse((string) $entry->published)
            : null;

        return [
            'video_id' => (string) $yt->videoId,
            'title' => (string) ($group?->title ?: $entry->title),
            'url' => (string) $entry->link->attributes()->href,
            'embed_url' => 'https://www.youtube-nocookie.com/embed/'.((string) $yt->videoId),
            'thumbnail_url' => (string) $thumbnail,
            'description' => trim((string) $group?->description),
            'published_at' => $published,
            'published_label' => $published?->format('M j, Y'),
        ];
    }
}
