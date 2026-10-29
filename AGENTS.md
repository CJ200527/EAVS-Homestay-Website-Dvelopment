# AGENTS.md — Agent Defaults & Rules (EAV's Homestay)

Read before any code change. Overrides guesswork; ask the owner when a rule blocks the request.

## 1. Design system (locked)
- Palette: ocean `#0A4A7A` / `#0E6BA8`, cream `#FFF8EC`, wood `#C8A97E`, ink `#1a2a3a`, gray `#f4f1ea`. Admin sidebar: ocean gradient + cream active link.
- Type: Georgia serif headings (`.serif`), Segoe UI body. No webfonts (offline).
- Components: pill buttons (`.btn-pill`), arrow suffix (`.btn-arrow`), spec tables (`.spec-table`), room cards (`.room-card`), uncropped gallery (4/3 contain on gray). Footer light buttons = ocean text (never cream-on-white).
- Brand logo: `assets/images/logo.jpg` (official badge) — login, curtain, sidebar, guest nav, favicons. Never hotlink; never edit originals.
- Admin icons: inline SVG sprite in `admin/layout.php` (`a-*` symbols, currentColor) + `.btn-act` quiet buttons with hover tints. Filter/notif panels must stack above tables (z-500).
- Layout language: Telly-inspired (full-bleed hero + overlay, split sections, booking bar, spec-table cards, shared admin shell).
- Dashboard-only glass pilot: `body.glass-dash` + `.glass` frosted cards (ocean mesh backdrop, `@supports` fallback). Never apply glass globally without owner approval.
- Navi pilot (ALL admin pages, owner picked winner 2026-10-05): `body.navi` — frosted gray-teal glass (`navi-bg` turquoise-glow backdrop, white-pill active nav, monochrome gray rail). Scoped via `admin_shell_open(...,navi:true)`. Aura-dark retired and removed (see CHANGELOG v0.9.0 for tokens if ever revived).
- **Deep Ocean Glass (v0.11 pilot, Dashboard + Booking):** `admin_shell_open($title,$active,$glass,$navi,'ocean')` → `body.theme-ocean` + `.theme-bg` = oceanfront photo → ocean gradient scrim → cyan/wood glows. Frosted panels `blur(18px) saturate(140%)` + `@supports` solid fallback. Accents: cyan `#2dd4bf` + wood `#C8A97E`. Pilot isolates ONE variable (theme only, layout order untouched) so the verdict is meaningful; rollout to Rooms/Users = pass `'ocean'`. Photos come from the mirror copy of `assets/images/` — keep source in sync.
- Dashboard order: strip → KPIs → Timeline → [Revenue | Occupancy] → [Room Rented | Recent Activity]. Receipt modal buttons: `.btn-receipt-save/.btn-receipt-confirm/.btn-receipt-close` (white text locked, never inline hover JS).

## 2. Offline-first (demo runs WiFi-off)
- Zero CDN at runtime. Vendor lives in `assets/vendor/`. System fonts only. FullCalendar has a PHP-list fallback.
- After CSS/JS edits: bump `?v=` in `includes/header.php`, `includes/footer.php`, `admin/login.php`, `admin/layout.php` (layout loads `admin.js`, `admin.css`, vendor).

## 3. Business rules
- 2 rooms (expandable via Rooms CRUD), both ₱3,000/night. Room 1 = free 1-day scooter badge. Free Wi-Fi throughout (credentials given at check-in, never published).
- Rooms have `status` pending/active. New rooms start pending (invisible to guests); Approve goes live, Deactivate hides. Guests (landing/rooms/booking/details/availability) see active-only. Cover photo upload required (jpg/png/webp ≤2MB → `assets/images/rooms/`).
- **v0.12 booking flow — no admin approval.** Guest submits → `booking_status='Confirmed'` immediately and the dates are RESERVED. `Confirmed`/`CheckedIn` block inventory; only `Declined` releases them. Admin actions: Check in, Check out, Complete, Mark Fully Paid, Decline.
- **Never regress the overlap guard.** It must be rendered as a quoted SQL list `('Confirmed','CheckedIn')`. Writing `IN ('Confirmed,CheckedIn')` is ONE string that matches nothing and silently disables double-booking protection — this exact bug shipped once already (see CHANGELOG v0.12.0). Check-then-insert must stay inside the `begin_transaction` + `SELECT … FOR UPDATE` lock.
- **Money model:** `total = nights × 3,000`; `reservation_fee = 1,000` (non-refundable); `amount_paid` records what was received. Advance pays the fee only, Full pays the total. **Proof of payment is required for EVERY method, cash included** — a photo/screenshot is the evidence, and it means money changed hands, so `amount_paid` follows `payment_type` for all methods. Refund on cancellation = `max(0, amount_paid − 1,000)` (`refundable_on_cancel()`). Admin **Mark Fully Paid** requires a proof upload. The admin walk-in form (used when a guest is already at the homestay) takes the same required proof and must carry `enctype="multipart/form-data"`.
- **No email.** The `email` column was dropped from `bookings`; the guest form, admin table/search/modal, walk-in form and receipt must NOT reintroduce it. Contact by phone only.
- Terms & Conditions are **required**: content lives in `includes/terms-content.php` (single source of truth), rendered as a **modal inside `booking.php`** (never a page hop — the guest must not lose their form input) and as the standalone `terms.php` (footer link). Server-side gate is authoritative: `if(empty($_POST['agree_terms'])) $errors[]=...`. **The visible `agreeChk` has no `name` and is never submitted — the hidden `agree_terms` input is what the server reads, so `syncAgree()` must be called from every place that changes the checkbox state.** Check-in 2:00 PM / check-out 11:00 AM must appear on the form, the modal and the receipt.
- Payment: type Advance|Full, method Cash|GCash|Maya, proof required if not Cash. `payment_status` is Unpaid|Part Paid|Fully Paid (the old `Advance Paid` status is gone).
- Auth: session guard; roles owner/staff; no public signup — owner creates accounts in Users; last active owner protected; deactivated logins rejected.
- All booking/user/room state changes: confirm modal (admin) — never raw GET links without confirmation.
- Every admin modal (create/view/edit/confirm) uses `modal-dialog-centered` so popups sit mid-screen.
- Action redirects: `flash('...')` before `header('Location: ...')` so the toast shows. Skeleton only via `?welcome=1` (auto-cleaned). Cascade is the base transition (no View Transitions dependency).
- Booking form must never lose guest input **after a validation failure**: every field repopulates from `$pref`. There is deliberately **no** sessionStorage draft — a refresh must give a blank form (v0.12.3).

## 4. Sync & verify (every change)
- **Run `powershell -NoProfile -ExecutionPolicy Bypass -File tools/verify.ps1` after every change block.** It is the real gate: parity, lint, rendered-output leak scan (`?>` / `toast-icon` / PHP warnings in output), routes, error-log delta. `php -l` and HTTP 200 CANNOT catch template tags leaking into output — that is exactly how the orphaned `?>` bug survived several blocks (v0.11.3).
- Source: `.../EAVS Homestay Website Dvelopment/` → mirror: `C:\xampp\htdocs\EAVS Homestay Website Development\` with exact relative paths (`assets/css/`, `assets/js/` — never dump into `assets/` root).
- Lint: `& "C:\xampp\php\php.exe" -l <file>`. Serve-check: `Invoke-WebRequest http://localhost/EAVS%20Homestay%20Website%20Development/<page>` → 200.
- **When inserting a PHP block that opens HTML mode (`?>`), check the original file's existing `?>` does not survive as a stray line.** Grep for lines that are exactly `?>` and confirm each is preceded by PHP, not `</div>`.
- DB checks: `& "C:\xampp\mysql\bin\mysql.exe" -u root -e "..."`. Remove only agent-created test rows; NEVER delete owner's live rows (#6+ Oct tests) without explicit confirmation.
- Update CHANGELOG.md (new version entry) + session.md status on each work block; sync docs too.

## 5. Never do
- No new CDN/webfont/npm/composer dependencies. No edits to `Images of the Place/` originals. No public admin signup. No deleting files without explicit owner confirmation (past-session cleanup = trim notes, not files). No committing secrets.
