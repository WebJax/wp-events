# WP Events Plugin

Et WordPress-plugin til begivenheder med SEO, gentagelser, relationer og import fra The Events Calendar.

## Funktioner
- Custom post types: Event, Organizer, Venue
- Relationer: mange-til-mange arrangører, én-til-en venue
- Gentagelser: daglig, ugentlig, månedlig, årlig
- JSON-LD Schema.org markup
- Import fra Tribe Events (WP-CLI og admin)
- Shortcodes: `[events_list]`, `[event id=123]`
- Gutenberg-blokke: Event-liste, Karusel (Swiper.js)
- Admin kolonner: Dato, Sted, Arrangør

## Installation
1. Upload plugin-mappen til `/wp-content/plugins/wp-events`
2. Aktivér via WordPress admin

## WP-CLI Import
Kør:
```sh
wp wpevents import-tribe
```

## Fremtidige udvidelser
- iCal eksport
- WooCommerce billet-integration


# WP Events Template System

Dette plugin inkluderer et fleksibelt template system der automatisk håndterer visning af events, men som kan overskrives i dit tema.

## Tilgængelige Templates

### 1. Archive Template (`archive-event.php`)
Viser en liste af alle events med:
- Filtrering efter kategori og tidspunkt
- Paginering
- Event cards med billede, titel, tidspunkt og sted
- Responsive design

### 2. Taxonomy Templates
- `taxonomy-event_category.php` - Events i en specifik kategori
- `taxonomy-event_tag.php` - Events med et specifikt tag

Begge viser:
- Event cards med samme layout som archive
- Kategori/tag beskrivelse
- Paginering

## Hvordan man overskriver templates

### I dit tema:
1. Opret mappen `wp-events/` i dit tema
2. Kopier den ønskede template fra `wp-content/plugins/wp-events/templates/`
3. Tilpas efter behov

### Eksempel:
```
wp-content/themes/dit-tema/
└── wp-events/
    ├── archive-event.php
    ├── taxonomy-event_category.php
    └── taxonomy-event_tag.php
```

### Prioritet:
1. `wp-content/themes/dit-tema/wp-events/template-navn.php` (høj prioritet)
2. `wp-content/themes/dit-tema/template-navn.php`
3. `wp-content/plugins/wp-events/templates/template-navn.php` (plugin standard)

## Template Struktur

### Event Card Data
Hver event card indeholder:
```php
$event_id = get_the_ID();
$start_date = get_post_meta( $event_id, 'event_start', true );
$end_date = get_post_meta( $event_id, 'event_end', true );
$venue_id = get_post_meta( $event_id, 'event_venue', true );
$venue_name = $venue_id ? get_the_title( $venue_id ) : '';
```

### Styling
Templates bruger CSS klasser fra `wp-events-frontend.css`:
- `.event-card` - Hoved container
- `.event-image` - Billede container  
- `.event-content` - Tekst indhold
- `.event-title` - Titel
- `.event-date` - Tidspunkt
- `.event-venue` - Sted
- `.event-excerpt` - Uddrag
- `.event-read-more` - Læs mere knap

## Tilpasning

### CSS Overrides
Tilføj custom CSS i dit tema:
```css
.wp-events-archive-page .event-card {
    /* Dine tilpasninger */
}
```

### Template Hooks
Du kan bruge standard WordPress hooks i dine templates:
- `get_header()`
- `get_footer()`
- `the_posts_pagination()`

### Filtrering
Archive template inkluderer JavaScript til filtering efter:
- Event kategori
- Tidspunkt (kommende/tidligere/alle)

## Troubleshooting

### Templates ikke synlige?
1. Flush permalink strukturen (Indstillinger > Permalinks > Gem)
2. Tjek at events er oprettet og publiceret
3. Verificer at event_start meta er sat på events

### Styling problemer?
1. Tjek at `wp-events-frontend.css` indlæses
2. Kontroller for tema CSS konflikter
3. Brug browser developer tools til debugging