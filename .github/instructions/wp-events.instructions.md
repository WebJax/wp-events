---
description: 'Guidelines for developing the wp-events WordPress plugin with custom post types, Schema.org markup, recurrence, and Tribe Events import'
applyTo: 'wp-content/plugins/wp-events/**'
---

# WordPress Events Plugin Development

This plugin manages events in WordPress using custom post types (CPTs) with SEO-correct Schema.org structure, recurrence support, and flexible relations to organizers and venues. It also supports import from The Events Calendar (Tribe Events).

## Coding Standards

All code in this plugin must comply with the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/). The key rules are summarised below. When in doubt, defer to the official documentation.

### PHP

**Naming**
- Functions, variables, action/filter names: `lowercase_with_underscores` — never camelCase.
- Classes, interfaces, traits, enums: PascalCase under the `WPEvents\` namespace (e.g. `WPEvents\Blocks`, `WPEvents\Admin\MetaBoxes`).
- Constants: `ALL_CAPS_WITH_UNDERSCORES` (plugin constants use the `WPEVENTS_` prefix).
- Class files follow PSR-4 under `src/`: `WPEvents\Recurrence` → `src/Recurrence.php`, `WPEvents\Admin\Columns` → `src/Admin/Columns.php`.
- One class/interface/trait/enum per file.

**Spacing & formatting**
- Indentation: real tabs, not spaces.
- Spaces inside parentheses of control structures and function calls: `if ( $foo )`, `my_function( $arg )`.
- Spaces around operators: `$a === $b`, `$x . $y`.
- Array index with a variable uses spaces: `$foo[ $bar ]`; string/integer index does not: `$foo['bar']`, `$foo[0]`.
- Opening brace on the same line as the declaration; closing brace on its own line.
- Always use braces, even for single-statement blocks.
- Use `elseif`, not `else if`.
- Long array syntax: `array()` — not `[]`.
- No shorthand PHP tags (`<?`); always `<?php`.
- No trailing whitespace; omit the closing `?>` tag at end of file.
- Remove trailing blank lines at the end of function bodies.

**Yoda conditions**
- Don't use Yoda conditions: `if ( 42 === $foo )` is wrong; use `if ( $foo === 42 )`.
- Prefer `if ( ! $foo )` over `if ( false === $foo )` or `if ( 0 === $foo )`.
- Improve readability by using `empty()` and `isset()` where appropriate.

**OOP**
- Always declare visibility (`public`, `protected`, `private`) on methods and properties; never use `var`.
- Modifier order for methods: `abstract`/`final` → visibility → `static`.
- Always use parentheses when instantiating: `new Foo()`, never `new Foo`.

**Security & database**
- Escape all output with the most specific function: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.
- Use `$wpdb->prepare()` for all custom queries; never interpolate variables directly into SQL.
- Validate and sanitize all input at system boundaries.
- Never use `eval()`, `create_function()`, or the backtick execution operator.
- Never use `extract()`.
- Do not suppress errors with `@`.

**Other PHP rules**
- Use `require_once` (not `include_once`) for unconditional includes; no parentheses around the path.
- Use pre-increment/decrement (`++$i`) over post-increment for stand-alone statements.
- No short ternary (`?:`); always test for `true`, not `false`, in ternaries.
- Do not use `preg_replace` with the `/e` flag; use `preg_replace_callback` instead.
- Do not place assignments inside conditionals.
- Closures must not be used as action/filter callbacks (cannot be removed with `remove_action`/`remove_filter`).
- Use interpolation (not concatenation) for dynamic hook names: `do_action( "{$status}_{$post->post_type}" )`.
- Namespacing for plugin code is required; use the `WPEvents\` prefix (PSR-4 under `src/`).

### JavaScript (block editor scripts)

- Indentation: tabs.
- Single quotes for all string literals.
- Always use semicolons; never rely on ASI.
- Strict equality: `===` and `!==`; never `==` or `!=`.
- Always use braces for all control-structure blocks.
- Use `const` by default; `let` when reassignment is needed; never `var` in new code.
- Space after `!` negation operator: `! $foo`.
- Lines should stay under 80 characters; hard limit 100.
- Variable and function names: camelCase with lowercase first letter.
- Constructor/class names: UpperCamelCase.
- Constants that are never mutated: `SCREAMING_SNAKE_CASE`.
- Comments precede the code they describe and are on their own line, preceded by a blank line.

### Inline documentation

- All functions, classes, and methods must have PHPDoc / JSDoc blocks.
- PHPDoc: `@param`, `@return`, `@throws` as applicable; `@since` for plugin version when added.
- Translatable strings must use the `wp-events` text domain and include translator comments (`/* translators: %s: description */`) above `__()` / `_n()` calls.

### PHPCS / PHPCBF

Always exclude these five sniffs — they crash with `trim(): Passing null` on PHP 8.x with the current WPCS version:

```
--exclude=WordPress.WP.I18n,WordPress.NamingConventions.PrefixAllGlobals,WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions,WordPress.WP.DeprecatedParameterValues
```

Prefer the project ruleset (`phpcs.xml.dist` already scopes `src/`, `wp-events.php`, and `templates/` and applies the excludes above):

```bash
# Whole-plugin check (uses phpcs.xml.dist)
phpcs

# Check a file
phpcs src/PostTypes.php

# Auto-fix a file
phpcbf src/PostTypes.php

# Equivalent explicit standard (if not using the ruleset)
phpcs --standard=WordPress --exclude=WordPress.WP.I18n,WordPress.NamingConventions.PrefixAllGlobals,WordPress.Security.EscapeOutput,WordPress.WP.AlternativeFunctions,WordPress.WP.DeprecatedParameterValues src/
```

### Internationalization (i18n)

- All user-facing strings in PHP must be wrapped in translation functions and escaped in output context (`esc_html__()`, `esc_attr__()`, `esc_html_e()`, `esc_attr_e()`).
- All user-facing strings in JavaScript blocks must use `wp.i18n` (`__`, `_x`, `sprintf`) with the `wp-events` domain.
- Plugin bootstrap must load translations with `load_plugin_textdomain( 'wp-events', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );`.
- Keep translation assets under `languages/`.
- Regenerate POT file after string changes:
  - `wp i18n make-pot . languages/wp-events.pot --domain=wp-events --exclude=node_modules,vendor,.git`

## Plugin Structure

Composer PSR-4 autoload: `WPEvents\` → `src/`. Template helpers are loaded via Composer's `files` autoload. Bootstrap is `wp-events.php` → `vendor/autoload.php` → `\WPEvents\Plugin::init()`.

```
wp-events.php                          — Main plugin file; defines constants and boots Plugin
composer.json                          — PSR-4 autoload for WPEvents\ → src/
phpcs.xml.dist                         — PHPCS ruleset for src/, wp-events.php, templates/
src/
  Plugin.php                           — Orchestrator: wires hooks and feature modules
  PostTypes.php                        — CPT registration (event, organizer, venue)
  Taxonomies.php                       — Taxonomy registration
  Meta.php                             — register_post_meta for event/organizer/venue
  Sanitizer.php                        — Shared sanitize callbacks for meta
  Blocks.php                           — Gutenberg block registrations and render callbacks
  Schema.php                           — JSON-LD output on wp_head
  Shortcodes.php                       — [events_list] and [event] shortcodes
  Recurrence.php                       — Recurrence generation on save_post_event
  QueryFilters.php                     — Query filters (pre_get_posts)
  ICal.php                             — iCal (.ics) export and REST feed
  WooCommerce.php                      — WooCommerce ticket integration
  OrganizerCapabilities.php            — Organizer role and frontend submission
  AdditionalFeatures.php               — Event status, RSVP/registration
  TemplateLoader.php                   — Theme/plugin template resolution
  Venue.php                            — Venue helper utilities
  functions-templates.php              — Template helper functions (always loaded)
  Admin/
    Columns.php                        — Admin list columns for event CPT
    MetaBoxes.php                      — Admin meta boxes and save handlers
    Ajax.php                           — Admin AJAX (venue featured image)
  Import/
    Tribe.php                          — Tribe Events importer (admin UI)
    TribeCLI.php                       — WP-CLI import commands
assets/
  blocks.js                            — Block editor scripts
  admin.js                             — Admin scripts
  wp-events.css / wp-events-frontend.css / wp-events-frontend.js
  font/                                — Icon font assets
templates/
  single-event.php                     — Single event template
  archive-event.php                    — Archive template
  archive-event-list.php               — List layout archive
  archive-event-compact.php            — Compact layout archive
  archive-event-calendar.php           — Calendar layout archive
  taxonomy-event_category.php          — Category taxonomy template
  taxonomy-event_tag.php               — Tag taxonomy template
  parts/                               — Partial templates (cards, filters, view switcher)
languages/
  wp-events.pot                        — Translation template
```

## Custom Post Types

CPTs are registered in `WPEvents\PostTypes`, taxonomies in `WPEvents\Taxonomies`, and core meta via `WPEvents\Meta` (with sanitizers in `WPEvents\Sanitizer`). Admin meta boxes live in `WPEvents\Admin\MetaBoxes`.

### CPT: `event`

- Register with `register_post_type('event', ...)` supporting title, editor, and featured image.
- Taxonomies: `event_category` (hierarchical), `event_tag` (flat).
- Use `register_post_meta` for the following fields:
  - `event_start` — datetime in ISO 8601 format, required
  - `event_end` — datetime in ISO 8601 format, optional
  - `event_price` — float, supports decimals
  - `event_currency` — ISO 4217 code (e.g. "DKK", "EUR")
  - `event_status` — string: `scheduled`, `cancelled`, `postponed`, `sold_out`
- Relations:
  - `event_organizer` — many-to-many relation to `organizer` CPT, stored as post meta array of IDs
  - `event_venue` — one-to-one relation to `venue` CPT, stored as post meta integer ID
- Recurrence meta: `recurrence_type`, `recurrence_interval`, `recurrence_end`
- Occurrence meta: `is_occurrence` (bool), `occurrence_of` (int parent ID), `_recurrence_parent` (int)

### CPT: `organizer`

- Title = organizer name (WP standard).
- Taxonomy: `organizer_category`.
- Meta fields:
  - `organizer_website` — URL
  - `organizer_phone` — string, validated phone number
  - `organizer_email` — email, optional

### CPT: `venue`

- Title = venue name (WP standard).
- Taxonomy: `venue_category`.
- Meta fields:
  - `venue_address` — string, full address
  - `venue_phone` — string, validated phone number
  - `venue_email` — email, optional
  - `venue_website` — URL
  - `venue_facebook` — URL, optional
  - `venue_instagram` — URL, optional
  - `venue_other_social` — array of URLs, optional

## Recurrence

- Admin meta box fields: `recurrence_type` (daily/weekly/monthly/yearly/custom), `recurrence_interval` (int), `recurrence_end` (date).
- On `save_post_event`, `WPEvents\Recurrence::maybe_generate_recurrences()` runs.
- Occurrences are stored as regular `event` posts (not a separate CPT) with these meta values:
  - `is_occurrence = true`
  - `occurrence_of = <parent post ID>`
  - `_recurrence_parent = <parent post ID>`
- Occurrence titles are suffixed with the occurrence date: `"Event Title (27. april 2026)"`.
- Existing occurrences for a parent are deleted and regenerated on every save.
- Occurrences inherit: `event_start`, `event_end`, `event_venue`, `event_organizer`, `event_price`, `event_currency`, featured image, `event_category`, `event_tag`.
- Safety cap: max 200 occurrences per parent.
- Skip generation if `is_occurrence` meta is set on the post being saved.

## Schema.org / SEO

- Output JSON-LD markup (`<script type="application/ld+json">`) in `<head>` for every single event page using `wp_head` at priority 99.
- Follow the Google event rich-results rules documented in `.github/structured-data-for-events.md` for required fields, date/time handling, eligibility constraints, and validation workflow.
- Use Schema.org type `Event` with the following fields:

```json
{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": "<post title>",
  "description": "<excerpt or trimmed content>",
  "image": "<featured image URL>",
  "startDate": "<event_start>",
  "endDate": "<event_end>",
  "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
  "eventStatus": "https://schema.org/EventScheduled",
  "location": {
    "@type": "Place",
    "name": "<venue title>",
    "address": "<venue_address>",
    "telephone": "<venue_phone>",
    "url": "<venue_website>"
  },
  "organizer": {
    "@type": "Organization",
    "name": "<organizer title>",
    "url": "<organizer_website>",
    "telephone": "<organizer_phone>"
  },
  "offers": {
    "@type": "Offer",
    "price": "<event_price>",
    "priceCurrency": "<event_currency>"
  }
}
```

- Omit `endDate` and `offers` if the respective values are not set.

## Gutenberg Blocks

All blocks are registered in `src/Blocks.php` via `WPEvents\Blocks::register_all_blocks()` on `init` at priority 20. Editor scripts live in `assets/blocks.js`. All blocks support `align`, `anchor`, `className`, `color`, `spacing`, and `typography`.

### `wp-events/venue`

Renders venue name, address, contact details, and directions link. Attributes: `showAddress` (bool), `showContact` (bool), `showDirections` (bool), `linkToVenue` (bool).

### `wp-events/event-organizer`

Renders organizer name. Attributes: `showContact` (bool), `linkToOrganizer` (bool).

### `wp-events/event-schedule`

Combined schedule block that replaces the former separate `event-start` and `event-end` blocks. Shows event date and time range in a single, compact line.

**Display modes** (controlled by the `displayMode` attribute):

| Mode | Output example |
|---|---|
| `combined` (default) | `27. april 2026 · 10:00 – 14:00` |
| `start-only` | `27. april 2026, 10:00` |
| `end-only` | `27. april 2026, 14:00` |

- In `combined` mode the date is rendered once, followed by start time and end time separated by a configurable separator (default `–`).
- If start and end fall on different dates in `combined` mode, render the full datetime for both: `27. april 2026, 10:00 – 28. april 2026, 14:00`.
- Use `<time datetime="<ISO8601>">` elements for start and end individually for accessibility and SEO.
- Attributes:
  - `displayMode` — string: `combined`, `start-only`, `end-only`; default `combined`
  - `timeSeparator` — string, separator between start and end time; default `–`
  - `dateFormat` — PHP date format string for the date part; default `j. F Y`
  - `timeFormat` — PHP date format string for the time part; default `H:i`
  - `showLabel` — bool, whether to show a label prefix; default `false`
  - `customLabel` — string, label text; default `''`
  - `labelBold` — bool; default `false`
  - `labelItalic` — bool; default `false`
- Block class on the wrapper: `wp-events-schedule`.
- Omit end entirely if `event_end` is not set (block falls back to start-only output regardless of `displayMode`).

### `wp-events/events-list`

Renders a list of upcoming events ordered by `event_start`. Attributes: `numberOfEvents` (int, default 5).

### `wp-events/events-carousel`

Renders upcoming events in a carousel. Attributes: `numberOfEvents` (int), configurable visible fields.

## Shortcodes

- `[events_list limit=5]` — outputs the next N upcoming events with date, venue, and permalink.
- `[event id=123]` — outputs a single event by ID including title, dates, venue, organizers, and excerpt.

## Admin UI

- Custom admin list columns for the `event` CPT: Date, Venue, Organizer (registered in `WPEvents\Admin\Columns`).
- Admin meta boxes on the `event` edit screen: Event Times, Recurrence, Venue & Organizer, Price (in `WPEvents\Admin\MetaBoxes`); Event Status and Registration (in `WPEvents\AdditionalFeatures`); Ticket Settings when WooCommerce is active (in `WPEvents\WooCommerce`).
- Admin meta boxes on `venue`: Venue Details.
- Admin meta boxes on `organizer`: Organizer Details.

## Import from Tribe Events (The Events Calendar)

- Implemented in `WPEvents\Import\Tribe` (admin UI) and `WPEvents\Import\TribeCLI` (WP-CLI).
- Check for existence of the `tribe_events` CPT before running any import logic.
- Map Tribe meta to plugin meta:
  - `tribe_event_start_date` → `event_start`
  - `tribe_event_end_date` → `event_end`
  - `EventCurrencySymbol` → `event_currency`
  - Tribe venue data → `venue` CPT with matching meta fields
  - Tribe organizer data → `organizer` CPT with matching meta fields
- Prevent duplicates by storing `_tribe_event_id` in meta on imported posts.
- Provide import via WP-CLI command (`wp wpevents import-tribe`) and/or an admin interface action.

## iCal Export

- Implemented in `WPEvents\ICal`.
- Query vars: `ical_download=1&event_id=<ID>` triggers a `.ics` file download for a single event.
- REST endpoint available for a full event feed.
- An iCal download button is injected via `the_content` filter on single event pages.
- Access control: only published, non-password-protected events are served; draft/private events require `read_post` capability.

## WooCommerce Ticket Integration

- Implemented in `WPEvents\WooCommerce`; only activates when WooCommerce is active (`class_exists('WooCommerce')`).
- Admin meta box on `event` edit screen: ticket settings including linked product, capacity, and ticket type.
- Ticket purchase button injected via `the_content` filter on event pages.
- Event metadata (event ID, start date) attached to cart items and order line items.
- Attendee fields added to WooCommerce checkout.
- Event capacity synced to WooCommerce product stock on `save_post_event`.

## Organizer Role & Capabilities

- Implemented in `WPEvents\OrganizerCapabilities`.
- Custom WordPress role: `event_organizer` — can create, edit, and delete own events.
- Frontend shortcodes: `[organizer_dashboard]` and `[event_submission_form]`.
- Organizers only see their own events in the admin list.
- User profile fields link a WP user to an `organizer` CPT post.

## Event Status & Registration

- Implemented in `WPEvents\AdditionalFeatures`.
- `event_status` meta: `scheduled`, `cancelled`, `postponed`, `sold_out`.
- Status badge appended to event titles where applicable.
- Registration/RSVP meta box with configurable settings; registration form injected via `the_content` filter.
- `admin_post` handlers for both authenticated and anonymous registration submissions.
