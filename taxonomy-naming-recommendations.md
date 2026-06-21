# Taxonomy Naming Proposal

## Summary

This proposal revises the earlier naming recommendations based on the follow-up discussion.

The preferred direction is:

- Keep internal database columns and JSON keys stable for now.
- Update editor-facing labels, helper text, manual wording, and tests.
- Favor a smaller shared vocabulary so the admin feels predictable.
- Use block context and helper text to clarify meaning instead of inventing a different label for every field.

## Core Vocabulary

| Term | Use For |
| --- | --- |
| `Title` | Name of a page, file, banner, image, or record. |
| `Heading` | Public section heading inside a content block. |
| `Small label` | Short eyebrow/kicker text above a title or heading. |
| `Intro text` | Short opening text near the top of a page. |
| `Body` | Main rich text in a content block. |
| `Layout` | How a block/card/listing is visually arranged or behaves on screen. |
| `Entries` | Repeating child rows inside a block. |
| `Label` | Short visible text inside a repeating entry. |
| `Text` | Supporting/display text inside a repeating entry. |
| `Button text` | Words shown on a button. |
| `Destination` | Where a button, navigation item, card, or link sends a visitor. |
| `Path` | The item’s own local site address, such as a page path or file path. |
| `URL` | Full web address only, when a field truly expects URL language. |
| `Sort order` | Numeric/manual ordering field. |

Avoid showing `Slug` to editors unless absolutely necessary.

## URL, Path, Slug, Destination

Use only two main editor-facing terms:

| Recommended Term | Meaning | Examples |
| --- | --- | --- |
| `Path` | The item’s own address on the site. | Page path, file path, image path. |
| `Destination` | Where a clickable thing sends someone. | Button destination, card destination, navigation destination. |

Use `Destination` broadly for clickable targets, even if the field accepts either `/local-path` or `https://...`.

Keep `Path` for fields that define the actual address or organization path of the record itself.

## Repeater Naming Pattern

Use this pattern for repeaters:

| Repeater | Add Button | Short Field | Text Field |
| --- | --- | --- | --- |
| `Info strip entries` | `Add info strip entry` | `Info strip label` | `Info strip text` |
| `Step entries` | `Add step entry` | `Step label` | `Step text` |
| `Card entries` | `Add card entry` | `Card label` | `Card text` |

This gives editors a consistent pattern while still keeping each block understandable.

`Text` is intentionally broad. It is better than `Summary` when the copy is not always a true summary, and better than `Value` for non-data-entry contexts.

If a text field allows rich text or HTML, clarify that in helper text rather than changing the label.

## Layout Naming Pattern

Use `Layout` for fields that control how the block/card/listing appears or behaves on screen.

Recommended helper text should explain the specific context:

| Area | Label | Helper Direction |
| --- | --- | --- |
| Image + Text | `Layout` | Choose how the image and text are arranged. |
| Button + Text | `Layout` | Choose where the button appears in relation to the text. |
| Info Cards | `Layout` | Choose how this card behaves or displays. |
| Child Page Listing | `Layout` | Choose how child pages and files are displayed. |

This keeps the label consistent while relying on the block context and helper text for precision.

## Pages

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Title | Page title | More specific than generic Title. |
| Small Label | Small label | Keep. |
| Make Page Live | Page is live | Clear boolean wording. |
| Intro | Intro text | Distinguishes from body/message. |
| Message | Header message | The message appears in the page header area. |
| Path | Page path | The page’s own local site path. |
| Sort order | Sort order | Keep consistent everywhere. |
| Collapse All / Expand All | Collapse all / Expand all | Keep; normalize casing. |
| Page Settings | Page settings | Keep. |
| Header Image | Header image | Keep; normalize casing. |
| Card Image (No Image=Header) | Card image | Explain fallback in helper text. |
| Show page header | Show page header | Keep. |
| Show navigation and footer | Show navigation and footer | Keep. |
| Publish At | Publish at | Normalize casing. |
| Expires At | Expires at | Normalize casing. |
| Featured At | Feature starting | Clearer for editors. |
| Featured Expires At | Feature until | Clearer for editors. |
| No Index, No Follow | Hide from search engines | Put robots/noindex detail in helper text. |
| SEO Title | SEO title | Normalize casing. |
| SEO Description | SEO description | Normalize casing. |
| Page Content Blocks | Page content | Shorter and clearer. |

## Page Block: Text

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Keep. |
| Heading | Heading | Keep. |
| Body | Body | Keep. |
| Content width | Content width | Keep. |
| Background | Background color | More precise. |

## Page Block: Info Strip

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Spacing | Spacing | Keep. |
| Content width | Content width | Keep. |
| Items | Info strip entries | Repeater section label. |
| Label | Info strip label | Short label, such as Sunday or Address. |
| Value | Info strip text | Text shown with the label. Site Variables can be used here. |
| Add Item | Add info strip entry | Match repeater language. |
| Background Color | Do not add for now | Only add if Info Strip should behave like full content blocks. |

## Page Block: Image + Text

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Image | Image | Keep. |
| Image Description | Image description / alt text | Helper should explain this is for accessibility, not hover text. |
| Small Label | Small label | Keep. |
| Heading | Heading | Keep. |
| Body | Body | Keep. |
| Button Label | Button text | Use Button text everywhere. |
| Button URL | Button destination | Click target. |
| Background | Background color | More precise. |
| Content width | Content width | Keep. |
| Image Position | Layout | Choose how image and text are arranged. |

## Page Block: Button + Text

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Keep. |
| Heading | Heading | Keep. |
| Body | Body | Keep. |
| Button Label | Button text | Use Button text everywhere. |
| Button URL | Button destination | Click target. |
| Background | Background color | More precise. |
| Layout | Layout | Keep. Helper should explain button placement. |
| Content width | Content width | Keep. |

## Page Block: Process List

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Keep. |
| Heading | Heading | Keep. |
| Background | Background color | More precise. |
| Content width | Content width | Keep. |
| Steps | Step entries | Repeater section label. |
| Title | Step label | Short visible label for the step. |
| Summary | Step text | Supporting text shown with the step. |
| Add Step | Add step entry | Match repeater language. |

## Page Block: Info Cards

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Keep. |
| Heading | Heading | Keep. |
| Background | Background color | More precise. |
| Content width | Content width | Keep. |
| Cards | Card entries | Repeater section label. |
| Title | Card label | Short visible label for the card. |
| Card Type | Layout | Choose how this card behaves/displays. |
| Summary | Card text | Supporting text shown on the card. |
| URL / href | Destination | Avoid `href` in editor-facing labels. |
| Add Card | Add card entry | Match repeater language. |

### Info Card Layout Options

Recommended option names:

| Current/Concept | Proposed Option Name |
| --- | --- |
| Link same tab | Link |
| Link new tab | Link opens in new tab |
| Flip Image | Flip card with image back |
| Flip HTML | Flip card with HTML back |
| JavaScript | JavaScript widget |

Recommended conditional field names:

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Flip Image | Card back image | Shown when layout is flip card with image back. |
| Flip HTML | Card back HTML | Shown when layout is flip card with HTML back. |
| Javascript | Widget JavaScript | Use this if it is a widget/mount script. |
| Javascript | Card back JavaScript | Only use this if it truly belongs to the back of a flip card. |

## Page Block: Embedded Content

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Optional if added for consistency. |
| Heading | Heading | Keep. |
| Background | Background color | More precise. |
| Content width | Content width | Keep. |
| Embed Code | Embed code | Prefer this over Embedded Code. |

## Page Block: Code

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Optional only if this block should have public heading structure. |
| Title | Block name | Use if admin-only. If public-facing, use Heading instead. |
| Background Color | Background color | Keep; normalize casing. |
| Content width | Content width | Keep. |
| Code | Custom code | Clearer and consistent with the trust level. |

## Page Block: YouTube Feed Listing

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Optional if added. |
| Title/Header | Heading | Use Heading if added. |
| Background | Background color | Add only if this block should match other block styling controls. |
| YouTube Channel URL | YouTube channel URL | Keep; normalize casing. |
| YouTube RSS feed URL | YouTube RSS feed URL | Keep. |
| View on YouTube text | YouTube link text | More specific. |
| Items shown | Items shown | Keep. |
| Content width | Content width | Keep. |
| Format option | Layout | If multiple visual layouts are added. |

## Page Block: Child Page Listing

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Small Label | Small label | Optional if added. |
| Associated Parent | Parent page | Simpler and clearer. |
| Display Format | Layout | Grid/list/carousel are layouts. |
| Auto-rotate delay seconds | Auto-rotate delay | Put seconds in helper text or suffix. |
| Heading | Heading | Keep. |
| Sort Cards By | Sort by | Applies to all listing layouts, not only cards. |
| Enable Search | Enable search | Keep; normalize casing. |
| Show Child Cards | Listing is live | Avoid conflict with Content type below. |
| Mode | Listing mode | Needs helper text explaining featured/all/newest behavior. |
| Show | Content type | This is really pages/files/both. |
| File Categories | File categories | Keep; normalize casing. |
| Items Shown | Items shown | Keep; normalize casing. |
| Content width | Content width | Keep. |
| Background | Background color | More precise. |

## File Library

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Category | Category | Keep. |
| Make File Live | File is live | Clear boolean wording. |
| Current File | Current file | Keep; normalize casing. |
| Title | File title | More specific. |
| Public or Private | Visibility | Cleaner for public/private. |
| Tags | Tags | Keep. Helper should say tags, not labels. |
| Path | File path | The file’s own public/local path. |
| Order | Sort order | Use one term everywhere. |
| Parent Page - optional | Parent page | Put optional in helper text. |
| Replace File | Replace file | Keep; normalize casing. |
| Publish Date | Publish at | Match Pages. |
| Expiration Date | Expires at | Match Pages. |
| Created Date | Created date | Keep. |
| Updated Date | Updated date | Keep. |
| Optional Page / File Content | Optional file content | Clearer than current label. |
| Card Image | Card image | Keep; normalize casing. |
| Auto-rotate delay seconds | Auto-rotate delay | Only if relevant to file display. |
| Version History | Version history | Keep; normalize casing. |

## Media Library

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Title | Image title | More specific. |
| Tags | Tags | Keep. |
| Optional Slug/Path | Image path | Avoid Slug for editors. |
| Current Image | Current image | Keep; normalize casing. |
| Replace Image | Replace image | Keep; normalize casing. |
| Make Image Live | Image is live | Only add if unpublished images are actually useful. Otherwise skip. |
| Created Date | Created date | Good read-only metadata. |
| Updated Date | Updated date | Good read-only metadata. |
| Optional Image Content | Image notes | Better than content/body unless rendered publicly. |

## Navigation Links

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Parent Link | Parent link | Keep. |
| Make Link Live | Link is live | Clear boolean wording. |
| Link Text | Link text | Keep; already good. |
| Open in new tab | Open in new tab | Keep. |
| Url | Destination | Link target. |
| Sort order | Sort order | Keep. |
| Publish at | Publish at | Keep. |
| Expires at | Expires at | Keep. |

## Homepage Banners

| Current Name | Proposed Name | Notes |
| --- | --- | --- |
| Title | Banner title | More specific. |
| Make Banner Live | Banner is live | Clear boolean wording. |
| Small Label | Small label | Keep. |
| Subtitle | Supporting text | Clearer than Subtitle/Message for banner copy. |
| Starts At | Publish at | Match Pages. |
| Ends At | Expires at | Match Pages. |
| Primary button label | Primary button text | Use Button text everywhere. |
| Primary button URL | Primary button destination | Click target. |
| Secondary button label | Secondary button text | Use Button text everywhere. |
| Secondary button url | Secondary button destination | Click target. |
| Banner Image | Banner image | Keep. |

## Site Settings

The Site Settings table in the Word document appears to duplicate Homepage Banner rows. I would not apply those rows to Site Settings directly.

Recommended Site Settings conventions:

| Current/Area | Proposed Name | Notes |
| --- | --- | --- |
| Church Name / Site Name | Site name | Current generic direction is correct. |
| Phone | Phone | Keep. |
| Email address | Email address | Keep. |
| Tagline | Tagline | Keep. |
| Site Variables | Site variables | Keep. |
| Site Variable Name | Name | Pretty editor-facing name. |
| Site Variable Variable | Variable | Lowercase dash-separated key. |
| Site Variable Value | Value | HTML-capable trusted value. |
| Social additional link Label | Link text | More specific than Label. |
| Social additional link Link | Destination | Click target. |
| Site Design elements | Site design | Consider shortening if desired. |
| Default page header image | Default page header image | Keep. |
| Background colors | Background colors | Keep. |
| Custom CSS | Custom CSS | Keep; admin/code-only. |
| Dashboard Notes | Dashboard notes | Keep. |
| AI Content Prompt | AI content prompt | Normalize casing. |
| Google Tracking | Google tracking | Normalize casing. |

## Implementation Guidance

1. Change labels, helper text, tooltips, and manual wording first.
2. Keep internal DB columns and JSON keys stable.
3. Use `Layout` for visual/behavior layout choices.
4. Use `[thing] entries`, `[thing] label`, `[thing] text` for repeaters.
5. Use `Destination` for clickable targets.
6. Use `Path` for an item’s own local address.
7. Use `Button text`, not button label.
8. Use `Sort order`, not Order.
9. Avoid `Slug` in editor-facing UI.
10. Put technical detail in helper text instead of long labels.
