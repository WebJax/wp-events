# WP Events Template Guide

Dette dokument beskriver de nye template designs der er tilgængelige i WP Events plugin.

## Oversigt

WP Events plugin tilbyder nu **fire forskellige visningsformater** for event-arkivet, som giver brugerne fleksibilitet til at vælge den bedste måde at vise events på.

## Tilgængelige Visninger

### 1. Grid View (Standard)
**Template fil:** `archive-event.php`

**URL:** `/events/` eller `/events/?view=grid`

**Beskrivelse:**
- Standard visning med event cards i grid layout
- Viser events i kolonner (responsive)
- Event cards inkluderer:
  - Featured billede
  - Event titel
  - Kategorier
  - Dato og tidspunkt
  - Venue navn og adresse
  - Kort uddrag
  - "Læs mere" knap

**Bedst til:**
- Generel event oversigt
- Når visuel præsentation er vigtig
- Desktop brugere

### 2. Calendar View
**Template fil:** `archive-event-calendar.php`

**URL:** `/events/?view=calendar`

**Beskrivelse:**
- 3-kolonne grid layout der ligner en kalender
- Større event cards med mere information
- Samme features som grid view, men med bredere layout

**Bedst til:**
- Månedlig eller ugentlig oversigt
- Planlægning af deltagelse i multiple events
- Store skærme

### 3. List View
**Template fil:** `archive-event-list.php`

**URL:** `/events/?view=list`

**Beskrivelse:**
- Horisontale event cards i liste format
- Dato badge på venstre side
- Featured billede til højre
- Detaljeret event information

**Features:**
- Stor dato badge (dag + måned)
- Horizontal layout med billede
- Mere detaljeret information synlig
- Bedre til scanning gennem mange events

**Bedst til:**
- Når brugere skal se gennem mange events hurtigt
- Tablet enheder
- Når detaljer er vigtigere end billeder

### 4. Compact View
**Template fil:** `archive-event-compact.php`

**URL:** `/events/?view=compact`

**Beskrivelse:**
- Minimalistisk liste med kompakte event cards
- Kun vigtigste information vises
- Ingen billeder
- Høj informations-tæthed

**Features:**
- Lille dato badge
- Event titel
- Tidspunkt og venue
- Første kategori som badge
- Pile-ikon for navigation

**Bedst til:**
- Sidebars og widgets
- Mobile enheder
- Når plads er begrænset
- Hurtig oversigt over kommende events

## View Switcher

Alle archive templates inkluderer en "View Switcher" UI komponent der gør det nemt for brugere at skifte mellem visninger.

**Placering:** Mellem page header og event filters

**Features:**
- Tre knapper for at skifte view
- Aktiv view highlightes
- Bevarer filter indstillinger når man skifter view
- Responsive design

## Template Parts (Genanvendelige Komponenter)

### Event Cards

#### 1. `parts/event-card-grid.php`
Standard event card brugt i grid/calendar view.

**Inkluderer:**
- Featured billede
- Event titel med link
- Kategorier
- Dato og tidspunkt
- Venue information med adresse
- Uddrag
- "Læs mere" knap

#### 2. `parts/event-card-list.php`
Horizontal event card brugt i list view.

**Inkluderer:**
- Stor dato badge (dag + måned)
- Featured billede (250px bred)
- Event titel og kategorier
- Dato og tidspunkt
- Venue navn og by
- Uddrag
- "Læs mere" knap med ikon

#### 3. `parts/event-card-compact.php`
Minimal event card brugt i compact view.

**Inkluderer:**
- Lille dato badge (60px)
- Event titel
- Tidspunkt
- Venue navn
- Første kategori som badge
- Pile-ikon

### UI Komponenter

#### `parts/view-switcher.php`
View switcher komponent der viser tre knapper for at skifte mellem visninger.

**Features:**
- Automatisk detektering af aktiv view
- Bevarer query parameters når man skifter
- Responsive design

#### `parts/event-filters.php`
Eksisterende filter komponent (uændret).

## Tilpasning

### Overskrive Templates i Dit Tema

For at tilpasse en template, kopier den til dit tema:

```
wp-content/themes/dit-tema/
└── wp-events/
    ├── archive-event-list.php      ← Dit custom list view
    ├── archive-event-calendar.php  ← Dit custom calendar view
    └── parts/
        ├── event-card-list.php     ← Dit custom list card
        └── view-switcher.php       ← Dit custom view switcher
```

### CSS Tilpasning

Alle nye templates bruger CSS klasser fra `wp-events-frontend.css`. Du kan overskrive styles i dit tema:

```css
/* List view tilpasninger */
.wp-events-list-view .event-card-list {
    /* Dine styles */
}

/* Calendar view tilpasninger */
.wp-events-calendar-view .events-grid-3col {
    /* Dine styles */
}

/* Compact view tilpasninger */
.wp-events-compact-view .events-compact-container {
    /* Dine styles */
}

/* View switcher tilpasninger */
.wp-events-view-switcher {
    /* Dine styles */
}
```

### CSS Klasser Reference

#### List View
- `.wp-events-list-view` - Container
- `.events-list-container` - List wrapper
- `.event-card-list` - Individual list card
- `.event-date-badge` - Dato badge
- `.event-image` - Billede container (250px)

#### Calendar View
- `.wp-events-calendar-view` - Container
- `.events-grid-3col` - 3-column grid
- `.event-card-grid` - Grid card (samme som standard)

#### Compact View
- `.wp-events-compact-view` - Container
- `.events-compact-container` - Compact list wrapper
- `.event-card-compact` - Compact card
- `.event-card-link` - Clickable link wrapper
- `.event-category-badge` - Kategori badge

## Programmatisk Brug

### Link til Specifik View

```php
// Grid view (standard)
$url = get_post_type_archive_link( 'event' );

// List view
$url = add_query_arg( 'view', 'list', get_post_type_archive_link( 'event' ) );

// Calendar view
$url = add_query_arg( 'view', 'calendar', get_post_type_archive_link( 'event' ) );

// Compact view
$url = add_query_arg( 'view', 'compact', get_post_type_archive_link( 'event' ) );
```

### Få Aktuel View

```php
$current_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'grid';
```

### Template Loader Logik

Plugin'et bruger `template_include` filter til at loade korrekt template baseret på `view` query parameter:

```php
// I includes/class-wpevents-blocks-clean.php
if ( is_post_type_archive( 'event' ) ) {
    $view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : '';
    $allowed_views = array( 'list', 'calendar', 'compact' );
    
    if ( $view && in_array( $view, $allowed_views ) ) {
        $default_file = 'archive-event-' . $view . '.php';
    } else {
        $default_file = 'archive-event.php';
    }
}
```

## Responsive Design

Alle templates er fuldt responsive:

### Desktop (> 1024px)
- Grid view: Multi-kolonne layout
- List view: Fuld horizontal layout
- Compact view: Fuld information synlig

### Tablet (768px - 1024px)
- Grid view: 2 kolonner
- List view: Fuld horizontal layout
- Compact view: Fuld information synlig

### Mobile (< 768px)
- Grid view: Single kolonne
- List view: Vertical stack (dato badge + billede + content)
- Compact view: Reduceret padding, skjulte labels

## Browser Support

Templates er testet og virker i:
- Chrome (seneste)
- Firefox (seneste)
- Safari (seneste)
- Edge (seneste)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Ydeevne

- CSS er optimeret med minimal overhead
- Template parts reducerer duplikering
- Lazy loading af billeder anbefales (WordPress standard)
- Paginering inkluderet i alle views

## Fremtidige Udvidelser

Potentielle fremtidige features:
- Map view med Google Maps integration
- Timeline view for kronologisk oversigt
- Agenda view med daglig gruppering
- User preference for standard view (cookies/localStorage)

## Support

For spørgsmål eller problemer med templates, kontakt plugin udvikleren eller opret et issue på GitHub repository.
