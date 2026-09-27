# session.md — AI Agent Handoff (read every new session)

> Token-saving file. Read this + README.md first. Do not re-scan images unless design changes.

## Status (admin redesign done)
- Admin shell: `admin/layout.php` + `assets/css/admin.css` (sidebar: Dashboard/Booking/Rooms/Users/Logout). Pages: dashboard (KPIs+analytics+calendar), bookings (search/modals/unapprove/edit), rooms (CRUD), users (same shell).
- Revenue=Fully Paid sum. Edit=dates+guests+pay. All state changes behind confirm modal.
- Next: midterm demo rehearsal + GitHub push.
- Source of truth: `C:\Users\user\OneDrive\Desktop\BSIT 3A 1ST SEM\SIA 101 - SYSTEM INTEGRATION & ARCHITECHTURE 1\EAVS Homestay Website Dvelopment\`
- Mirror (must update on every change): `C:\xampp\htdocs\EAVS Homestay Website Development\` (renamed from `\EAVS\` tonight) + keep `\EAVS\` alias if exists
- Do NOT edit `Images of the Place/` originals. Web images live in `assets/images/room1|room2|outside/`.

## Stack / Env
- XAMPP: PHP 8.2.12 (`C:\xampp\php\php.exe`), MariaDB 10.4.32 (`C:\xampp\mysql\bin\mysql.exe`), Apache+MySQL running
- DB: `localhost/root/''/eavs_homestay`. Import `database.sql` or run `install.php`. Admin `admin/123`.
- Frontend: Bootstrap 5.3 CDN, vanilla JS, FullCalendar 6 (admin). No composer/npm.
- Lint: `& "C:\xampp\php\php.exe" -l <file>`. Test: `Invoke-WebRequest http://localhost/EAVS...` (use `%20` path if spaces).

## Conventions
- Guest booking: no login. Overlap blocks only on Approved/CheckedIn (`check_in < new_out AND check_out > new_in` in `config/helpers.php:is_room_available()`).
- Payment: type Advance/Full, method Cash/GCash/Maya, proof required if not Cash (`uploads/proofs/`).
- Admin guard: `admin/auth.php` session. Actions via GET in `admin/dashboard.php` (approve overlap-checked).
- Prices: both ₱3,000 for now. Room 1 = scooter badge. Room 2 package TBD.
- Sync rule: every source edit -> copy same relative path to mirror folder(s). Verify with 200 check.

## Tomorrow's Order
1. Design system lock (colors #0A4A7A/#FFF8EC/#C8A97E, components) 2. UI/interactivity 3. Backend 4. GitHub (`eavs-homestay` planned, ignore `uploads/proofs/*`, decide on `Images of the Place/`).
