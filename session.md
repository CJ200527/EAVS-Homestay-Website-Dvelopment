# session.md — AI Agent Handoff (read every new session)

> Token-saving file. Read this + README.md + AGENTS.md first. Do not re-scan images unless design changes.

## Status (v0.12.3 current, handoff 2026-10-18)
- **v0.12 "Instant Reservation" shipped.** Bookings now confirm instantly and reserve their dates (`Confirmed` blocks inventory) — no admin approval, so two guests can no longer both pay for the same night. Check-then-insert is transaction + `FOR UPDATE` guarded.
- Money: `reservation_fee` ₱1,000 non-refundable + `amount_paid`; refund = `max(0, amount_paid − 1000)`. Advance/Fult intent kept; `Advance Paid` status removed; **Mark Fully Paid requires a proof photo**.
- **Email fully removed** (form + DB column). **Terms & Conditions** open in a modal over the booking form (no page hop, input preserved); standalone `terms.php` kept + footer-linked. Required, server-side enforced. Check-in 2PM / out 11AM surfaced on form + receipt.
- Admin actions: View · Edit · Mark Fully Paid · overflow(Check in/out, Complete, Decline). Approve/Unapprove gone.
- **DB is empty (0 bookings)** — owner wiped it to test the new flow himself. Proofs folder empty but upload flow intact.
- ⚠️ **Do not regress the overlap guard.** `IN ('Confirmed,CheckedIn')` (one quoted string) matched nothing and silently allowed double bookings — it shipped once. Always use `blocking_status_sql()`.
- ⚠️ `verify.ps1` now POSTs the booking form; GET-only checks missed four fatal errors that lint and HTTP 200 both passed.
- Open: git still 1 commit/unpushed; Group B guest emoji→SVG; T11 hosting deferred; T4/T8/T10 still 0%.
- **Deep Ocean Glass live on ALL 4 admin pages** (owner approved pilot and rolled out). Notification + filter panels are now true frosted glass; filter panel opens left.
- Emoji removed from admin toasts (`🔔` U+1F514 was rendering as `??` — astral-plane, no emoji font). **Guest site still has 20 emoji** in footer/index/room-details/booking — agreed to swap for inline SVG, NOT yet done.
- Dashboard: revenue = pointy crypto area chart (`L`-only, no smoothing) with `3/6/12m` filter; occupancy has `30/60/90d` filter; timeline shows its date range; uniform `--dash-gap` rhythm.
- Verified: 200 on 12 routes, zero new Apache errors (caught+fixed a `$f['cat']` regression this pass).
- Still open: #1/#2 sit in `Approved` from September (inflates 30-day occupancy) — needs owner call. T11 hosting deferred. T4 mockups, T8 maps/payments, T10 UAT still 0%. Git: 1 commit, nothing pushed.
- **Deep Ocean Glass pilot** live on Dashboard + Booking (`body.theme-ocean`, oceanfront photo + gradient scrim + frosted panels, cyan + wood accents). Rooms/Users still Navi pending owner verdict.
- Data reconciled + seeded: #6/#8 CheckedOut, #7 Declined; #9 Maria CheckedIn R1, #10 Paolo Pending R2, #11 Ana Approved+Paid R1, #12 Liza CheckedOut+Paid R2. Revenue Sep ₱21k + Oct ₱18k.
- Verified: 200 on 12 routes, zero new Apache errors, overlap guard + Pending-does-not-block both proven, GCash proof upload exercised.
- **Repo fix done:** 17 photos restored to source `assets/images/` (source had only `logo.jpg`).
- Gantt Progress column corrected to reality (dates/scope untouched).
- Still open: #1/#2 sit in `Approved` from September (inflates 30-day occupancy to 28%) — needs owner call on marking `Completed`. T11 hosting deferred by owner. T4 mockups, T8 maps/payments, T10 UAT still 0%.
- Git: still 1 commit, changes uncommitted/unpushed — needs owner go-ahead.
- Guest site Telly-styled + offline (vendored Bootstrap/FullCalendar, `?v=20251008`). Curtain login card final (flex-centered, two-step roll-up). Booking success = centered receipt modal + canvas PNG save (Save violet / Confirm green / Close blue, white text locked).
- Admin suite, ALL pages Navi theme (`body.navi`, Aura retired): `admin/layout.php` shell `($title,$active,$glass,$navi)` — categorized sidebar + logout bottom + SVG `a-*` sprite, unified glass strip header (title + actions + bell + user), flash toasts, bell activity, skeleton on `?welcome=1`, cascade unveil, uniform toolbars (`[search+refresh] … [funnel+Create]`, `.tool-left`/`.tool-right`), centered modals.
- Dashboard order: strip → uniform KPIs (absolute-change chips, no %) → Timeline → [Revenue bars | Occupancy + available tonight] → Recent Activity full-width. Room Rented + calendar-month removed.
- Rooms page: 4 KPI cards (Total Units · Free Tonight · Active Stays · Total Stays, absolute counts, filter-independent).
- Booking page: 4 KPI cards (Needs Review · Today's Movements · Awaiting Payment · Total Bookings, Review links to `?filter=Pending`).
- Users page: 4 KPI cards (Total · Active · Owners · Staff-zero, all clickable to exact slices).
- Clickable KPIs: whole-card `.stretched-link` only where destination shows exactly that slice (9 cards); others static, never fake links.
- Decisions locked: Revenue = Fully Paid sum. Edit = dates+guests+pay. Rooms pending/active approval (guests see active-only; cover upload required). No public signup (owner creates staff).
- Live data 2026-10-06: 5 bookings (#1 Approved/Fully Paid, #2 Approved/Fully Paid, #6 CheckedIn/Pending, #7 Pending/Pending, #8 Approved/Pending — #6–8 are owner's live Oct tests, DO NOT delete); 2 rooms active; 1 admin (admin/owner).
- Next: midterm demo rehearsal (WiFi off) -> GitHub push (`eavs-homestay` planned; ignore `uploads/proofs/*`).
- Source of truth: `C:\Users\user\OneDrive\Desktop\BSIT 3A 1ST SEM\SIA 101 - SYSTEM INTEGRATION & ARCHITECHTURE 1\EAVS Homestay Website Dvelopment\`
- Mirror (update every change): `C:\xampp\htdocs\EAVS Homestay Website Development\` — exact relative paths.

## Stack / Env
- XAMPP: PHP 8.2.12 (`C:\xampp\php\php.exe`), MariaDB 10.4.32 (`C:\xampp\mysql\bin\mysql.exe`), Apache+MySQL running
- DB: `localhost/root/''/eavs_homestay`. Import `database.sql` or run `install.php`. Admin `admin/123` (owner).
- Frontend: local vendor only. No composer/npm. System fonts (Georgia + Segoe UI).
- Lint: `& "C:\xampp\php\php.exe" -l <file>`. Test: `Invoke-WebRequest http://localhost/EAVS%20Homestay%20Website%20Development/...`

## Conventions
- Overlap blocks only on Approved/CheckedIn (`config/helpers.php:is_room_available()`).
- Payment: type Advance/Full, method Cash/GCash/Maya, proof required if not Cash (`uploads/proofs/`).
- Admin guard: `admin/auth.php` (+ `require_owner()`). All booking/user/room state changes behind centered confirm modal.
- Rooms: `status` pending/active; guests (landing/rooms/booking/details/availability) see active-only. Cover upload required on create.
- Prices: both ₱3,000. Room 1 = scooter badge. Room 2 package TBD.
- Sync rule: every source edit -> mirror same relative path (`css/`+`js/` subfolders, never `assets/` root — that caused v0.4.1 stale-asset bug). Bump `?v=` after CSS/JS edits (current `?v=20251008`). Verify with 200 check.
- Do NOT edit `Images of the Place/` originals. Web images: `assets/images/room1|room2|outside|rooms/`, logo `assets/images/logo.jpg`.
- Test rows: remove only rows the agent created for verification; NEVER delete owner's live rows (#6–8) without explicit confirmation. No file deletions without explicit owner confirmation.
