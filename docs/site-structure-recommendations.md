# Site Structure and Content Architecture

This document replaces the early site-structure proposal. The original version described removed or superseded features such as dedicated ministry and announcement models. The current application is a flexible page-and-file CMS with homepage tools, public page blocks, related content, file-library publishing, site alerts, navigation management, analytics, backups, and workflow notifications.

## Current Public Routes

The public route surface is intentionally small:

- `/`: homepage rendered by `HomeController`.
- `/manual`: admin/user manual page.
- `/files/{fileName}`: stable file-library document links.
- `/{slug}`: published CMS pages and redirects.

Removed from the public route surface:

- `/concepts`
- `/concept-screens`
- `/concepts/{concept}`

Those concept routes were design/demo tools and should stay out of production routing. If design previews are needed later, put them behind admin auth, a local-only route group, or static internal documentation.

## Current Content Model

### Homepage

The homepage is configured through:

- Homepage banners for the hero/rotating hero.
- Homepage content blocks for flexible sections.
- Site settings for global identity, design variables, social links, contact details, tracking IDs, and custom code.
- Navigation links for public header and utility navigation.
- Site alerts for temporary public notices.

The homepage should stay focused on:

- Clear first-screen identity.
- One or two primary visitor actions.
- Current information that can be maintained without code changes.
- A short path into the most important page groups or file-library resources.

### Pages

Pages are the primary public content type. Use pages for:

- Evergreen public content.
- Landing pages.
- Parent pages that group child pages and files.
- Temporary campaign pages.
- Redirects from old or short paths.

Page records support:

- Page title and page path.
- Small label.
- Intro text and header message.
- Header image and card image.
- Content blocks.
- Parent page relationship.
- Publish and expiration windows.
- Featured windows for child-page listings.
- SEO title/description.
- Noindex/nofollow.
- Show/hide site chrome and page header.
- Redirect destination and redirect status.

### Content Blocks

Current page and homepage blocks are flexible enough to replace the older dedicated announcement/ministry/content-section ideas:

- Text
- Image + text
- Button + text
- Process list
- Link cards
- Info strip
- Related content
- YouTube feed
- Embed
- Code block, limited to trusted users

Use a dedicated model only when records need their own lifecycle, permissions, reporting, or workflows. Otherwise, start with pages, child pages, file-library records, and related-content blocks.

### File Library

The file library is the right place for durable downloadable or viewable resources:

- Forms
- Policies
- PDFs
- Bulletins or weekly documents
- Public packets
- Internal/private documents for logged-in users

Files support:

- Stable public path under `/files/{fileName}`.
- Category.
- Parent page.
- Sort order.
- Card image.
- Public/private visibility.
- Publish and expiration windows.
- Current version history.
- Optional extracted or AI-assisted content.
- Tags.

Private file-library documents currently mean "requires any logged-in user account." They do not currently require file-library admin permission.

### Related Content

Use related-content blocks on parent pages to display attached child pages and/or file-library documents. This is the current replacement for many older listing-page ideas.

Recommended parent-page pattern:

1. Create a parent page such as `/resources`, `/forms`, `/events`, or `/news`.
2. Attach child pages and/or file documents to that parent.
3. Add a related-content block to the parent page.
4. Choose the display mode, sort preset, layout, item limit, and optional search.

This keeps most structured public sections editable without custom route/controller/model work.

## Recommended Navigation Shape

Navigation should be managed in Filament, not hard-coded. Recommended top-level structure should follow current content, not the older proposal:

1. Home
2. New Here or Visit
3. About
4. Resources
5. Connect or Contact

Optional top-level items, depending on actual content:

- Events
- Sermons or Messages, if represented by pages, files, or embeds.
- Give, if needed, as a manually managed navigation or utility link.

Keep navigation shallow. Prefer parent pages with related-content blocks over large dropdown structures.

## Suggested Page Groups

These are content architecture suggestions, not required code work.

### Visit / New Here

Purpose: first-time visitor clarity.

Likely content:

- What to expect.
- Service times.
- Location and parking.
- Kids/students note if relevant.
- Contact or next-step CTA.

Implementation:

- Normal page at `/new-here` or `/visit`.
- Text, info strip, image + text, and button + text blocks.
- Site variables for repeated service-time/address details.

### About

Purpose: identity, trust, and durable background.

Likely content:

- Mission and values.
- Story.
- Beliefs.
- Leadership overview or linked child pages.

Implementation:

- Parent page at `/about`.
- Child pages for deeper evergreen content if needed.
- Related-content block if there are child pages.

### Resources

Purpose: a maintainable public library.

Likely content:

- Forms.
- Policies.
- PDFs.
- Guides.
- Bulletins if they remain document-based.

Implementation:

- Parent page at `/resources`.
- File-library documents attached to the parent page.
- Categories such as Form, Policy, Bulletin, Guide, or Packet.
- Related-content block with search enabled.

### Connect / Contact

Purpose: help visitors take action or ask for help.

Likely content:

- Contact details.
- Staff or office contact info.
- Next-step links.
- External form embed if form handling stays outside this app.

Implementation:

- Normal page at `/connect` or `/contact`.
- Info strip, text, button + text, embed, and link-card blocks.
- If native form handling is needed later, build it deliberately as a new feature with storage, spam protection, notification routing, and admin review behavior.

### Events / Current Updates

Purpose: current or time-bound content.

Current best implementation:

- Use pages with publish/expiration/featured windows for event-style items.
- Group them under a parent page.
- Use related-content sorting for "next up" or recent ordering.

Only add a dedicated Event model if the site needs date-specific filtering, recurring event logic, calendars, registration, or event-specific admin workflows.

## Site Settings Responsibilities

Site Settings should own global values:

- Church/site name.
- Logo and default page header image.
- Tagline.
- Contact details.
- Design palette variables and custom CSS.
- Header/body custom scripts.
- Site variables.
- Social links and placements.
- Google Tag Manager and Google Analytics IDs.
- AI model/key settings.

Use site variables for content repeated across pages:

- Address.
- Service times.
- Contact email or phone.
- Office hours.
- Standard call-to-action text.

## Admin Documentation Cleanup

The in-app manual is still useful, but its `updatedAt` value in `routes/web.php` should be kept current when manual content changes. Consider moving that date into a config value or deriving it from source control in the future.

The old design concept files still exist under `public/concept-screens` and `resources/views/concepts`. With routes removed, they are no longer public entry points through Laravel, but the static files under `public` are still directly addressable if someone knows the path. Remove or move those assets later if they should not be web-accessible.

## Current Backlog Recommendations

High-value cleanup:

- Replace placeholder defaults in `config/twyxtco.php` with project-appropriate fallback copy.
- Refresh `/manual` after major admin UX changes.
- Decide whether to remove public concept screenshot assets from `public/concept-screens`.
- Decide whether OpenAI keys should be encrypted in the database.
- Keep the committed MaxMind database for now, but document how it is updated.

Potential feature work:

- Native contact/connection form handling.
- Better indexed search across body/content-block text if global search becomes important.
- Dedicated event model only if page-based event listings become limiting.
- Dedicated bulletin model only if file-library bulletins become limiting.

No longer recommended as immediate work:

- Rebuilding the removed ministry/announcement/bulletin feature set as separate models simply because the early proposal mentioned them. The current page, file, and related-content system should be the default until a clear workflow gap appears.
