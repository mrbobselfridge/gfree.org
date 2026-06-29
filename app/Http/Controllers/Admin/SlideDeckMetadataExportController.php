<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlideDeck;
use App\Support\AdminAccess;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SlideDeckMetadataExportController extends Controller
{
    public function __invoke(SlideDeck $slideDeck, string $format): Response|StreamedResponse
    {
        abort_unless(AdminAccess::canAccessTool(Auth::user(), AdminAccess::SLIDE_DECK_IMPORT), Response::HTTP_FORBIDDEN);
        abort_unless(in_array($format, ['csv', 'json'], true), Response::HTTP_NOT_FOUND);

        $filename = Str::slug($slideDeck->name ?: 'slide-deck').'-metadata.'.$format;
        $rows = $slideDeck->slides()->get()->map(fn ($slide): array => [
            'slide_number' => $slide->slide_number,
            'slide_type' => $slide->slide_type,
            'suggested_name' => $slide->suggested_name,
            'extracted_text' => $slide->extracted_text,
            'intro_text' => $slide->summary,
            'event_title' => $slide->event_title,
            'event_date' => $slide->event_date,
            'event_time' => $slide->event_time,
            'event_location' => $slide->event_location,
            'event_audience' => $slide->event_audience,
            'contact_person' => $slide->contact_person,
            'announcement_details' => $slide->announcement_details,
            'confidence_score' => $slide->confidence_score,
            'image_path' => $slide->image_path,
            'thumbnail_path' => $slide->thumbnail_path,
        ])->all();

        if ($format === 'json') {
            return response(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            $headers = array_keys($rows[0] ?? [
                'slide_number' => null,
                'slide_type' => null,
                'suggested_name' => null,
            ]);

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
