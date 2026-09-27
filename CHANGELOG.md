# Changelog — EAV's Homestay

## [v0.1.0] — 2026-09-21 (Tonight)
### Added
- Initial guest site: landing (exterior+lawn hero), rooms, room-details gallery, booking flow (no login), success page, AJAX availability
- Booking fields: Name/Phone/Email/Guests + Advance/Full + Cash/GCash/Maya + proof upload to `uploads/proofs/`
- Admin: login/logout, dashboard stats, FullCalendar, approve/decline/check-in/check-out/complete, payment verify (Pending/Advance Paid/Fully Paid), proof viewer
- DB: `rooms`, `bookings`, `admins` + seed 2x ₱3,000 rooms + admin `admin/123`, overlap-safe logic
- Assets: 17 real photos wired from `Images of the Place/` to `assets/images/`
- Theme: ocean blue + white + wood/cream, Bootstrap 5

### Fixed
- `database.sql`: replaced placeholder admin hash with real bcrypt for `123` (verified), idempotent room seeds (fixed IDs 1/2)
- PHP lint clean on PHP 8.2.12, DB import tested, site returns HTTP 200 on XAMPP

### Deployed
- Mirror: `C:\xampp\htdocs\EAVS\` (to be renamed to `EAVS Homestay Website Development`)
- URLs: `/index.php`, `/booking.php`, `/admin/login.php`

## [v0.2.0] — Offline mode (today)
- Vendored `assets/vendor/bootstrap.min.css`, `bootstrap.bundle.min.js`, `fullcalendar.min.js` — zero CDN at runtime
- Rewired `includes/header.php`, `includes/footer.php`, `admin/login.php`, `admin/dashboard.php` to local vendor
- Admin calendar offline fallback: PHP list table shows when FullCalendar JS unavailable
- Verified offline: index 200, login 200, vendor CSS 200, check-availability JSON OK, bookings table reachable

## [v0.5.0] — Admin redesign (today)
- New shared shell `admin/layout.php` + `assets/css/admin.css`: left sidebar (Dashboard/Booking/Rooms/Users/Logout, owner-gated Users), header with title + welcome fullname + role + initial avatar. Matches landing theme, collapses on mobile.
- Dashboard: KPIs (Pending, Checked-In Now, Total Check-In, Revenue=Fully Paid sum) + CSS-bar analytics (by status, revenue by month) + calendar (kept fallback).
- Bookings page: search (guest/phone/email + status + room, prepared), View modal, Approve/Unapprove(Pending)/Edit(dates+guests+pay, overlap re-checked, total recalculated)/Check-in/out/Complete/Decline + Adv/Full pay — all state changes behind Bootstrap confirm modal.
- Rooms CRUD page: add/edit/delete with active-booking delete guard + cover preview + View link.
- Users restyled into same shell + username search. Fixed stale `filter` warning (page rewritten + resynced).
- Verified: approve→unapprove→pay→edit→rooms add/delete round-trips OK, guest data intact.

## [v0.4.1] — Fix stale-asset sync
- Root cause: earlier sync copied style.css/main.js into `assets/` root instead of `css/`/`js/` — server kept serving old JS (modal, no navigate) + old CSS (no visibility:hidden) = yellow bleed + stuck wipe. Removed strays, synced correct paths.
- Bumped assets to `?v=20250929`. Verified served JS navigates + CSS hides curtain + login still 200.

## [v0.4.0] — Curtain wipe + staff accounts (today)
- Curtain: Admin nav rolls down blur wipe, navigates to single `admin/login.php`; Back to site rolls up. No duplicate login card, no scrollbars.
- Staff (Option A, owner-only): `admins.role/active`, `admin/users.php` create/toggle/delete (last-owner guard), staff blocked from Users (403), deactivated logins rejected.
- Hardened dashboard filter whitelist. Verified: owner create → staff login → dashboard 200 → users 403 → deactivate → login blocked.

## [v0.3.0] — Telly-inspired design (reversible)
- Backup: `_backup_original_20250928/` (index/rooms/details/booking + header/footer + style.css). Revert = copy back.
- New Telly order on landing: full-bleed hero + Welcome + amenities-as-activities + spec-table room cards + booking bar + contact split + gray footer
- Pill-arrow buttons, Georgia serif headings (offline system fonts), section spacing. Still 100% offline (vendored Bootstrap).
- Logic untouched: booking validation, availability, admin approve/pay flow identical.

## [Unreleased] — Next (Design, then Functionality)
- Lock design system (palette, type, components) before backend changes
- Landing/rooms interactivity + mobile refinements
- Backend polish after UI freeze
- GitHub push
