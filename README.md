# EAV's Homestay — Web-Based Booking & Management System

2-room oceanfront vacation rental in Catohugan, Mahinog, Camiguin, Philippines.
Guest-facing direct booking (no account) + secure admin dashboard for the owner.

## 1. Business Overview

**Name:** EAV's Homestay
**Location:** Catohugan, Mahinog, Camiguin, 9101, Philippines
**Contact:** +63 966 419 7812 | ricardo_macarine82@yahoo.com
**Inventory:** Exactly 2 units, both large-capacity (up to ~6 guests)

| Room | Price | Package |
|------|-------|---------|
| Room 1 — Brown Unit | ₱3,000/night | Base package **includes FREE 1-day scooter rental**. Wooden bunk beds, AC, flat-screen TV, hot/cold dispenser, private dining setup |
| Room 2 — Blue Unit | ₱3,000/night (temporary, package TBD) | Queen bed, AC, flat-screen TV, mini-fridge, hot/cold dispenser, private wooden dining setup |

**Property highlights:** oceanfront exterior, large grassy lawn, palm trees, walking distance to sea.
**Brand theme:** ocean blue (#0A4A7A) + white, accented with warm wood/cream (#C8A97E / #FFF8EC) matching interior furniture.

## 2. Business Process Flow

```
GUEST (no login)
  1. Browse landing (hero: exterior + lawn) -> Rooms -> Room details (gallery + amenities)
  2. Pick check-in / check-out + room -> live availability check (AJAX)
  3. Fill direct form: Name, Phone, Email, No. of Guests
  4. Choose payment option: Advance | Full
  5. Choose method: Cash (pay on arrival) | GCash | Maya (+ proof screenshot upload)
  6. Submit -> Booking Status = Pending, Payment Status = Pending
  7. See confirmation with reference # + total (nights x price)

ADMIN (admin/123, session-guarded)
  1. Login -> Dashboard (stats: Pending / Checked-In Now)
  2. Calendar (FullCalendar, Approved bookings block dates)
  3. Review Pending list + proof image
  4. Approve (overlap-checked: same room, check_in < new_out AND check_out > new_in blocks) or Decline
  5. Verify payment: Pending -> Advance Paid -> Fully Paid
  6. Guest lifecycle: Approved -> CheckedIn -> CheckedOut -> Completed
```

Overlap rule: only `Approved` / `CheckedIn` block inventory. `Pending` holds do not block until approved.

## 3. Tech Stack

- Backend: PHP 8.2 native (no framework), mysqli prepared statements
- Database: MariaDB 10.4 / MySQL (via XAMPP), schema in `database.sql`
- Frontend: HTML5, CSS3, vanilla JS, Bootstrap 5.3 (CDN), FullCalendar 6 (admin only)
- Local server: XAMPP (Apache + MySQL) — PHP 8.2.12 verified
- Images: real photos in `assets/images/room1|room2|outside/`, originals in `Images of the Place/`

No build step, no composer, no npm.

## 4. Project Structure

```
index.php, rooms.php, room-details.php, booking.php, booking-success.php
check-availability.php (JSON: available, nights, total)
config/db.php, config/helpers.php
includes/header.php, includes/footer.php
assets/css/style.css, assets/js/main.js, assets/images/...
uploads/proofs/ (GCash/Maya screenshots)
admin/login.php, logout.php, auth.php, dashboard.php, calendar-events.php
database.sql, install.php
README.md, CHANGELOG.md, session.md
```

## 5. Run on a New Device (Fresh Install)

Required download first: **XAMPP for Windows with PHP 8.2** from https://www.apachefriends.org/

1. Install + open XAMPP Control Panel -> Start **Apache** + **MySQL**
2. Copy this project folder to `C:\xampp\htdocs\EAVS Homestay Website Development\`
   (URL-encode spaces: `http://localhost/EAVS%20Homestay%20Website%20Development/index.php`)
   Short alias also works if you copy to `C:\xampp\htdocs\EAVS\`
3. Create DB either way:
   - Option A (recommended): visit `http://localhost/<folder>/install.php` (creates DB + admin hash)
   - Option B: `http://localhost/phpmyadmin` -> Import `database.sql`
4. Login admin: `http://localhost/<folder>/admin/login.php` — user `admin`, pass `123`
5. Guest site: `http://localhost/<folder>/index.php`

DB defaults: host `localhost`, user `root`, pass empty, db `eavs_homestay`.

## 6. Tomorrow's Plan (Design-First)

1. Lock design system (colors, fonts, buttons, cards) before backend edits
2. Refine landing/rooms interactivity (gallery, date UX, mobile)
3. Backend polish only after UI frozen
4. GitHub push (planned)

See `CHANGELOG.md` for history and `session.md` for AI-agent handoff.
