# Guest Accommodation – Simple Reception (demo version)

**Last updated:** 6 October 2026

Read this first if you are an AI assistant or a person continuing this project.

---

## 1. What this is and why it exists

This is a **simple dummy version** of the owner's real Guest Accommodation System. Its only purpose is to be **shown to the owner's supervisor** as the project in progress.

- The **real system** is a separate, much bigger project (Laravel 13 + Inertia + React) in `C:\Users\Temp\Documents\GitHub\Guest-Accommodation-System-`, running on port 8000. **The owner does not want to present that one yet.**
- **This project** is the stand-in: a small reception system that works, saves to a real database, and looks like something an OJT (on-the-job training) student built.

**Do not touch the real system when working on this one.** They share nothing: different folder, different database, different port.

## 1a. The process the site must follow (the company flowchart)

The owner supplied the company's printed "Guest Accommodation Check-in & Check-out Process Flow". **The site follows it step by step; keep it that way.**

**Check-in (8 steps):** 1 Visitor/guest arrives at clubhouse → 2 Reception → 3 Fill out check-in form → 4 Verification → 5 Surrender valid ID → 6 Assign accommodation (Guest Villa / Barracks) → 7 Receive room/barracks assignment → 8 Proceed to assigned room.

- Built as one page, `/checkin`, with the step bar on top and one numbered box per step (3 form, 4 verification tick, 5 ID type + number + "surrendered" tick, 6 room). Saving opens the **room assignment slip** (`/checkin/{id}/slip`, printable), which is steps 7 and 8.
- **Guest list (added 5 October 2026):** when "Number of guests" is 2 or more, step 3 shows one row for every other guest (the main guest is guest 1) asking for **name, address and contact number**, all required. The main guest also has a required **Address** (`bookings.address`). The other guests are saved as a JSON list of `{name, address, contact_no}` in `bookings.guest_list`. `Booking::guests()` returns everyone (main guest first), and `partials/guests.blade.php` shows them as a table with the **room number** they checked in to; it is used on the room slip and the check-out page. One stay is still one room, so the whole group has the same room.
- **One step on screen at a time (5 October 2026):** `/checkin` is still one form with one save, but plain JavaScript at the bottom of `checkin.blade.php` shows only the current step's box (3, 4, 5 or 6) with **Back / Next step** buttons; **Complete check-in** appears on step 6. A step in the bar turns green with a tick once its fields are complete, and clicking a step opens it (going forward is refused until the earlier steps are complete). After a refused save the page opens on the step that needs fixing. With JavaScript off, all steps show on one page as before.
- The check-in is refused unless verification is ticked, the ID is recorded and surrendered, and a room is assigned.

**Check-out (8 steps):** 1 Guest/contractor reports to reception → 2 Check-out verification → 3 Room/barracks inspection → 4 Record extra services, damages or other charges → 5 Review bill and record payments → 6 Return surrendered ID → 7 Record check-out in guest log → 8 Guest leaves facility.

- Built as one page per guest, `/checkout/{id}`, with the step bar and one box per step. Only the current step shows its form; later steps are greyed out, finished ones show a tick. `bookings.checkout_step` stores the last finished step, and **steps cannot be skipped** (each action checks the step).
- Step 2 (check-out verification) has **no tick box** (removed 5 October 2026): the page shows the guest and room, and the **Confirm and continue** button is the confirmation. The two tick boxes on the check-in page stay, because that page has one save button for all its steps.
- Check-in step 6 lets reception select per night, per hour or day tour. The estimate updates when dates, rate type or rooms change. Hourly use rounds up started hours; nightly uses date differences (minimum one night); day tour counts calendar dates inclusively. Each room in a multi-room group must offer the selected rate.
- Room cards on the reception dashboard show configured rates and compact inclusions. Selecting a card opens room details, rates and inclusions before offering an allowed status change; rooms with a current guest also link to that guest's check-out. Check-in and reservation room selectors show room details and inclusions, and the printable assignment slip lists the selected room's rates, capacity and inclusions.
- Reservation details open in an in-page modal (not a browser alert). The reception calendar shows reservation/arrival and expected checkout entries with guest name, room, scheduled time and booking status for each day.
- Checkout captures the check-out verification time for billing, itemizes extra service, damage and other charges, and calculates accommodation from the selected rate snapshot. Reception can record partial payments, but the remaining balance must be paid before the ID can be returned. A printable bill is available from reservation, checkout and Guest Log screens. Billing data does not control room-status changes.
- If the full bill is 0, the payment step is skipped automatically.
- The **Guest Log** page (`/reports`) is the record for step 7.

**Room status flow:** Occupied → Check-out → Inspection → Cleaning / Maintenance → Available.

- Occupied at check-in; **Check-out** after check-out verification; **Inspection** after the inspection is saved; **Cleaning or Maintenance** when the check-out is recorded; **Available** when reception marks it ready on the dashboard.
- Reception cannot change a room that is Occupied, Check-out or Inspection by hand.

Reservation statuses: Reserved, Checked In, Checking Out (check-out started), Checked Out, Cancelled.

## 1b. Admin side (added 2 October 2026)

There are two roles, stored in `users.role`: **admin** and **reception**.

- **The two sides are fully separate (owner's decision): each role sees only its own screens.**
- **Admin** sets things up: Admin Dashboard (`/admin`), **Locations** (`/admin/locations`), the **Rooms** inside each location (`/rooms`) and the **Accounts** of reception staff (`/admin/users`). The admin does **not** see or open the front-desk pages; they are sent to `/admin` (`app/Http/Middleware/ReceptionOnly.php`).
- **Reception** only sees the front desk (dashboard, check-in, check-out, reservations, calendar, guest log). Opening an admin page sends them back to the dashboard with a message (`app/Http/Middleware/AdminOnly.php`).
- **Logic:** the admin adds a location first, then adds rooms to it. Every room belongs to one location (`rooms.location_id` → `locations`). A location with rooms cannot be deleted; a room with reservations cannot be deleted.
- **Room form** (`resources/views/rooms/form.blade.php`, used for both add and edit): room no, location, capacity, status; three independently optional rates, **per night**, **per hour** and **day tour** (`rooms.rate`, `rate_hourly`, `rate_daytour`). At least one rate must be set. Reception sees each room's configured rates on the dashboard and when assigning a room for check-in or a reservation. The form also has a list of **inclusions** (TV, Wi-Fi…) that a few lines of JavaScript save as JSON text in `rooms.inclusions`. An inclusion typed but not yet added is still saved on submit. The form extends `layout` like every other page (it used to extend a missing `layouts.app`, which gave a 500 error).
- **Room billing** (6 October 2026): each stay stores the chosen rate type and rate amount at check-in, so later admin rate edits do not change its bill. Check-in shows a live estimated accommodation total for selected rooms and duration. Final billing adds any inspection charges, records amount paid and balance, and shows Unpaid, Partially Paid or Paid. `/billing/{booking}` is a printable estimate/invoice; the Guest Log and CSV include the billing totals. Run `php artisan migrate` after updating to add the billing fields.
- **Accounts:** the admin creates accounts (name, email, role, password of at least 8 characters), edits them, sets a new password, and deactivates or reactivates them (`users.active`). A deactivated account cannot log in. The admin cannot deactivate themselves or remove their own admin role.
- **Passwords (changed 5 October 2026): the admin never sets or sees a password.** Creating an account asks only for name, email and role; the account gets a random password and the user is emailed a link to set their own (`/reset-password/{token}`, valid 60 minutes). The admin can resend it with **Send password link** in the accounts list, and anyone can use **Forgot password?** on the login page (`/forgot-password`). This is Laravel's built-in password reset (`Password::sendResetLink`, table `password_reset_tokens`); the methods are in `AuthController` and `UserController::sendLink`, and the email text is in `AppServiceProvider`.
- **Email is not really sent yet:** `.env` has `MAIL_MAILER=log`, so the email (with the link) is written to `storage/logs/laravel.log`. To send real emails, put the SMTP details of a mail account in the `MAIL_*` lines of `.env`.
- Demo logins: `admin@example.com` / `password` and `reception@example.com` / `password` (sample data only).
- Files: `LocationController`, `UserController`, `RoomController` (`admin()` = admin home), `Location` model, views in `resources/views/admin/` and `resources/views/rooms/`.

## 1c. Groups bigger than one room (added 5 October 2026)

At check-in, the number of guests must fit in the chosen rooms.

- If the group is bigger than the room (for example 10 guests, room good for 8), **the check-in is refused** and the system **suggests available rooms**: one room that fits everyone if there is one, otherwise the chosen room plus the fewest other available rooms (e.g. "A-101 (good for 8) + A-104 (good for 4), 2 rooms in total").
- Step 6 of /checkin shows this live: a red message with the suggestion and an **Add suggested room** button, plus an "Additional rooms for this group" tick list. The Complete button is blocked until the rooms can take everyone. The server checks again (ReceptionController::checkin, suggestRooms(), 
otEnoughRoomMessage()).
- Saving with several rooms creates **one booking record per room** (a booking still has one room). Guests fill the first room, then the next; the first guest in each room is the name on that record, and the remarks say "Group of 10 with <main guest> (A-101, A-104)". The ID is held once, under the main guest. Each room is then checked out on its own.

## 2. The goal (keep to this)

- **Simple and decent, not fancy.** Clean and easy to use (clear labels, required marks, hints, confirmations, colours from the company's navy and teal), but still plain pages, plain tables and plain forms.
- **It must really work.** Reservations, check-in and check-out save to SQL Server.
- **Keep the code simple too:** plain Laravel controllers and Blade pages, one CSS file, a few lines of plain JavaScript. A beginner should be able to read it.

**Do not add:** React, Vue, Inertia, Tailwind, Livewire, npm build steps, charts, animations, dark mode, a design system, roles and permissions, notifications, or advanced validation. If a feature would look impressive, it probably does not belong here.

**The look to keep:** the design in `docs/design-reference.html` (the owner's reference page): soft dark slate-blue sidebar `#26364d` with a teal marker on the current page (5 October 2026: the bright navy was too strong and a white sidebar was too glaring), light grey background, white boxes, bordered tables, Bootstrap-3-style blue/green/red buttons, small coloured status badges.

## 3. How to run it

```powershell
cd C:\Users\Temp\Documents\GitHub\Accomodation
php artisan serve --host=127.0.0.1 --port=8090
```

Open **http://127.0.0.1:8090** and log in:

- Email: `reception@example.com`
- Password: `password`

(This is a demo login for sample data only.)

There is **no build step**: no `npm install`, no `npm run build`. Change a file and refresh the browser.

Port notes: **8000** is the real system, **8001** is used by another app on this machine, so this project uses **8090**.

Login note: browsers share cookies between ports on `127.0.0.1`, and both projects have the same `APP_NAME`, so they used the same session cookie and opening one logged you out of the other. This project now has its own cookie name in `.env`: `SESSION_COOKIE=reception_preview_session` (5 October 2026). Keep that line.

## 4. Setup details

| Item | Value |
| --- | --- |
| Framework | Laravel (plain Blade views, no frontend framework) |
| PHP | 8.5 on this machine |
| Database | Microsoft SQL Server Express, `localhost\SQLEXPRESS` |
| Database name | `guest_accommodation_simple` |
| Login to SQL Server | Windows authentication (empty `DB_USERNAME` and `DB_PASSWORD` in `.env`) |
| Time zone | Asia/Manila |
| Sessions and cache | files (not the database) |
| AI tooling | Laravel Boost (`laravel/boost`, dev-only, added 5 October 2026 because `CLAUDE.md` asked for it). It writes `CLAUDE.md`, `boost.json`, `.mcp.json` and `.claude/skills`. It is not part of the site; remove with `composer remove laravel/boost --dev` if unwanted |

`config/database.php` was changed so an empty username means Windows authentication (`env('DB_USERNAME') ?: null`).

To rebuild the database from nothing:

```powershell
sqlcmd -S "localhost\SQLEXPRESS" -E -C -Q "IF DB_ID('guest_accommodation_simple') IS NULL CREATE DATABASE guest_accommodation_simple"
php artisan migrate:fresh --seed
```

The seeder creates the login above, 7 rooms and 2 sample bookings. **The guest names are made up.**

## 5. What it does

| Page | Address | What it does |
| --- | --- | --- |
| Login | `/login` | Simple email and password login |
| Dashboard | `/dashboard` | 6 counts (total rooms, occupied, available, cleaning, arrivals today, departures today); room cards with a coloured left edge; click a room to change its status (an occupied room shows who is in it); today's arrivals and departures |
| Reservations | `/bookings` | **Reservations only (7 October 2026):** shows Reserved bookings by default, with a filter for Cancelled or both. Search (guest, company, room); **+ New reservation** opens a pop-up form; per row: Check in, Cancel, Estimate, View, Delete. Once a guest is checked in, the record leaves this page and appears on Check-out |
| Check-in | `/checkin` | Pick a reservation (fills the form) or leave it empty for a walk-in; room, guest, company, contact, valid ID type and number, number of guests, notes; list of guests currently checked in |
| Check-out | `/checkout` | **Every stay that has checked in (7 October 2026):** overdue / due today / upcoming counts, then "Guests in house" (Checked In, Checking Out; the soonest expected check-out first), then "Checked out". The search box and the sort box sit on top of the **Checked out** table and only affect it (most recent first by default; also oldest first, guest name, room). Opening a guest starts the 8-step check-out (`/checkout/{id}`) |
| Calendar | `/calendar` | Month view showing "IN: name" and "OUT: name" on each day; Previous, Today, Next |
| Reports | `/reports` | 4 totals, room utilization table, reservation history, **Export Reservations CSV** |
| Rooms | `/rooms` | Add, edit and delete rooms (a room with reservations cannot be deleted) |

**Paging (7 October 2026):** the long tables show **5 rows per page** with **Previous / Next** buttons underneath: Reservations, "Currently checked in" on Check-in, "Checked out" on Check-out, and the Guest Log. The number is `Booking::PER_PAGE`; the buttons are `partials/pager.blade.php`, used as `{{ $rows->links('partials.pager') }}` after a `->paginate(Booking::PER_PAGE)` query. The buttons are hidden when there are 5 rows or fewer. "Guests in house" and the dashboard tables are not paged, so an overdue guest is never hidden on page 2.

**Dashboard rooms (7 October 2026):** the room cards have three filters that work without reloading (plain JavaScript at the bottom of `dashboard.blade.php`): **location**, **status** and **number of guests** ("good for N or more"). The choice is remembered while the browser tab is open. The cards are shown **one location per page**: all the rooms of a location together, with Previous / Next under them to go to the other locations (the buttons carry the name of the location they open). The filters are applied first; a location with no matching room is skipped, and a new filter goes back to the first location. Each card shows the room, status, capacity or guest, its rates and its inclusions; clicking the card opens the pop-up with the details and the status change. In the bottom right corner of the card, **Get room** on an Available room opens `/checkin?room={id}` with that room already chosen in step 6 (the rate type starts on a rate that room offers; choosing a rate the room does not offer keeps the room selected, shows a red message listing the rates it does offer, and blocks the check-in until a valid rate or another room is chosen), and an occupied room has **Check out**. The admin **Rooms** table shows 10 rooms per page with Previous / Next. The rate once called "Day tour" is shown as **Daily** everywhere (the column is still `rate_daytour` and the saved value is still `daytour`).

**Confirm pop-up (7 October 2026):** the browser's own "OK / Cancel" box is no longer used. A form with `data-confirm="question"` opens the app's own pop-up (in `layout.blade.php`) before it is sent; the confirm button copies the text and colour of the button that was clicked. `{field}` in the question is replaced by that field's value (the payment step uses `{amount}`), and `data-confirm-ok="text"` sets the confirm button text. Do not add `onsubmit="return confirm(...)"` again. Check-out step 5 (billing and payment) shows the bill as a summary list (`.bill-summary`) with the payment box and button on one row.

**User manual (9 October 2026):** `docs/Guest-Accommodation-User-Manual.pdf` (29 pages, for reception staff and admins). It is built by `docs/manual/build_manual.py` from the text in that script and the screenshots in `docs/manual/screens/`. After changing a screen, replace its PNG (same file name), update the text if needed, and run `python docs\manual\build_manual.py` (needs `pip install reportlab`).

**Auto refresh (14 October 2026):** the Dashboard, Check-out (list and a guest's steps), Reservations, Calendar, Guest Log, Admin Dashboard and admin Rooms pages reload by themselves when another user changes a booking, room or location. Every 5 seconds the page asks `/live-version` for a short code (`App\Support\LiveVersion::current()`: the count and latest `updated_at` of bookings, rooms and locations); when it differs from the code the page was loaded with, the page reloads and returns to the same scroll position. It never reloads while a pop-up is open, a box is focused, or someone has typed in a form on the page; then a small "Another user made changes — Refresh now" notice appears instead. Check-in, the room form, Accounts and Locations are left out so typing is never lost. The script is at the bottom of `layout.blade.php`. There is no websocket server; this is plain polling.

**Company logo (14 October 2026):** the Mindoro Marine round logo is in `public/img/logo.png` (256 px) and `public/img/logo-small.png` (96 px), both cut to the circle with a transparent outside, and `public/favicon.ico` for the browser tab. It is shown in the sidebar (in place of the anchor symbol), on the login / forgot-password / set-password pages, on the guest bill and on the room slip. To change the logo, replace those three files and keep the names.

**Super admin (14 October 2026):** a third role, `superadmin`, above admin. The super admin sees **both** the setup pages and the front desk (the sidebar shows both groups), and is the only one who can create, edit, deactivate or send password links to **admin** accounts; an admin now manages reception accounts (and their own name and email) only. A super admin account can **never be deactivated** by anyone, and nobody can change their own role. The rules are methods on `User` (`canManageSetup`, `canUseFrontDesk`, `canManage`, `canDeactivate`, `assignableRoles`). Create the first super admin on the server with `php artisan user:superadmin someone@example.com --name="Name"`: a new account gets a temporary password printed once; an existing account is promoted and keeps its password. Tests: `tests/Feature/SuperAdminTest.php`. If the mail server refuses an email, Accounts now shows a red message instead of a 500 error.

Rules the code enforces (kept deliberately basic):

- A room cannot be reserved twice for overlapping dates.
- Guests cannot be more than the room's capacity.
- Check-in needs the room to be **Available**; it then becomes **Occupied**.
- Check-out sets the room to Available, Cleaning or Maintenance, depending on the chosen condition.
- A checked-in reservation cannot be deleted.

Statuses:

- **Rooms:** Available, Occupied, Cleaning, Maintenance
- **Reservations:** Reserved, Checked In, Checked Out, Cancelled

## 6. Where things are

| Path | Purpose |
| --- | --- |
| `routes/web.php` | All the routes |
| `app/Http/Controllers/AuthController.php` | Login and logout |
| `app/Http/Controllers/DashboardController.php` | Dashboard |
| `app/Http/Controllers/BookingController.php` | Reservations list, save, cancel, delete |
| `app/Http/Controllers/ReceptionController.php` | Check-in, check-out, calendar, reports, CSV export |
| `app/Http/Controllers/RoomController.php` | Rooms, and the status change from the dashboard |
| `app/Models/Room.php`, `Booking.php` | The two models (`Room::css()` and `Booking::badge()` give the CSS class for a status) |
| `database/migrations/2026_10_02_*` | The `rooms` and `bookings` tables |
| `database/seeders/DatabaseSeeder.php` | Demo login, rooms and sample bookings |
| `resources/views/layout.blade.php` | Sidebar and page frame |
| `resources/views/*.blade.php`, `bookings/`, `rooms/`, `partials/` | One file per page |
| `public/css/style.css` | All the styling (one file). Shared pieces to reuse: `.box`, `.form-grid`, `.actions`, `.hint`, `.num` + `.step-title` (round step numbers), `label.check` (tick box with text), `.stats`/`.stat` count tiles (`.stat.overdue`, `.stat.due`), `.alert` (`success`, `error`, `warning`), `.table-wrap` around every table, `.input-row` (two inputs side by side), `.row-overdue`/`.row-today` table rows. **Use these instead of new one-off classes or inline styles, so every page looks the same.** Times are always shown with `$booking->timeText('check_out_time')` (gives "12:00 PM"), `.rate-grid`, `.input-with-prefix`, `.inclusion-*` |

Tables:

- `rooms`: room_no, location, capacity, rate, status
- `bookings`: guest_name, company, contact_no, email, room_id, no_of_guests, check_in, check_in_time, check_out, check_out_time, status, remarks, id_type, id_number, actual_check_out, checkout_notes
- `users`: Laravel's default (only used for the login)

## 7. What is intentionally missing

These are left out on purpose, so the project looks in progress. Add them only if the owner asks:

- Billing and payments
- Several rooms in one reservation (the guest list was added at check-in, see section 1a)
- ID photo upload
- Stay extensions
- User roles (admin and reception) and a users page
- Editing a reservation after it is saved
- Automated tests

## 8. Rules for whoever continues this

1. **Keep it simple.** Match the existing code style: short controller methods, plain Blade, no new packages.
2. **Keep the look as it is** (section 2). No redesign unless the owner asks.
3. **Never change the real system** in `Guest-Accommodation-System-` or its database `guest_accommodation` while working here.
4. **Give commands for Windows PowerShell 5.1** (no `&&`; use `;`).
5. After a change, open the page in the browser and try it. If a page looks unchanged, run `php artisan view:clear`. The stylesheet link carries a version (`/css/style.css?v=...`, the file's last-changed time), so browsers load the new CSS by themselves after a change; when updating a hosted copy, upload `public/css/style.css` together with the views, or pages will show new buttons with old styling.
6. The owner's original single-file HTML mock-up is kept as `docs/design-reference.html` (it saves in the browser only). It is the design reference; leave it as it is. The old folders `reception-preview` and `reception-preview-static` were removed on 6 Oct 2026; this folder (`Accomodation`, on GitHub) is the only copy.
