---
name: "medlink"
description: "Project guide for MedLink (Laravel 13 + Vue, Barangay Honrado Health Center; folder medlink). Use for any MedLink work: medicine requests, delivery, home service, BHW/nurse portals, SMS/OTP, announcements."
---

# MedLink - project skill

MedLink is a barangay health center system for **Barangay Honrado Health Center** (Philippines). It has three roles: residents request medicine, home service and schedules; BHWs (barangay health workers) handle deliveries, home visits and walk-ins; nurses run inventory, verification, consultations, vaccinations and reports. It's a capstone project deployed on localhost. Team/repo name: **medlink**.

Facts below reflect the code as of 2026-11-24, after the SMS/OTP phases P0-P9 were implemented and tests passed. When code and this file disagree, the code wins: re-read the file, then tell the user this skill is stale.

## Stack and environment

- **Laravel 13** (`laravel/framework ^13.17`), PHP 8.3, MySQL (`DB_CONNECTION=mysql`), Eloquent, Laravel migrations.
- Frontend: Blade pages that mount **Vue 3** components (Vite entry `resources/js/medlink.js`), with **Tailwind v4** (both the Vite plugin and the browser CDN `@tailwindcss/browser@4` in page heads), Inter font, Material Icons Outlined.
- Laragon on Windows. Folder `C:\laragon\www\medlink`. A `Dockerfile` and `docker-entrypoint.sh` also exist.
- `.env` drivers: `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`. Queued jobs only run if `php artisan queue:work` is running, so prefer `dispatch()->afterResponse()` or synchronous sends for anything that must happen.
- SMS provider is **httpSMS**. Its `.env` keys are `HTTPSMS_API_KEY`, `HTTPSMS_FROM_NUMBER` (`+639...`) and `HTTPSMS_SIM_SLOT`, currently `1` (normalize to `SIM1`/`SIM2`). Never print or commit their values. When checking `.env`, list key names only.
- Tests: `phpunit.xml` uses SQLite `:memory:`, `SESSION_DRIVER=array`, sync queue. Suites: tests/Unit (PhoneNumberTest, PhilippineMobileRuleTest) and tests/Feature (OtpServiceTest, RegistrationOtpTest, PasswordResetOtpTest, LoginOtpTest, PhoneChangeOtpTest, SmsNotificationTest, AnnouncementSmsTest). Six old enum migrations carry a `DB::getDriverName() === 'mysql'` guard so they run on SQLite; their MySQL branch is unchanged and already applied, so leave them alone.
- Agent guardrails (optional kit the developer may have installed): `.claude/settings.json` + `.claude/hooks/*.php` (Claude Code) and `.github/copilot-instructions.md` + `.vscode/settings.json` (Copilot). They ask before every file write and DB command, block `migrate:fresh/refresh/reset`, `db:wipe`, `.env` access and `git push`, refuse a real `migrate` unless `C:\laragon-backups` has a `.sql` backup newer than 60 min, and run `php -l` / `node --check` / `migrate --pretend` after edits. Never work around a guard.
- Loose files in the project root: `medlink_database.sql` (a dump), `database/database.sqlite`, `Explain.md` (FHSIS/PIDSR reporting notes), `project_defense_guide.md`, `remove_comments.py`, `brain/` (old task notes). Laravel serves only `public/`, so they aren't web-exposed, but don't add secrets to them.

## Directory map

```
app/Http/Controllers/
  AuthController            register, login, resetPassword, findAccount, deactivate/delete account, logout
  MedicineRequestController index/store/accept/approve/reject/updateQuantity/complete (nurse + BHW for delivery)
  HomeServiceController     nurse side: store, storeResident, accept/approve/reject/complete/assign
  BhwDashboardController    BHW portal data, home-service accept/approve/confirm/complete, nurse mgmt, profile
  NurseDashboardController  inventory, returned medicines, notifications, profile
  ResidentDashboardController  resident portal data, profile update, notifications
  AnnouncementController    nurseIndex, store/update/destroy (nurse + BHW)
  SchedulingController, ConsultationController, ChildVaccinationController, WalkinController,
  PatientVerificationController, UserManagementController, AccountHistoryController,
  ReportsController, DeliveryReportController, GlobalSearchController, SyncController, HomeServiceTypeController, MedicineController
app/Http/Middleware/  EnsureUserHasRole (alias role), EnsureSingleDeviceSession (alias single.device)
app/Models/           User, MedicineRequest, Medicine, MedicineReturn, HomeServiceRequest, HomeServiceType,
                      Schedule, Consultation, ChildVaccination, Announcement, AppNotification, SystemNotification, AccountLog
resources/views/      auth/{login,register,pending}, dashboards/{nurse-*.blade.php, bhw, resident}, layouts/nurse
resources/js/components/resident/  ResidentPortal, RequestMedicine, HomeService, Profile, Notifications, ...
resources/js/components/bhw/       BhwPortal, MedicineDeliveryRequests, HomeServices, WalkinRegistration, BhwAnnouncements, ...
public/js/auto-sync.js  polling engine against /api/sync-version
public/js/ph-phone.js   Blade phone-input helper (input[data-ph-phone])
routes/web.php          all routes (no api.php)

SMS / OTP layer:
app/Support/PhoneNumber.php     clean, detectFormat, normalize, toE164, mask, isPlaceholder, isSendable, mergeNormalized
app/Support/SmsTemplates.php    otp, pickupReady, deliveryOnTheWay, homeServiceOnTheWay, announcement, sanitize, fit
app/Rules/PhilippineMobile.php  the only phone validation rule
app/Services/SmsService.php     httpSMS sender (send, sendAfterResponse, isConfigured), logs to sms_logs
app/Services/OtpService.php     issue, verify, lockoutRemaining, errorMessage
app/Http/Controllers/PhoneChangeController.php  /account/phone/{verify,resend,cancel}
app/Models/OtpCode, OtpLockout, SmsLog
config/otp.php                  expiry, attempts, lockout, cooldown, hourly cap, log_fallback
resources/views/auth/otp.blade.php, auth/reset-password.blade.php
resources/views/dashboards/partials/phone-otp-modal.blade.php (nurse)
resources/js/components/shared/PhoneOtpModal.vue, resources/js/utils/phPhone.js
```

Portals: nurse pages are Blade + inline JS under `layouts/nurse`. Resident and BHW portals are single Blade pages that mount `<resident-portal>` / BHW Vue components with JSON props. All of them load `auto-sync.js`.

## Roles, auth and sessions

- `users.role` is an enum: `resident`, `bhw`, `nurse`. `status` is `pending` / `active` / `inactive`. There are also `can_manage_nurses` and `is_maintenance` flags.
- **Login is phone number + password + selected role.** Email is nullable and unused.
- Public registration creates **residents only**. They start `pending` (with an ID photo upload) until a nurse verifies them in Patient Verification. Pending residents can log in but are redirected to `pending.approval`.
- Nurses and BHWs are created by nurses (UserManagement) or by BHWs (`bhw/nurses`).
- **Single-device sessions:** at login a random `active_session_token` is stored on the user and in the session. The `single.device` middleware logs out any other device. Every new login path (OTP included) must issue this token the same way, through one shared login-completion method.
- Route groups in `web.php`: `auth + single.device`, then `role:nurse`, `role:nurse,bhw`, `role:resident` and `role:bhw`. The role middleware returns JSON 403 for `expectsJson()` requests and otherwise redirects.
- **Login is password + SMS OTP for every role.** `login()` checks phone, password, role and status, then issues a `login` OTP and stores `login_otp_user_id`, `login_otp_remember`, `login_otp_format` and `login_otp_started_at` (15-min window) in the session. The user is not authenticated until `verifyLoginOtp` succeeds. Then the private `completeLogin($request, $user, $remember, $logDetails)` issues the single-device token, writes the 'Logged In' AccountLog and redirects pending residents to `pending.approval`. All login paths must go through `completeLogin`.
- **Known weaknesses:** `findAccount` reveals a masked phone. Login messages separate "Incorrect number" from "Incorrect password", which allows account enumeration. BHW `acceptHomeService` / `approveHomeService` don't check status or assignment, so any BHW can re-claim a request (this predates the SMS work). Mention these once when relevant; don't fix them unless asked or they're in scope.

## Phone numbers

- They're stored as `09XXXXXXXXX` (11 digits) and `users.phone_number` is unique.
- Validation is currently the duplicated regex `/^09[0-9]{9}$/` in 10 places: AuthController (register, reset), ResidentDashboardController (`phone_number`, `emergency_contact_phone`), NurseDashboardController profile, UserManagementController store/update, BhwDashboardController storeNurse/updateProfile, WalkinController, HomeServiceController `contact_number` (store, storeResident).
- Walk-ins without a phone get a placeholder `WALKIN-<time>-<rand>`. Treat it as "no number": never SMS it and never show it as a phone.
- **Implemented rule:** only PH mobiles typed as `09XXXXXXXXX` or `+639XXXXXXXXX` are accepted. The input detects the format from the prefix (max 11 characters for `09`, 13 for `+63`). Numbers are stored as `09...` and sent as `+63...`. Controllers call `PhoneNumber::mergeNormalized($request, [...])` BEFORE `validate()` and use `new PhilippineMobile`; never reintroduce the old regex. Capture the typed format with `PhoneNumber::detectFormat()` before merging, so masks match what the user typed (`+639******456` / `09******456`).
- Unique phone checks for self-service use `Rule::unique('users','phone_number')->where(registration_unverified = false)`. Login and search normalize the input before lookup.

## Core workflows

**Medicine requests** (`MedicineRequest`, displayed as `REQ-001`):
- Status: `Pending -> Accepted -> Approved -> Completed`, or `Rejected` (which restores stock).
- `delivery_method` is `pickup` or `delivery`. BHWs may act only on `delivery` requests, and only nurses reject.
- Accept tells the resident a consultation is required before claiming and creates a linked `Consultation`.
- Complete records `delivered_by`, `claimed_by`, `claimed_relationship`, proof photo, signature and `receipt_number`.
- `updateQuantity` handles returns and damaged items (`MedicineReturn`).
- Patient can be `self` or `other` (`patient_name`, age, gender, relationship). The resident store checks for duplicate pending requests, cooldowns and age ranges.
- There is no "out for delivery" status. Adding one means changing an enum that reports depend on, so prefer a timestamp column plus a button.

**Home service** (`HomeServiceRequest`, displayed as `HS-001`):
- Status: `Pending`, `Accepted`, `Approved`, `Confirmed`, `Completed`, `Rejected`.
- Fields: `assigned_to` and `assigned_bhw_name`, `contact_number`, `preferred_date` and `preferred_time`, `service_type` (managed in `HomeServiceType`).
- The BHW accept, approve and confirm actions all tell the resident "on the way". Only the assigned BHW may confirm or complete.
- Residents have a home-service cooldown.

**Other modules:** schedules (with BHW assignment and confirmation), consultations (FHSIS/PIDSR morbidity and surveillance), child vaccinations (NIP baby book), walk-in registration (BHW), returned medicines, reports and Excel exports (FHSIS/BHW accomplishment), account history (`AccountLog`, nurse view plus export and clear), global search.

## Conventions - follow for every change

1. **Validation:** use `$request->validate([...], [custom messages])`. Use `validateWithBag('forgot'|'find', ...)` for the login-page modals, and reopen them with `->with('open_forgot_modal', 'reset'|'find')`. Messages should be user-readable and mention the PH format.
2. **Dual responses:** many actions serve both AJAX (Vue/fetch) and form posts. Follow the existing pattern: `if ($request->wantsJson() || $request->ajax()) return response()->json(['success' => ..., 'message' => ...]);`, otherwise `redirect()->back()->with('success'|'error', ...)`. BHW AJAX endpoints return `{success, message}` with 403/422 on failure.
3. **In-app notifications:** `AppNotification::create(['user_id','type' => info|success|warning|error,'title','message'])`. Residents are notified about their own requests and nurses or BHWs about staff events, as in sibling actions. In-app notifications stay even when SMS is added; SMS is an extra channel.
4. **Audit:** use `AccountLog::record($user, 'Action', 'details', actorName?, actorRole?)` for account events (created, logged in, password reset, deactivated, deleted). Never put passwords, OTP codes or full message bodies in details.
5. **Auto-sync:** if a new table or column should refresh dashboards live, add it to `SyncController::version()` signatures.
6. **Models:** add new columns to `$fillable` and `$casts`. `User` uses `#[Fillable]` / `#[Hidden]` attributes, and `active_session_token` stays hidden.
7. **Migrations:** add a new timestamped file in `database/migrations/` and never edit an old one. Enum changes have used separate alter migrations (`alter_status_in_...`). Also keep `medlink_database.sql` in sync only if the user asks.
8. **Frontend:** Vue SFCs in `resources/js/components/{resident,bhw}`, nurse pages in Blade with inline scripts, CSRF taken from `<meta name="csrf-token">`. Match the existing Tailwind styling. After editing, run `npm run build` (or `npm run dev`) so changes show up.
9. Run `php -l` on touched PHP files, `php artisan route:list` after route changes, and `php artisan migrate --pretend` before migrating.

## SMS and OTP (implemented 2026-11-24)

The design below is built and tested (phases P0-P9). Decisions are final: login OTP for all roles; login 5 min; registration 30 min (invalidate only, no lockout); password reset and phone change 10 min; 5 wrong tries cancel the code and start a 30-min lockout for login, password_reset and phone_change; OTP on self phone change; `OTP_LOG_FALLBACK` local only (refused in production); **delivery SMS is sent when the request is approved** (no "On the Way" button). Announcement SMS goes to active residents with a sendable number and `registration_unverified = false` (verified phone not required).

httpSMS API (verified against docs.httpsms.com): `POST https://api.httpsms.com/v1/messages/send`, header `x-api-key`, JSON `{from, to, content}`. It has no SIM field; the SIM is chosen by the `from` number, so `HTTPSMS_SIM_SLOT` / `services.httpsms.sim` is unused.

Original design notes (still accurate except where the decisions above differ):

- **Phone:** one `PhilippineMobile` rule and a `PhoneNumber` helper (`normalize`, `toE164`, `mask`). The form auto-detects `09` (max 11 characters) versus `+63` (max 13). Masked display follows the user's format: `+639******456` / `09******456`.
- **SMS:** `config/services.php` gets `httpsms`. `App\Services\SmsService` posts to the httpSMS send-message API with the API-key header (verify the endpoint against the httpSMS docs before coding). A `sms_logs` table stores purpose, masked number, status and provider code, with no OTP bodies. If the key is absent, sending is skipped gracefully. Messages stay at 160 GSM-7 characters or less, with no emoji or em dashes.
- **OTP:** an `otp_codes` table (user, phone, purpose `registration|login|password_reset`, hashed 6-digit code, `expires_at`, attempts, consumed/invalidated) plus a per-phone, per-purpose lockout. `users.phone_verified_at` is added. Resends are limited to one per 60 seconds and 5 per hour.
  - Registration: 30-minute expiry. The account stays unverified until the code is entered, nurses are notified only after verification, and an unverified row with the same phone is replaced on re-registration.
  - Forgot password: 10-minute expiry. 5 wrong tries cancel the code and start a 30-minute penalty. This replaces the current reset that needs no code.
  - Login: OTP after the password check. The user waits in the session (not logged in), then the shared login completion runs with the single-device token.
  - The OTP page header shows the masked destination number.
- **Notifications:**
  - Pickup: on approve, ready for pickup.
  - Delivery: sent on approve (nurse or BHW), "BHW {name} / A BHW is on the way with your medicine ({REQ}). Please be ready for a short consultation before it is handed over." Guarded by `medicine_requests.approved_sms_at`, which is claimed atomically before dispatch. Pickup uses the same column.
  - Home service: "BHW on the way" on the first BHW accept, approve or confirm, sent once (`on_the_way_sms_at`).
  - Recipient is the account phone, falling back to `contact_number`. `WALKIN-` numbers are skipped.
  - Notification SMS are sent with `afterResponse()`.
- **Announcements:** an optional slide toggle on create (nurse and BHW) sends to all active, verified residents. The announcement stores `sms_sent_at` and `sms_recipient_count`. Editing never resends.
- **Phases:**
  - P0 read-only inventory
  - P1 phone format
  - P2 migrations + SmsService + OtpService
  - P3 registration OTP
  - P4 forgot-password OTP
  - P5 login OTP
  - P6 pickup/delivery/home-service SMS
  - P7 announcement toggle
  - P8 tests with `Http::fake()` and no real SMS
- Registration rows: `users.registration_unverified = true` until the code is entered. Nurses are notified only after verification. Re-registering the same number replaces the unverified row. Login by such a user redirects to `register.verify`. Legacy users keep `false`. `pending_phone_number` holds a self-service phone change until it's verified. A nurse editing another user's phone sets `phone_verified_at = null` with no OTP.
- Routes: `login.otp` (+ `.submit` / `.resend` / `.cancel`), `register.verify` (+ `.submit` / `.resend`), `forgot.reset-password` -> `password.otp` (+ `.submit` / `.resend`) -> `password.new` (+ `.submit`), `account.phone.{verify,resend,cancel}`.
- **Pending corrections (P10, drafted 2026-11-24, check whether applied):**
  1. `throttle:10,1` on POST /login and `throttle:5,10` on POST /register.
  2. Clear error when an account's phone isn't sendable (legacy or invalid numbers currently can't log in); the developer runs a SQL check for non-`09` numbers.
  3. `User::scopeRegistered()` to keep unverified registrations out of Reports, User Management, Global Search, Sync and dashboard counts.
  4. `User::purgeUnverifiedRegistration()` for staff-created accounts and walk-ins, plus a manual `registrations:prune-unverified {--hours=24} {--dry-run}` command.
  5. `Str::ascii()` in `SmsTemplates::sanitize()` so ñ and accents survive ("Pena", not "Pea").
  6. Comment that the SIM setting is unused.

## Security and privacy rules

- Secrets only in `.env`. Never read values aloud, log them, commit them or put them in tests.
- Residents' health data (allergies, chronic conditions, consultations, vaccinations) falls under the Philippine Data Privacy Act. Keep it out of logs, SMS text and error messages. SMS should carry only the request ID, item and service names, the BHW name and instructions, never diagnoses.
- OTP codes are hashed and single-use, compared with `hash_equals` / `Hash::check`, and never logged except through an explicit local-only fallback that is refused in production.
- Uploaded ID photos, profile photos and proof photos go to the `public` disk (`storage/app/public`, symlinked). Validate type and size the way the existing rules do.

## Testing rules

- Feature tests use the phpunit SQLite memory DB with `RefreshDatabase`. Never point tests at the Laragon MySQL database.
- Fake external calls with `Http::fake()` for httpSMS. No real SMS is sent in automated tests.
- Some migrations use MySQL-only enum alters. If a migration breaks on SQLite, guard it by driver and report it; don't weaken the production migration.
- Don't loosen assertions. Never claim something was tested unless it was run, and list what still needs manual checking (real SMS delivery on the phone, the UI in a browser).

## Working agreement with the developer

- The developer codes through an AI coding agent (GitHub Copilot or Claude Code). They want **exact, copy-paste-ready prompts** with precise file lists, hard rules and verification steps, and a diff or summary before multi-file changes are applied.
- Each phase runs as **Step A** (plan, read-only, report and wait), then **Step B** (implement after approval, summarize the diff first), then **Step C** (verify: lint, route list, tests, UI check, report).
- When the developer says "don't modify any files" or "planning only", read only. Stage files to inspect them and never commit them back.
- The coding agent doesn't commit, push or migrate the real DB until the developer says so. The developer backs up the database (`mysqldump` through `cmd /c` so PowerShell doesn't write UTF-16) before migrating.
- Stay in scope and mention an adjacent problem once.
- Present a plan summary first and the phase prompts after the developer confirms.
- After the developer reports that phases passed, review the updated folder read-only (device_list_dir + device_stage_files when device_bash is unavailable), rank corrections by severity, and hand back one fix prompt in the same Step A/B/C format.