---
description: 'Guidelines for developing the wp-events WordPress plugin with custom post types, Schema.org markup, recurrence, and Tribe Events import'
applyTo: 'wp-content/plugins/wp-events/**'
---

# WordPress Events Plugin Development

This plugin manages events in WordPress using custom post types (CPTs) with SEO-correct Schema.org structure, recurrence support, and flexible relations to organizers and venues. It also supports import from The Events Calendar (Tribe Events).

## Custom Post Types

### CPT: `event`

- Register with `register_post_type('event', ...)` supporting title, editor, and featured image.
- Use `register_post_meta` for the following fields:
  - `event_start` — datetime in ISO 8601 format, required
  - `event_end` — datetime in ISO 8601 format, optional
  - `event_price` — float, supports decimals
  - `event_currency` — ISO 4217 code (e.g. "DKK", "EUR")
- Relations:
  - `event_organizer` — many-to-many relation to `organizer` CPT, stored as post meta array of IDs
  - `event_venue` — one-to-one relation to `venue` CPT, stored as post meta integer ID

### CPT: `organizer`

- Title = organizer name (WP standard).
- Meta fields:
  - `organizer_website` — URL
  - `organizer_phone` — string, validated phone number
  - `organizer_email` — email, optional

### CPT: `venue`

- Title = venue name (WP standard).
- Meta fields:
  - `venue_address` — string, full address
  - `venue_phone` — string, validated phone number
  - `venue_email` — email, optional
  - `venue_website` — URL
  - `venue_facebook` — URL, optional
  - `venue_instagram` — URL, optional
  - `venue_other_social` — array of URLs, optional

## Recurrence

- Add a Gutenberg sidebar panel or meta box with the following fields:
  - `recurrence_type` — one of: `daily`, `weekly`, `monthly`, `yearly`, `custom`
  - `recurrence_interval` — integer (e.g. every 2nd week)
  - `recurrence_end` — date for last occurrence
- On save, store the master event and generate child occurrences.
- Child events can either be stored as a separate `event_occurrence` CPT or as serialized meta on the master event.
- All occurrences inherit organizer and venue data from the master.

## Schema.org / SEO

- Output JSON-LD markup (`<script type="application/ld+json">`) in `<head>` for every single event page using `wp_head`.
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

## Import from Tribe Events (The Events Calendar)

- Check for existence of the `tribe_events` CPT before running any import logic.
- Map Tribe meta to plugin meta:
  - `tribe_event_start_date` → `event_start`
  - `tribe_event_end_date` → `event_end`
  - `EventCurrencySymbol` → `event_currency`
  - Tribe venue data → `venue` CPT with matching meta fields
  - Tribe organizer data → `organizer` CPT with matching meta fields
- Prevent duplicates by storing `_tribe_event_id` in meta on imported posts.
- Provide import via WP-CLI command and/or an admin interface action.

## Admin UI

- Register two Gutenberg blocks:
  - **Event list block** — displays upcoming events with configurable limit.
  - **Event carousel block** — displays events in a Swiper.js carousel with configurable visible fields.
- Register shortcodes:
  - `[events_list limit=5]` — outputs the next N upcoming events.
  - `[event id=123]` — outputs a single event by ID.
- Add custom admin list columns for the `event` CPT: Date, Venue, Organizer.

## Future Extensions

- iCal export (`.ics`) — design meta and query logic to be forward-compatible.
- Ticket integration — keep WooCommerce compatibility in mind when modelling `event_price`.
