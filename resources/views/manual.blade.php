@php
    $churchName = $settings?->church_name ?? 'TwyxtCo Church';
    $logoUrl = $settings?->logoUrl() ?? asset('images/twyxtco-logo.png');
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Manual | {{ $churchName }}</title>
    <meta name="description" content="Printable website and CMS manual for {{ $churchName }} admins and content editors.">
    <script>
        (() => {
            const hash = window.location.hash;

            if (hash && hash !== '#top' && hash !== '#contents') {
                document.documentElement.classList.add('manual-start-collapsed');
            }
        })();
    </script>
    <style>
        :root {
            --ink: #1d241f;
            --muted: #5d6762;
            --line: #d7ded8;
            --paper: #ffffff;
            --soft: #f5f2e9;
            --accent: #e5b62e;
            --accent-deep: #8c6411;
            --teal: #15998f;
            --forest: #294e3c;
            --clay: #a75d42;
            --shadow: 0 18px 45px rgb(41 78 60 / 0.12);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            color: var(--ink);
            background:
                linear-gradient(180deg, #fbfaf5 0, var(--soft) 320px, #eef6f4 100%);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
            line-height: 1.58;
        }

        a {
            color: #075f58;
            font-weight: 700;
        }

        .manual-shell {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 64px;
        }

        .manual-cover,
        .manual-section {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .manual-cover {
            position: relative;
            overflow: hidden;
            padding: 44px;
            background:
                linear-gradient(135deg, rgb(255 255 255 / 0.96), rgb(255 247 219 / 0.92) 58%, rgb(230 247 244 / 0.9));
        }

        .manual-cover::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 6px;
            background: linear-gradient(90deg, var(--accent), var(--teal), var(--forest), var(--clay));
        }

        .manual-cover-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
        }

        .manual-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .manual-brand img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            padding: 8px;
            background: #fff;
            border: 1px solid rgb(215 222 216 / 0.9);
            border-radius: 8px;
            box-shadow: 0 10px 28px rgb(41 78 60 / 0.12);
        }

        .manual-brand span {
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .manual-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
            margin-bottom: 0;
        }

        .manual-actions a,
        .manual-actions button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            color: var(--ink);
            font: inherit;
            font-weight: 800;
            text-decoration: none;
            background: var(--accent);
            border: 1px solid #c59c14;
            border-radius: 6px;
            box-shadow: 0 8px 18px rgb(140 100 17 / 0.12);
            cursor: pointer;
        }

        .manual-actions a:nth-child(2) {
            color: #fff;
            background: var(--forest);
            border-color: var(--forest);
        }

        .manual-actions a:nth-child(3) {
            color: #fff;
            background: var(--teal);
            border-color: var(--teal);
        }

        .manual-kicker {
            margin: 34px 0 12px;
            color: var(--accent-deep);
            font-size: 0.82rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1,
        h2,
        h3,
        h4 {
            margin: 0;
            line-height: 1.12;
        }

        h1 {
            max-width: 820px;
            font-size: 3rem;
            letter-spacing: 0;
        }

        h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            font-size: 2rem;
            border-bottom: 2px solid #edf0ed;
        }

        h2::before {
            content: "";
            display: inline-block;
            width: 0.72em;
            height: 0.72em;
            border-radius: 4px;
            background: linear-gradient(135deg, var(--accent), var(--teal));
            box-shadow: 0 0 0 4px #f8f1d8;
        }

        h3 {
            margin-top: 28px;
            font-size: 1.35rem;
        }

        h4 {
            margin-top: 20px;
            font-size: 1rem;
        }

        p {
            margin: 12px 0 0;
        }

        ul,
        ol {
            margin: 12px 0 0;
            padding-left: 1.35rem;
        }

        li + li {
            margin-top: 6px;
        }

        code {
            padding: 2px 5px;
            background: #eef1ef;
            border: 1px solid #d9dfdc;
            border-radius: 4px;
        }

        .manual-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 24px;
            color: var(--muted);
            font-size: 0.96rem;
        }

        .manual-pill {
            padding: 6px 10px;
            background: rgb(255 255 255 / 0.72);
            border: 1px solid #e1ca74;
            border-radius: 999px;
        }

        .manual-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 28px;
        }

        .manual-note,
        .manual-card {
            position: relative;
            padding: 18px;
            background: #fbfbf8;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        .manual-note strong,
        .manual-card strong {
            display: block;
            margin-bottom: 4px;
        }

        .manual-section {
            margin-top: 18px;
            padding: 34px 44px 40px;
            scroll-margin-top: 120px;
        }

        .manual-contents-section {
            position: sticky;
            top: 0;
            z-index: 20;
            padding-bottom: 28px;
            margin-bottom: var(--manual-contents-spacer, 0);
        }

        .manual-contents-section.is-docked {
            box-shadow: 0 16px 34px rgb(29 36 31 / 0.18);
        }

        .manual-contents-section.is-collapsed {
            padding-bottom: 28px;
        }

        .manual-contents-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #edf0ed;
        }

        .manual-contents-bar h2 {
            flex: 1 1 auto;
            padding-bottom: 0;
            border-bottom: 0;
        }

        .manual-contents-controls {
            display: flex;
            flex: 0 0 auto;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .manual-contents-control {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 0 12px;
            color: var(--ink);
            font: inherit;
            font-size: 0.86rem;
            font-weight: 800;
            text-decoration: none;
            background: #fbfbf8;
            border: 1px solid var(--line);
            border-radius: 6px;
            cursor: pointer;
        }

        .manual-contents-control:hover,
        .manual-contents-control:focus {
            border-color: var(--teal);
            background: #f1fbf9;
            outline: none;
        }

        .manual-contents-top {
            color: #fff;
            background: var(--forest);
            border-color: var(--forest);
        }

        .manual-contents-top:hover,
        .manual-contents-top:focus {
            color: #fff;
            background: var(--teal);
            border-color: var(--teal);
        }

        .manual-section:target {
            border-color: #d7a61f;
            box-shadow: 0 0 0 4px rgb(229 182 46 / 0.22), var(--shadow);
        }

        .manual-section:nth-of-type(4n + 1) {
            border-top: 5px solid var(--teal);
        }

        .manual-section:nth-of-type(4n + 2) {
            border-top: 5px solid var(--accent);
        }

        .manual-section:nth-of-type(4n + 3) {
            border-top: 5px solid var(--forest);
        }

        .manual-section:nth-of-type(4n + 4) {
            border-top: 5px solid var(--clay);
        }

        .manual-toc {
            columns: 4;
            column-gap: 18px;
            margin-top: 18px;
            padding: 0;
            list-style: none;
            counter-reset: manual-toc;
            font-size: 0.92rem;
            line-height: 1.35;
        }

        html.manual-start-collapsed .manual-contents-section .manual-toc,
        body:not(.manual-contents-user-expanded):has(.manual-section:target:not(#top):not(#contents)) .manual-contents-section .manual-toc,
        .manual-contents-section.is-collapsed .manual-toc {
            display: none;
        }

        .manual-contents-section.is-measuring .manual-toc {
            display: block;
            visibility: hidden;
        }

        .manual-toc li {
            break-inside: avoid;
            margin: 0 0 7px;
            counter-increment: manual-toc;
        }

        .manual-toc a {
            display: block;
            padding: 8px 9px;
            color: var(--ink);
            text-decoration: none;
            background: #fbfbf8;
            border: 1px solid var(--line);
            border-radius: 6px;
        }

        .manual-toc a::before {
            content: counter(manual-toc, decimal-leading-zero) " ";
            color: var(--teal);
            font-weight: 900;
            margin-right: 4px;
        }

        .manual-toc a:hover,
        .manual-toc a:focus {
            border-color: var(--teal);
            background: #f1fbf9;
        }

        .manual-checklist {
            padding: 18px 18px 18px 34px;
            background: #f1fbf9;
            border: 1px solid #cbdeda;
            border-radius: 8px;
        }

        .manual-table {
            width: 100%;
            margin-top: 18px;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .manual-table th,
        .manual-table td {
            padding: 10px;
            text-align: left;
            vertical-align: top;
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        .manual-table th {
            background: #f7edc5;
            color: #3f3109;
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .manual-table tr > :last-child {
            border-right: 0;
        }

        .manual-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .manual-table tbody tr:nth-child(even) td {
            background: #fbfbf8;
        }

        .manual-print-footer {
            display: none;
        }

        @media (max-width: 760px) {
            .manual-cover,
            .manual-section {
                padding: 24px;
            }

            .manual-section {
                scroll-margin-top: 102px;
            }

            .manual-contents-section {
                padding-bottom: 24px;
            }

            .manual-contents-bar {
                align-items: flex-start;
                flex-direction: column;
            }

            .manual-contents-controls {
                justify-content: flex-start;
            }

            .manual-cover-top {
                display: block;
            }

            .manual-actions {
                justify-content: flex-start;
                margin-top: 18px;
            }

            h1 {
                font-size: 2.2rem;
            }

            .manual-grid {
                grid-template-columns: 1fr;
            }

            .manual-toc {
                columns: 1;
            }
        }

        @media print {
            body {
                color: #000;
                background: #fff;
                font-size: 11pt;
            }

            a {
                color: #000;
                text-decoration: none;
            }

            .manual-shell {
                width: 100%;
                padding: 0;
            }

            .manual-actions {
                display: none;
            }

            .manual-cover,
            .manual-section {
                margin: 0;
                padding: 0.45in 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                page-break-inside: avoid;
            }

            .manual-cover {
                background: #fff;
            }

            .manual-cover::before,
            h2::before {
                display: none;
            }

            .manual-section {
                page-break-before: always;
                scroll-margin-top: 0;
            }

            .manual-contents-section {
                position: static;
            }

            .manual-contents-controls {
                display: none;
            }

            .manual-note,
            .manual-card,
            .manual-checklist {
                background: #fff;
                border-color: #999;
            }

            .manual-table {
                border-radius: 0;
            }

            .manual-toc {
                columns: 1;
            }

            .manual-print-footer {
                display: block;
                margin-top: 24px;
                color: #333;
                font-size: 9pt;
            }
        }
    </style>
</head>
<body>
    <main class="manual-shell">
        <section class="manual-cover" id="top">
            <div class="manual-cover-top">
                <div class="manual-brand">
                    <img src="{{ $logoUrl }}" alt="{{ $churchName }} logo">
                    <span>Website operations guide</span>
                </div>

                <div class="manual-actions" aria-label="Manual actions">
                    <button type="button" onclick="window.print()">Print or Save PDF</button>
                    <a href="{{ url('/admin') }}">Open Admin</a>
                    <a href="{{ url('/') }}">Open Website</a>
                </div>
            </div>

            <p class="manual-kicker">Website and CMS Manual</p>
            <h1>{{ $churchName }} Admin and Content Editor Guide</h1>
            <p>This manual explains the functional areas of the church website CMS, how to keep public content current, and how to print or save this guide for less technical users.</p>

            <div class="manual-meta">
                <span class="manual-pill">Manual URL: {{ url('/manual') }}</span>
                <span class="manual-pill">Last updated: {{ $updatedAt }}</span>
                <span class="manual-pill">Admin area: {{ url('/admin') }}</span>
            </div>

            <div class="manual-grid">
                <div class="manual-card">
                    <strong>Who this is for</strong>
                    <p>Site admins, ministry leaders, office staff, bulletin editors, communications volunteers, and anyone allowed to edit public website content.</p>
                </div>
                <div class="manual-card">
                    <strong>How to use it</strong>
                    <p>Use the table of contents for daily tasks. For printed copies, use the Print or Save PDF button and choose your browser's PDF option.</p>
                </div>
            </div>
        </section>

        <section class="manual-section manual-contents-section is-collapsed" id="contents" data-manual-contents>
            <div class="manual-contents-bar">
                <h2>Contents</h2>
                <div class="manual-contents-controls" aria-label="Contents controls">
                    <button
                        type="button"
                        class="manual-contents-control"
                        data-manual-contents-toggle
                        aria-expanded="false"
                        aria-controls="manual-contents-links"
                    >
                        Expand
                    </button>
                    <a class="manual-contents-control manual-contents-top" href="#top">Top</a>
                </div>
            </div>
            <ol class="manual-toc" id="manual-contents-links" hidden>
                <li><a href="#roles">Roles and Permissions</a></li>
                <li><a href="#daily-workflow">Daily Workflow</a></li>
                <li><a href="#dashboard">Dashboard</a></li>
                <li><a href="#homepage">Homepage</a></li>
                <li><a href="#banners">Banners</a></li>
                <li><a href="#content-blocks">Content Blocks</a></li>
                <li><a href="#media-library">Media Library</a></li>
                <li><a href="#file-library">File Library</a></li>
                <li><a href="#pages">Pages</a></li>
                <li><a href="#navigation">Navigation</a></li>
                <li><a href="#settings">Site Settings</a></li>
                <li><a href="#analytics">Analytics</a></li>
                <li><a href="#backups">Backups</a></li>
                <li><a href="#workflow-notifications">Notifications and Email</a></li>
                <li><a href="#users">Users</a></li>
                <li><a href="#publishing">Publishing Checklist</a></li>
                <li><a href="#troubleshooting">Troubleshooting</a></li>
            </ol>
        </section>

        <section class="manual-section" id="roles">
            <h2>Roles and Permissions</h2>
            <p>The admin area can show different tools to different users. If a person cannot see a tool described in this manual, their account may not have permission for that area.</p>
            <div class="manual-grid">
                <div class="manual-card">
                    <strong>Site Admins</strong>
                    <p>Usually manage users, site settings, navigation, analytics, and all content areas.</p>
                </div>
                <div class="manual-card">
                    <strong>Content Editors</strong>
                    <p>Usually manage selected pages, media, or other assigned content areas.</p>
                </div>
            </div>
            <ul>
                <li>Use <strong>Users</strong> to grant or limit access.</li>
                <li>Some accounts may be limited to specific pages.</li>
                <li>When in doubt, ask a site admin before changing sitewide settings or navigation.</li>
            </ul>
        </section>

        <section class="manual-section" id="daily-workflow">
            <h2>Daily Workflow</h2>
            <ol>
                <li>Log in at <code>{{ url('/admin') }}</code>.</li>
                <li>Open the area you need from the sidebar.</li>
                <li>Create or edit the record.</li>
                <li>Check required fields, dates, images, links, and publication status.</li>
                <li>Save the record.</li>
                <li>Open the public page and confirm it looks right.</li>
                <li>If a workflow rule exists for the area, use <strong>Notify</strong> when another person or team should be emailed manually.</li>
            </ol>
            <div class="manual-note">
                <strong>Best practice</strong>
                <p>Use clear titles, short summaries, and real links. If a page is important for visitors, test it on a phone-sized screen before considering it done.</p>
            </div>
            <div class="manual-note">
                <strong>Admin buttons</strong>
                <p>Many admin buttons are icon-only to save space. Hover over an icon to see its label, such as Save, Save &amp; close, View, Notify, Delete, Create, or Cancel.</p>
            </div>
        </section>

        <section class="manual-section" id="dashboard">
            <h2>Dashboard</h2>
            <p>The Dashboard is the starting point for site health, recent changes, media activity, analytics snapshots, and items that may need attention.</p>
            <ul>
                <li>Review recent CMS updates to see what changed.</li>
                <li>Use site health cards to find missing or stale content.</li>
                <li>Use analytics widgets to understand which public pages people visit.</li>
                <li>Dashboard cards may be collapsed, reordered, or used as shortcuts into content areas.</li>
                <li>Dashboard Notes can show sitewide links or notes from Site Settings. This card can be moved but is not collapsible.</li>
            </ul>
        </section>

        <section class="manual-section" id="homepage">
            <h2>Homepage</h2>
            <p>Homepage controls the flexible sections below the top banner. These sections can be reordered and built from content blocks.</p>
            <h3>Common Tasks</h3>
            <ol>
                <li>Open <strong>Homepage</strong>.</li>
                <li>Add, copy, delete, collapse, or reorder content blocks.</li>
                <li>Update text, links, images, backgrounds, and block-specific settings.</li>
                <li>Save and review the homepage.</li>
            </ol>
            <h3>What To Watch</h3>
            <ul>
                <li>The homepage also uses <strong>Banners</strong> for the top hero/banner area.</li>
                <li>If Homepage is empty, the site can fall back to starter/default content.</li>
            </ul>
        </section>

        <section class="manual-section" id="banners">
            <h2>Banners</h2>
            <p>Banners manage the large rotating hero images and messages at the top of the homepage.</p>
            <ul>
                <li>Use strong landscape images that still work when cropped on mobile.</li>
                <li>Use start and end dates when a banner is seasonal.</li>
                <li>Keep titles short so they fit over images.</li>
                <li>Deactivate old banners instead of deleting them if you may reuse them later.</li>
            </ul>
            <div class="manual-note">
                <strong>Image tip</strong>
                <p>Use a real ministry, worship, building, or event photo when possible. Avoid tiny graphics, flyers with lots of text, or images where important content sits at the edge.</p>
            </div>
        </section>

        <section class="manual-section" id="content-blocks">
            <h2>Content Blocks</h2>
            <p>Content blocks are reusable sections used on the homepage and pages. The available block types may vary by area, but the core behavior is the same.</p>

            <table class="manual-table">
                <thead>
                    <tr>
                        <th>Block</th>
                        <th>Use it for</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Text</td>
                        <td>Headings, paragraphs, rich text, simple page sections.</td>
                        <td>Choose a background and content width. Width options include Small, Medium, and Wide.</td>
                    </tr>
                    <tr>
                        <td>Image + Text</td>
                        <td>A photo with supporting copy and optional button.</td>
                        <td>Choose image position. Full-screen image layouts work best with high-quality landscape photos.</td>
                    </tr>
                    <tr>
                        <td>Process Steps</td>
                        <td>Step-by-step instructions such as visit, serve, join, or sign up.</td>
                        <td>Use short step titles and practical summaries.</td>
                    </tr>
                    <tr>
                        <td>CTA</td>
                        <td>A clear action such as Contact Us, Give, Register, or Plan a Visit.</td>
                        <td>Always test the button URL after saving.</td>
                    </tr>
                    <tr>
                        <td>Cards</td>
                        <td>Groups of links to pages, forms, files, or next steps.</td>
                        <td>Cards center by row. One, two, four, or five cards should balance visually.</td>
                    </tr>
                    <tr>
                        <td>Info Strip</td>
                        <td>Service times, address, office hours, or short facts.</td>
                        <td>Can pull values from Site Settings or use custom values.</td>
                    </tr>
                    <tr>
                        <td>Embed</td>
                        <td>Trusted third-party embed code such as calendar or forms.</td>
                        <td>Only paste embed code from trusted providers.</td>
                    </tr>
                </tbody>
            </table>

            <h3>Block Editing Tips</h3>
            <ul>
                <li>Collapse blocks to make long pages easier to manage.</li>
                <li>Copy a block when creating a similar section.</li>
                <li>Use backgrounds intentionally. Too many alternating colors can feel busy.</li>
                <li>Keep button labels short: "Register", "Learn More", "Contact Us", or "Plan a Visit".</li>
                <li>Use local paths such as <code>/give</code> for pages on this site, or full <code>https://</code> links for external sites.</li>
            </ul>
        </section>

        <section class="manual-section" id="media-library">
            <h2>Media Library</h2>
            <p>The Media Library is the shared library area for reusable images used across pages, content blocks, and banners. Downloadable documents are managed separately in <strong>File Library</strong>.</p>
            <h3>Common Tasks</h3>
            <ul>
                <li>Upload one image at a time for pages, content blocks, and banners.</li>
                <li>Use image search and sorting to find files by path, filename, size, dimensions, usage, creator, or tracked dates.</li>
                <li>Use Edit image to update details or upload a replacement when the same file is used in multiple tracked places.</li>
                <li>Review image usage before deleting media.</li>
                <li>Use descriptive filenames when possible.</li>
            </ul>
            <h3>Image Details</h3>
            <ul>
                <li>Upload image starts with the image field, then shows title, slug, and tags after a file is selected. Edit image keeps details and optional replacement together.</li>
                <li>Image fields elsewhere in the admin use icon actions: choose an existing image, remove the selected image, add a new image, or edit the selected image. New image uploads show title, slug/path, and tags after a file is selected.</li>
                <li><strong>Title:</strong> Optional friendly title shown in Media Library and image picker results. If left blank while uploading or replacing, the uploaded filename is cleaned up and used as the title.</li>
                <li><strong>Optional Slug / Path:</strong> Optional searchable path-style label for organizing images without changing the actual file location. If left blank while uploading or replacing, the uploaded filename is cleaned into a slug and used here.</li>
                <li><strong>Tags:</strong> Optional multi-select labels shared with File Library tags. Existing tags can be selected directly from the list, new tags can be added from the field, and common image titles can add helpful tags automatically.</li>
                <li><strong>Created, Updated, and By:</strong> Automatically tracked details shown on each image card. These fields are searchable when available.</li>
                <li>When the last image using a tag is deleted or changed, that tag no longer appears as an option.</li>
                <li>New uploads use a generated folder with a clean filename, such as <strong>media-library/id/image-name.jpg</strong>.</li>
            </ul>
            <h3>Image Guidelines</h3>
            <ul>
                <li>Use clear, bright photos.</li>
                <li>Avoid images with heavy text baked into the image.</li>
                <li>Use landscape photos for banners and wide image sections.</li>
                <li>Use portrait or square photos only when the layout expects a profile image.</li>
            </ul>
        </section>

        <section class="manual-section" id="file-library">
            <h2>File Library</h2>
            <p>File Library manages downloadable documents as their own content area. Use it for PDFs, forms, bulletins, policies, spreadsheets, handouts, and other files that need stable links or optional extracted web content.</p>
            <h3>Common Fields</h3>
            <ul>
                <li><strong>Category:</strong> Groups the file, controls category-specific AI extraction instructions, and can suggest a default parent page.</li>
                <li><strong>Title:</strong> Admin/public title for the file.</li>
                <li><strong>Path:</strong> Stable file URL under <code>/files/</code>. New uploads can fill this from the uploaded filename, and the refresh icon can regenerate a <code>category-title</code> path such as <code>bulletin-sunday-worship-guide</code>.</li>
                <li><strong>Tags:</strong> Optional labels shared with Media Library image tags. Uploading a new file or editing the title can add matching tags automatically.</li>
                <li><strong>Make File Live:</strong> Turns the public file URL on or off.</li>
                <li><strong>Public or private:</strong> Public links work for anyone. Private published links require a user or admin login.</li>
                <li><strong>Parent Page:</strong> Optional. Groups the file under a related page such as Resources, Forms, or Bulletins. Selecting a category can fill this from the category default, and you can still change it.</li>
                <li><strong>Optional content:</strong> Formatted notes or AI-extracted content for the file record.</li>
                <li><strong>Publish date, Created Date, Updated Date:</strong> Publish date controls availability; created/updated dates are shown for reference.</li>
            </ul>
            <h3>File Categories</h3>
            <ul>
                <li>Use the tag-shaped <strong>Categories</strong> icon on the File Library page to manage file categories.</li>
                <li>File Categories are intentionally not shown as a separate main navigation item.</li>
                <li>Each category can have an optional <strong>Default Parent Page</strong>. Files using that category suggest this page as their parent, but the file can still be changed to another parent.</li>
                <li>Each category can have an optional <strong>Default Card Image</strong>. Files with their own card image use the file image first; files without one use the category image before the standard file fallback.</li>
                <li>Each category has <strong>Extraction Instructions</strong> used by the file extraction AI action for files in that category.</li>
                <li>Keep instructions direct. Example: what to preserve, what to ignore, and what output style is useful for that document type.</li>
            </ul>
            <h3>AI File Extraction</h3>
            <ul>
                <li>Open an existing file record with a saved current file.</li>
                <li>Choose <strong>Extract File Content</strong>.</li>
                <li>The tool saves the current file record and shows the exact prompt that will be sent with the saved file.</li>
                <li>Choose <strong>Continue</strong> to send the saved file and prompt to OpenAI.</li>
                <li>Review and edit the extracted HTML when it returns, then choose <strong>Use extracted content</strong>.</li>
                <li>Accepted extracted content is placed into Optional content. Review it before using it publicly.</li>
            </ul>
            <h3>Document Guidelines</h3>
            <ul>
                <li>Prefer PDFs for public visitors because they open consistently across phones, tablets, and computers.</li>
                <li>Keep source documents such as Word files or spreadsheets private unless visitors truly need them.</li>
                <li>Use expiration dates for temporary flyers, posters, forms, and event handouts.</li>
                <li>Use categories so files are easy to find later.</li>
                <li>Use Replace file to upload a new version while keeping older versions available.</li>
                <li>Copy the View or Download link when the file should be linked from a page, file listing, or email.</li>
            </ul>
        </section>

        <section class="manual-section" id="pages">
            <h2>Pages</h2>
            <p>Pages are general website pages such as About, New Here, Give, Contact, or other standalone content.</p>
            <h3>Common Fields</h3>
            <ul>
                <li><strong>Title:</strong> The page name.</li>
                <li><strong>Path:</strong> The URL path. Example: a path of <code>new-here</code> creates <code>/new-here</code>. Nested paths such as <code>resources/forms</code> are allowed.</li>
                <li><strong>Message:</strong> Optional rich text shown in the page header for short callouts, contact details, links, lists, or other header notes.</li>
                <li><strong>Content blocks:</strong> Preferred flexible page layout.</li>
                <li><strong>Show navigation and footer:</strong> Turn off only for special landing pages.</li>
                <li><strong>Parent Page:</strong> Optional grouping for child pages, useful for Resources, Forms, or other landing pages.</li>
                <li><strong>SEO title, SEO description, and No Index, No Follow:</strong> Optional search metadata shown only to admins and editors with Code Blocks access. No Index, No Follow asks search engines not to index the page or follow links on it.</li>
                <li><strong>Make Page Live:</strong> Must be enabled for public display or for a redirect to work.</li>
            </ul>
            <h3>Path Rules</h3>
            <ul>
                <li>Use lowercase words separated by hyphens. Use slashes only when intentionally creating a nested URL path.</li>
                <li>Do not change a public path casually. Old links may stop working.</li>
                <li>Avoid paths already used by system routes such as admin or manual.</li>
                <li>Use the generate icon to regenerate the path from the title when needed.</li>
            </ul>
            <h3>Parent Pages and Child Pages</h3>
            <ul>
                <li>A page can be a parent to other pages.</li>
                <li>Set Parent Page on the child page, not on the parent.</li>
                <li>Child pages can use Featured at and Featured expires at for parent-page listing or feature areas.</li>
                <li>The edit screen shows direct child pages attached to the current page.</li>
            </ul>
            <h3>Redirect Pages</h3>
            <p>A page can also be used as a simple redirect. This is useful for old links, printed QR codes, short campaign URLs, or pages that have moved.</p>
            <ul>
                <li>Turn on <strong>Redirect this page</strong>.</li>
                <li>Enter the path visitors will use, such as <code>/visit-us</code>.</li>
                <li>Enter the destination, such as <code>/new-here</code> or a full <code>https://</code> URL.</li>
                <li>Use <strong>Temporary</strong> for most redirects unless the old URL has permanently moved.</li>
                <li>A redirect page must be marked live before visitors can use it.</li>
            </ul>
        </section>

        <section class="manual-section" id="navigation">
            <h2>Navigation</h2>
            <p>Navigation controls the public header navigation and dropdown structure.</p>
            <ul>
                <li><strong>Label:</strong> Text visitors see in the header.</li>
                <li><strong>URL:</strong> Internal path, file link, or full external URL.</li>
                <li><strong>Parent link:</strong> Use to place a link under another top-level link as a dropdown item.</li>
                <li><strong>Sort order:</strong> Controls order in the header or dropdown.</li>
                <li><strong>Make Link Live, Publish at, Expires at:</strong> Control whether and when the link appears publicly.</li>
                <li><strong>Page limits:</strong> The list can warn when a navigation link points to a page that is draft, scheduled, expired, or missing.</li>
            </ul>
            <h3>Navigation Checklist</h3>
            <ul class="manual-checklist">
                <li>Keep top-level navigation short.</li>
                <li>Use dropdowns for related pages instead of crowding the header.</li>
                <li>Test every new or changed navigation link on desktop and mobile.</li>
                <li>Use Open in new tab mostly for external websites or downloadable files.</li>
                <li>When linking to a Page record, make sure both the navigation link and the page itself are live for visitors.</li>
            </ul>
        </section>

        <section class="manual-section" id="settings">
            <h2>Site Settings</h2>
            <p>Site Settings control church-wide information and default public page settings.</p>
            <ul>
                <li>Church name.</li>
                <li>Site logo used in the public header and footer.</li>
                <li>Default page header image used on pages that show a header but do not have their own Header Image selected.</li>
                <li>Address, email, phone, office hours, and Sunday service times.</li>
                <li>Site Design elements, including managed background colors for content blocks.</li>
                <li>Dashboard Notes shown to users and admins on the admin dashboard.</li>
                <li>Social media links.</li>
                <li>Default listing page titles, subtitles, images, and SEO information.</li>
                <li>OpenAI API key used by rewrite, page review, and file extraction tools when enabled. File extraction uses its own app environment model and reasoning settings so document reading can be tuned separately.</li>
                <li>Google Tag Manager and Google Analytics tracking IDs.</li>
            </ul>
            <div class="manual-note">
                <strong>Sitewide caution</strong>
                <p>Changes here can affect many public pages at once. Review the homepage, listings, footer, and contact information after saving major setting changes.</p>
            </div>
        </section>

        <section class="manual-section" id="analytics">
            <h2>Analytics</h2>
            <p>Analytics shows public website traffic, popular pages, referrers, devices, browsers, locations, and recent page views.</p>
            <ul>
                <li>Use date range controls to compare recent activity.</li>
                <li>Review top pages to learn what visitors use most.</li>
                <li>Review referrers to see where traffic comes from.</li>
                <li>Use device/browser information to understand how visitors are browsing.</li>
            </ul>
            <p>Analytics is useful for decisions, but it should not replace pastoral or ministry judgment. Use it as one signal.</p>
        </section>

        <section class="manual-section" id="backups">
            <h2>Backups</h2>
            <p>Backups help protect the website if content is deleted by mistake, files are lost, or the site needs to be restored after a technical problem.</p>

            <h3>Backup Profiles</h3>
            <table class="manual-table">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>What it includes</th>
                        <th>Typical schedule</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Database Backup</td>
                        <td>CMS content, users, settings, file records, analytics, and other database content.</td>
                        <td>Every 4 hours.</td>
                    </tr>
                    <tr>
                        <td>Full Site Backup</td>
                        <td>Database content, Media Library images, and private File Library documents.</td>
                        <td>Nightly.</td>
                    </tr>
                    <tr>
                        <td>Archive Backup</td>
                        <td>A full-site backup kept longer for weekly or every-X-days recovery points.</td>
                        <td>Weekly by default.</td>
                    </tr>
                </tbody>
            </table>

            <h3>Common Tasks</h3>
            <ul>
                <li>Review the latest backup age and size for each profile.</li>
                <li>Use <strong>Run now</strong> before a major content cleanup, launch, or sitewide change.</li>
                <li>Use <strong>Download latest</strong> when a trusted admin needs a local copy.</li>
                <li>Use <strong>Check health</strong> to confirm backup destinations have recent backups.</li>
            </ul>

            <div class="manual-note">
                <strong>Restore caution</strong>
                <p>Downloading a backup is safe for trusted admins, but restoring a backup should be handled by a technical admin or developer. A restore can overwrite current content, media, and files.</p>
            </div>

            <h3>What To Watch</h3>
            <ul>
                <li>If a profile says no backups are present, run that backup or ask a technical admin to check the schedule.</li>
                <li>If a health check reports a problem, confirm the backup storage destination is reachable and has enough space.</li>
                <li>Backups may be stored locally, offsite, or both depending on site configuration.</li>
                <li>The server must have the correct database dump tool installed for the site's database type.</li>
            </ul>
        </section>

        <section class="manual-section" id="workflow-notifications">
            <h2>Notifications and Email</h2>
            <p>Notifications let the CMS email people when important content is created, updated, deleted, or manually sent for review. Use them for handoffs such as asking another team to check media, review pages, or approve public content.</p>

            <h3>Where To Find Them</h3>
            <ul>
                <li>Open <strong>Notifications</strong> in the admin sidebar.</li>
                <li>Create a rule for one content area, such as Pages, Media Library, File Library, Site Settings, Users, Navigation, Homepage, or Banners.</li>
                <li>Each rule can be enabled or disabled without deleting it.</li>
            </ul>

            <h3>Triggers</h3>
            <table class="manual-table">
                <thead>
                    <tr>
                        <th>Trigger</th>
                        <th>When it sends</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Created</td>
                        <td>After a new item is created in the selected content area.</td>
                    </tr>
                    <tr>
                        <td>Updated</td>
                        <td>After an existing item is saved in the selected content area.</td>
                    </tr>
                    <tr>
                        <td>Deleted</td>
                        <td>After an item is deleted in the selected content area.</td>
                    </tr>
                    <tr>
                        <td>Manual</td>
                        <td>Adds a <strong>Notify</strong> action to matching edit screens or admin pages. The editor chooses when to send it.</td>
                    </tr>
                </tbody>
            </table>

            <h3>Automatic Send Delay</h3>
            <p>The automatic send delay controls how long the system waits before sending automatic Created, Updated, or Deleted emails. Available choices are Immediately, 15 minutes, 30 minutes, or 60 minutes after the last change.</p>
            <div class="manual-note">
                <strong>Why delays matter</strong>
                <p>If the same item is saved more than once during the delay, the pending email is updated instead of sending several separate emails. This keeps people from receiving a burst of messages while an editor is still working.</p>
            </div>

            <h3>Manual Notify</h3>
            <ol>
                <li>Create or edit a workflow rule with the <strong>Manual</strong> trigger.</li>
                <li>Open a matching content item, such as a page, file, or media item.</li>
                <li>Click the bell-shaped <strong>Notify</strong> action.</li>
                <li>Choose the workflow message or messages to send.</li>
                <li>Send the notification.</li>
            </ol>
            <p>Manual notifications are sent right away. If a matching automatic notification is still pending for the same item, the manual send cancels that pending automatic notification for that item.</p>

            <h3>Recipients</h3>
            <ul>
                <li><strong>All admins:</strong> Sends to every admin account.</li>
                <li><strong>All admins and users:</strong> Sends to every CMS user account.</li>
                <li><strong>Selected users:</strong> Sends only to the chosen user accounts.</li>
                <li><strong>Extra email addresses:</strong> Sends to addresses typed into the rule. Separate addresses with commas, semicolons, or new lines.</li>
            </ul>
            <p>A rule can combine recipient choices. Duplicate email addresses are removed before sending.</p>

            <h3>Email Content</h3>
            <ul>
                <li><strong>Subject:</strong> The email subject line.</li>
                <li><strong>Message:</strong> The main note recipients see at the top of the email.</li>
                <li>The email also includes the content area, action, item name, person who made the change when known, and links to view the public page or open the item in admin when available.</li>
            </ul>

            <h3>Recommended Uses</h3>
            <ul>
                <li>Notify communications when public pages need review.</li>
                <li>Notify ministry leaders when their page or file listing changes.</li>
                <li>Notify admins when users, site settings, navigation, or homepage content changes.</li>
                <li>Use manual notifications for review requests and automatic notifications for accountability or routine handoffs.</li>
            </ul>

            <div class="manual-note">
                <strong>Email caution</strong>
                <p>Only add recipients who need the message. Disable old rules instead of deleting them if you may reuse the workflow later.</p>
            </div>
        </section>

        <section class="manual-section" id="users">
            <h2>Users</h2>
            <p>Users manages admin and editor accounts.</p>
            <ul>
                <li>Create user accounts only for people who need CMS access.</li>
                <li>Grant the least access that lets someone do their job.</li>
                <li>Use area permissions for broad access, such as all pages, media, files, or settings.</li>
                <li>Use record-level permissions for specific pages when appropriate.</li>
                <li>Users can also be selected as recipients in Workflow Notification Rules.</li>
                <li>Remove or reduce access when someone no longer needs it.</li>
            </ul>
            <div class="manual-note">
                <strong>Password resets</strong>
                <p>Users can use password reset if configured. Site admins can also help by confirming the user's email and access level.</p>
            </div>
        </section>

        <section class="manual-section" id="publishing">
            <h2>Publishing Checklist</h2>
            <ul class="manual-checklist">
                <li>Title is clear and spelled correctly.</li>
                <li>Summary is short and useful.</li>
                <li>Body/content blocks are readable on desktop and mobile.</li>
                <li>Images are appropriate, high quality, and not awkwardly cropped.</li>
                <li>Buttons and links work.</li>
                <li>Publish/expire dates are correct.</li>
                <li>Featured status and feature dates are intentional.</li>
                <li>The item is marked published if it should be public.</li>
                <li>The public page has been opened and checked after saving.</li>
            </ul>
        </section>

        <section class="manual-section" id="troubleshooting">
            <h2>Troubleshooting</h2>
            <h3>I cannot see a tool in the sidebar.</h3>
            <p>Your user account may not have permission for that area. Ask a site admin to review your permissions.</p>

            <h3>I saved something but it is not public.</h3>
            <ul>
                <li>Confirm it is marked published.</li>
                <li>Check Publish at is not in the future.</li>
                <li>Check Expires at is not in the past.</li>
                <li>For featured sections, check Featured, Featured at, and Feature expires at.</li>
            </ul>

            <h3>An image is missing.</h3>
            <ul>
                <li>Confirm the image uploaded successfully.</li>
                <li>Check whether it was deleted or replaced in the Media Library.</li>
                <li>Try saving the record again after selecting the image.</li>
            </ul>

            <h3>A file link is missing or unavailable.</h3>
            <ul>
                <li>Confirm the file record is marked Make File Live.</li>
                <li>Check whether Publish date is in the future or the file has expired.</li>
                <li>Check whether the file is private. Private published files require a user or admin login.</li>
                <li>Open the file record in File Library and copy the current View or Download link after saving.</li>
            </ul>

            <h3>A link opens the wrong place.</h3>
            <ul>
                <li>Internal site paths should usually begin with <code>/</code>, such as <code>/new-here</code>.</li>
                <li>External links should include the full <code>https://</code> address.</li>
                <li>After saving, click the public link to test it.</li>
            </ul>

            <h3>A workflow email did not send.</h3>
            <ul>
                <li>Confirm the workflow rule is enabled.</li>
                <li>Confirm the rule's content area matches the item being edited.</li>
                <li>Confirm the needed trigger is selected: Created, Updated, Deleted, or Manual.</li>
                <li>Check that the rule has at least one valid recipient.</li>
                <li>If the rule has a delay, wait until the delay period has passed after the last change.</li>
                <li>For manual emails, use the <strong>Notify</strong> action on the matching edit screen.</li>
            </ul>

            <h3>The page layout feels uneven.</h3>
            <ul>
                <li>Try changing block background colors less often.</li>
                <li>Use Text block width settings to keep long text readable.</li>
                <li>Use Cards blocks for short groups of links, not long paragraphs.</li>
                <li>Use fewer sections if a page feels crowded.</li>
            </ul>

            <div class="manual-print-footer">
                <p>{{ $churchName }} Website Manual. Printed from {{ url('/manual') }}.</p>
            </div>
        </section>
    </main>
    <script>
        (() => {
            const contents = document.querySelector('[data-manual-contents]');
            const toggle = document.querySelector('[data-manual-contents-toggle]');
            const links = document.getElementById('manual-contents-links');

            if (! contents || ! toggle || ! links) {
                return;
            }

            let contentsTop = 0;
            let expandedHeight = 0;
            let isDocked = false;
            let isExpanded = false;
            let userExpandedWhileDocked = false;
            let keepCollapsedForHashScroll = false;
            let hashCollapseHandled = false;

            const hashTarget = () => {
                if (! window.location.hash || window.location.hash === '#top') {
                    return null;
                }

                try {
                    return document.getElementById(decodeURIComponent(window.location.hash.slice(1)));
                } catch (error) {
                    return null;
                }
            };

            const hasManualSectionHash = () => {
                return Boolean(window.location.hash && window.location.hash !== '#top' && window.location.hash !== '#contents');
            };

            const hasSectionHash = () => {
                const target = hashTarget();

                return target && target !== contents;
            };

            const restoreHashScroll = () => {
                const target = hashTarget();

                if (! target || target === contents) {
                    return;
                }

                window.requestAnimationFrame(() => {
                    target.scrollIntoView({ block: 'start', behavior: 'auto' });
                });
            };

            const measure = () => {
                const shouldStartCollapsed = ! hashCollapseHandled && hasManualSectionHash();

                if (shouldStartCollapsed) {
                    keepCollapsedForHashScroll = true;
                }

                contents.classList.toggle('is-measuring', shouldStartCollapsed);
                contents.style.setProperty('--manual-contents-spacer', '0px');
                contents.classList.remove('is-collapsed');
                links.hidden = false;
                isExpanded = true;

                expandedHeight = contents.offsetHeight;
                contentsTop = contents.offsetTop;

                isDocked = shouldStartCollapsed || keepCollapsedForHashScroll || window.scrollY >= Math.max(0, contentsTop + 1);
                contents.classList.toggle('is-docked', isDocked);

                setExpanded(isDocked ? userExpandedWhileDocked : true, true);
                document.documentElement.classList.remove('manual-start-collapsed');
                contents.classList.remove('is-measuring');

                if (shouldStartCollapsed) {
                    hashCollapseHandled = true;

                    return;
                }

                update();
            };

            const setExpanded = (expanded, force = false) => {
                if (! force && isExpanded === expanded) {
                    return;
                }

                isExpanded = expanded;
                toggle.textContent = expanded ? 'Collapse' : 'Expand';
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');

                if (expanded) {
                    contents.style.setProperty('--manual-contents-spacer', '0px');
                    contents.classList.remove('is-collapsed');
                    links.hidden = false;

                    return;
                }

                contents.classList.add('is-collapsed');
                links.hidden = true;

                const collapsedHeight = contents.offsetHeight;
                const spacer = Math.max(0, expandedHeight - collapsedHeight);
                contents.style.setProperty('--manual-contents-spacer', spacer + 'px');
            };

            const collapseForHashIfNeeded = () => {
                if (hashCollapseHandled || ! hasManualSectionHash()) {
                    return false;
                }

                hashCollapseHandled = true;
                keepCollapsedForHashScroll = true;
                userExpandedWhileDocked = false;
                isDocked = true;
                contents.classList.add('is-docked');
                document.body.classList.remove('manual-contents-user-expanded');
                setExpanded(false, true);

                return true;
            };

            const update = () => {
                const scrollY = window.scrollY;

                if (keepCollapsedForHashScroll) {
                    const reachedContents = scrollY >= Math.max(0, contentsTop - 24);

                    if (! reachedContents) {
                        isDocked = true;
                        contents.classList.add('is-docked');
                        setExpanded(false);

                        return;
                    }

                    keepCollapsedForHashScroll = false;
                }

                const docked = isDocked
                    ? scrollY >= Math.max(0, contentsTop - 24)
                    : scrollY >= Math.max(0, contentsTop + 1);

                if (docked !== isDocked) {
                    isDocked = docked;
                    contents.classList.toggle('is-docked', docked);
                }

                if (! docked) {
                    userExpandedWhileDocked = false;
                    setExpanded(true);

                    return;
                }

                setExpanded(userExpandedWhileDocked);
            };

            toggle.addEventListener('click', () => {
                const willExpand = ! isExpanded;

                keepCollapsedForHashScroll = false;
                userExpandedWhileDocked = isDocked ? willExpand : false;
                document.body.classList.toggle('manual-contents-user-expanded', willExpand);
                setExpanded(willExpand);
            });

            contents.querySelectorAll('.manual-toc a').forEach((link) => {
                link.addEventListener('click', () => {
                    userExpandedWhileDocked = false;
                    document.body.classList.remove('manual-contents-user-expanded');
                    window.setTimeout(() => {
                        update();
                        restoreHashScroll();
                    }, 0);
                });
            });

            window.addEventListener('resize', measure);
            window.addEventListener('scroll', update, { passive: true });
            window.addEventListener('beforeprint', () => {
                document.body.classList.add('manual-contents-user-expanded');
                setExpanded(true);
            });
            window.addEventListener('afterprint', () => {
                document.body.classList.remove('manual-contents-user-expanded');
                update();
            });
            window.addEventListener('load', () => {
                measure();
                restoreHashScroll();
            });
            window.addEventListener('hashchange', () => {
                userExpandedWhileDocked = false;
                document.body.classList.remove('manual-contents-user-expanded');
                hashCollapseHandled = false;
                keepCollapsedForHashScroll = hasManualSectionHash();
                window.setTimeout(() => {
                    update();
                    restoreHashScroll();
                }, 0);
            });
            measure();
            window.requestAnimationFrame(() => {
                collapseForHashIfNeeded();
                restoreHashScroll();
            });
        })();
    </script>
</body>
</html>
