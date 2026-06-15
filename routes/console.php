<?php

use App\Models\Page;
use App\Support\BackupProfiles;
use App\Support\PageVisualSnapshot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('debug:page-visual-snapshot {slug? : Page slug to capture} {--id= : Page ID to capture}', function () {
    $id = $this->option('id');
    $slug = $this->argument('slug');

    if (filled($id)) {
        $page = Page::query()->find($id);
        $notFoundMessage = "No page found for ID [{$id}].";
    } elseif (filled($slug)) {
        $normalizedSlug = ltrim((string) $slug, '/');
        $page = Page::query()->where('slug', $normalizedSlug)->first();
        $notFoundMessage = "No page found for slug [{$normalizedSlug}].";
    } else {
        $this->error('Provide a page slug or --id.');

        return SymfonyCommand::FAILURE;
    }

    if (! $page) {
        $this->error($notFoundMessage);

        return SymfonyCommand::FAILURE;
    }

    /** @var PageVisualSnapshot $snapshots */
    $snapshots = app(PageVisualSnapshot::class);

    $this->line('Page: #'.$page->getKey().' '.$page->title);
    $this->line('Public URL: '.($page->publicUrl() ?: 'none'));
    $this->line('Node binary: '.config('services.page_visual_snapshot.node_binary', 'node'));
    $this->line('Snapshot directory: '.Storage::disk('local')->path('page-visual-snapshots'));

    try {
        $this->line('Preview URL: '.$snapshots->previewUrl($page));

        $result = $snapshots->capture($page);
    } catch (\Throwable $exception) {
        $this->error('Snapshot failed: '.$exception->getMessage());

        return SymfonyCommand::FAILURE;
    }

    $this->info('Snapshot captured.');
    $this->line('Path: '.$result->path);
    $this->line('Absolute path: '.$result->absolutePath);
    $this->line('Image URL: '.($result->imageUrl ?: 'none'));

    return SymfonyCommand::SUCCESS;
})->purpose('Capture a page visual snapshot and print runtime diagnostics');

BackupProfiles::scheduleConfiguredProfiles();
