<?php

use App\Http\Controllers\Admin\PageVisualSnapshotImageController;
use App\Http\Controllers\Admin\PageVisualSnapshotPreviewController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\BulletinController;
use App\Http\Controllers\FileDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadershipController;
use App\Http\Controllers\MinistryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SermonController;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{slug}', [AnnouncementController::class, 'show'])->name('announcements.show');

Route::get('/bulletins', [BulletinController::class, 'index'])->name('bulletins.index');
Route::get('/bulletins/{date}', [BulletinController::class, 'show'])
    ->where('date', '\d{4}-\d{2}-\d{2}')
    ->name('bulletins.show');

Route::get('/files/{fileName}', [FileDocumentController::class, 'show'])
    ->where('fileName', '[A-Za-z0-9\\-]+')
    ->name('files.show');

Route::middleware('auth')->group(function () {
    Route::get('/admin/files/{fileDocument}/download', [FileDocumentController::class, 'download'])
        ->name('admin.files.download');

    Route::get('/admin/files/versions/{fileDocumentVersion}/download', [FileDocumentController::class, 'downloadVersion'])
        ->name('admin.files.versions.download');
});

Route::get('/admin/page-visual-snapshots/preview/{type}/{record?}', PageVisualSnapshotPreviewController::class)
    ->middleware('signed')
    ->name('admin.page-visual-snapshots.preview');

Route::get('/admin/page-visual-snapshots/image', PageVisualSnapshotImageController::class)
    ->middleware('signed')
    ->name('admin.page-visual-snapshots.image');

Route::get('/leadership', [LeadershipController::class, 'index'])->name('leadership.index');
Route::get('/leadership/{slug}', [LeadershipController::class, 'show'])->name('leadership.show');

Route::get('/ministry', [MinistryController::class, 'index'])->name('ministries.index');
Route::get('/ministry/{slug}', [MinistryController::class, 'show'])->name('ministries.show');

Route::get('/sermons', SermonController::class)->name('sermons.index');

Route::get('/manual', function () {
    return view('manual', [
        'settings' => SiteSetting::query()->first(),
        'updatedAt' => 'June 6, 2026',
    ]);
})->name('manual');

Route::get('/concepts', function () {
    return view('concepts.index', [
        'concepts' => [
            'editorial' => 'Editorial: Full White Header',
            'editorial-gold' => 'Editorial: Gold Accent',
            'editorial-clay' => 'Editorial: Clay Accent',
            'editorial-forest' => 'Editorial: Forest Accent',
            'editorial-bands' => 'Editorial: Full-Width Bands',
            'editorial-bands-gold' => 'Full-Width Bands: Gold',
            'editorial-bands-clay' => 'Full-Width Bands: Clay',
            'editorial-bands-forest' => 'Full-Width Bands: Forest',
            'editorial-color' => 'Editorial: Color Accent Bands',
            'editorial-color-gold' => 'Color Bands: Gold',
            'editorial-color-clay' => 'Color Bands: Clay',
            'editorial-color-forest' => 'Color Bands: Forest',
            'refined-a' => 'Refined A: Color Bands',
            'refined-b' => 'Refined B: White/Black Bands',
            'refined-c' => 'Refined C: Structured Cards',
            'editorial-black' => 'Editorial: Full Black Header',
            'editorial-mono' => 'Editorial: Black & White',
            'editorial-teal' => 'Editorial: Teal Rule',
            'bright' => 'Floating Pill Header',
            'hub' => 'Content-Width Bar Header',
            'hub-gold' => 'Hub: Gold Accent',
            'hub-clay' => 'Hub: Clay Accent',
            'hub-forest' => 'Hub: Forest Accent',
        ],
    ]);
});

Route::get('/concept-screens', function () {
    $labels = [
        'refined-a-first.png' => 'Refined A: First Screen',
        'refined-a-tall.png' => 'Refined A: Tall Capture',
        'refined-b-first.png' => 'Refined B: First Screen',
        'refined-b-tall.png' => 'Refined B: Tall Capture',
        'refined-c-first.png' => 'Refined C: First Screen',
        'refined-c-tall.png' => 'Refined C: Tall Capture',
        'editorial-gold-first.png' => 'Editorial Gold: First Screen',
        'editorial-gold-tall.png' => 'Editorial Gold: Tall Capture',
        'editorial-clay-first.png' => 'Editorial Clay: First Screen',
        'editorial-clay-tall.png' => 'Editorial Clay: Tall Capture',
        'editorial-forest-first.png' => 'Editorial Forest: First Screen',
        'editorial-forest-tall.png' => 'Editorial Forest: Tall Capture',
        'editorial-bands-gold-first.png' => 'Full-Width Bands Gold: First Screen',
        'editorial-bands-gold-tall.png' => 'Full-Width Bands Gold: Tall Capture',
        'editorial-bands-clay-first.png' => 'Full-Width Bands Clay: First Screen',
        'editorial-bands-clay-tall.png' => 'Full-Width Bands Clay: Tall Capture',
        'editorial-bands-forest-first.png' => 'Full-Width Bands Forest: First Screen',
        'editorial-bands-forest-tall.png' => 'Full-Width Bands Forest: Tall Capture',
        'editorial-color-gold-first.png' => 'Color Bands Gold: First Screen',
        'editorial-color-gold-tall.png' => 'Color Bands Gold: Tall Capture',
        'editorial-color-clay-first.png' => 'Color Bands Clay: First Screen',
        'editorial-color-clay-tall.png' => 'Color Bands Clay: Tall Capture',
        'editorial-color-forest-first.png' => 'Color Bands Forest: First Screen',
        'editorial-color-forest-tall.png' => 'Color Bands Forest: Tall Capture',
        'hub-gold-first.png' => 'Hub Gold: First Screen',
        'hub-gold-tall.png' => 'Hub Gold: Tall Capture',
        'hub-clay-first.png' => 'Hub Clay: First Screen',
        'hub-clay-tall.png' => 'Hub Clay: Tall Capture',
        'hub-forest-first.png' => 'Hub Forest: First Screen',
        'hub-forest-tall.png' => 'Hub Forest: Tall Capture',
        'editorial-bands-fullbands.png' => 'Editorial Bands: First Screen',
        'editorial-color-fullbands.png' => 'Editorial Color Bands: First Screen',
        'editorial-bands-tall.png' => 'Editorial Bands: Tall Capture',
        'editorial-color-tall.png' => 'Editorial Color Bands: Tall Capture',
        'editorial-v2.png' => 'Editorial: Full-Width White Header',
        'editorial-black-v2.png' => 'Editorial: Full-Width Black Header',
        'editorial-mono-v2.png' => 'Editorial: Black & White',
        'editorial-teal-v2.png' => 'Editorial: Teal Rule',
        'header-pill.png' => 'Header: Floating Pill',
        'header-full.png' => 'Header: Full-Width Bar',
        'header-width.png' => 'Header: Content-Width Bar',
        'header-editorial.png' => 'Header: Editorial',
        'header-bright.png' => 'Header: Bright',
        'header-hub.png' => 'Header: Hub',
        'round3-editorial.png' => 'Round 3: Editorial',
        'round3-bright.png' => 'Round 3: Bright',
        'round3-hub.png' => 'Round 3: Hub',
        'editorial.png' => 'Round 2: Editorial',
        'bright.png' => 'Round 2: Bright',
        'hub.png' => 'Round 2: Hub',
        'editorial-mobile.png' => 'Mobile: Editorial',
    ];

    return view('concepts.screenshots', [
        'screenshots' => collect($labels)
            ->map(fn (string $label, string $file) => [
                'label' => $label,
                'file' => $file,
                'url' => asset("concept-screens/{$file}"),
            ])
            ->values(),
    ]);
});

Route::get('/concepts/{concept}', function (string $concept) {
    $concepts = [
        'bright' => [
            'name' => 'Floating Pill Header',
            'eyebrow' => "You're invited",
            'headline' => 'Following Jesus together.',
            'subhead' => 'A clean white-first direction with strong black sections, direct Sunday information, and a guest path that is easy to follow.',
            'primaryCta' => 'Plan a Visit',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Latest Message',
            'secondaryUrl' => '/messages',
            'variant' => 'bright',
            'heroImage' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial' => [
            'name' => 'Full-Width White Header',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A black-first direction with oversized type, clear visit details, and white content blocks that keep the page from feeling too heavy.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'teal',
            'heroMode' => 'default',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-black' => [
            'name' => 'Full-Width Black Header',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A black-first direction with the navigation absorbed into the dark surface and a white logo field running through the header.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-black-header',
            'accentMode' => 'teal',
            'heroMode' => 'default',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-gold' => [
            'name' => 'Editorial Gold Accent',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The favorite editorial layout with warm gold used for featured content, labels, and action accents.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'gold',
            'heroMode' => 'default',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-clay' => [
            'name' => 'Editorial Clay Accent',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The favorite editorial layout with a warmer clay accent for care, community, and hospitality content.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'clay',
            'heroMode' => 'default',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-forest' => [
            'name' => 'Editorial Forest Accent',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The favorite editorial layout with a deeper evergreen accent for a mature, grounded feel.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'forest',
            'heroMode' => 'default',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-bands' => [
            'name' => 'Full-Width Section Bands',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A version of the favorite editorial direction with screen-wide white and black bands below the hero.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'teal',
            'heroMode' => 'default',
            'sectionMode' => 'bands',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-bands-gold' => [
            'name' => 'Full-Width Bands: Gold',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The full-width white and black band layout with warm gold used only where it can carry attention cleanly.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'gold',
            'heroMode' => 'default',
            'sectionMode' => 'bands',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-bands-clay' => [
            'name' => 'Full-Width Bands: Clay',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The full-width white and black band layout with a warmer clay accent for hospitality and community moments.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'clay',
            'heroMode' => 'default',
            'sectionMode' => 'bands',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-bands-forest' => [
            'name' => 'Full-Width Bands: Forest',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The full-width white and black band layout with a deep evergreen accent for a more grounded version.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'forest',
            'heroMode' => 'default',
            'sectionMode' => 'bands',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-color' => [
            'name' => 'Color Accent Bands',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A version of the favorite editorial direction with full-width teal and black bands for more visual punch.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'color-bands',
            'heroMode' => 'default',
            'sectionMode' => 'bands color',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-color-gold' => [
            'name' => 'Color Bands: Gold',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The color-band layout with warm gold reserved for featured sections, events, and announcements.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'gold',
            'heroMode' => 'default',
            'sectionMode' => 'bands color',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-color-clay' => [
            'name' => 'Color Bands: Clay',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The color-band layout with clay warmth for hospitality, care, and family-oriented content.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'clay',
            'heroMode' => 'default',
            'sectionMode' => 'bands color',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-color-forest' => [
            'name' => 'Color Bands: Forest',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The color-band layout with evergreen sections for a more grounded and less bright accent option.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'forest',
            'heroMode' => 'default',
            'sectionMode' => 'bands color',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'refined-a' => [
            'name' => 'Refined A: Color Bands',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'The top direction refined: dark editorial hero, white navigation, teal section bands, and more intentional visitor pathways.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'refined-a',
            'heroMode' => 'default',
            'sectionMode' => 'bands refined-a',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'refined-b' => [
            'name' => 'Refined B: White/Black Bands',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A quieter refinement: full-width white and black bands, strong type, and fewer color accents beyond the TwyxtCo teal.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'refined-b',
            'heroMode' => 'default',
            'sectionMode' => 'bands refined-b',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'refined-c' => [
            'name' => 'Refined C: Structured Cards',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A refinement that keeps the dark editorial feel but borrows more of the hub structure: bold cards, clear pathways, and current church life.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-white-header',
            'accentMode' => 'refined-c',
            'heroMode' => 'default',
            'sectionMode' => 'cards refined-c',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-mono' => [
            'name' => 'Black & White Type',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A nearly black-and-white treatment that lets the logo carry the teal while the interface relies on type, scale, and contrast.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-mono-header',
            'accentMode' => 'mono',
            'heroMode' => 'mono',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'editorial-teal' => [
            'name' => 'Teal Rule Header',
            'eyebrow' => 'Welcome home',
            'headline' => 'Grace for real life.',
            'subhead' => 'A white full-width navigation bar with a strong teal rule, keeping the dark hero but making the brand color more intentional.',
            'primaryCta' => 'Visit This Sunday',
            'primaryUrl' => '/new-here',
            'secondaryCta' => 'Watch Live',
            'secondaryUrl' => '/live',
            'variant' => 'editorial',
            'headerVariant' => 'editorial-teal-header',
            'accentMode' => 'teal-rule',
            'heroMode' => 'default',
            'heroImage' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?auto=format&fit=crop&w=1800&q=85',
        ],
        'hub' => [
            'name' => 'Content-Width Bar Header',
            'eyebrow' => 'Take your next step',
            'headline' => 'Find your place. Use your gifts.',
            'subhead' => 'A black, white, and teal direction shaped around visitor clarity, ministry pathways, serving, and fast access to current church life.',
            'primaryCta' => 'Join a Team',
            'primaryUrl' => '/sundays',
            'secondaryCta' => 'Open One Church',
            'secondaryUrl' => 'https://onechurchsoftware.com/',
            'variant' => 'hub',
            'heroImage' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1800&q=85',
        ],
        'hub-gold' => [
            'name' => 'Hub: Gold Accent',
            'eyebrow' => 'Take your next step',
            'headline' => 'Find your place. Use your gifts.',
            'subhead' => 'The structured hub layout with gold used for the high-priority action path and current church-life markers.',
            'primaryCta' => 'Join a Team',
            'primaryUrl' => '/sundays',
            'secondaryCta' => 'Open One Church',
            'secondaryUrl' => 'https://onechurchsoftware.com/',
            'variant' => 'hub',
            'accentMode' => 'gold',
            'heroImage' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1800&q=85',
        ],
        'hub-clay' => [
            'name' => 'Hub: Clay Accent',
            'eyebrow' => 'Take your next step',
            'headline' => 'Find your place. Use your gifts.',
            'subhead' => 'The structured hub layout with clay warmth for serving, groups, students, and care-oriented pathways.',
            'primaryCta' => 'Join a Team',
            'primaryUrl' => '/sundays',
            'secondaryCta' => 'Open One Church',
            'secondaryUrl' => 'https://onechurchsoftware.com/',
            'variant' => 'hub',
            'accentMode' => 'clay',
            'heroImage' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1800&q=85',
        ],
        'hub-forest' => [
            'name' => 'Hub: Forest Accent',
            'eyebrow' => 'Take your next step',
            'headline' => 'Find your place. Use your gifts.',
            'subhead' => 'The structured hub layout with deep evergreen accents for a calmer, more established feel.',
            'primaryCta' => 'Join a Team',
            'primaryUrl' => '/sundays',
            'secondaryCta' => 'Open One Church',
            'secondaryUrl' => 'https://onechurchsoftware.com/',
            'variant' => 'hub',
            'accentMode' => 'forest',
            'heroImage' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1800&q=85',
        ],
    ];

    abort_unless(array_key_exists($concept, $concepts), 404);

    return view('concepts.show', [
        'concept' => $concepts[$concept],
        'allConcepts' => $concepts,
        'currentSlug' => $concept,
    ]);
});

Route::get('/{slug}', PageController::class)
    ->where('slug', '^(?!admin$|manual$|concepts$|concept-screens$|build$|storage$|livewire$)[A-Za-z0-9-]+$')
    ->name('pages.show');
