# TwyxtCo Site Structure Recommendations

This document captures the suggested public site structure for the new TwyxtCo site, based on the current Laravel/Filament build and the content areas already being modeled.

## Primary Navigation

Recommended top-level navigation:

1. New Here
2. About
3. Ministries
4. Sermons
5. Connect

Keep Give out of the hard-coded/default navigation. Manage it manually as a normal navigation link when needed.

## Suggested Navigation Shape

### New Here

Purpose: help first-time guests know what to expect and take one clear next step.

Suggested pages or sections:

- Plan a Visit
- What to Expect
- Sunday Service Times
- Kids and Students note
- Location and parking
- Contact or connection CTA

Suggested route:

- `/new-here`

### About

Purpose: explain who TwyxtCo is and build trust.

Suggested pages or sections:

- Our Story
- What We Believe
- Leadership
- Mission and values

Suggested routes:

- `/about`
- `/leadership`

### Ministries

Purpose: help people find a next place to participate.

Current build supports:

- Ministry index page
- Ministry detail pages
- Card image
- Hero image
- Category
- Meeting time
- Location
- Leader name/email
- One Church URL
- Rich description
- Embed code

Suggested route structure:

- `/ministry`
- `/ministry/kids`
- `/ministry/students`
- `/ministry/groups`
- `/ministry/outreach`

### Sermons

Purpose: give people a simple way to watch recent teaching.

Current build supports:

- `/sermons`
- Latest YouTube RSS video grid
- Configurable feed URL
- Configurable YouTube/channel URL
- Configurable hero small label, title, subtitle, text, image, and button label through Site Settings

Current RSS limitation:

- The YouTube RSS feed works well for recent/latest videos.
- It does not support a full archive, true pagination, popular sorting, or oldest-first sorting.
- The current practical limit is 12 recent videos.

Future upgrade path:

- Add local Sermon records in Filament for a full archive.
- Store YouTube URL, title, date, speaker, series, scripture, description, and thumbnail.
- Optionally use RSS/API import later to prefill sermon records.

### Connect

Purpose: combine practical next steps, connection form, and current-week bulletin content.

Suggested route:

- `/connect`

Suggested page sections:

- Hero: "Connect"
- Short intro text
- Connection form
- This week's bulletin
- Prayer request or pastoral care CTA
- Optional upcoming announcements/events preview

Suggested connection form fields:

- Name
- Email
- Phone
- I am new / regular attender / member
- I would like to learn about
- Prayer request
- Message

Suggested bulletin fields:

- Week/date label
- Rich text bulletin content
- Optional PDF upload
- Optional service order
- Optional announcements list
- Optional sermon notes or scripture

Future admin model idea:

- `Bulletin`
- Fields: title, service_date, summary, body, pdf_path, is_published
- Public page can show the latest published bulletin.

## Homepage Structure

Recommended homepage order:

1. Hero banner
2. Sunday service information
3. New Here / Plan a Visit CTA
4. Ministries preview
5. Latest sermons preview
6. Announcements preview
7. Connect / bulletin CTA
8. Location and footer

Current build already supports:

- Homepage banners
- Homepage content blocks
- Announcements bar
- Ministry previews
- Site settings powered contact/service info
- Navigation managed in Filament

Suggested homepage emphasis:

- Keep the first screen focused on welcome, service info, and one clear guest action.
- Use content blocks for flexible ordering, but keep recurring content like announcements and ministries powered by their structured admin sections.

## Public Page Types

### Standard Editable Pages

Use for durable content that does not need its own custom model.

Examples:

- New Here
- About
- Beliefs
- Serve
- Baptism
- Membership

Current build supports:

- Page title
- Optional small hero label
- Hero image
- Rich content blocks
- Published/unpublished state

### Structured Listing Pages

Use for content that benefits from admin records and repeatable cards.

Current or recommended:

- Announcements
- Ministries
- Leadership
- Sermons
- Future bulletins

## Admin Content Map

### Site Settings

Use for global and listing-page settings:

- Church name
- Contact info
- Service times
- Address
- Social links
- Livestream URL
- One Church URL
- Giving URL
- Listing hero settings for Announcements, Ministries, Leadership, Sermons
- Sermons feed/channel settings

### Homepage Banners

Use for the homepage hero:

- Small label/eyebrow
- Title
- Subtitle
- Primary CTA
- Secondary CTA
- Background image
- Publish window

### Homepage Content

Use for ordering homepage sections:

- Text blocks
- Image/text blocks
- CTA blocks
- Info strips
- Link cards
- Process blocks
- Announcements bar

### Pages

Use for normal public pages:

- New Here
- About
- Beliefs
- Serve
- Other evergreen content

### Announcements

Use for current updates and featured items:

- Title
- Summary
- Body
- Image
- CTA
- Publish/expiration windows
- Featured windows

### Ministries

Use for ministry index and detail pages:

- Name
- Slug
- Category
- Summary
- Description
- Images
- Meeting time
- Location
- Leader info
- One Church URL
- Embed code

### Navigation

Use for all public header links:

- Label
- URL
- Location
- Parent navigation
- Sort order
- Publish/expiration windows

## Recommended Build Priority

1. Finish Sermons page polish and navigation entry.
2. Add Connect page.
3. Add connection form handling.
4. Add bulletin content management.
5. Add latest sermon preview block to homepage if desired.
6. Add local sermon archive only if RSS becomes too limiting.

## Suggested URL Map

- `/`
- `/new-here`
- `/about`
- `/leadership`
- `/ministry`
- `/ministry/{slug}`
- `/sermons`
- `/connect`
- `/announcements`
- `/announcements/{slug}`

Optional later:

- `/beliefs`
- `/serve`
- `/baptism`
- `/membership`
- `/bulletins`
- `/bulletins/{date-or-slug}`

## Notes

- Keep navigation editable through Filament instead of hard-coding special links.
- Keep Give as a manually managed navigation item.
- Use structured admin resources for repeatable content.
- Use normal Pages for evergreen content.
- Use Site Settings for listing-page hero copy and global URLs.
