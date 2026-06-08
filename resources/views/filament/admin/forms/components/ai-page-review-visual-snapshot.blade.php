@if (filled($visualSnapshotUrl))
    <div class="twyxtco-ai-page-review-visual">
        <div class="twyxtco-ai-page-review-visual-header">
            <div class="twyxtco-ai-page-review-visual-title">Page screenshot</div>

            <a
                href="{{ $visualSnapshotUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="twyxtco-ai-page-review-visual-link"
            >
                Open full-size screenshot
            </a>
        </div>

        <a
            href="{{ $visualSnapshotUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="twyxtco-ai-page-review-visual-preview"
        >
            <img
                src="{{ $visualSnapshotUrl }}"
                alt="Full-page screenshot used for this AI review"
                loading="lazy"
            >
        </a>
    </div>
@endif
