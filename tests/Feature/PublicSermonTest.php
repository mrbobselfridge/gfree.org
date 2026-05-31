<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicSermonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget($this->cacheKey());
    }

    protected function tearDown(): void
    {
        Cache::forget($this->cacheKey());

        parent::tearDown();
    }

    public function test_sermons_page_lists_recent_youtube_videos(): void
    {
        Http::fake([
            'youtube.com/feeds/videos.xml*' => Http::response($this->youtubeFeed(), 200, [
                'Content-Type' => 'application/xml',
            ]),
        ]);

        $this->get('/sermons')
            ->assertOk()
            ->assertSee('Sermons')
            ->assertSee('Latest')
            ->assertSee('3. Bless This Home- Peace')
            ->assertSee('https://i2.ytimg.com/vi/5n_lIV6pxyQ/hqdefault.jpg')
            ->assertSee('https://www.youtube.com/watch?v=5n_lIV6pxyQ')
            ->assertSee('May 26, 2026')
            ->assertSee('Let’s learn what it means', false);
    }

    public function test_sermons_page_falls_back_to_channel_link_when_feed_fails(): void
    {
        Http::fake([
            'youtube.com/feeds/videos.xml*' => Http::response('', 500),
        ]);

        $this->get('/sermons')
            ->assertOk()
            ->assertSee('Sermons')
            ->assertSee('Sermons are currently available on YouTube.')
            ->assertSee('https://www.youtube.com/@gfreesermons9521/videos');
    }

    public function test_sermons_page_can_use_configured_feed_and_channel_urls(): void
    {
        Http::fake([
            'https://example.com/custom-sermons.xml' => Http::response($this->customYoutubeFeed(), 200, [
                'Content-Type' => 'application/xml',
            ]),
            'youtube.com/feeds/videos.xml*' => Http::response('', 500),
        ]);

        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'sermons_youtube_feed_url' => 'https://example.com/custom-sermons.xml',
            'sermons_youtube_channel_url' => 'https://www.youtube.com/@customsermons/videos',
        ]);

        $this->get('/sermons')
            ->assertOk()
            ->assertSee('Custom Feed Sermon')
            ->assertSee('https://www.youtube.com/watch?v=custom123')
            ->assertSee('https://www.youtube.com/@customsermons/videos')
            ->assertDontSee('Sermons are currently available on YouTube.');
    }

    public function test_sermons_page_adds_videos_path_to_configured_base_channel_url(): void
    {
        Http::fake([
            'youtube.com/feeds/videos.xml*' => Http::response('', 500),
        ]);

        SiteSetting::query()->create([
            'church_name' => 'gFree Church',
            'sermons_youtube_channel_url' => 'https://www.youtube.com/@customsermons',
        ]);

        $this->get('/sermons')
            ->assertOk()
            ->assertSee('https://www.youtube.com/@customsermons/videos');
    }

    private function youtubeFeed(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns:media="http://search.yahoo.com/mrss/" xmlns="http://www.w3.org/2005/Atom">
            <entry>
                <id>yt:video:5n_lIV6pxyQ</id>
                <yt:videoId>5n_lIV6pxyQ</yt:videoId>
                <title>3. Bless This Home- Peace</title>
                <link rel="alternate" href="https://www.youtube.com/watch?v=5n_lIV6pxyQ"/>
                <published>2026-05-26T21:26:52+00:00</published>
                <media:group>
                    <media:title>3. Bless This Home- Peace</media:title>
                    <media:thumbnail url="https://i2.ytimg.com/vi/5n_lIV6pxyQ/hqdefault.jpg" width="480" height="360"/>
                    <media:description>Let’s learn what it means to not just keep the peace, but to be true peacemakers for our families.</media:description>
                </media:group>
            </entry>
        </feed>
        XML;
    }

    private function customYoutubeFeed(): string
    {
        return <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns:media="http://search.yahoo.com/mrss/" xmlns="http://www.w3.org/2005/Atom">
            <entry>
                <id>yt:video:custom123</id>
                <yt:videoId>custom123</yt:videoId>
                <title>Custom Feed Sermon</title>
                <link rel="alternate" href="https://www.youtube.com/watch?v=custom123"/>
                <published>2026-05-28T12:00:00+00:00</published>
                <media:group>
                    <media:title>Custom Feed Sermon</media:title>
                    <media:thumbnail url="https://i2.ytimg.com/vi/custom123/hqdefault.jpg" width="480" height="360"/>
                    <media:description>Loaded from the configured feed URL.</media:description>
                </media:group>
            </entry>
        </feed>
        XML;
    }

    private function cacheKey(): string
    {
        return 'youtube-sermons-feed-v3-'.sha1('channel:UCDDrEtN3XPxVE9-oY008IYA').'-12';
    }
}
