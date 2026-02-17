# WP Events Plugin

Et WordPress-plugin til begivenheder med SEO, gentagelser, relationer og import fra The Events Calendar.

## Funktioner
- Custom post types: Event, Organizer, Venue
- Relationer: mange-til-mange arrangører, én-til-en venue
- Gentagelser: daglig, ugentlig, månedlig, årlig
- JSON-LD Schema.org markup
- **iCal Eksport**: Download events i iCalendar format (.ics fil)
- **WooCommerce Integration**: Sælg billetter gennem WooCommerce
- **Arrangør Login**: Arrangører kan logge ind og administrere egne events
- **Avanceret Import fra Tribe Events** (WP-CLI og admin):
  - Oversigt over alle tilgængelige events
  - Selektiv import med checkboxes
  - Status tracking (importerede vs. tilgængelige)
  - Filtrering efter fremtidige/tidligere events
  - Import-statistik dashboard
- Shortcodes: `[events_list]`, `[event id=123]`, `[organizer_dashboard]`, `[event_submission_form]`
- Gutenberg-blokke: Event-liste, Karusel (Swiper.js)
- Admin kolonner: Dato, Sted, Arrangør

## Installation
1. Upload plugin-mappen til `/wp-content/plugins/wp-events`
2. Aktivér via WordPress admin

## Import fra The Events Calendar

### Via Admin Interface
1. Gå til **WP Events → Import from Tribe** i WordPress admin
2. Se oversigt over alle Tribe Events med statistik
3. Vælg events ved at sætte flueben
4. Klik på "Import Selected Events"

Funktioner:
- **Filtrering**: Alle, Tilgængelige, Importerede, Fremtidige, Tidligere
- **Status tracking**: Se hvilke events der allerede er importeret
- **Duplikat-forebyggelse**: Events kan kun importeres én gang
- **Visual feedback**: Importerede events markeres med ✓

Se [IMPORT-GUIDE.md](IMPORT-GUIDE.md) for detaljeret dokumentation.

## WP-CLI Import
Kør:
```sh
# Import alle tilgængelige events
wp wpevents import-tribe

# Import i batches
wp wpevents import-tribe --batch=50
```

## iCal Eksport

Events kan eksporteres i iCalendar format (.ics) til kalenderapps.

### Funktioner
- **Download knap**: Automatisk "Add to Calendar" knap på event sider
- **REST API**: `/wp-json/wp-events/v1/ical/{event_id}` - Hent iCal for enkelt event
- **Feed**: `/wp-json/wp-events/v1/ical/feed` - Hent alle kommende events som iCal feed
- **Direkte download**: `?ical_download=1&event_id=123` på event URL

### Brug
1. Besøg en event side
2. Klik på "Add to Calendar" knappen
3. Åbn .ics filen i din kalender app (Google Calendar, Apple Calendar, Outlook)

## WooCommerce Integration

Sælg billetter til events gennem WooCommerce.

### Opsætning
1. Installér og aktivér WooCommerce plugin
2. Opret et produkt i WooCommerce til billetter
3. I event editor, find "Ticket Settings" meta box:
   - Aktivér "Enable ticket sales"
   - Vælg WooCommerce produkt
   - Sæt event kapacitet (max deltagere)
4. Gem event

### Funktioner
- **Automatisk ticket knap**: Vises på event sider
- **Kapacitetsstyring**: Synkroniserer med produkt lager
- **Order tracking**: Events linkes til orders
- **Deltagerliste**: Attendee info gemmes på orders
- **Udsolgt beskeder**: Vises automatisk når kapacitet nået

## Arrangør Login & Administration

System til at lade arrangører logge ind og administrere egne events.

### Brugerroller
- **Event Organizer**: Ny rolle kun til event administration
- Kan oprette, redigere og slette egne events
- Kan ikke tilgå andet admin indhold

### Opsætning
1. Opret bruger med "Event Organizer" rolle
2. I event editor, find "Assigned Organizers" meta box
3. Vælg hvilke brugere der kan administrere eventet
4. Gem event

### Shortcodes for Organizers
```php
// Dashboard med liste af egne events
[organizer_dashboard]

// Frontend formular til at indsende events
[event_submission_form]
```

### Frontend Event Indsendelse
1. Tilføj `[event_submission_form]` shortcode til en side
2. Arrangører logger ind
3. Udfylder formular med event detaljer
4. Event oprettes med status "pending" (afventer godkendelse)
5. Admin gennemgår og publicerer event

## Fremtidige udvidelser
- Email notifikationer til deltagere
- Påmindelse for kommende events
- Deltagerregistrering uden WooCommerce
- Integration med flere betalingsgateways


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