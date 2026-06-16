<?php

namespace Tests\Unit;

use App\Support\UploadedFilenameTitle;
use Carbon\Carbon;
use Tests\TestCase;

class UploadedFilenameTitleTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_formats_dates_from_uploaded_filename_titles(): void
    {
        Carbon::setTestNow('2026-06-16 12:00:00');

        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-06-15-2026'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('2026.06.15_sunday-bulletin'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin_6.15.26'));
        $this->assertSame('Sunday Bulletin June 14, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin_26.06.14'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-06-15'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-15-06'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-june-15-2026'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-15-jun-26'));
        $this->assertSame('June 15, 2026', UploadedFilenameTitle::fromStem('06-15-2026'));
    }

    public function test_returns_the_first_date_from_uploaded_filename_titles(): void
    {
        Carbon::setTestNow('2026-06-16 12:00:00');

        $this->assertSame('2026-06-15', UploadedFilenameTitle::dateFromStem('sunday-bulletin-6.15.26')?->toDateString());
        $this->assertSame('2026-06-14', UploadedFilenameTitle::dateFromStem('sunday-bulletin-26.06.14')?->toDateString());
        $this->assertSame('2026-06-15', UploadedFilenameTitle::dateFromStem('family-fire-night-06.15')?->toDateString());
        $this->assertNull(UploadedFilenameTitle::dateFromStem('sunday-bulletin'));
    }

    public function test_returns_uploaded_filename_text_without_the_date(): void
    {
        Carbon::setTestNow('2026-06-16 12:00:00');

        $this->assertSame('Sunday Bulletin', UploadedFilenameTitle::textFromStemWithoutDate('sunday-bulletin-6.15.26'));
        $this->assertSame('Sunday Bulletin', UploadedFilenameTitle::textFromStemWithoutDate('sunday-bulletin-26.06.14'));
        $this->assertSame('Student Page Hero', UploadedFilenameTitle::textFromStemWithoutDate('student_page-hero'));
    }

    public function test_keeps_existing_title_behavior_when_no_date_is_found(): void
    {
        $this->assertSame('Student Page Hero', UploadedFilenameTitle::fromStem('student_page-hero'));
    }
}
