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
**Free Wi-Fi** is provided throughout the homestay (network details are given to the guest at check-in — deliberately not published on the public site).
**Check-in from 2:00 PM · Check-out by 11:00 AM.**
**Brand theme:** ocean blue (#0A4A7A) + white, accented with warm wood/cream (#C8A97E / #FFF8EC) matching interior furniture.

## 2. Business Process Flow

```
GUEST (no login)
  1. Browse landing (hero: exterior + lawn) -> Rooms -> Room details (gallery + amenities)
  2. Pick check-in / check-out + room -> live availability check (AJAX)
  3. Fill direct form: Name, Phone, No. of Guests  (NO email is collected or stored)
  4. Agree to the Terms & Conditions (required checkbox + terms.php)
  5. Choose payment option: Advance (₱1,000 reservation fee) | Full, and method: Cash (on arrival) | GCash | Maya (+ proof screenshot for e-wallets)
  6. Submit -> booking is CONFIRMED INSTANTLY and the dates are reserved; receipt modal opens (Save PNG / Confirm / Close)

  v0.12 changed this deliberately. Previously a booking sat at Pending until an
  admin approved it, and Pending did NOT block inventory -- so two guests could
  pay for the same night and the second one got rejected after paying. Now a
  booking reserves its dates the moment it is created.

ADMIN (session-guarded, roles: owner / staff)
  1. Click Admin in nav -> blurred curtain rolls down with Welcome Back card (Back rolls it up); or open `admin/login.php` directly
  2. Login (owner `admin/123`; staff created by owner, no public signup) -> Navi-themed shell: categorized sidebar (Dashboard, Booking, Rooms, Users owner-only, Logout bottom), unified glass strip (title + actions + bell + user)
  3. Dashboard: KPI sparklines, room timeline, Revenue (pointy area chart + 3/6/12m filter), Occupancy (30/60/90d filter), Recent Activity
  4. Booking page: uniform toolbar `[search+refresh] … [funnel+Create]` -> table -> View / Edit / Mark-Fully-Paid + overflow (Check-in, Check-out, Complete, Decline). No approval step. Declining releases the dates.
  5. Rooms page: check/add/edit/delete rooms (new rooms start Pending, Approve goes live; delete blocked if bookings exist)
  6. Users page (owner): create staff/owner, on/off toggle, delete (last owner protected)
```

**Overlap rule (v0.12):** a booking reserves its dates **the instant it is created** — `Confirmed` and `CheckedIn` block inventory; only `Declined` releases the dates. Check-then-insert runs inside a transaction with `SELECT … FOR UPDATE` on the room row, so two simultaneous submissions cannot both claim the same night.

**Payment model:** `total = nights × ₱3,000`. A **non-refundable ₱1,000 reservation fee** is deducted. `Advance` pays the fee only; `Full` pays the total; `Cash` collects nothing up front. `amount_paid` records what was actually received so the cancellation refund is computable: refund = `max(0, amount_paid − 1,000)`. The admin's **Mark Fully Paid** action requires a proof photo.

## 3. Tech Stack

- Backend: PHP 8.2 native (no framework), mysqli prepared statements
- Database: MariaDB 10.4 / MySQL (via XAMPP), schema in `database.sql` (+ `install.php` migrator)
- Frontend: HTML5, CSS3, vanilla JS, Bootstrap 5.3 + FullCalendar 6 **vendored locally** (`assets/vendor/`, zero CDN at runtime = offline-ready)
- Design: Telly-inspired layout, Georgia serif headings (system fonts, no webfonts), pill-arrow buttons — see `AGENTS.md`
- Local server: XAMPP (Apache + MySQL) — PHP 8.2.12 verified
- Images: real photos in `assets/images/room1|room2|outside/`, originals in `Images of the Place/` (never edit)

No build step, no composer, no npm.

## 4. Project Structure

```
index.php, rooms.php, room-details.php, booking.php, booking-success.php
check-availability.php (JSON: available, nights, total)
config/db.php, config/helpers.php
includes/header.php (curtain trigger), includes/footer.php (curtain card)
assets/css/style.css, assets/css/admin.css, assets/js/main.js, assets/js/admin.js, assets/vendor/, assets/images/... (incl. `logo.jpg`, `rooms/` uploads)
uploads/proofs/ (GCash/Maya screenshots)
admin/login.php, logout.php, auth.php, layout.php, dashboard.php, bookings.php, rooms.php, users.php, calendar-events.php
database.sql, install.php
README.md, CHANGELOG.md, session.md, AGENTS.md
_backup_original_20250928/ (pre-Telly backup, revert source)
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

## 6. Status & Next

Current: v0.9.5 — Navi admin on all pages (Aura retired), Telly guest site (offline), receipt modal + PNG save, room approval workflow, dynamic landing. Live data: 5 bookings (2 Sept + 3 Oct tests), 2 rooms active, 1 admin.

Next: midterm demo rehearsal (WiFi off) -> GitHub push (`eavs-homestay` planned; ignore `uploads/proofs/*`).

Sync rule: source of truth is this folder; mirror every change to `C:\xampp\htdocs\EAVS Homestay Website Development\` (exact relative paths — `css/`+`js/` subfolders, never `assets/` root). Bump `?v=` on CSS/JS after style/script edits. See `CHANGELOG.md` for history, `session.md` for handoff, `AGENTS.md` for agent rules.
