# Visual Overview - Event Template Designs

Dette dokument giver en visuel beskrivelse af de fire forskellige event template designs.

## 1. Grid View (Standard)
**URL:** `/events/` eller `/events/?view=grid`  
**Template:** `archive-event.php`

```
┌─────────────────────────────────────────────────────────┐
│                    Alle Events                          │
│               [Kalender][Liste][Kompakt]                │
│                                                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │  [IMG]   │  │  [IMG]   │  │  [IMG]   │             │
│  │          │  │          │  │          │             │
│  │ Titel    │  │ Titel    │  │ Titel    │             │
│  │ Kategori │  │ Kategori │  │ Kategori │             │
│  │ 📅 Dato  │  │ 📅 Dato  │  │ 📅 Dato  │             │
│  │ 📍 Venue │  │ 📍 Venue │  │ 📍 Venue │             │
│  │ Uddrag   │  │ Uddrag   │  │ Uddrag   │             │
│  │[Læs mere]│  │[Læs mere]│  │[Læs mere]│             │
│  └──────────┘  └──────────┘  └──────────┘             │
│                                                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐             │
│  │  [IMG]   │  │  [IMG]   │  │  [IMG]   │             │
│  └──────────┘  └──────────┘  └──────────┘             │
└─────────────────────────────────────────────────────────┘
```

**Karakteristika:**
- Responsive grid (1-3 kolonner afhængig af skærmstørrelse)
- Billede øverst med hover effect
- Vertikal card layout
- Mest visuel præsentation
- God til desktop browsing

---

## 2. Calendar View
**URL:** `/events/?view=calendar`  
**Template:** `archive-event-calendar.php`

```
┌─────────────────────────────────────────────────────────┐
│                  Event Kalender                          │
│               [Kalender][Liste][Kompakt]                │
│                                                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   [IMAGE]   │  │   [IMAGE]   │  │   [IMAGE]   │    │
│  │             │  │             │  │             │    │
│  │ Event Titel │  │ Event Titel │  │ Event Titel │    │
│  │ [Kategori]  │  │ [Kategori]  │  │ [Kategori]  │    │
│  │ 📅 15. Feb  │  │ 📅 16. Feb  │  │ 📅 17. Feb  │    │
│  │    kl. 10:00│  │    kl. 14:00│  │    kl. 19:00│    │
│  │ 📍 Venue    │  │ 📍 Venue    │  │ 📍 Venue    │    │
│  │   Address   │  │   Address   │  │   Address   │    │
│  │ Event text  │  │ Event text  │  │ Event text  │    │
│  │[Læs mere →] │  │[Læs mere →] │  │[Læs mere →] │    │
│  └─────────────┘  └─────────────┘  └─────────────┘    │
└─────────────────────────────────────────────────────────┘
```

**Karakteristika:**
- Fast 3-kolonne layout (desktop)
- Samme style som grid men bredere
- Kalender-lignende præsentation
- Ideel til månedsoversigt
- God til planlægning

---

## 3. List View
**URL:** `/events/?view=list`  
**Template:** `archive-event-list.php`

```
┌─────────────────────────────────────────────────────────┐
│                    Alle Events                          │
│               [Kalender][Liste][Kompakt]                │
│                                                          │
│  ┌─────┬──────────┬──────────────────────────────────┐ │
│  │ 15  │ [IMAGE]  │ Event Titel 1                    │ │
│  │ Feb │          │ [Kategori 1][Kategori 2]         │ │
│  │     │          │ 📅 15. Feb 2024 kl. 10:00        │ │
│  │     │          │ 📍 Venue Name, København         │ │
│  │     │          │ Dette er en beskrivelse af       │ │
│  │     │          │ eventet...                       │ │
│  │     │          │                    [Læs mere →]  │ │
│  └─────┴──────────┴──────────────────────────────────┘ │
│                                                          │
│  ┌─────┬──────────┬──────────────────────────────────┐ │
│  │ 16  │ [IMAGE]  │ Event Titel 2                    │ │
│  │ Feb │          │ [Kategori 3]                     │ │
│  └─────┴──────────┴──────────────────────────────────┘ │
│                                                          │
│  ┌─────┬──────────┬──────────────────────────────────┐ │
│  │ 17  │ [IMAGE]  │ Event Titel 3                    │ │
│  │ Feb │          │ [Kategori 1]                     │ │
│  └─────┴──────────┴──────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

**Karakteristika:**
- Horizontal layout
- Stor dato badge til venstre (dag + måned)
- Billede i midten
- Detaljeret information til højre
- Let at scanne gennem
- God til mange events

---

## 4. Compact View
**URL:** `/events/?view=compact`  
**Template:** `archive-event-compact.php`

```
┌─────────────────────────────────────────────────────────┐
│                 Kommende Events                          │
│               [Kalender][Liste][Kompakt]                │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ ┌───┐ Event Titel 1                          → │  │
│  │ │15 │ 📅 10:00  📍 Venue Name                    │  │
│  │ │Feb│ [Kategori 1]                                │  │
│  │ └───┘                                               │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ ┌───┐ Event Titel 2                          → │  │
│  │ │16 │ 📅 14:00  📍 Andet Venue                   │  │
│  │ │Feb│ [Kategori 2]                                │  │
│  │ └───┘                                               │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ ┌───┐ Event Titel 3                          → │  │
│  │ │17 │ 📅 19:00  📍 Tredje Venue                  │  │
│  │ │Feb│ [Kategori 1]                                │  │
│  │ └───┘                                               │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ ┌───┐ Event Titel 4                          → │  │
│  │ │20 │ 📅 15:00  📍 Fjerde Venue                  │  │
│  │ │Feb│ [Kategori 3]                                │  │
│  │ └───┘                                               │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

**Karakteristika:**
- Meget kompakt layout
- Lille dato badge (60px)
- Ingen billeder
- Kun vigtigste information
- Høj informations-tæthed
- Perfekt til sidebars
- God til mobile

---

## View Switcher Component

Alle views inkluderer en view switcher øverst:

```
┌─────────────────────────────────────────────┐
│ Visning: [📅 Kalender] [📋 Liste] [→ Kompakt] │
└─────────────────────────────────────────────┘
```

- Aktiv view er highlighted (blå baggrund)
- Klikbar for at skifte view
- Bevarer alle filter indstillinger
- Responsive (skjuler labels på mobil)

---

## Responsive Breakpoints

### Desktop (> 1024px)
- Grid: 3 kolonner
- Calendar: 3 kolonner
- List: Fuld horizontal layout
- Compact: Fuld layout

### Tablet (768px - 1024px)
- Grid: 2 kolonner
- Calendar: 2 kolonner
- List: Fuld horizontal layout
- Compact: Fuld layout

### Mobile (< 768px)
- Grid: 1 kolonne
- Calendar: 1 kolonne
- List: Vertical stack (dato → billede → content)
- Compact: Reduceret padding

---

## Color & Style Guide

### Primary Colors
- Primary color: `#007cba` (blå)
- Background: `white`
- Borders: `#e1e5e9` (lys grå)
- Hover: `#f8f9fa` (meget lys grå)

### Typography
- Titler: `1.3rem - 1.5rem`, bold
- Body text: `0.95rem`, normal
- Meta info: `0.85rem - 0.95rem`, normal
- Buttons: `0.9rem`, semi-bold

### Spacing
- Card padding: `20px`
- Gap mellem cards: `20px - 30px`
- Border radius: `8px - 12px`

### Shadows
- Card shadow: `0 4px 6px rgba(0, 0, 0, 0.1)`
- Hover shadow: `0 8px 25px rgba(0, 0, 0, 0.15)`

---

## Icons Used

- 📅 Calendar icon (`.icon-calendar`)
- 📍 Location icon (`.icon-location`)
- → Arrow icon (`.icon-right-dir`)
- 🔽 Down arrow (`.icon-down-dir`)
- 🏷️ Tag icon (`.icon-tag`)

---

## Use Cases

### Grid View - Bedst til:
- Visuel præsentation
- Når billeder er vigtige
- Event portfolios
- Desktop browsing

### Calendar View - Bedst til:
- Månedlig planlægning
- Event oversigt
- Konferencer/festivaler
- Når dato er vigtigst

### List View - Bedst til:
- Scanning af mange events
- Detaljeret information
- Tablet enheder
- Søgeresultater

### Compact View - Bedst til:
- Sidebars og widgets
- Mobile enheder
- Hurtig oversigt
- Pladsbegrænsede områder

---

## Accessibility Features

✅ Semantic HTML  
✅ ARIA labels  
✅ Keyboard navigerbar  
✅ Screen reader venlig  
✅ Color contrast (WCAG AA)  
✅ Focus indicators  
✅ Alt text på billeder  

---

## Performance

- Optimeret CSS (minimal overhead)
- No JavaScript required for basic functionality
- Lazy loading supported (WordPress standard)
- Efficient template loading
- Minimal HTTP requests

---

Dette visuelle overview giver et hurtigt overblik over de fire forskellige template designs og deres use cases.
