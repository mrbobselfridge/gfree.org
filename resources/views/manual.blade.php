@php
    $churchName = $settings?->church_name ?? 'TwyxtCo Church';
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Manual | {{ $churchName }}</title>
    <meta name="description" content="Printable website and CMS manual for {{ $churchName }} admins and content editors.">
    <style>
        :root {
            --ink: #171717;
            --muted: #555f5c;
            --line: #d8ddd9;
            --paper: #ffffff;
            --soft: #f6f5ef;
            --accent: #f4c542;
            --teal: #22b9ad;
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
            background: var(--soft);
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
        }

        .manual-cover {
            padding: 44px;
        }

        .manual-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 32px;
        }

        .manual-actions a,
        .manual-actions button {
            display: inline-flex;
            align-items: center;
            min-height: 42px;
            padding: 0 16px;
            color: var(--ink);
            font: inherit;
            font-weight: 800;
            text-decoration: none;
            background: var(--accent);
            border: 1px solid #c59c14;
            cursor: pointer;
        }

        .manual-kicker {
            margin: 0 0 12px;
            color: #63500e;
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
        }

        h2 {
            padding-bottom: 12px;
            font-size: 2rem;
            border-bottom: 2px solid var(--line);
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
            background: #f8f1d5;
            border: 1px solid #e1ca74;
        }

        .manual-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 28px;
        }

        .manual-note,
        .manual-card {
            padding: 18px;
            background: #fbfbf8;
            border: 1px solid var(--line);
        }

        .manual-note strong,
        .manual-card strong {
            display: block;
            margin-bottom: 4px;
        }

        .manual-section {
            margin-top: 18px;
            padding: 34px 44px 40px;
        }

        .manual-toc {
            columns: 2;
            column-gap: 32px;
            margin-top: 18px;
            padding-left: 1.1rem;
        }

        .manual-toc li {
            break-inside: avoid;
            margin: 0 0 8px;
        }

        .manual-checklist {
            padding: 18px 18px 18px 34px;
            background: #f8fbfa;
            border: 1px solid #cbdeda;
        }

        .manual-table {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .manual-table th,
        .manual-table td {
            padding: 10px;
            text-align: left;
            vertical-align: top;
            border: 1px solid var(--line);
        }

        .manual-table th {
            background: #f5edc7;
        }

        .manual-print-footer {
            display: none;
        }

        @media (max-width: 760px) {
            .manual-cover,
            .manual-section {
                padding: 24px;
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
                page-break-inside: avoid;
            }

            .manual-section {
                page-break-before: always;
            }

            .manual-note,
            .manual-card,
            .manual-checklist {
                background: #fff;
                border-color: #999;
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
            <div class="manual-actions" aria-label="Manual actions">
                <button type="button" onclick="window.print()">Print or Save PDF</button>
                <a href="{{ url('/admin') }}">Open Admin</a>
                <a href="{{ url('/') }}">Open Website</a>
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

        <section class="manual-section" id="contents">
            <h2>Contents</h2>
            <ol class="manual-toc">
                <li><a href="#roles">Roles and Permissions</a></li>
                <li><a href="#daily-workflow">Daily Workflow</a></li>
                <li><a href="#dashboard">Dashboard</a></li>
                <li><a href="#homepage">Homepage Content</a></li>
                <li><a href="#banners">Homepage Banners</a></li>
                <li><a href="#content-blocks">Content Blocks</a></li>
                <li><a href="#announcements">Announcements</a></li>
                <li><a href="#bulletins">Bulletins</a></li>
                <li><a href="#media-library">Media Library</a></li>
                <li><a href="#ministries">Ministries</a></li>
                <li><a href="#pages">Pages</a></li>
                <li><a href="#leaders">Leaders</a></li>
                <li><a href="#sermons">Sermons</a></li>
                <li><a href="#navigation">Navigation Links</a></li>
                <li><a href="#settings">Site Settings</a></li>
                <li><a href="#analytics">Analytics</a></li>
                <li><a href="#workflow-notifications">Workflow Notifications and Email</a></li>
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
                    <p>Usually manage selected pages, ministries, announcements, bulletins, leaders, or media.</p>
                </div>
            </div>
            <ul>
                <li>Use <strong>Users</strong> to grant or limit access.</li>
                <li>Some accounts may be limited to specific pages, ministries, or leader profiles.</li>
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
            </ul>
        </section>

        <section class="manual-section" id="homepage">
            <h2>Homepage Content</h2>
            <p>Homepage Content controls the flexible sections below the top banner. These sections can be reordered and built from content blocks.</p>
            <h3>Common Tasks</h3>
            <ol>
                <li>Open <strong>Homepage Content</strong>.</li>
                <li>Add, copy, delete, collapse, or reorder content blocks.</li>
                <li>Update text, links, images, backgrounds, and block-specific settings.</li>
                <li>Save and review the homepage.</li>
            </ol>
            <h3>What To Watch</h3>
            <ul>
                <li>The homepage also uses <strong>Homepage Banners</strong> for the top hero/banner area.</li>
                <li>The Announcements block can be moved or hidden from Homepage Content.</li>
                <li>If Homepage Content is empty, the site can fall back to starter/default content.</li>
            </ul>
        </section>

        <section class="manual-section" id="banners">
            <h2>Homepage Banners</h2>
            <p>Homepage Banners manage the large rotating hero images and messages at the top of the homepage.</p>
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
            <p>Content blocks are reusable sections used on the homepage, pages, ministries, leaders, and announcements. The available block types may vary by area, but the core behavior is the same.</p>

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
                        <td>Groups of links to pages, ministries, forms, or next steps.</td>
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
                    <tr>
                        <td>Announcements</td>
                        <td>Featured church updates shown in a content section.</td>
                        <td>Shows up to 10 active featured announcements, ordered by urgency and publish timing.</td>
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

        <section class="manual-section" id="announcements">
            <h2>Announcements</h2>
            <p>Announcements are current updates, events, reminders, and opportunities. They can appear on the Announcements landing page, detail pages, and Announcements content blocks.</p>
            <h3>Key Fields</h3>
            <ul>
                <li><strong>Title:</strong> Public headline.</li>
                <li><strong>Summary:</strong> Short preview text shown on listings and cards.</li>
                <li><strong>Image:</strong> Optional listing/detail image.</li>
                <li><strong>Published:</strong> Must be enabled for the public site.</li>
                <li><strong>Publish at:</strong> Optional date/time when the announcement becomes visible.</li>
                <li><strong>Expires at:</strong> Optional date/time when the announcement stops showing publicly.</li>
                <li><strong>Featured:</strong> Marks the announcement for featured feeds and visual labels.</li>
                <li><strong>Featured at:</strong> Optional date/time when it begins appearing in featured sections.</li>
                <li><strong>Feature expires at:</strong> Optional date/time when it leaves featured sections.</li>
                <li><strong>CTA label and URL:</strong> Optional action link on detail pages.</li>
            </ul>
            <h3>Public Ordering</h3>
            <p>Announcement listings and content-block feeds are ordered by:</p>
            <ol>
                <li>Feature expires at, soonest first.</li>
                <li>Featured at, newest first.</li>
                <li>Expires at, soonest first.</li>
                <li>Publish at, newest first.</li>
                <li>Featured announcements before non-featured announcements.</li>
                <li>Title A-Z.</li>
            </ol>
            <div class="manual-note">
                <strong>Planning tip</strong>
                <p>If something should be highlighted for a limited period, fill in both Featured at and Feature expires at. If it should disappear completely after an event, also fill in Expires at.</p>
            </div>
        </section>

        <section class="manual-section" id="bulletins">
            <h2>Bulletins</h2>
            <p>Bulletins manage weekly bulletin PDFs and their public bulletin pages.</p>
            <ol>
                <li>Create or edit a bulletin.</li>
                <li>Upload the PDF.</li>
                <li>Use the PDF extraction action when available to create readable web content from the bulletin.</li>
                <li>Review extracted content for dates, event names, contact information, and links.</li>
                <li>Save and open the public bulletin page.</li>
            </ol>
            <ul>
                <li>Keep bulletin dates accurate.</li>
                <li>Do not rely only on a PDF if the content should be searchable or readable on phones.</li>
                <li>Check that important links, such as connection cards or forms, work after extraction.</li>
            </ul>
        </section>

        <section class="manual-section" id="media-library">
            <h2>Media Library</h2>
            <p>The Media Library stores reusable images used throughout the site.</p>
            <h3>Common Tasks</h3>
            <ul>
                <li>Upload images for pages, announcements, ministries, leaders, and banners.</li>
                <li>Replace an image when the same file is used in multiple places.</li>
                <li>Review usage before deleting media.</li>
                <li>Use descriptive filenames when possible.</li>
            </ul>
            <h3>Image Guidelines</h3>
            <ul>
                <li>Use clear, bright photos.</li>
                <li>Avoid images with heavy text baked into the image.</li>
                <li>Use landscape photos for banners and wide image sections.</li>
                <li>Use portrait or square photos for leaders when the layout expects a profile image.</li>
            </ul>
        </section>

        <section class="manual-section" id="ministries">
            <h2>Ministries</h2>
            <p>Ministries control the ministry listing page and individual ministry detail pages.</p>
            <ul>
                <li><strong>Title and slug:</strong> Public name and URL.</li>
                <li><strong>Summary:</strong> Short listing text.</li>
                <li><strong>Description or content blocks:</strong> Main detail page content.</li>
                <li><strong>Image:</strong> Listing/detail image.</li>
                <li><strong>One Church URL:</strong> Optional ministry-specific form or external link.</li>
                <li><strong>Sort order:</strong> Controls display order in ministry lists.</li>
                <li><strong>Published:</strong> Must be enabled for public display.</li>
            </ul>
            <div class="manual-note">
                <strong>Content tip</strong>
                <p>Write ministry pages for visitors first: who it is for, when it happens, where to go, and how to take a next step.</p>
            </div>
        </section>

        <section class="manual-section" id="pages">
            <h2>Pages</h2>
            <p>Pages are general website pages such as About, New Here, Give, Contact, or other standalone content.</p>
            <h3>Common Fields</h3>
            <ul>
                <li><strong>Title:</strong> The page name.</li>
                <li><strong>Slug:</strong> The URL path. Example: a slug of <code>new-here</code> creates <code>/new-here</code>.</li>
                <li><strong>Body:</strong> Legacy or fallback content.</li>
                <li><strong>Content blocks:</strong> Preferred flexible page layout.</li>
                <li><strong>Show navigation and footer:</strong> Turn off only for special landing pages.</li>
                <li><strong>SEO title and description:</strong> Optional browser/search metadata.</li>
                <li><strong>Published:</strong> Must be enabled for public display.</li>
            </ul>
            <h3>Slug Rules</h3>
            <ul>
                <li>Use lowercase words separated by hyphens.</li>
                <li>Do not change a public slug casually. Old links may stop working.</li>
                <li>Avoid slugs already used by system routes such as announcements, bulletins, leadership, ministry, sermons, admin, or manual.</li>
            </ul>
        </section>

        <section class="manual-section" id="leaders">
            <h2>Leaders</h2>
            <p>Leaders manage staff or leadership profiles shown on the leadership page and individual profile pages.</p>
            <ul>
                <li>Use consistent names, roles, and image style.</li>
                <li>Keep bios short and warm.</li>
                <li>Use content blocks for richer profile pages when needed.</li>
                <li>Publish only profiles that should be visible publicly.</li>
            </ul>
            <div class="manual-note">
                <strong>Photo tip</strong>
                <p>Use clear headshots with similar framing so the leadership page feels consistent.</p>
            </div>
        </section>

        <section class="manual-section" id="sermons">
            <h2>Sermons</h2>
            <p>Sermons manages the public sermons landing page and YouTube feed settings.</p>
            <ul>
                <li>Update the sermons title, subtitle, intro text, image, and YouTube link label.</li>
                <li>Paste a YouTube channel URL when the system can derive the RSS feed automatically.</li>
                <li>Use a custom YouTube RSS feed URL only when needed.</li>
                <li>Open the public sermons page after saving to confirm videos load.</li>
            </ul>
        </section>

        <section class="manual-section" id="navigation">
            <h2>Navigation Links</h2>
            <p>Navigation Links control the public header navigation and dropdown structure.</p>
            <ul>
                <li><strong>Label:</strong> Text visitors see in the header.</li>
                <li><strong>URL:</strong> Internal path or full external URL.</li>
                <li><strong>Parent:</strong> Use to place a link under another link as a dropdown item.</li>
                <li><strong>Sort order:</strong> Controls order in the header or dropdown.</li>
                <li><strong>Publish and expire dates:</strong> Optional scheduling for seasonal links.</li>
            </ul>
            <h3>Navigation Checklist</h3>
            <ul class="manual-checklist">
                <li>Keep top-level navigation short.</li>
                <li>Use dropdowns for related pages instead of crowding the header.</li>
                <li>Test every new or changed navigation link.</li>
                <li>Use external links carefully and only when visitors should leave the site.</li>
            </ul>
        </section>

        <section class="manual-section" id="settings">
            <h2>Site Settings</h2>
            <p>Site Settings control church-wide information and default public page settings.</p>
            <ul>
                <li>Church name.</li>
                <li>Address, email, phone, office hours, and Sunday service times.</li>
                <li>Social media links.</li>
                <li>One Church URL fallback.</li>
                <li>Default listing page titles, subtitles, images, and SEO information.</li>
                <li>AI rewrite and bulletin extraction settings when enabled.</li>
                <li>Sermons page defaults and YouTube feed settings.</li>
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

        <section class="manual-section" id="workflow-notifications">
            <h2>Workflow Notifications and Email</h2>
            <p>Workflow Notifications let the CMS email people when important content is created, updated, deleted, or manually sent for review. Use them for handoffs such as asking another team to review a bulletin, update matching announcements, check media, or approve public content.</p>

            <h3>Where To Find Them</h3>
            <ul>
                <li>Open <strong>Workflow Notification Rules</strong> in the admin sidebar.</li>
                <li>Create a rule for one content area, such as Bulletins, Announcements, Pages, Media Library, Site Settings, Users, Leaders, Ministries, Navigation Links, Homepage Content, or Homepage Banners.</li>
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
                <li>Open a matching content item, such as a bulletin or announcement.</li>
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
                <li>Notify communications when a bulletin is ready to turn into announcements.</li>
                <li>Notify ministry leaders when their page, leader profile, or announcement changes.</li>
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
                <li>Use area permissions for broad access, such as all announcements or all pages.</li>
                <li>Use record-level permissions for specific ministries, pages, or leaders when appropriate.</li>
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

            <h3>A link opens the wrong place.</h3>
            <ul>
                <li>Internal site paths should usually begin with <code>/</code>, such as <code>/announcements</code>.</li>
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
</body>
</html>
