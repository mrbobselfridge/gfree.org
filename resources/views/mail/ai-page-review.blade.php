<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AI Review</title>
</head>
<body style="margin: 0; background: #f5f5f5; color: #171717; font-family: Arial, Helvetica, sans-serif; line-height: 1.5;">
    <div style="max-width: 760px; margin: 0 auto; padding: 24px;">
        <div style="background: #ffffff; border: 1px solid #d4d4d4; border-radius: 8px; padding: 24px;">
            <h1 style="margin: 0 0 18px; color: #111827; font-size: 24px; line-height: 1.25;">AI Review</h1>

            <p style="margin: 0 0 16px;">
                <strong>Reviewed @</strong> {{ $timestamp }} for {{ $churchName }}
            </p>

            <p style="margin: 0 0 4px;">
                <strong>Page Reviewed:</strong> {{ $recordTitle }} -
                <a href="{{ $publicUrl }}" style="color: #2563eb; font-weight: 700;">LINK</a>
            </p>

            <p style="margin: 0 0 4px;">
                <strong>Edit Content:</strong>
                <a href="{{ $adminUrl }}" style="color: #2563eb; font-weight: 700;">LINK</a>
            </p>

            @if (filled($visualSnapshotUrl))
                <p style="margin: 0 0 18px;">
                    <strong>Screenshot Reviewed:</strong>
                    <a href="{{ $visualSnapshotUrl }}" style="color: #2563eb; font-weight: 700;">LINK</a>
                </p>
            @endif

            <hr style="border: 0; border-top: 1px solid #d4d4d4; margin: 20px 0;">

            <div style="font-size: 16px;">
                {!! $reviewHtml !!}
            </div>
        </div>
    </div>
</body>
</html>
