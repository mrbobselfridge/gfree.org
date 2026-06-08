@if (filled($visualSnapshotUrl))
    <div
        class="twyxtco-ai-page-review-visual"
        x-data="{
            syncReviewHeight() {
                this.$nextTick(() => {
                    const results = this.$root.closest('.twyxtco-ai-page-review-results')

                    if (! results) {
                        return
                    }

                    results.style.setProperty('--twyxtco-ai-page-review-visual-height', `${this.$root.offsetHeight}px`)
                })
            },
        }"
        x-init="
            syncReviewHeight()

            const observer = new ResizeObserver(() => syncReviewHeight())
            observer.observe($el)

            window.addEventListener('resize', syncReviewHeight)
        "
    >
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
                x-on:load="syncReviewHeight()"
            >
        </a>
    </div>
@endif
