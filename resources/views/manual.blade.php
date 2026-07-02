<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Manual</title>
    <meta name="description" content="Printable website and CMS manual for admins and content editors.">
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

        .manual-screenshot-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 22px;
        }

        .manual-screenshot {
            margin: 22px 0 0;
            padding: 10px;
            background: #fbfbf8;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        .manual-screenshot-grid .manual-screenshot {
            margin-top: 0;
        }

        .manual-screenshot img {
            display: block;
            width: 100%;
            height: auto;
            border: 1px solid #d9dfdc;
            border-radius: 6px;
        }

        .manual-screenshot figcaption {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.35;
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

            .manual-screenshot-grid {
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
            .manual-screenshot,
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
                    <span>Website operations guide</span>
                </div>

                <div class="manual-actions" aria-label="Manual actions">
                    <button type="button" onclick="window.print()">Print or Save PDF</button>
                    <a href="{{ url('/admin') }}">Open Admin</a>
                    <a href="{{ url('/') }}">Open Website</a>
                </div>
            </div>

            <p class="manual-kicker">Website and CMS Manual</p>
            <h1>Website Admin and Content Editor Guide</h1>
            <p>This manual explains the functional areas of the website CMS, how to keep public content current, and how to print or save this guide for less technical users.</p>

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
                <li><a href="#admin-search-shortcuts">Admin Search and Shortcuts</a></li>
                <li><a href="#system-overview">System Overview</a></li>
                <li><a href="#public-page-structure">Public Page Structure</a></li>
                <li><a href="#dashboard">Dashboard</a></li>
                <li><a href="#homepage">Homepage</a></li>
                <li><a href="#banners">Banners</a></li>
                <li><a href="#site-alerts">Site Alerts</a></li>
                <li><a href="#content-blocks">Content Blocks</a></li>
                <li><a href="#slide-decks">Slide Deck Import</a></li>
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
                <p>Many admin buttons are icon-only to save space. Hover over an icon to see its label and shortcut, such as Save, Save &amp; close, View, Notify, Delete, Create, or Cancel.</p>
                <p>Common shortcuts include Ctrl/Cmd+S for Save, Ctrl/Cmd+Enter for Save &amp; close, Ctrl/Cmd+Shift+S for Create &amp; add another, Ctrl/Cmd+D for Delete, Escape for Cancel, Alt+V for View, Alt++ for New, Alt+N for Notes, Alt+B for Notify, Alt+A for the primary AI action, Alt+D for Download, Alt+C for Collapse, and Alt+E for Expand when those actions are available.</p>
            </div>
        </section>

        <section class="manual-section" id="admin-search-shortcuts">
            <h2>Admin Search and Shortcuts</h2>
            <p>The highlighted <strong>Search Full System</strong> box in the top admin bar is the fastest way to jump to records across the CMS. Search results are grouped by area, such as Navigation Links or Pages, with individual results indented under each group.</p>

            <h3>Global Search</h3>
            <ul>
                <li>Use <strong>Search Full System</strong> to find CMS records without first opening that content area.</li>
                <li>Results are grouped by type. Click a result to open the matching edit or view screen, depending on the record and your permissions.</li>
                <li>The search box is intentionally larger and visually highlighted so it is easy to find from any admin screen.</li>
                <li>Current global search focuses on key record fields such as titles and names. Deeper body-content and block-content search can be added later as a separate indexed search enhancement.</li>
            </ul>

            <h3>Keyboard Shortcuts</h3>
            <table class="manual-table">
                <thead>
                    <tr>
                        <th>Shortcut</th>
                        <th>Action</th>
                        <th>Where it applies</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Ctrl/Cmd+S</td>
                        <td>Save</td>
                        <td>Edit and create forms.</td>
                    </tr>
                    <tr>
                        <td>Ctrl/Cmd+Enter</td>
                        <td>Save &amp; close</td>
                        <td>Edit forms that return to the list, Dashboard, or previous area after saving.</td>
                    </tr>
                    <tr>
                        <td>Ctrl/Cmd+Shift+S</td>
                        <td>Create &amp; add another</td>
                        <td>Create forms that support adding multiple records.</td>
                    </tr>
                    <tr>
                        <td>Ctrl/Cmd+D</td>
                        <td>Delete</td>
                        <td>Records with a delete action and permission to delete.</td>
                    </tr>
                    <tr>
                        <td>Escape</td>
                        <td>Cancel</td>
                        <td>Forms and modals with a Cancel action.</td>
                    </tr>
                    <tr>
                        <td>Alt+V</td>
                        <td>View</td>
                        <td>Pages, Homepage, File Library, and other screens with a public or view action.</td>
                    </tr>
                    <tr>
                        <td>Alt++</td>
                        <td>Create new</td>
                        <td>Listing pages with a New or Create action.</td>
                    </tr>
                    <tr>
                        <td>Alt+N</td>
                        <td>Notes</td>
                        <td>Edit screens with an internal Notes field.</td>
                    </tr>
                    <tr>
                        <td>Alt+B</td>
                        <td>Notify users</td>
                        <td>Areas with workflow notification support.</td>
                    </tr>
                    <tr>
                        <td>Alt+A</td>
                        <td>Primary AI action</td>
                        <td>Pages, Files, Slide Decks, and other screens with a top primary AI action.</td>
                    </tr>
                    <tr>
                        <td>Alt+D</td>
                        <td>Download</td>
                        <td>File Library and Slide Deck screens with a download action.</td>
                    </tr>
                    <tr>
                        <td>Alt+C / Alt+E</td>
                        <td>Collapse / Expand</td>
                        <td>Primary page-level or section-level collapse controls.</td>
                    </tr>
                </tbody>
            </table>

            <div class="manual-note">
                <strong>Shortcut hints</strong>
                <p>Hover over admin buttons to see the action name and any keyboard shortcut attached to that button.</p>
            </div>
        </section>

        <section class="manual-section" id="system-overview">
            <h2>System Overview</h2>
            <p>The CMS is organized around a simple flow: create or update content in the admin area, attach media or files when needed, save the record, then review the public page. Most tools use the same patterns: searchable lists, edit screens, live toggles, image/file pickers, and optional notifications.</p>

            <div class="manual-screenshot-grid">
                <figure class="manual-screenshot">
                    <img src="{{ asset('images/manual/admin-dashboard-overview.jpg') }}" alt="Admin dashboard overview with cards for site status and recent activity.">
                    <figcaption>The Dashboard gives admins a quick view of site status, recent work, and shortcuts into common areas.</figcaption>
                </figure>
                <figure class="manual-screenshot">
                    <img src="{{ asset('images/manual/homepage-public-preview.jpg') }}" alt="Public homepage preview with banner, calls to action, and service information.">
                    <figcaption>The public site is built from Banners, Homepage sections, Pages, Navigation, Media Library images, and File Library links.</figcaption>
                </figure>
            </div>

            <table class="manual-table">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Primary job</th>
                        <th>Feeds into</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Homepage and Banners</td>
                        <td>Keep the front page current with featured messages, sections, buttons, and seasonal images.</td>
                        <td>Homepage hero and content below the hero.</td>
                    </tr>
                    <tr>
                        <td>Site Alerts</td>
                        <td>Show short sitewide alert bars above the public header for urgent, seasonal, or important notices.</td>
                        <td>Public top-of-site alert area, optional alert links, and dismissible visitor notices.</td>
                    </tr>
                    <tr>
                        <td>Pages and Navigation</td>
                        <td>Create public pages, redirects, parent-child page groups, and header menu links.</td>
                        <td>Standalone pages, dropdown navigation, page listings, and short links.</td>
                    </tr>
                    <tr>
                        <td>Slide Deck Import</td>
                        <td>Upload announcement PowerPoint decks, review slide images and AI metadata, and create missing announcement pages.</td>
                        <td>Slide images, announcement-page drafts, Media Library images, and exportable slide metadata.</td>
                    </tr>
                    <tr>
                        <td>Media Library and File Library</td>
                        <td>Store reusable images and downloadable documents with searchable titles, paths, tags, and usage notes.</td>
                        <td>Images, banners, page blocks, file cards, and public download links.</td>
                    </tr>
                    <tr>
                        <td>Site Settings</td>
                        <td>Manage sitewide contact details, variables, design options, footer links, default images, analytics IDs, and AI settings.</td>
                        <td>Footer, page defaults, reusable variables, analytics, AI tools, and dashboard notes.</td>
                    </tr>
                    <tr>
                        <td>Notifications, Users, Analytics, and Backups</td>
                        <td>Control who can work in the CMS, who gets notified, how site traffic is reviewed, and how recovery copies are maintained.</td>
                        <td>Admin operations, accountability, reporting, and recovery planning.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="manual-section" id="public-page-structure">
            <h2>Public Page Structure</h2>
            <p>Public pages use a consistent structure so visitors always know where they are, how to move around the site, and what action to take next. The exact content changes from page to page, but the frame is intentionally familiar: sitewide notices first, navigation second, page context third, then the page body and footer.</p>

            <h3>Top of the Page</h3>
            <p>The top of a public page can include a site alert, a utility bar, the main logo/navigation row, and then the page hero. This order keeps urgent information visible before visitors start reading the page.</p>
            <ul>
                <li><strong>Site alert:</strong> Used for temporary notices, schedule changes, reminders, or urgent messages. Alerts appear above navigation so they are hard to miss.</li>
                <li><strong>Utility bar:</strong> Holds smaller links and social icons that are useful but should not compete with the main menu.</li>
                <li><strong>Main navigation:</strong> Keeps the primary visitor paths available on every normal public page.</li>
                <li><strong>Page hero:</strong> Introduces the page with a small label, title, intro text, and optional image. The hero is meant to orient the visitor before they reach detailed content.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/public-page-header-alert.png') }}" alt="Public page showing a gold site alert, utility links, main navigation, and New Here page hero.">
                <figcaption>The public page frame starts with alert and navigation areas, then moves into a large page hero that explains the purpose of the page.</figcaption>
            </figure>

            <h3>Page Hero and Message Area</h3>
            <p>Standard pages can show a header image behind the title and intro. Some pages also use a message card in the hero area for short supporting copy, a key detail, or a callout that should be visible before the main page sections.</p>
            <ul>
                <li>Use the page title for the main visitor-facing label.</li>
                <li>Use the small label for a short category or orientation phrase.</li>
                <li>Use intro text for the one-paragraph summary of the page.</li>
                <li>Use the message field only when the hero needs a compact callout beside the main text.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/public-page-about-header-message.png') }}" alt="About page hero with small label, page title, intro text, background image, and a message card.">
                <figcaption>The About page shows how a page hero can combine orientation text on the left with a short message card on the right.</figcaption>
            </figure>

            <h3>Homepage Hero</h3>
            <p>The homepage uses the same overall public frame, but the hero is managed through Banners and Homepage settings instead of a normal Page record. It is designed to be more action-oriented than a standard page hero.</p>
            <ul>
                <li>The headline should identify the site and welcome the visitor quickly.</li>
                <li>Buttons should point to the highest-value next steps.</li>
                <li>The supporting message card should be short enough to scan.</li>
                <li>Rotating banners should feel related to each other so the page does not change personality between slides.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/public-page-homepage-hero.png') }}" alt="Homepage hero with dark background image, headline, action buttons, and a short message card.">
                <figcaption>The homepage hero is the main front-door section. It uses stronger calls to action than ordinary pages because many visitors start here.</figcaption>
            </figure>

            <h3>Body Sections and Footer</h3>
            <p>Below the hero, content blocks carry the page details. These sections can use text, image/text layouts, cards, process lists, embeds, and related content. The footer repeats contact details and social links so visitors can still find basic information after reading or scrolling.</p>
            <ul>
                <li>Use body sections for the information visitors came to read, not for sitewide details that belong in the header or footer.</li>
                <li>Use background color changes to separate major sections, but keep copy readable.</li>
                <li>Keep contact details in Site Settings when they should be reused across the site.</li>
                <li>Review the bottom of each important page, not only the first screen, because many visitors scroll before deciding what to do next.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/public-page-footer-contact.png') }}" alt="Public page lower content section followed by footer with logo, address, phone, email, and social links.">
                <figcaption>The footer provides a dependable final stop for address, phone, email, and social links after the page content.</figcaption>
            </figure>

            <h3>Special-Purpose Pages</h3>
            <p>Some public pages are built for a single task, such as a connection card, form, campaign, or next-step page. These pages can still use the same header, hero, and footer structure, but their body should stay focused on the task.</p>
            <ul>
                <li>Keep the page title and intro direct so visitors know what the page is for.</li>
                <li>Use one clear primary action or form instead of several competing choices.</li>
                <li>Use a button in the hero only when it helps visitors jump to the next section or related page.</li>
                <li>Turn off the normal page header or navigation only for special cases where the public frame would distract from the task.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/public-page-connection-card.png') }}" alt="Connection Card page with page hero, action button, intro copy, and beginning of a form.">
                <figcaption>A special-purpose page can keep the same visual system while narrowing the body content around one visitor action.</figcaption>
            </figure>

            <div class="manual-note">
                <strong>Why pages are built this way</strong>
                <p>The repeated frame reduces confusion. Alerts handle urgent sitewide information, navigation handles movement, heroes explain context, content blocks carry page-specific information, and the footer keeps contact details available everywhere.</p>
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
                <li>Dashboard notes can show sitewide links or notes from Site Settings. This card can be moved but is not collapsible.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/dashboard-overview.jpg') }}" alt="Admin dashboard with status cards, analytics summaries, recent updates, and shortcut cards.">
                <figcaption>Use the Dashboard as the starting point for recent activity, quick checks, collapsible cards, and shortcuts into the tools you use most.</figcaption>
            </figure>
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
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/homepage-admin-overview.jpg') }}" alt="Homepage admin screen with homepage links, event blocks, and page content blocks.">
                <figcaption>The Homepage editor combines quick homepage fields with reorderable content blocks. Collapse sections while working so long pages stay manageable.</figcaption>
            </figure>
            <h3>What To Watch</h3>
            <ul>
                <li>The homepage also uses <strong>Banners</strong> for the top hero/banner area.</li>
                <li>If Homepage is empty, the site can fall back to starter/default content.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/homepage-public-preview.jpg') }}" alt="Public homepage example with banner and homepage content.">
                <figcaption>After changing Homepage content or Banners, review the public homepage and check that the hero, buttons, service details, and lower sections still work together.</figcaption>
            </figure>
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
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/banner-editor.jpg') }}" alt="Banner editor with selected banner image, title, dates, and button fields.">
                <figcaption>The Banner editor controls the large homepage hero area. Check image crop, title length, button text, and schedule dates before saving.</figcaption>
            </figure>
        </section>

        <section class="manual-section" id="site-alerts">
            <h2>Site Alerts</h2>
            <p>Site Alerts manage short public alert bars shown above the website header. Use them for urgent announcements, weather changes, temporary service updates, registration reminders, or other notices that should be visible across the site.</p>

            <h3>Common Tasks</h3>
            <ol>
                <li>Open <strong>Site Alerts</strong> from the admin sidebar.</li>
                <li>Create a new alert or edit an existing one.</li>
                <li>Choose the alert level, message, optional link, publish window, and whether visitors can dismiss it.</li>
                <li>Turn on <strong>Alert is live</strong> when the alert should appear publicly.</li>
                <li>Save, then open the public website and confirm the alert reads clearly on desktop and mobile.</li>
            </ol>

            <h3>Important Fields</h3>
            <ul>
                <li><strong>Alert notification level:</strong> Controls the visual tone or color of the alert. Use stronger levels only when the notice truly needs extra attention.</li>
                <li><strong>Alert is live:</strong> Turns public display on or off, subject to Publish at and Expires at.</li>
                <li><strong>Alert label:</strong> Optional short prefix before the message, such as News Alert, Weather Update, or Important.</li>
                <li><strong>Visitors can dismiss:</strong> Lets a visitor hide the alert in their own browser until the alert is edited. Turn this off for notices that must remain visible.</li>
                <li><strong>Alert message:</strong> Main alert text. Keep it brief because alerts can stack and must fit on phones.</li>
                <li><strong>Link text and Link destination:</strong> Optional call-to-action. Use a local path like <code>/events</code> or a full <code>https://</code> URL.</li>
                <li><strong>Publish at and Expires at:</strong> Optional scheduling. Use Expires at for temporary weather notices, deadlines, and event reminders.</li>
                <li><strong>Sort order:</strong> Lower numbers appear first when multiple alerts are live.</li>
            </ul>

            <div class="manual-note">
                <strong>Writing alert copy</strong>
                <p>Write alerts as one direct sentence when possible. Put details on a linked page instead of trying to fit a whole announcement into the alert bar.</p>
            </div>

            <h3>What To Watch</h3>
            <ul>
                <li>Old alerts should be unpublished or expired so the site does not look stale.</li>
                <li>If several alerts are live at once, confirm they still fit well on mobile.</li>
                <li>Dismissible alerts are hidden per visitor/browser; editing the alert makes it eligible to appear again.</li>
                <li>Use workflow notifications if another team should review new or changed alert copy.</li>
            </ul>
        </section>

        <section class="manual-section" id="content-blocks">
            <h2>Content Blocks</h2>
            <p>Content blocks are reusable page sections used on the Homepage and Pages. They control the visible body content below the page header or homepage banner. Editors can add, copy, delete, collapse, reorder, and configure blocks without writing code.</p>

            <h3>Shared Block Controls</h3>
            <ul>
                <li><strong>Block name:</strong> Optional admin-only label used to identify a block while editing. It is not shown publicly.</li>
                <li><strong>Small label:</strong> Optional short text above a heading, such as New Here, Featured, or Resources.</li>
                <li><strong>Heading:</strong> Optional public heading for the section.</li>
                <li><strong>Background color:</strong> Uses the managed background color options from Site Settings.</li>
                <li><strong>Content width:</strong> Controls how wide the public section content can be. Use narrower widths for reading and wider widths for card groups, image sections, and listings.</li>
                <li><strong>Copy:</strong> Duplicates a block or nested entry so similar content can be created quickly.</li>
                <li><strong>Collapse:</strong> Keeps long pages manageable while editing. Open one block at a time when reviewing details.</li>
                <li><strong>Publish at and Expire at:</strong> Homepage blocks can be scheduled. Page blocks are usually controlled by the page itself, child-page feature dates, or the Child Cards block settings.</li>
            </ul>

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
                        <td>Use for normal written content. Choose a readable content width and avoid turning every short note into its own block.</td>
                    </tr>
                    <tr>
                        <td>Image + Text</td>
                        <td>A photo with supporting copy and optional button.</td>
                        <td>Choose layout, image, alt text, copy, and optional button. Full-width and screen-width image layouts need strong landscape images.</td>
                    </tr>
                    <tr>
                        <td>Process List</td>
                        <td>Step-by-step instructions such as visit, serve, join, or sign up.</td>
                        <td>Use short step labels and practical step text. Affected background controls whether the chosen color applies to the step items or the full page band.</td>
                    </tr>
                    <tr>
                        <td>Button + Text</td>
                        <td>A clear action such as Contact Us, Give, Register, or Plan a Visit.</td>
                        <td>Requires button text and destination. Layout controls whether the button sits beside, above, or below the copy.</td>
                    </tr>
                    <tr>
                        <td>Cards</td>
                        <td>Groups of links to pages, forms, files, or next steps.</td>
                        <td>Supports display-only cards, same-tab links, new-tab links, flip cards with image backs, and advanced code-backed cards for users with Code Blocks access.</td>
                    </tr>
                    <tr>
                        <td>Strip</td>
                        <td>Service times, address, links, or short facts.</td>
                        <td>Use up to five compact entries. Site variables such as <code>[[address]]</code> and <code>[[service-times]]</code> can be typed into the Strip text field.</td>
                    </tr>
                    <tr>
                        <td>Embedded</td>
                        <td>Trusted third-party embed code such as calendar or forms.</td>
                        <td>Only paste embed code from trusted providers.</td>
                    </tr>
                    <tr>
                        <td>Code</td>
                        <td>Trusted custom HTML, CSS, or JavaScript.</td>
                        <td>Available only to users with Code Blocks access. Use sparingly because code renders directly on the public page.</td>
                    </tr>
                    <tr>
                        <td>Child Cards</td>
                        <td>Automatic listings of a parent page's child pages and attached files.</td>
                        <td>Page-only block. Use it for Resources, Ministries, Forms, Announcements, or other structured page groups.</td>
                    </tr>
                    <tr>
                        <td>YouTube Feed</td>
                        <td>Recent videos from a YouTube channel.</td>
                        <td>Page-only block. Paste the public channel URL; the RSS feed can fill automatically when possible.</td>
                    </tr>
                </tbody>
            </table>

            <h3>Cards Block Details</h3>
            <ul>
                <li><strong>Card label:</strong> Short visible label. Keep it brief enough to scan.</li>
                <li><strong>Layout:</strong> Choose Nothing / display only, Link opens in same tab, Link opens in new tab, or Flip card with image back. Advanced users may also see Flip card with HTML back and JavaScript widget.</li>
                <li><strong>Card text:</strong> Optional supporting copy. Use one or two short sentences.</li>
                <li><strong>Destination:</strong> Required for link card layouts. Use local paths such as <code>/give</code> or full external URLs.</li>
                <li><strong>Flip card image controls:</strong> Image sizing can fill/crop or fit the full image. Horizontal position, vertical position, and zoom help crop the image back intentionally.</li>
                <li><strong>Affected background:</strong> Choose whether the selected background applies to each card/item or to the full page band behind the cards.</li>
            </ul>

            <h3>Child Cards Block</h3>
            <p>The Child Cards block is the main tool for turning a parent page into an automatic listing. It pulls from direct child pages and files attached to the selected parent page.</p>
            <ul>
                <li><strong>Associated parent page:</strong> Choose the page whose direct child pages and files should feed this block. The current page is selected by default when it already has children or attached files.</li>
                <li><strong>Content type:</strong> Choose All Pages, All Files, or Both.</li>
                <li><strong>File categories:</strong> Optional filter for file listings. Leave empty to include all file categories.</li>
                <li><strong>Listing mode:</strong> Featured/active shows child pages within their Feature start and Feature end window. All live shows every live child page.</li>
                <li><strong>Listing is live:</strong> Turns this block on or off without deleting the configuration.</li>
                <li><strong>Enable search:</strong> Adds a public search box that filters page names, file names, tags, descriptions, and related content.</li>
                <li><strong>Layout:</strong> Card grid, Card carousel, Card Carousel Auto, or Label list.</li>
                <li><strong>Items shown:</strong> Number of items shown initially and added with each Load more click.</li>
                <li><strong>Sort by:</strong> Options include announcements/events next up first, news/blog most recent first, sort order then random, publish date, title A-Z/Z-A, and created date.</li>
                <li><strong>Auto-rotate delay:</strong> Used only for Card Carousel Auto.</li>
            </ul>
            <div class="manual-note">
                <strong>Child Cards source rule</strong>
                <p>The block only lists direct child pages and files for the selected parent. To change what appears, edit the child page or file and change its Parent page.</p>
            </div>

            <h3>YouTube Feed Block</h3>
            <ul>
                <li><strong>YouTube channel URL:</strong> Paste the public channel URL. The system tries to fill the RSS feed URL automatically.</li>
                <li><strong>YouTube RSS feed URL:</strong> Optional fallback when the channel URL cannot be resolved.</li>
                <li><strong>Items shown:</strong> Maximum number of recent videos to show.</li>
                <li><strong>YouTube link text:</strong> Text for the link that opens more videos on YouTube.</li>
            </ul>

            <h3>Block Editing Tips</h3>
            <ul>
                <li>Collapse blocks to make long pages easier to manage.</li>
                <li>Copy a block when creating a similar section.</li>
                <li>Use backgrounds intentionally. Too many alternating colors can feel busy.</li>
                <li>Keep button text short: "Register", "Learn More", "Contact Us", or "Plan a Visit".</li>
                <li>Use local paths such as <code>/give</code> for pages on this site, or full <code>https://</code> links for external sites.</li>
                <li>Use Site Variables such as <code>[[address]]</code> and <code>[[service-times]]</code> when sitewide details should stay synced.</li>
                <li>Use Child Cards instead of manually rebuilding the same page or file list in several places.</li>
                <li>After changing page structure, review any Child Cards blocks that depend on that parent page.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/content-block-editor.jpg') }}" alt="Content block editor with background color, affected background, heading, layout, and item fields.">
                <figcaption>Most content blocks share the same editing pattern: choose the block type, fill the main content, set layout/background options, then add rows or items as needed.</figcaption>
            </figure>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/content-block-cards-preview.jpg') }}" alt="Public page showing a cards content block with teal card styling.">
                <figcaption>Content blocks should read as complete public sections after saving. Use preview checks to confirm spacing, background choices, card colors, and button links.</figcaption>
            </figure>
        </section>

        <section class="manual-section" id="slide-decks">
            <h2>Slide Deck Import</h2>
            <p>Slide Deck Import turns a PowerPoint announcement deck into individual slide images and AI-assisted slide metadata. It is designed for weekly announcement workflows where slides may become public announcement pages.</p>

            <h3>Create an Import</h3>
            <ol>
                <li>Open <strong>Slide Deck Import</strong>.</li>
                <li>Create a new deck and enter a <strong>Deck name</strong>.</li>
                <li>Upload a <code>.pptx</code> file in <strong>PowerPoint deck</strong>.</li>
                <li>Save the record. Processing runs in the queue after the record is created.</li>
                <li>Open the deck again to review status, processed slide count, original filename, and the created File Library record.</li>
            </ol>

            <h3>Deck-Level Actions</h3>
            <ul>
                <li><strong>Re-run import:</strong> Reconverts the original PowerPoint and replaces generated slide images and slide analysis records. Use this when the conversion failed or the original deck needs to be processed again.</li>
                <li><strong>Re-run analysis:</strong> Queues AI analysis again for every slide in the deck without reconverting the PowerPoint.</li>
                <li><strong>Download PNG ZIP:</strong> Downloads the generated slide images as a ZIP file.</li>
                <li><strong>Export CSV / Export JSON:</strong> Downloads slide metadata for review, reporting, or reuse outside the CMS.</li>
                <li><strong>Save / Save &amp; close:</strong> Save deck-level fields. Slide edits are handled separately in the Slide Review table.</li>
            </ul>

            <h3>Slide Review Columns</h3>
            <ul>
                <li><strong>Slide:</strong> Thumbnail preview of the generated image.</li>
                <li><strong>#:</strong> Slide number from the deck.</li>
                <li><strong>Suggested name:</strong> AI-suggested or manually edited name.</li>
                <li><strong>Analysis:</strong> Shows Pending, Analyzed, Analysis failed, or OpenAI balance issue. Failure details appear as a short description when available.</li>
                <li><strong>Page?:</strong> Shows whether a matching announcement page appears to exist. Missing matches display <strong>Missing!</strong>.</li>
                <li><strong>Slide type:</strong> Announcement, General, or Unknown.</li>
                <li><strong>Date, Time, Location, Visible text:</strong> Extracted slide details. Visible text can be toggled into view from the table controls.</li>
            </ul>

            <h3>Slide Row Actions</h3>
            <ul>
                <li><strong>Edit slide:</strong> Opens a modal with the slide image at the top and editable analysis fields such as suggested name, slide type, confidence score, visible text, intro text, event title, date, time, location, audience, contact person, and announcement details.</li>
                <li><strong>Re-run slide analysis:</strong> Queues AI analysis for that one slide.</li>
                <li><strong>Delete slide:</strong> Removes the slide record, generated images, thumbnail, and copied Media Library image.</li>
                <li><strong>Edit existing page:</strong> Opens the matching page edit screen when a matching page exists. If the page is missing, the icon stays visible but inactive and greyed out.</li>
                <li><strong>View existing page:</strong> Opens the matching public page in a new tab when a matching page exists. If the page is missing, the icon stays visible but inactive and greyed out.</li>
                <li><strong>Create missing page:</strong> Opens the Page create screen with slide details prefilled when no matching page exists. If a matching page already exists, the icon stays visible but inactive and greyed out.</li>
            </ul>

            <h3>Creating Pages From Slides</h3>
            <ul>
                <li>The create-page action builds a new announcement page using the slide's event title or suggested name, intro text, event dates, and slide image.</li>
                <li>The slide image is copied into the public Media Library so it can be used as the card image and in an Image + Text content block.</li>
                <li>If there is an <strong>Announcements</strong> parent page, the new page is placed under it and uses a path such as <code>/announcements/family-fire-night</code>.</li>
                <li>Pages created from slides should still be reviewed before relying on them publicly. Confirm title, path, dates, image crop, body copy, and publishing dates.</li>
                <li>If the slide mentions a connection card, the created content can include a button pointing visitors to <code>/card</code>.</li>
            </ul>

            <div class="manual-note">
                <strong>AI review caution</strong>
                <p>Slide analysis is a starting point. Always review extracted dates, times, names, and locations before creating or publishing pages from a deck.</p>
            </div>
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
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/media-library-picker.jpg') }}" alt="Media Library image picker showing reusable image thumbnails and search controls.">
                <figcaption>The image picker lets editors choose existing media instead of uploading duplicates. Search by title, path, tag, or other tracked details when available.</figcaption>
            </figure>
            <h3>Toolbar Layout</h3>
            <ul>
                <li><strong>Unsplash</strong> and <strong>Upload</strong> appear together at the top of the Media Library for adding new images.</li>
                <li>The selected-image controls sit separately from the add-image buttons. Use Select All when bulk cleanup is needed, then use the selected delete action carefully.</li>
                <li>The image count appears in its own compact summary area, centered as a count such as <strong>30 of 30 Images</strong>.</li>
                <li>The toolbar and summary area are separated visually so adding images, selecting images, and reading the current count are easier to scan.</li>
            </ul>
            <h3>Image Details</h3>
            <ul>
                <li>Upload image starts with the image field, then shows image title, image path, and tags after a file is selected. Edit image keeps details and optional replacement together.</li>
                <li>Image fields elsewhere in the admin use icon actions: choose an existing image, remove the selected image, add a new image, or edit the selected image. New image uploads show image title, image path, and tags after a file is selected.</li>
                <li><strong>Image title:</strong> Optional friendly title shown in Media Library and image picker results. If left blank while uploading or replacing, the uploaded filename is cleaned up and used as the title.</li>
                <li><strong>Image path:</strong> Optional searchable path-style label for organizing images without changing the actual file location. If left blank while uploading or replacing, the uploaded filename is cleaned into a path and used here.</li>
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
                <li><strong>File title:</strong> Admin/public title for the file.</li>
                <li><strong>File path:</strong> Stable file path under <code>/files/</code>. New uploads can fill this from the uploaded filename, and the refresh icon can regenerate a <code>category-title</code> path such as <code>bulletin-sunday-worship-guide</code>.</li>
                <li><strong>Tags:</strong> Optional tags shared with Media Library image tags. Uploading a new file or editing the title can add matching tags automatically.</li>
                <li><strong>File is live:</strong> Turns the public file path on or off.</li>
                <li><strong>Visibility:</strong> Public links work for anyone. Private published links require a user or admin login.</li>
                <li><strong>Parent page:</strong> Optional. Groups the file under a related page such as Resources, Forms, or Bulletins. Selecting a category can fill this from the category default, and you can still change it. Child Cards can list files attached to the selected parent page.</li>
                <li><strong>Optional file content:</strong> Formatted notes or AI-extracted content for the file record.</li>
                <li><strong>Publish at, Created date, Updated date:</strong> Publish at controls availability; created/updated dates are shown for reference.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/file-library-overview.jpg') }}" alt="File Library list view with file records and admin actions.">
                <figcaption>Use the File Library list to scan documents, confirm live status, find records by title or path, and open files for editing, replacement, extraction, or link copying.</figcaption>
            </figure>
            <h3>File Categories</h3>
            <ul>
                <li>Use the tag-shaped <strong>Categories</strong> icon on the File Library page to manage file categories.</li>
                <li>File Categories are intentionally not shown as a separate main navigation item.</li>
                <li>Each category can have an optional <strong>Default parent page</strong>. Files using that category suggest this page as their parent, but the file can still be changed to another parent.</li>
                <li>Each category can have an optional <strong>Default card image</strong>. Files with their own card image use the file image first; files without one use the category image before the standard file fallback.</li>
                <li>Each category has <strong>Extraction instructions</strong> used by the file extraction AI action for files in that category.</li>
                <li>Keep instructions direct. Example: what to preserve, what to ignore, and what output style is useful for that document type.</li>
            </ul>
            <h3>AI File Extraction</h3>
            <ul>
                <li>Open an existing file record with a saved current file.</li>
                <li>Choose <strong>Extract File Content</strong>, or press <strong>Alt+A</strong> when the edit screen's primary AI action is available.</li>
                <li>The tool saves the current file record and shows the exact prompt that will be sent with the saved file.</li>
                <li>Choose <strong>Continue</strong> to send the saved file and prompt to OpenAI.</li>
                <li>Review and edit the extracted HTML when it returns, then choose <strong>Use extracted content</strong>.</li>
                <li>Accepted extracted content is placed into Optional file content. Review it before using it publicly.</li>
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
            <p>Pages are general website pages such as About, New Here, Give, Contact, Resources, Announcements, Forms, or other standalone content. Pages can also act as parent pages that organize child pages and files.</p>
            <h3>Common Fields</h3>
            <ul>
                <li><strong>Page title:</strong> The page name.</li>
                <li><strong>Page path:</strong> The local site path. Example: a path of <code>new-here</code> creates <code>/new-here</code>. Nested paths such as <code>resources/forms</code> are allowed.</li>
                <li><strong>Small label:</strong> Optional short label shown above the page title.</li>
                <li><strong>Intro text:</strong> Optional intro shown near the top of the page when the page header is visible.</li>
                <li><strong>Message:</strong> Optional rich text shown in the page header for short callouts, contact details, links, lists, or other header notes.</li>
                <li><strong>Header image:</strong> Optional image used in the page header. If empty, the default page header image from Site Settings can be used.</li>
                <li><strong>Card image:</strong> Optional image used when this page appears in cards, Child Cards, or listing areas. If empty, the header image may be used.</li>
                <li><strong>Page content:</strong> Flexible content block layout for the visible body of the page.</li>
                <li><strong>Show navigation:</strong> Turn off only for special landing pages.</li>
                <li><strong>Show page header:</strong> Controls whether the public page shows the title, intro, message, and header image area.</li>
                <li><strong>Parent page:</strong> Optional grouping for child pages, useful for Resources, Forms, or other landing pages.</li>
                <li><strong>Parent to the following pages and files:</strong> Read-only helper showing direct child pages and files attached to this page.</li>
                <li><strong>Publish at and Expires at:</strong> Optional public availability window.</li>
                <li><strong>Feature start and Feature end:</strong> Optional child-page feature window used by Child Cards when the page has a parent.</li>
                <li><strong>SEO title, SEO description, and Hide from search engines:</strong> Optional search metadata shown only to admins and editors with Code Blocks access. Hide from search engines asks search engines not to index the page or follow links on it.</li>
                <li><strong>Page is live:</strong> Must be enabled for public display or for a redirect to work.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/pages-list.jpg') }}" alt="Pages list with status icons, titles, paths, and page actions.">
                <figcaption>The Pages list is where admins scan page status, open existing pages, create new pages, and confirm paths before editing public content.</figcaption>
            </figure>
            <h3>Path Rules</h3>
            <ul>
                <li>Use lowercase words separated by hyphens. Use slashes only when intentionally creating a nested URL path.</li>
                <li>Do not change a public path casually. Old links may stop working.</li>
                <li>Avoid paths already used by system routes such as admin or manual.</li>
                <li>Use the generate icon to regenerate the path from the title when needed.</li>
            </ul>
            <h3>Parent Pages and Child Pages</h3>
            <ul>
                <li>A page becomes a parent when another page chooses it in <strong>Parent page</strong>.</li>
                <li>Set Parent page on the child page, not on the parent page.</li>
                <li>Files can also attach to a parent page from File Library. This is how a Resources or Forms page can collect both child pages and downloadable files.</li>
                <li>The parent page edit screen shows <strong>Parent to the following pages and files</strong>, including quick view/edit links for direct children.</li>
                <li>Parent selection does not automatically change the page path. Choose a matching path intentionally, such as <code>resources/forms</code> for a child page under <code>/resources</code>.</li>
                <li>A page cannot be its own parent or create a parent loop.</li>
                <li>Use <strong>Sort order</strong> on child pages and files when a Child Cards block sorts by sort order.</li>
                <li>Use <strong>Feature start</strong> and <strong>Feature end</strong> on child pages when a Child Cards block should show only currently featured or active children.</li>
                <li>Use a parent page for structured groups such as Announcements, Ministries, Resources, Forms, Events, or Serving opportunities.</li>
            </ul>
            <h3>Using Child Cards With Pages</h3>
            <ol>
                <li>Create or choose the parent page, such as <strong>Announcements</strong> or <strong>Resources</strong>.</li>
                <li>Create child pages and set their <strong>Parent page</strong> to that parent.</li>
                <li>Attach files to the same parent from File Library when files should appear in the same listing.</li>
                <li>Add a <strong>Child Cards</strong> content block to the parent page or another page.</li>
                <li>Choose the <strong>Associated parent page</strong> and decide whether to show pages, files, or both.</li>
                <li>Choose Listing mode, Layout, Items shown, Sort by, and optional file category filters.</li>
            </ol>
            <div class="manual-note">
                <strong>Structure tip</strong>
                <p>Parent-child page structure controls automatic listings. Navigation controls menus. They often match, but they are separate settings.</p>
            </div>
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
            <p>Navigation controls the public main menu, dropdown links, and the utility bar above the main navigation. It is separate from page parent-child structure: pages can be grouped for listings without appearing in the menu, and menu links can point anywhere allowed.</p>
            <ul>
                <li><strong>Link text:</strong> Text visitors see in the main navigation, utility bar, or dropdown.</li>
                <li><strong>Location:</strong> Navigation links appear in the main menu. Utility links appear in the thin bar above the main navigation.</li>
                <li><strong>Destination:</strong> Internal path, file link, media path, or full external URL. Type <code>/</code> to use suggested page, file, or media paths.</li>
                <li><strong>Parent link:</strong> Navigation links can be nested under another top-level navigation link to create a dropdown item. Utility links cannot have parent links.</li>
                <li><strong>Sort order:</strong> Controls order within the main navigation, utility bar, or selected parent dropdown. Lower numbers appear earlier.</li>
                <li><strong>Open in new tab:</strong> Use mostly for external websites and documents. Internal pages usually stay in the same tab.</li>
                <li><strong>Link is live, Publish at, Expires at:</strong> Control whether and when the link appears publicly.</li>
                <li><strong>Page limits:</strong> The list can warn when a navigation link points to a page that is draft, scheduled, expired, or missing.</li>
            </ul>

            <h3>Navigation vs Utility</h3>
            <table class="manual-table">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Best use</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Navigation</td>
                        <td>Main visitor navigation: New Here, Ministries, Events, Resources, Give, Contact.</td>
                        <td>Supports top-level links and dropdown child links through Parent link.</td>
                    </tr>
                    <tr>
                        <td>Utility</td>
                        <td>Small secondary links: Login, Give, Watch, Contact, app links, or urgent shortcuts.</td>
                        <td>Appears above the main navigation. No dropdown parent links.</td>
                    </tr>
                </tbody>
            </table>

            <h3>Site Structure vs Navigation</h3>
            <ul>
                <li><strong>Parent pages</strong> organize content for Child Cards and page/file groupings.</li>
                <li><strong>Navigation links</strong> control what appears in the public menu.</li>
                <li>A child page is not automatically added to navigation. Add a navigation link when it should be menu-accessible.</li>
                <li>A navigation dropdown does not automatically make those pages children of the parent page. Set Parent page separately when automatic listings should use that structure.</li>
                <li>For clean sites, make page structure and navigation match when visitors expect the same grouping, but do not force every page into the main navigation.</li>
            </ul>

            <h3>Navigation Checklist</h3>
            <ul class="manual-checklist">
                <li>Keep top-level navigation short.</li>
                <li>Use dropdowns for related pages instead of crowding the main navigation.</li>
                <li>Test every new or changed navigation link on desktop and mobile.</li>
                <li>Use Open in new tab mostly for external websites or downloadable files.</li>
                <li>When linking to a Page record, make sure both the navigation link and the page itself are live for visitors.</li>
                <li>Use Publish at and Expires at for temporary campaign or seasonal navigation links.</li>
                <li>Review Utility links together with social links from Site Settings so the top bar does not become crowded.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/navigation-header-preview.jpg') }}" alt="Public main navigation with top-level links above the homepage banner.">
                <figcaption>Navigation changes should be checked on the public site. Keep the main navigation short, readable, and easy to scan.</figcaption>
            </figure>
        </section>

        <section class="manual-section" id="settings">
            <h2>Site Settings</h2>
            <p>Site Settings control sitewide information and default public page settings.</p>
            <ul>
                <li><strong>Organizational information:</strong> Site name, phone, email, tagline, and contact details used across the public site and admin tools.</li>
                <li><strong>Dashboard notes:</strong> Optional rich text shown in a movable Dashboard notes widget for admins and users.</li>
                <li><strong>Default page header image:</strong> Used on pages that show a header but do not have their own Header image selected.</li>
                <li><strong>Site Variables:</strong> Reusable sitewide content for text such as address, service times, or ministry contact information.</li>
                <li><strong>Site Design elements:</strong> Managed background colors for content blocks, accent colors, logo, and advanced customization for users with Code Blocks access.</li>
                <li><strong>Social and additional links:</strong> Managed social links and custom-icon links that can appear in the Utility Nav, Footer, or both.</li>
                <li><strong>OpenAI settings:</strong> API key and model settings used by rewrite, page review, slide analysis, and file extraction tools when enabled. File extraction uses its own app environment model and reasoning settings so document reading can be tuned separately.</li>
                <li><strong>Google tracking:</strong> Google Tag Manager and Google Analytics tracking IDs.</li>
            </ul>

            <h3>Social and Custom Links</h3>
            <ul>
                <li>Managed social fields include Facebook, Instagram, YouTube, TikTok, LinkedIn, Google Business Profile, Pinterest, X, and Threads.</li>
                <li>Each social link has a <strong>Show</strong> setting: Show in Utility Nav, Show in Footer, or Show in Both.</li>
                <li><strong>Additional links</strong> support a custom label, destination, placement, and uploaded icon image.</li>
                <li>Additional link labels are used for hover text and screen-reader text, so keep them clear even if the icon is recognizable.</li>
                <li>Use Utility Nav placement for links that belong near the top of the site. Use Footer placement for links visitors may need but that should not compete with the main navigation.</li>
                <li>When adding a custom icon, use a simple high-contrast image that remains readable at small sizes.</li>
            </ul>

            <h3>Site Variables</h3>
            <ul>
                <li>Each variable has a friendly name, a lowercase variable key, and an HTML-capable value.</li>
                <li>Use variables in content as <code>[[variable-name]]</code>. Example: <code>[[address]]</code> or <code>[[service-times]]</code>.</li>
                <li>Use variables for details that appear in multiple places. Updating the variable updates every public field where the token is used.</li>
                <li>Only trusted users should edit variables because HTML is allowed in the value.</li>
            </ul>

            <h3>Design and Structure Defaults</h3>
            <ul>
                <li>The Site logo appears in the public header and footer when set.</li>
                <li>The Default page header image keeps pages visually consistent when individual pages do not have their own image.</li>
                <li>Managed background colors feed the Content Blocks background dropdown. Keep the palette limited and intentional so pages feel consistent.</li>
                <li>Sitewide custom CSS and JavaScript affect many pages at once and should be changed only by trusted users with Code Blocks access.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/site-settings-overview.jpg') }}" alt="Site Settings form with logo and site design fields.">
                <figcaption>Site Settings affects shared values across the website, including contact details, default images, reusable variables, footer links, design colors, analytics, and AI settings.</figcaption>
            </figure>
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
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/analytics-overview.jpg') }}" alt="Analytics dashboard with traffic totals, top pages, referrers, and timeline chart.">
                <figcaption>Analytics helps editors understand which public pages are being used and whether recent content is receiving visitor attention.</figcaption>
            </figure>
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
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/backups-overview.jpg') }}" alt="Backups screen with database, full-site, and archive backup profile cards.">
                <figcaption>The Backups screen groups recovery profiles, latest backup status, run actions, download actions, and health checks in one place.</figcaption>
            </figure>

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
                <li>Create a rule for one content area, such as Pages, Media Library, File Library, Site Settings, Users, Navigation, Site Alerts, Slide Deck Import, Homepage, or Banners.</li>
                <li>Each rule can be enabled or disabled without deleting it.</li>
            </ul>
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/notifications-overview.jpg') }}" alt="Workflow Notifications list with enabled rules, triggers, recipients, and actions.">
                <figcaption>Notification rules show which content area, trigger, recipient group, and delay are active. Disable old rules when they should stop sending.</figcaption>
            </figure>

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
                <li>Notify staff when Site Alerts, Navigation, or Slide Deck Imports change.</li>
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
            <figure class="manual-screenshot">
                <img src="{{ asset('images/manual/users-editor.jpg') }}" alt="User editor with name, email, role, and access controls.">
                <figcaption>The User editor controls login identity and access. Review permissions carefully before giving someone sitewide admin tools.</figcaption>
            </figure>
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
                <li>For featured sections, check Feature start and Feature end.</li>
            </ul>

            <h3>An image is missing.</h3>
            <ul>
                <li>Confirm the image uploaded successfully.</li>
                <li>Check whether it was deleted or replaced in the Media Library.</li>
                <li>Try saving the record again after selecting the image.</li>
            </ul>

            <h3>A file link is missing or unavailable.</h3>
            <ul>
                <li>Confirm the file record is marked File is live.</li>
                <li>Check whether Publish at is in the future or the file has expired.</li>
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
                <p>Website Manual. Printed from {{ url('/manual') }}.</p>
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
