# Changelog — EAV's Homestay

## [v0.12.3] — Cash requires proof, walk-in proof, centering, draft removed (today)
- **Proof of payment is now required for EVERY method, cash included.** A face-to-face handover is still evidence and settles disputes about what was paid. Server guard drops the Cash exemption; upload relabelled *"Required for every payment method, cash included. jpg/png/webp, under 2MB."* and marked `required`. Removed `paintProof()` in `booking.php` **and** the duplicate `toggleProof()` in `assets/js/main.js` (a second copy of the same hide-for-Cash logic).
- **Money model unified:** `amount_paid` now follows `payment_type` for all methods — Advance → ₱1,000 `Part Paid`, Full → total `Fully Paid`. The old "Cash = collect nothing up front" case is gone, because a proof now means money changed hands.
- **Admin walk-in accepts proof.** The walk-in form was `<form method="post">` with **no `enctype`**, so a file upload would have silently failed. Added `enctype="multipart/form-data"`, a required `walkin_proof` field, and a validating handler that stores `proof_image`. This is the path for a guest who is already at the homestay and can't upload.
- **Form draft removed entirely** (`saveDraft/loadDraft/clearDraft`, their listeners, and the `sessionStorage` cleanup in `booking-success.php`). A refresh now always gives a blank form. Validation-error recovery is unaffected — `$pref` repopulation still preserves input after a failed submit.
- **Centering:** `Your Details` heading and the Book Now block (button + reservation-fee note) are centred; form fields stay left-aligned.
- **Terms §2 updated** to state proof is required for every method including cash, and that the homestay can create a booking on a guest's behalf when they're already at the property. Flows to the standalone page and the modal automatically.
- **Fixes found while building:** duplicate Phone field in the walk-in modal (left over from removing Email in v0.12); `main.js`'s own `checkAvail()` was competing with `booking.php`'s richer price breakdown for `#availMsg`/`#totalHint` — now guarded so the booking page owns those; walk-in `bind_param` type string was 14 types for 13 placeholders (fatal on every walk-in, second time this mistake was made in this codebase).
- **`verify.ps1` gained an admin walk-in POST test** — the guest-form test could not catch that defect. Verified by hand: Cash without proof rejected, Cash+proof records ₱1,000 `Part Paid`, Full+Cash records total `Fully Paid`, walk-in stores its proof, and the walk-in race guard is intact.

## [v0.12.2] — Dropdown clipping, centered toasts, form copy trim (today)
- **Admin row action menu no longer clipped.** `.table-responsive` is `overflow-x:auto`, and CSS makes a `visible` cross-axis compute to `auto` — so the wrapper clipped the ⋯ dropdown mid-item. `.book-table .table-responsive{overflow:visible}`, restoring `overflow-x:auto` under 860px for phones. Fixing the container covers every row; `dropup`/padding would only patch the last row and break on any row count.
- **Guest notifications are now centered pop-up toasts** (`.toast-wrap` + `.eav-toast` in `style.css`, guest-styled — the admin keeps its own dark toasts). Auto-dismiss at **8s for errors, 4s for info/success**, click-to-dismiss, stacked, `aria-live="polite"`, `prefers-reduced-motion` respected. Server-side `$errors` now fire as toasts instead of inline alert boxes, and the native `alert()` in the terms gate became a toast (a blocking browser dialog was jarring and inconsistent). `#availMsg` stays an inline strip — it is a live status indicator tied to date selection, not a transient message.
- **Form reset after a successful booking.** `booking-success.php` clears the `eavs_book_draft` sessionStorage draft on load, so the next visit to `booking.php` is a clean form instead of silently refilling the previous guest's details. Added a "Book another stay" button on the receipt.
- **Copy trims:** reservation-fee note moved *below* the Book Now button; removed "Opening the terms keeps everything you have typed here."; `Cash (on arrival)` → `Cash`; removed the "Instant confirmation…" info block. Check-in/out times remain on the page via the Room Details spec table (plus terms modal + receipt), so the AGENTS.md rule still holds.
- Assets `?v=20251017`. Verified: lint clean, `verify.ps1` ALL GREEN, every copy/state change confirmed present in the served output.

## [v0.12.1] — Terms as a modal + agreement sync fix (today)
- **Terms now open in a modal over the booking form**, so the guest never leaves the page and nothing they typed is lost — nothing is submitted or reloaded, so the fields are preserved structurally (no restore needed). Modal is `modal-lg modal-dialog-centered modal-dialog-scrollable`, placed *after* `</form>` so it sits outside the booking form's submit scope; trigger is `type="button"` so it can never submit the form.
- Modal footer: `Close` (dismiss, nothing lost) + **`I Agree & Continue`**, which ticks the checkbox, syncs the hidden field, clears `is-invalid`, saves the draft, closes, and scrolls the guest to the Book Now button.
- **Bug fixed — hand-ticking the checkbox did nothing.** `agreeChk` has no `name` attribute, so it was never submitted; the hidden `agree_terms` input is what the server reads, and nothing kept them in sync. A guest who ticked the box by hand and pressed Book was rejected with *"You must agree to the Terms & Conditions"* despite having agreed. `syncAgree()` now writes the hidden field in both directions and is wired to the change handler, the modal button, and the sessionStorage draft.
- **Single source of truth:** terms markup moved to `includes/terms-content.php`, included by both `terms.php` (kept as the standalone/printable page, now linked from the footer Quick Links) and the booking modal — so the wording can only be edited in one place.
- Assets `?v=20251016` (header + footer). Verified: lint clean, `verify.ps1` ALL GREEN, modal renders all 10 sections after the form, agreement POST reaches the receipt.

## [v0.12.0] — Instant Reservation: no approval step, real double-booking protection (today)
**Why:** bookings sat at `Pending` until an admin approved, and `Pending` did not block inventory — so two guests could book the same room+dates, both pay (Advance/Full + proof), and the second was rejected only *after* paying. A booking now reserves its dates the instant it is created.

### Changed
- **Status model:** `booking_status ENUM('Confirmed','Declined','Completed','CheckedIn','CheckedOut')` — `Pending` and `Approved` both removed. Guest *and* walk-in bookings insert as `Confirmed`. Admin actions are now Check in · Check out · Complete · **Mark Fully Paid** · Decline; Approve/Unapprove removed. `Declined` is the only path that releases dates.
- **Overlap guard now actually works.** `Confirmed`/`CheckedIn` block inventory, rendered via `blocking_status_sql()` as a quoted list.
- **Race guard:** check-then-insert moved inside `begin_transaction` + `SELECT … FROM rooms WHERE id=? FOR UPDATE`, so two simultaneous submissions cannot both claim the same night.
- **Money model:** added `reservation_fee` (₱1,000, non-refundable) and `amount_paid`. Advance pays the fee, Full pays the total, Cash collects nothing up front. `refundable_on_cancel() = max(0, amount_paid − 1,000)` makes the cancellation rule computable. `payment_status` is now Unpaid|Part Paid|Fully Paid; **Advance Paid removed**. **Mark Fully Paid requires a proof photo** and hides itself once paid.
- **Email removed** from the guest form, admin table/search/modal, walk-in form and receipt — and `DROP COLUMN email` from `bookings` (the collected PII is actually gone).
- **Terms & Conditions** (`terms.php`, owner-drafted): required checkbox, enforced **server-side**, plus a client-side reminder. Check-in 2:00 PM / check-out 11:00 AM shown on the form, the terms page and the receipt.
- **Form resilience:** every field repopulates from `$pref` on validation failure (payment_type/method previously silently reset), plus a `sessionStorage` draft so a 500 can't wipe a half-filled form. Live price breakdown + availability hint.
- **Ripple:** dashboard KPI "Pending"→"Confirmed", bookings KPI "Needs Review"→"Arriving Today", bell + timeline legend/colours, calendar colours, rooms delete guard, receipt PNG rows. README gains free Wi-Fi + booking rules.
- **Data:** all bookings deleted for a clean slate (owner-approved); orphaned proofs removed; backup at `tools/../database-backup-20261018.sql` (temp).

### Bugs found and fixed while testing
- **Overlap guard was a no-op.** Refactoring the blocking statuses into a constant produced `IN ('Confirmed,CheckedIn')` — ONE string matching no row, so every booking was accepted. Caught only by an explicit double-booking test. This silently disabled the most important business rule; `AGENTS.md` now forbids that exact string.
- `bind_param()` cannot take a **constant** by reference (fatal on every guest booking) → bind a variable.
- Wrong `bind_param` type string (14 types for 13 placeholders) → fatal; fixed in both guest and walk-in inserts.
- Proof upload wrote to `uploads/proofs/uploads/proofs/...` (doubled path) in both booking and admin proof handlers.
- `$pref` missing `guest_name`/`phone`/`num_guests` defaults → PHP warnings on first page load.

### Verification
`tools/verify.ps1` gained a **POST smoke test** — GET-only checks could not see any of the fatal errors above (the pages returned 200 and linted clean while every submission died). It now asserts: booking confirms instantly, same room+dates is rejected, terms gate blocks. Verified by hand: back-to-back bookings (out Nov 12 / in Nov 12) still allowed, true overlaps rejected, Mark Fully Paid rejects a missing proof, walk-in/check-in/decline work, and `?action=Approved` is now ignored.

## [v0.11.4] — Notification/filter panel layering + legibility (today)
- **Layering root cause found:** `backdrop-filter` creates a stacking context. `.head-strip` gets glass blur but had **no `position`/`z-index`**, so it became a stacking context at `z-index:auto` — the notification panel's `z-index:500` was trapped inside it and could only compete with siblings *within* the strip, so the KPI `.row` following it in the DOM painted on top. The toolbar never had this bug because `.ad-card.tool-card` already carries `position:relative;z-index:30` (added v0.8.0); the header strip was simply missed.
- Fix: `.head-strip{position:relative;z-index:600;}` — beats `tool-card`'s 30, and stays trapped inside `.ad-main`'s own `z-index:1` context so the sidebar (z-index 100) is unaffected. `.notif-wrap` remains the panel's containing block, so its `right:0` anchoring is unchanged.
- **Legibility:** panel base raised `rgba(8,32,52,.62)` → `rgba(7,28,48,.90)` with blur `22px` → `30px saturate(180%)`. At 62% opacity, high-contrast card text punched through and collided with panel text; blur alone only softens it, so opacity is what actually fixes it. Brighter hairline border + deeper shadow. `@supports` fallback stays near-solid.
- **Filter panel investigated:** structurally sound (lives inside the properly-stacked `.tool-card`), so no behavioural fix was needed — given the same treatment for visual consistency and `.filter-wrap` raised to `z-index:600` as belt-and-braces.
- Assets `?v=20251015`. Verified: rules present in served CSS, `verify.ps1` ALL GREEN, zero new Apache errors.

## [v0.11.3] — Orphaned `?>` bug + verification gate (today)
- **Fixed a bug I introduced in v0.9.8–v0.10.0:** when the KPI rows were added to Rooms/Bookings/Users, the inserted markup opened HTML mode with its own `?>` while the original file's `?>` (the one after `admin_strip(...)`) was left behind as literal text — so PHP, already in HTML mode, echoed a visible `?>` between the KPI row and the toolbar. Removed the stray tags at `users.php:70`, `rooms.php:107`, `bookings.php:109`. Dashboard + login were never affected (their `?>` tags all follow real PHP statements).
- **Why lint missed it:** an orphaned `?>` in HTML mode is *valid* PHP — it just prints two characters. Page returned 200 and my earlier greps only looked for `toast-icon`, so the leak went unnoticed for several blocks.
- **New `tools/verify.ps1` gate** (runs after every change block): source↔mirror MD5 parity, full PHP lint, **rendered-output leak scan** (no `?>`, no `toast-icon`, no PHP warnings echoed), route status, and baseline-aware Apache error-log delta. Negative-tested: injecting a stray `?>` into the mirror correctly fails the gate.

## [v0.11.2] — Rail/content dead-space fix (today)
- Sidebar is a **floating** card (`left:1rem`, `width:230px`) but `.ad-main` only cleared `230px`, and `.ad-body`'s padding added a second inset — leaving a dead gap between the navigation rail and the first card.
- Introduced `--rail-w` / `--rail-inset` tokens; `.ad-main` now clears `calc(rail-w + rail-inset)` so the rail no longer overlaps the content column, and `.ad-body` keeps `.5rem` left padding because Bootstrap rows carry a negative gutter margin (`-.5 * gutter`) that must cancel for cards to sit **flush** against the rail.
- Mobile reset re-asserted (`@media(max-width:860px)`) since the new rule lands after it.
- Assets `?v=20251014`. Verified: rules present in served CSS, 200 on all 4 admin pages, zero new Apache errors.

## [v0.11.1] — Emoji removal, glass panels, filters, area chart (today)
- **Removed the `??` toast glyph.** Root cause: `🔔` U+1F514 (astral-plane emoji) was emitted by both `config/helpers.php` and `assets/js/admin.js`; machines without a colour-emoji font render it as `??`. Icon dropped from both toast builders — toast is now message + close button. Verified: no `toast-icon` in rendered HTML.
- **Notification panel = real glass** (was solid `#163038` from the Navi block, so page text bled through): `blur(22px) saturate(160%)`, brighter title with separator, row hover tint, `@supports` fallback.
- **Filter panel opens LEFT** (`left:auto;right:0`) — the funnel button lives in right-aligned `.tool-right`, so `left:0` pushed it off-card and clipped it. Fixes Bookings/Rooms/Users.
- **Uniform vertical rhythm.** Regression fix: `.row > .ad-card` was a *direct-child* selector, so wrapping the timeline in `col-12` silently re-enabled its `margin-bottom:1rem`, making timeline→analytics 32px vs header→KPI 16px. Now `.row .ad-card` (descendant) + `--dash-gap:1rem` token on `.head-strip`, `.ad-body` padding and every row.
- **Timeline shows its window** — `Oct 18 – Nov 1, 2026` chip beside Prev/Today/Next (month-spanning aware); nav preserves active analytics filters.
- **Analytics filters added:** Revenue `3/6/12` months (`?rev=`), Occupancy `30/60/90` days (`?occ=`) as glass segmented controls. Verified honest: 30d=28%, 60d=14%, 90d=9% (17 booked nights over 120/180 available). Independent of `?start=`, no param collisions.
- **Revenue bars → pointy crypto-style area chart:** straight `L`-only polyline (sharp vertices, zero `C` curves / no smoothing), cyan gradient fill, ₱k axis, point tooltips, current month highlighted in white.
- **Theme rolled out to all 4 admin pages** (owner approved pilot): Rooms + Users now `theme-ocean`.
- Fixed a bug I introduced mid-pass: `$cat`/`$f['cat']` in `flash_html()` (caught by the error-log delta check, not by lint).
- Assets `?v=20251013`. Verified: 200 on 12 routes incl. filter/timeline variants, zero new Apache errors.

## [v0.11.0] — Deep Ocean Glass pilot + inspection readiness (today)
- **New admin theme (pilot: Dashboard + Booking).** Oceanfront photo (`outside/528041068…jpg`) → ocean gradient scrim + cyan/wood radial glows → frosted panels (`blur(18px) saturate(140%)`, `rgba(255,255,255,.09)`, hairline border, inset highlight) with `@supports` solid fallback. Wood `#C8A97E` joins cyan `#2dd4bf` as brand accent (first use of wood in admin).
- Shell gains optional 5th param: `admin_shell_open($title,$active,$glass,$navi,$theme)` → `body.theme-ocean` + `.theme-bg`. Rooms/Users untouched (still Navi) so the pilot isolates one variable; rollout later = pass `'ocean'` on two more pages.
- **Layout fixes:** timeline now wrapped in `col-12` (was a bare `.ad-card` child of `.row`); `.row > .ad-card` margin zeroed (its `margin-bottom:1rem` was double-stacking with `g-3` gutters); hardcoded `100/14` ×3 and in-loop `array_search()` replaced by a precomputed `$dayIdx`/`$dayW` map.
- **Data reconciled:** #6/#8 → CheckedOut, #7 → Declined (all ended Oct 6). Seeded 4 demo bookings dated around today (Maria CheckedIn R1, Paolo Pending R2, Ana Approved+Paid R1, Liza CheckedOut+Paid R2) — created through the real `booking.php` endpoint, which also exercised the never-before-tested `uploads/proofs/` GCash path. Revenue now shows Sep ₱21k + Oct ₱18k; timeline has 3 bars (was 0).
- **Tests:** overlap guard verified (conflicting Room 1 stay rejected; Pending correctly does *not* block); 200 on all 12 routes; **zero new Apache error-log entries**; proof upload + admin flow verified. Test rows removed after.
- **Repo fix:** `assets/images/outside|room1|room2` (17 photos) were missing from source — restored, so the GitHub repo is no longer photo-less.
- **Gantt:** Progress column corrected to reality (T1 100, T2 100, T3 90, T5 95, T6 90, T7 80, T9 30, T12 50; T4/T8/T10/T11 honestly 0). Dates/scope untouched.
- Assets `?v=20251012`.

## [v0.10.0] — Users KPIs + clickable KPI cards (today)
- Users page: 4 Navi `.kpi-dash glass` cards (Total Accounts · Active · Owners · Staff-zero, absolute counts) — every card whole-card clickable to its exact slice (`users.php`, `?factive=1`, `?frole=owner`, `?frole=staff`).
- Click-through everywhere via Bootstrap `.stretched-link` (vendored, offline): dashboard Pending/Checked-In → bookings `?filter=`; Rooms Total → reset; Bookings Review/Total → `?filter=Pending`/reset. Revenue, Occupancy, Movements, Awaiting Payment, Free Tonight, Active/Total Stays stay static (no exact slice — never a fake link). Inner `kpi-more` anchors converted to spans (no nested links).
- affordance CSS: pointer + hover lift + keyboard focus ring (`.has-link`). Assets `?v=20251011`.
- Verified: lint clean ×4 pages, 200 on all pages + all 9 click destinations, DB cross-check (1 account: owner/active; staff slice = honest empty + Reset).

## [v0.9.9] — Booking KPI cards (today)
- 4 Navi `.kpi-dash glass` cards between strip and toolbar (global pipeline, ignore search/filter): Needs Review (pending → `?filter=Pending`), Today's Movements (arrivals/departures), Awaiting Payment (count + ₱ to collect, collection framing), Total Bookings (status split).
- Absolute counts only, no percentages; existing sprite icons, no CSS changes.
- Verified: lint clean, bookings 200, DB cross-check (1 review · 0 in/2 out · 3 unpaid ₱9,000 · 5 total).

## [v0.9.8] — Rooms KPI cards (today)
- 4 Navi `.kpi-dash glass` cards between strip and toolbar (global inventory, ignore search/filter): Total Units (2 live), Free Tonight (overlap rule, names listed), Active Stays (`Approved/CheckedIn` + `check_out >= today`, checked-in/approved split), Total Stays (non-declined per-unit leaderboard, no money per owner call).
- Absolute counts only, no percentages; existing sprite icons (`i-bed/k-key/i-book/k-bed`), no CSS changes.
- Verified: lint clean, rooms 200, DB cross-check (2 units · 2 free · 2 active stays · 5 total stays: Room 1 × 3, Room 2 × 2).

## [v0.9.7] — Honest absolute KPI chips (today)
- Removed MoM % deltas (+50% / −100% misled at n=5): chips now show absolute change (`+1 vs Sep`, `−₱21,000 vs Sep`) with hover titles giving the exact month breakdown.
- Revenue sub-row now states this-month collected (`₱0.00 collected in Oct`); neutral muted chip color (no green/red editorializing); `—` when both months are zero.
- Assets `?v=20251010`. Verified: lint clean, dashboard 200, chips match DB (3 vs 2 bookings, ₱0 vs ₱21,000).

## [v0.9.6] — Dashboard simple & convincing (today)
- KPIs uniform Navi frosted (no dark/light split, no sparklines): honest MoM delta or `new` chip, sub-context + Review/View links.
- Revenue rebuilt as single-metric bars (Fully Paid sums, 6 months, ₱k axis, current month teal, value labels + tooltips). Removed crypto 3-line + dual axes + gradient.
- Removed Room Rented card (duplicated occupancy) + hover popup JS + FullCalendar month collapse. Timeline stays the single calendar.
- Occupancy kept (donut + per-room x/30 bars) + new `Available tonight` ops line. Recent Activity now full-width with Open bookings link.
- Style locked to Navi tokens (teal `#2dd4bf` + ocean `#0E6BA8`, muted labels). Assets `?v=20251009`.
- Verified: lint clean, dashboard 200, DB cross-check (₱21k Sep Fully Paid, 1 pending / 1 checked-in, 9/60 nights ≈ 15%), no leftover spark/rent/calendar refs.

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

## [v0.9.5] — Revenue crypto style + Room Rented + reorder (today)
- "Bookings Analytics" renamed Revenue (legend Revenue-first). Crypto concept: jagged lines, green gradient area under revenue, red bookings line.
- New Room Rented card: per-room rental counts per check-in month with hover popup (mini bars per unit, cursor-following, offline JS).
- Order: Header → KPIs → Timeline → [Revenue | Occupancy] → [Room Rented | Recent Activity].
- Verified against DB (per-room month counts match), no PHP errors.

## [v0.9.4] — Profit-led chart + bigger occupancy
- Chart is backend-fed (was only *looking* decorative): dual axes — left ₱ profit ticks (Fully Paid sums), right counts; month names, value tooltips, honest flat baselines.
- Occupancy scaled up (190px donut, larger rows, vertically centered to fill card).
- Verified ticks match DB (₱21k Sep), no PHP errors.

## [v0.9.3] — Analytics upgrade: centered donut + multi-line chart
- Donut % moved out of rotated SVG into an HTML overlay (always horizontal); card contents centered, label untouched.
- Bookings bars replaced by smooth 3-line SVG chart (bookings teal / revenue purple / check-ins yellow, Catmull-Rom, gridlines + legend, all-zero safe); swapped left, occupancy right.
- Verified live, no PHP errors.

## [v0.9.2] — Navi wins, rollout everywhere
- Dashboard converted to Navi (KPIs/analytics/timeline/skeleton re-skinned teal-frosted; neon sparklines kept).
- Aura-dark removed (CSS block + shell flag + per-page calls; tokens preserved in v0.9.0 entry). Shell signature now `($title,$active,$glass,$navi)`.
- Verified: navi on all 4 pages, sparklines intact, no errors. Assets `?v=20251008`.

## [v0.9.1] — Navi frosted-teal pilot on bookings/rooms/users
- `body.navi` theme (gray rail per owner call): turquoise-glow backdrop, frosted panels, white-pill active nav; dashboard stays Aura for comparison.
- Verified: navi on 3 pages, Aura only on dashboard, no PHP errors. Assets `?v=20251007`.

## [v0.9.0] — Aura-dark dashboard pilot
- Full-dark theme scoped to dashboard (`body.aura-dash`): navy bg/panels, dark sidebar + strip + tables/forms, neon SVG sparklines on all 4 KPIs (real monthly data), white numbers. Flat colors = old-browser safe. Other pages untouched.

## [v0.8.4] — Receipt button hover fix
- Root cause: Close cleared its inline background on mouse-out but kept white text → invisible. All three receipt buttons now use stylesheet classes (white text locked in every state); repo-wide scan confirms no inline hover handlers remain.

## [v0.8.3] — Booking receipt modal + save-as-image
- `booking-success.php` keeps its URL but opens a centered receipt modal on load: logo + name + location header, 2-col body (details left, room photo right), Save/Confirm/Close footer.
- Save renders an offline canvas PNG receipt (ocean header, ref/guest/room/dates/total, room photo, pending stamp) and downloads `booking-#id.png`.
- Hovers: Save violet-lighten, Confirm green-brighten, Close ocean-lighten. Confirm → home, Close → dismiss.
- Verified modal markup live; test booking #5 removed, 2 demo bookings intact.

## [v0.8.2] — Centered modals + docs closeout
- All admin modals now `modal-dialog-centered` (create/view/edit on bookings, rooms, users; confirm already was).
- Docs: README + AGENTS.md updated with modal-centering rule.

## [v0.8.1] — Uniform toolbars CPSC order
- All three toolbars now `[search + refresh] … [funnel icon + Create]` via shared `.tool-left`/`.tool-right` (can't drift again).
- Verified: order + icon-only funnel on all pages, filters/search work, no warnings.

## [v0.8.0] — Room approval workflow + toolbar uniformity
- Rooms: `status` pending/active; new rooms start pending (hidden from guests), Approve goes live, Deactivate hides again. Delete guard kept.
- Room creation requires cover photo upload (jpg/png/webp ≤2MB → `assets/images/rooms/`); edit keeps cover unless replaced.
- Landing/rooms/booking/details all query active-only; landing cards + count auto-render (pending 404s on direct URL).
- Users toolbar: [+ New account] [Filter] [search 46%] [refresh]; filter = created dates + active status + role. Rooms toolbar same + search; filter = created dates + status.
- Dashboard strip cleaned (no action buttons). Glass backdrop now pure blue (cream blob removed, all pages).
- Layering fixed at source: toolbar cards `z-30` stacking, Bootstrap modals relocated to `<body>` (glass filters trap fixed positioning).
- Verified end-to-end: pending hidden → approve → live on landing/rooms → deactivate → hidden; upload saves uniquely; test rows removed.

## [v0.7.3] — Glass on Rooms + Users, uniform tables
- Rooms + Users now glass (all 4 admin pages). Tables share frosted style: sticky ocean header, avatar-initials, chip badges, icon actions.
- Users search capped at 46% like Booking. Verified: all pages 200, 1 strip each, rooms edit form OK.

## [v0.7.2] — Booking glass + table redesign (today)
- Dashboard strip: New booking button removed (Booking-only now); Review pending kept.
- Booking page glass (same ocean pilot). Toolbar: search capped at 46%, New booking beside refresh.
- Table: frosted glass card, sticky ocean header, avatar-initial guests, nights chips, zoomable proof thumbs; actions = View/Approve/Edit + ⋯ overflow menu (rest unchanged, confirms kept).
- Verified: strip button gone, approve toast round-trip, data intact.

## [v0.7.1] — Unified header, equal analytics, recent activity
- One header only: `admin_strip()` glass card (title + actions + bell + user) on all pages; old shell bar removed.
- Analytics cards equal height (`h-100` flex). Recent Activity card after timeline (latest 8 bookings → filtered view).
- Verified: 1 strip + bell on all 4 pages, no PHP errors.

## [v0.7.0] — Glass dashboard pilot + KPI/analytics/calendar
- Glass pilot (dashboard only): ocean-mesh backdrop, floating glass sidebar rail, frosted KPI/analytics/calendar cards, `@supports` solid fallback. Other pages untouched; remove `glass` flag to revert.
- KPI cards (Dribbble anatomy, EAV palette): SVG chips (inbox/key/peso/bed), honest MoM deltas (hidden without prior data), dark/light alternation.
- Rounded header strip: title + live date + New booking / Review pending actions.
- Analytics A+B: 30-day occupancy donut (per-room bars) + 6-month stacked bookings bars with legend. C+D (revenue area, payment mix) deferred.
- Calendar: 14-day room-row timeline (click bar → filtered bookings) primary; FullCalendar month collapsed secondary.
- Verified: all admin pages 200, no PHP errors, timeline empty-correct (demo bookings are September).

## [v0.6.1] — Logo, toasts, icon buttons, filter fix
- Official badge (`Logo and outside/471376988…`) copied to `assets/images/logo.jpg`; wired to login card, curtain card, sidebar brand, guest nav + favicons.
- Login errors now floating toasts (card never shifts); successful login flashes CPSC-style "Welcome back" toast on dashboard arrival.
- Action buttons → inline-SVG icon set (view/edit/approve/decline/trash/check/in/out/flag/undo, currentColor) with CPSC hover tints + lift on bookings/users/rooms.
- Filter/notif panels raised above tables (`z-500`, toolbar card `overflow:visible`).
- Verified: bad-login toast (no alert), welcome toast, icons/filter markup live, guest index 200. Assets `?v=20251005`.

## [v0.6.0] — CPSC motion port (EAV theme kept)
- Shell: categorized sidebar (Main/Manage, logout pinned bottom), SVG sprite icons, hover lift animations.
- Toasts: `flash()` helper + top-center bounce cards; all booking/user/room actions flash ("Approved successfully." etc).
- Bell: activity panel (5 newest Pending + today's check-ins/outs, badge count).
- Skeleton shimmer overlay only on login→dashboard arrival (`?welcome=1`, auto-cleaned).
- Cascade unveil on sidebar/filter navigation (sidebar stays pixel-still); View Transitions skipped for classroom-PC safety.
- Toolbars: search + funnel filter panel + refresh + Create modals (walk-in booking as Approved, room add, staff account).
- Verified: skeleton+bell on dash, approve toast, walk-in create (removed after), demo data intact.

## [v0.5.1] — Curtain card final
- Restored roll-down login card (Welcome Back design, centered via flex) over blurred home; Back to site / ESC / backdrop roll it back up (two-step close so the slide animates).
- Removed page-wipe navigation; `admin/login.php` direct page kept as fallback with plain Back link. Assets `?v=20251001`.

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

## [Unreleased] — Next
- Continue admin redesign (owner's pending list)
- Midterm demo rehearsal (offline, WiFi off)
- GitHub push (`eavs-homestay` planned; ignore `uploads/proofs/*`)
