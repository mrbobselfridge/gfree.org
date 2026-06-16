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
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-06-15'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-15-06'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-june-15-2026'));
        $this->assertSame('Sunday Bulletin June 15, 2026', UploadedFilenameTitle::fromStem('sunday-bulletin-15-jun-26'));
        $this->assertSame('June 15, 2026', UploadedFilenameTitle::fromStem('06-15-2026'));
    }

    public function test_keeps_existing_title_behavior_when_no_date_is_found(): void
    {
        $this->assertSame('Student Page Hero', UploadedFilenameTitle::fromStem('student_page-hero'));
    }
}
