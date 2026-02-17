# Nye Features - Implementeringsguide

Denne guide viser hvordan du bruger de nye features der er tilføjet til WP Events plugin.

## 🗓️ iCal Eksport

### Automatisk funktion
- Hver event side viser automatisk en "Add to Calendar" knap
- Besøgende kan downloade .ics fil til deres kalender app

### REST API Endpoints
```
# Enkelt event:
GET /wp-json/wp-events/v1/ical/{event_id}

# Alle kommende events:
GET /wp-json/wp-events/v1/ical/feed
```

### Direkte Download
Tilføj query parameters til event URL:
```
https://yoursite.com/events/min-event/?ical_download=1&event_id=123
```

---

## 🛒 WooCommerce Billet Integration

### Opsætning (kræver WooCommerce plugin)

1. **Opret et produkt i WooCommerce**
   - Gå til Products → Add New
   - Opret et produkt (f.eks. "Event Billet")
   - Sæt pris og andre detaljer

2. **Link produkt til event**
   - Rediger en event
   - Find "Ticket Settings (WooCommerce)" meta box i sidebar
   - ✓ Aktiver "Enable ticket sales"
   - Vælg WooCommerce produkt fra dropdown
   - Indtast event kapacitet (0 = ubegrænset)
   - Gem event

3. **Resultat**
   - "Buy Ticket" knap vises på event siden
   - Kapacitet vises (f.eks. "Available: 45/50 tickets")
   - Ved udsolgt: "Sorry, this event is sold out"

### Features
- Ticket antal synkroniseres med produkt lager
- Event info tilføjes til WooCommerce orders
- Kun completed/processing orders tælles
- Attendee navn og email kan tilføjes ved checkout

---

## 👥 Arrangør Login & Administration

### Opret Event Organizer bruger

1. **Tilføj ny bruger**
   - Gå til Users → Add New
   - Vælg rolle: **Event Organizer**
   - Udfyld brugeroplysninger
   - Klik "Add New User"

2. **Tildel events til organizer**
   - Rediger en event
   - Find "Assigned Organizers (Users)" meta box i sidebar
   - ✓ Vælg bruger(e) der kan administrere eventet
   - Gem event

### Frontend Dashboard

Opret en side med shortcode:
```
[organizer_dashboard]
```

Dette viser:
- Liste over brugerens events
- Event status (draft, published, etc.)
- Links til at redigere events
- "Add New Event" knap

### Frontend Event Indsendelse

Opret en side med shortcode:
```
[event_submission_form]
```

Features:
- Organizers kan indsende events fra frontend
- Events oprettes med status "pending" (afventer godkendelse)
- Admin får besked og kan godkende/publicere
- Kun logged-in brugere kan indsende

---

## 🏷️ Event Status & Badges

### Sæt event status

1. Rediger en event
2. Find "Event Status" meta box i sidebar
3. Vælg status:
   - **Scheduled** - Normal (ingen badge)
   - **Cancelled** - Aflyst (rød badge)
   - **Postponed** - Udsat (gul badge)
   - **Rescheduled** - Omlagt (blå badge)
   - **Sold Out** - Udsolgt (grå badge)
   - **Completed** - Afsluttet (grøn badge)
4. Gem event

### Resultat
Status badges vises automatisk på:
- Event arkiv sider
- Kategori sider
- Event single pages
- Admin event liste

---

## 📝 Registration/RSVP System

### Aktivér tilmelding

1. **Rediger event**
2. **Find "Registration Settings" meta box**
3. **Konfigurer indstillinger:**
   - ✓ "Enable Registration" - Aktiver tilmelding
   - **Maximum Attendees** - Maks. deltagere (0 = ubegrænset)
   - **Registration Deadline** - Sidste frist for tilmelding
   - ✓ "Require Approval" - Kræv godkendelse (optional)
4. **Gem event**

### Hvordan det virker

**Frontend (besøgende):**
- Tilmeldingsformular vises automatisk på event siden
- Udfylder: Navn, Email, Telefon, Noter
- Klikker "Register Now"
- Modtager email bekræftelse

**Admin:**
- Se alle tilmeldinger i event editor
- Se deltager info (navn, email, telefon, noter)
- Se status (Pending eller Confirmed)
- Godkend tilmeldinger hvis "Require Approval" er aktiveret

### Kapacitetsstyring
- Tilmelding lukkes automatisk ved fuldt event
- "X spots remaining" vises på formular
- "This event is full" besked ved udsolgt

---

## 🔗 Link Bruger til Organizer Post

Hvis du har både bruger-konti og organizer posts:

1. Gå til Users → Edit User
2. Find "Event Organizer Settings" sektion
3. Vælg organizer post fra dropdown
4. Gem

Dette linker brugerens konto til en specifik organizer post.

---

## 🎯 Brug af Shortcodes

### Events Liste
```
[events_list limit="10"]
```

### Enkelt Event
```
[event id="123"]
```

### Organizer Dashboard
```
[organizer_dashboard]
```

### Event Indsendelse
```
[event_submission_form]
```

---

## 📊 Admin Features

### Nye kolonner i event liste:
- **Status** - Event status (Scheduled, Cancelled, etc.)
- **Date** - Event start dato
- **Venue** - Event lokation
- **Organizer** - Arrangører

### Filtrering:
Event organizers ser kun deres tildelte events i admin.

---

## 🔒 Sikkerhed

Alle nye features inkluderer:
- ✅ Input sanitization
- ✅ Nonce verification
- ✅ Capability checks
- ✅ SQL injection prevention
- ✅ XSS protection

---

## 💡 Tips

1. **WooCommerce Integration**: Opret et "Event Ticket" produkt som template, derefter dupliker det for hver event type.

2. **Organizer Workflow**: Lad organizers indsende events via frontend, så kan admin godkende dem.

3. **Registration vs. WooCommerce**: Brug Registration for gratis events, WooCommerce for betalte events.

4. **iCal Feed**: Del feed URL så folk kan subscribe til alle dine events i deres kalender.

5. **Status Badges**: Opdater status når events aflyses eller ændres - besøgende får automatisk visuel feedback.

---

## ❓ Support

Hvis du har spørgsmål eller problemer:
1. Tjek at alle required plugins er installeret (WooCommerce hvis du bruger tickets)
2. Aktivér WordPress debug mode for at se eventuelle fejl
3. Kontakt plugin support

Tak for at bruge WP Events! 🎉
