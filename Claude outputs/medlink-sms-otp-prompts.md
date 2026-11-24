# MedLink: SMS + OTP phase prompts

Project: `C:\laragon\www\techyknowloadeia1` (Laravel 13, PHP 8.3, MySQL, Blade + Vue 3, httpSMS).

Install the guardrail kit first (see `README-guardrails.md`). Then paste one prompt at a time into your coding agent. Each phase has three steps:

- **Step A:** plan only. The agent reads the code, reports and waits.
- **Step B:** after you reply `APPROVED`, the agent shows a diff summary, then edits.
- **Step C:** the agent verifies its work and reports.

Don't start the next phase until Step C of the current one passes.

**Decisions locked:**

| # | Decision |
|---|---|
| 1 | Login OTP for all roles (resident, BHW, nurse) |
| 2 | Login OTP expires in 5 min; 5 wrong tries cancel the code and start a 30-min lockout |
| 3 | Changing the phone in a profile needs OTP on the new number |
| 4 | `OTP_LOG_FALLBACK` is local only and refused when `APP_ENV=production` |
| 5 | Delivery SMS is sent when the request is **approved** (no extra button) |
| - | Registration OTP expires in 30 min; 5 wrong tries cancel the code, with no lockout |
| - | Forgot-password OTP expires in 10 min; 5 wrong tries cancel the code and start a 30-min lockout |
| - | Phones are stored as `09XXXXXXXXX` and sent as `+639XXXXXXXXX`; masks are `+639******456` or `09******456`, matching how the user typed the number |

**Phase order:**

| Phase | Work |
|---|---|
| P0 | Read-only inventory |
| P1 | Philippine phone format |
| P2 | Migrations + SMS/OTP services |
| P3 | Registration OTP |
| P4 | Forgot-password OTP |
| P5 | Login OTP |
| P6 | Phone-change OTP |
| P7 | Pickup / delivery / home-service SMS |
| P8 | Announcement SMS toggle |
| P9 | Tests + full sweep |

---

## Shared rules (already included in every prompt below)

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Read existing sibling code before writing new code and match its style (validation messages, dual JSON/redirect responses, AppNotification, AccountLog::record).
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only. Do not edit, create or delete any file. Report, then STOP and wait for me to reply "APPROVED".
- Step B = before editing, show a per-file diff summary (what changes, why). Then apply. Touch only the files listed; if another file must change, ask first.
- Never print, log or copy any .env value. Refer to keys by name only.
- Do not run `php artisan migrate` against the real database. Use `php artisan migrate --pretend` only. I run real migrations myself after a backup.
- Do not commit or push.
- Keep every existing in-app AppNotification and AccountLog entry; SMS is an extra channel.
- Never put OTP codes, passwords, message bodies or health data into logs, AccountLog details, sms_logs or error messages.
- Step C = run `php -l` on every touched PHP file, `php artisan route:list` if routes changed, `npm run build` if any .vue/.js under resources/ changed, then report exactly what was run and what I still need to test by hand. Never claim something was tested if it was not run.
```

---

## P0: Read-only inventory

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- This whole phase is READ-ONLY. Do not create, edit or delete any file. Do not run migrations.
- Never print any .env value; list key names only.

TASK: Produce an inventory report for an upcoming SMS + OTP feature. Do not propose code yet.

1. PHONE VALIDATION: List every place in app/ that validates a phone with `regex:/^09[0-9]{9}$/` or similar (file, method, line, field name). I expect ~10: AuthController (register, resetPassword), ResidentDashboardController (phone_number, emergency_contact_phone), NurseDashboardController profile, UserManagementController store/update, BhwDashboardController storeNurse/updateProfile, WalkinController, HomeServiceController contact_number (store, storeResident). Report any extra ones.
2. PHONE LOOKUPS/SEARCH: List every query that matches on phone_number or contact_number (login, findAccount, resetPassword, walk-in duplicate check, search in PatientVerificationController, UserManagementController, HomeServiceController, GlobalSearchController, etc.).
3. PHONE INPUTS IN UI: List every phone input in resources/views/**/*.blade.php and resources/js/components/**/*.vue (file, line, field name, current maxlength/pattern/placeholder, and any JS that restricts input).
4. AUTH FLOW: Describe the current flow in AuthController::register, login, resetPassword, findAccount, and the login.blade.php forgot-account modals (which inputs, which error bags, the open_forgot_modal session keys). Describe how active_session_token is issued in each login path.
5. PROFILE UPDATES: For ResidentDashboardController::updateProfile, NurseDashboardController::updateProfile, BhwDashboardController::updateProfile, UserManagementController::update: how the phone is updated and what the response looks like (JSON vs redirect), and which view/Vue component calls each.
6. NOTIFICATION TRIGGERS: Show the exact methods and lines for:
   - MedicineRequestController::approve (pickup vs delivery branch, who can call it: nurse/BHW)
   - BhwDashboardController::acceptHomeService, approveHomeService, confirmHomeService
   - AnnouncementController::store, and the create forms in dashboards/nurse-announcements.blade.php and components/bhw/BhwAnnouncements.vue
7. ENV/CONFIG: Confirm config/services.php has no httpsms entry; list .env and .env.example key NAMES for HTTPSMS_*; confirm QUEUE_CONNECTION, APP_ENV key exists.
8. TESTS: Report whether `php artisan migrate:fresh --env=testing` style migration on SQLite (phpunit.xml uses sqlite :memory:) would break on any migration using MySQL-only SQL (e.g. DB::statement ALTER ... ENUM). List those migration files.
9. WALKIN placeholders: Show where `WALKIN-` phone placeholders are created and where phone numbers are displayed in the UI.

Also give me these read-only SQL checks to run myself in HeidiSQL/MySQL (do NOT run them):
- users whose phone_number is not 09XXXXXXXXX (excluding WALKIN-)
- users whose emergency_contact_phone is set and not 09XXXXXXXXX
- home_service_requests whose contact_number is not 09XXXXXXXXX

OUTPUT: one structured report with the sections above. Then STOP.
```

---

## P1: Philippine phone format (auto-detect `09` / `+63`)

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = show per-file diff summary first, then apply. Touch only the files listed below; ask before touching others.
- Never print .env values. No migrations in this phase. No commits.
- Step C = php -l on touched PHP, npm run build if resources/js changed, report what ran + manual checks.

GOAL: Accept ONLY Philippine mobile numbers, typed as either 09XXXXXXXXX (11 chars) or +639XXXXXXXXX (13 chars). Auto-detect the format from how the user starts typing. Store as 09XXXXXXXXX (unchanged storage format, unique index keeps working). Remove the copied regex everywhere.

CREATE
1. app/Support/PhoneNumber.php (final class, static methods):
   - clean(?string $raw): string  -> trim, remove spaces, dashes, dots, parentheses.
   - detectFormat(?string $raw): ?string -> 'e164' if cleaned starts with '+63', 'local' if starts with '09', else null.
   - normalize(?string $raw): ?string -> returns 09XXXXXXXXX or null.
       '+639XXXXXXXXX' (exactly) -> '09XXXXXXXXX'
       '09XXXXXXXXX'  (exactly) -> same
       anything else (landline, 639..., 9XXXXXXXXX, other countries) -> null
   - toE164(string $local09): string -> '+63' . substr($local09, 1)
   - mask(?string $phone, ?string $format = null): string
       format 'e164' -> '+639******' + last 3 digits   e.g. +639******456
       otherwise     -> '09******'  + last 3 digits    e.g. 09******456
       ($format null = detect from $phone)
   - isPlaceholder(?string $phone): bool -> true for null/empty or starting with 'WALKIN-'
   - isSendable(?string $phone): bool -> !isPlaceholder && normalize() !== null
   - mergeNormalized(\Illuminate\Http\Request $request, array $fields): void
       for each field present and non-empty: if normalize() succeeds, $request->merge([field => normalized]); otherwise leave the raw value so validation fails with the proper message.
2. app/Rules/PhilippineMobile.php implements Illuminate\Contracts\Validation\ValidationRule.
   Fails unless PhoneNumber::normalize($value) !== null.
   Message: "The :attribute must be a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX."
3. public/js/ph-phone.js (plain JS, no build step, for Blade pages):
   - Auto-attaches to every input[data-ph-phone].
   - On input: allow digits and a single leading '+'. If value starts with '+': only '+63' prefix allowed, maxlength 13. If starts with '0': must be '09', maxlength 11. Otherwise show the hint.
   - Shows a small hint element after the input: "Format: 09XXXXXXXXX" / "Format: +639XXXXXXXXX" / red "Use 09XXXXXXXXX or +639XXXXXXXXX".
   - On blur: mark invalid (red border class matching existing Tailwind error style) if not a complete valid number. Do not block submit; server validation is the source of truth.
4. resources/js/utils/phPhone.js (ES module for Vue): export clean, detectFormat, normalize, isValid, maxLengthFor(value), hintFor(value), mask(phone, format). Same rules as the PHP helper.

UPDATE (replace every `regex:/^09[0-9]{9}$/` with `new PhilippineMobile`, and call PhoneNumber::mergeNormalized($request, [...]) BEFORE validate so `unique` checks the normalized value):
- app/Http/Controllers/AuthController.php: register (phone_number), resetPassword (recover_phone_number). login: normalize $request->phone_number before the lookup (keep the raw input for onlyInput). findAccount: use PhoneNumber::mask($user->phone_number) for the hint.
- app/Http/Controllers/ResidentDashboardController.php: updateProfile (phone_number, emergency_contact_phone).
- app/Http/Controllers/NurseDashboardController.php: updateProfile.
- app/Http/Controllers/UserManagementController.php: store, update.
- app/Http/Controllers/BhwDashboardController.php: storeNurse, updateProfile.
- app/Http/Controllers/WalkinController.php: store (phone_number stays nullable; keep the WALKIN- placeholder logic for empty phone).
- app/Http/Controllers/HomeServiceController.php: store, storeResident (contact_number).
- Search/lookups found in P0 (PatientVerificationController, UserManagementController, HomeServiceController, GlobalSearchController): if the search term normalizes to a valid PH mobile, search with the normalized value; otherwise keep current behaviour.
- Remove any custom 'phone_number.regex' style messages that mention only "starting with 09"; the rule's message replaces them.

UPDATE UI (every phone input listed in P0):
- Blade (auth/login, auth/register, nurse pages): type="tel" inputmode="tel" autocomplete="tel", add data-ph-phone, remove maxlength="11"/pattern that blocks '+', placeholder "09XXXXXXXXX or +639XXXXXXXXX", and include <script src="{{ asset('js/ph-phone.js') }}"></script> once per page.
- Vue components (resident Profile.vue, HomeService.vue, bhw BhwProfile.vue, WalkinRegistration.vue, NurseManagement.vue, any other from P0): use utils/phPhone.js for maxlength, hint text and blur validation. Same placeholder.
- Anywhere the UI displays a phone that starts with 'WALKIN-', show "No phone" instead (display only, no data change).

DO NOT: change the users.phone_number column, add migrations, or touch OTP/SMS (later phases).

STEP C CHECKS
- php -l on all touched PHP files; npm run build.
- php artisan tinker one-liners (read-only) proving: normalize('+639171234567')=='09171234567', normalize('09171234567')=='09171234567', normalize('639171234567')===null, normalize('0281234567')===null, mask('09171234567','e164')=='+639******567', mask('09171234567')=='09******567'.
- Manual checklist for me: register with +63 format, log in with the other format, profile edit, walk-in, home service form, nurse user add/edit, search by +63 number.
```

---

## P2: Foundation: migrations, config, SmsService, OtpService

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = per-file diff summary first, then apply. Touch only listed files.
- Never print .env values. Do NOT run migrate on the real DB; run `php artisan migrate --pretend` only. No commits.
- Step C = php -l, migrate --pretend output, report.

GOAL: Build the shared SMS + OTP layer. No user-facing flow changes yet.

A. MIGRATIONS (new timestamped files in database/migrations, all with working down()):
1. add_otp_fields_to_users_table:
   - users.phone_verified_at timestamp nullable (after phone_number)
   - users.registration_unverified boolean default false  (true ONLY for new self-registrations awaiting OTP; legacy users stay false)
   - users.pending_phone_number string(11) nullable (for phone change)
2. create_otp_codes_table:
   id; user_id nullable FK users cascadeOnDelete; phone string(11) index; purpose string(20) ('registration','login','password_reset','phone_change');
   code_hash string; expires_at timestamp; attempts unsignedTinyInteger default 0;
   sent_at timestamp; consumed_at nullable timestamp; invalidated_at nullable timestamp; invalidated_reason string(30) nullable;
   ip_address string(45) nullable; timestamps; index [phone, purpose].
3. create_otp_lockouts_table:
   id; phone string(11); purpose string(20); locked_until timestamp; timestamps; unique [phone, purpose].
4. create_sms_logs_table:
   id; user_id nullable FK users nullOnDelete; purpose string(30); phone_masked string(20);
   status string(10) ('sent','failed','skipped'); provider_message_id string nullable; error_code string(60) nullable;
   related_type string nullable; related_id unsignedBigInteger nullable; timestamps; index [related_type, related_id].
   NO message body column.
5. add_sms_tracking_columns:
   - medicine_requests.approved_sms_at timestamp nullable
   - home_service_requests.on_the_way_sms_at timestamp nullable
   - announcements.sms_sent_at timestamp nullable, announcements.sms_recipient_count unsignedInteger nullable

B. MODELS
- app/Models/User.php: add phone_verified_at, registration_unverified, pending_phone_number to #[Fillable]; casts phone_verified_at datetime, registration_unverified boolean.
- New app/Models/OtpCode.php, app/Models/OtpLockout.php, app/Models/SmsLog.php (fillable + datetime casts; OtpCode hides code_hash).
- MedicineRequest, HomeServiceRequest, Announcement: add the new columns to $fillable/$casts.

C. CONFIG
- config/services.php add:
  'httpsms' => [
      'api_key'  => env('HTTPSMS_API_KEY'),
      'from'     => env('HTTPSMS_FROM_NUMBER'),
      'sim'      => env('HTTPSMS_SIM_SLOT', 'SIM1'),
      'base_url' => env('HTTPSMS_BASE_URL', 'https://api.httpsms.com'),
      'timeout'  => 10,
  ],
- New config/otp.php:
  'expiry_minutes' => ['registration' => 30, 'login' => 5, 'password_reset' => 10, 'phone_change' => 10],
  'max_attempts' => 5,
  'lockout_minutes' => 30,
  'lockout_purposes' => ['login', 'password_reset', 'phone_change'],   // registration: invalidate only, no lockout
  'resend_cooldown_seconds' => 60,
  'max_sends_per_hour' => 5,
  'log_fallback' => (bool) env('OTP_LOG_FALLBACK', false),
- .env.example: add key names only: HTTPSMS_API_KEY=, HTTPSMS_FROM_NUMBER=, HTTPSMS_SIM_SLOT=SIM1, OTP_LOG_FALLBACK=false. Do NOT touch .env (I will add OTP_LOG_FALLBACK myself).

D. app/Support/SmsTemplates.php (all SMS text in ONE place; plain GSM-7: no emoji, no em/en dashes, no smart quotes):
- otp(string $purpose, string $code, int $minutes): string
    registration:   "MedLink: Your verification code is {code}. Valid for {m} minutes. Do not share this code with anyone."
    login:          "MedLink: Your login code is {code}. Valid for {m} minutes. Do not share this code with anyone."
    password_reset: "MedLink: Your password reset code is {code}. Valid for {m} minutes. If you did not request this, ignore this message."
    phone_change:   "MedLink: Your code to confirm this new number is {code}. Valid for {m} minutes. Do not share this code."
- sanitize(string): replace em/en dash with '-', smart quotes with straight, strip non-GSM characters, collapse whitespace/newlines.
- fit(string $text, int $max = 160): truncate with '...' to $max chars.
(Notification and announcement templates are added in P7/P8.)

E. app/Services/SmsService.php
- isConfigured(): bool -> api_key and from are non-empty.
- send(string $phone, string $message, string $purpose, ?User $user = null, ?Model $related = null): array{ok:bool,status:string,error:?string}
  - If !PhoneNumber::isSendable($phone) -> write sms_logs status 'skipped' error_code 'invalid_or_placeholder', return.
  - If !isConfigured() -> sms_logs 'skipped' error_code 'not_configured', return.
  - POST {base_url}/v1/messages/send with header x-api-key, JSON {from, to: PhoneNumber::toE164(), content: SmsTemplates::sanitize($message)} and the SIM field if httpSMS supports it (normalize '1'->'SIM1', '2'->'SIM2').
    VERIFY the endpoint path, header name and JSON field names against https://docs.httpsms.com (or the httpSMS API reference) before coding. If you cannot check the docs, use these and tell me clearly in the Step B summary.
  - Use Http::withHeaders()->timeout()->acceptJson(). Catch ConnectionException/RequestException; never throw.
  - Log to sms_logs: purpose, PhoneNumber::mask(), status sent/failed, provider message id if returned, short error_code (HTTP status or exception class). NEVER the message body or the API key.
- sendAfterResponse(...same args...): void -> dispatch(fn () => app(SmsService::class)->send(...))->afterResponse(); (pass ids, not models, into the closure and reload inside). Confirm this runs without a queue worker even though QUEUE_CONNECTION=database.

F. app/Services/OtpService.php
- issue(string $phone, string $purpose, ?User $user = null): array{ok:bool, error:?string, retry_after:?int, expires_in_minutes:?int}
  errors: 'locked' (retry_after = seconds left), 'cooldown' (60s since last sent_at for phone+purpose), 'hourly_limit' (>= 5 sends in last 60 min), 'sms_unavailable', 'sms_failed'.
  Steps: check lockout -> cooldown -> hourly limit -> invalidate previous active codes for phone+purpose (reason 'superseded') -> code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT) -> store Hash::make(code), expires_at = now()+expiry_minutes[purpose], sent_at, ip -> send SYNCHRONOUSLY via SmsService (purpose 'otp_'.$purpose).
  If SMS not configured: if config('otp.log_fallback') AND !app()->environment('production') -> Log::info('[OTP-DEV] '.$purpose.' '.PhoneNumber::mask($phone).' code='.$code) and return ok; else invalidate the code (reason 'sms_unavailable') and return 'sms_unavailable'.
  If SMS send fails: invalidate (reason 'sms_failed'), return 'sms_failed'.
  In production, log_fallback must NEVER log a code, even if OTP_LOG_FALLBACK=true.
- verify(string $phone, string $purpose, string $code): array{ok:bool, error:?string, attempts_left:?int, retry_after:?int}
  errors: 'locked', 'no_code', 'expired', 'invalid', 'too_many_attempts', 'bad_format'.
  - Reject unless code matches ^\d{6}$ ('bad_format', does NOT count as an attempt).
  - Lockout check first.
  - In a DB::transaction with lockForUpdate on the latest active code (not consumed, not invalidated) for phone+purpose.
  - Expired -> invalidate ('expired'), return 'expired'.
  - Hash::check fails -> attempts++. If attempts >= max_attempts -> invalidate ('too_many_attempts'); if purpose in lockout_purposes -> upsert otp_lockouts locked_until = now()+30min; return 'too_many_attempts' (+ retry_after when locked). Else return 'invalid' with attempts_left.
  - Success -> consumed_at = now(), return ok.
- lockoutRemaining(string $phone, string $purpose): ?int (seconds).
- errorMessage(array $result): string -> user-readable text in one place, e.g.
  locked: "Too many incorrect codes. Try again in {N} minutes."
  cooldown: "Please wait {N} seconds before requesting a new code."
  hourly_limit: "Too many codes requested. Try again later."
  sms_unavailable / sms_failed: "We couldn't send the code right now. Please try again or contact Barangay Honrado Health Center."
  expired: "This code has expired. Request a new one."
  invalid: "Incorrect code. {N} attempt(s) left."
  too_many_attempts: "Too many incorrect attempts. This code is no longer valid." (+ " Try again in 30 minutes." when locked)
  no_code: "No active code. Request a new one."
  bad_format: "Enter the 6-digit code."

G. SHARED OTP VIEW: resources/views/auth/otp.blade.php (Tailwind, match login.blade.php look).
  Variables: $title, $masked (e.g. 09******456), $verifyUrl, $resendUrl, $cancelUrl, $expiresMinutes, $resendAvailableIn (seconds).
  Header text: "We sent a 6-digit code to {masked}". One 6-digit input (inputmode numeric, autocomplete one-time-code, maxlength 6), Verify button, Resend button disabled with a countdown until $resendAvailableIn hits 0, Cancel link, error/status flash area, @csrf.

DO NOT wire any routes or controllers in this phase.

STEP C CHECKS
- php -l on all new/changed PHP files.
- php artisan migrate --pretend (paste output summary).
- php artisan tinker, read-only: config('otp.expiry_minutes'), app(App\Services\SmsService::class)->isConfigured() (print true/false only).
- Report the httpSMS API details you used and whether you verified them against the docs.
- Reminder for me: back up the DB (cmd /c "mysqldump ... > C:\laragon-backups\medlink_before_otp.sql"), then run `php artisan migrate`.
```

---

## P3: Registration OTP (30 min)

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = per-file diff summary first, then apply. Touch only listed files.
- Never print .env values. No migrations. No commits. Never log OTP codes.
- Step C = php -l, route:list, report + manual checklist.
- Prerequisite: P1 and P2 done and migrated.

GOAL: A new resident account must verify its phone by SMS OTP before it exists for the system.

FILES: app/Http/Controllers/AuthController.php, routes/web.php, resources/views/auth/register.blade.php (only if needed for messages), resources/views/auth/otp.blade.php (reuse), app/Http/Controllers/PatientVerificationController.php, any nurse list/count that shows pending residents (from P0).

1. Extract a private method in AuthController:
   completeLogin(Request $request, User $user, bool $remember, string $logDetails): RedirectResponse
   -> Auth::login, session regenerate, new active_session_token (same as current code), last_login_at, AccountLog 'Logged In' with $logDetails, redirect to pending.approval for pending residents else dashboard. Refactor existing login/register code to use it WITHOUT changing behaviour yet.
2. register():
   - Before validation: PhoneNumber::mergeNormalized. Keep the raw typed phone's format: $format = PhoneNumber::detectFormat($request->input('phone_number')) captured BEFORE merge.
   - Unique rule: phone must be unique among users EXCEPT rows where registration_unverified = true.
   - If a row exists with the same phone AND registration_unverified = true: delete its id_photo file from the public disk and delete the row (AccountLog not needed for a never-verified row).
   - Create the user exactly as today PLUS registration_unverified = true, phone_verified_at = null.
   - REMOVE from register(): the nurse AppNotification loop, Auth::login, session token, 'Logged In' log. Keep the 'Account Created' AccountLog.
   - OtpService::issue(phone, 'registration', $user). On failure: keep the row, redirect to the verify page anyway with the error flashed (user can press Resend).
   - session: reg_otp_user_id, reg_otp_format. Redirect route('register.verify').
3. New routes (guest, outside auth middleware):
   GET  /register/verify         -> AuthController@showRegisterOtp   name register.verify
   POST /register/verify         -> AuthController@verifyRegisterOtp name register.verify.submit  throttle:10,1
   POST /register/verify/resend  -> AuthController@resendRegisterOtp name register.verify.resend  throttle:5,1
   All three: if session reg_otp_user_id missing or the user no longer has registration_unverified = true -> redirect to register with a message.
4. showRegisterOtp: render auth/otp with title "Verify your phone number", masked = PhoneNumber::mask($user->phone_number, session format), expiresMinutes 30, resendAvailableIn from the latest code's sent_at.
5. verifyRegisterOtp: OtpService::verify(phone, 'registration', code).
   - Failure: back with OtpService::errorMessage. 5 wrong -> code invalidated, message tells user to resend (NO 30-min lockout for registration).
   - Success: registration_unverified = false, phone_verified_at = now(); AccountLog 'Phone Verified' ("Phone verified via SMS OTP during registration."); NOW run the nurse "New Resident Verification Needed" notification loop (moved from register); forget session keys; completeLogin(..., 'User logged in after phone verification.') -> pending.approval.
6. resendRegisterOtp: OtpService::issue again; back with status or errorMessage.
7. login(): if the account has registration_unverified = true -> do NOT log in; put reg_otp_user_id + format in session, issue a new registration OTP (respect cooldown), redirect to register.verify with "Please verify your phone number to finish registration."
8. Nurse side: PatientVerificationController index/counts and any pending-resident counters must exclude registration_unverified = true rows. findAccount / resetPassword must ignore registration_unverified rows.

STEP C CHECKS
- php -l, php artisan route:list --name=register.
- With OTP_LOG_FALLBACK=true and SMS key blank on my machine (I set this, not you): explain how I read the code from storage/logs/laravel.log.
- Manual checklist: register -> code page shows 09******xxx or +639******xxx matching what I typed; wrong code x5 -> invalidated; expired after 30 min; resend cooldown 60s; re-register same number while unverified replaces the old row; nurse is notified only after verification; unverified user trying to log in is sent to verification.
```

---

## P4: Forgot password OTP (10 min, 5 tries, then a 30-min lockout)

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = per-file diff summary first, then apply. Touch only listed files.
- Never print .env values. No migrations. No commits. Never log OTP codes or passwords.
- Step C = php -l, route:list, report + manual checklist.
- Prerequisite: P1-P3 done.

GOAL: Replace the current "name + role + phone -> set new password" with a 3-step OTP flow.

FILES: app/Http/Controllers/AuthController.php, routes/web.php, resources/views/auth/login.blade.php (forgot modal), resources/views/auth/otp.blade.php (reuse), new resources/views/auth/reset-password.blade.php.

1. Step 1 (login.blade.php "reset" modal): fields recover_role, recover_first_name, recover_last_name, recover_phone_number ONLY. Remove new_password / new_password_confirmation from this modal. Keep the 'forgot' error bag and open_forgot_modal behaviour.
   resetPassword() becomes requestPasswordReset(): validate as today (PhilippineMobile), find the matching user (exclude registration_unverified), keep the current "No matching account found..." message on mismatch.
   - If otp lockout active for phone+'password_reset' -> back to the modal with "Too many incorrect codes. Try again in N minutes."
   - OtpService::issue(phone, 'password_reset', $user). On error -> back to the modal with errorMessage.
   - session: pwreset_user_id, pwreset_format (detected from the typed number). Redirect route('password.otp').
   Keep route path POST /forgot-account/reset-password (name unchanged) pointing to requestPasswordReset, throttle:5,1 stays.
2. Routes (guest):
   GET  /forgot-account/verify          showPasswordOtp      name password.otp
   POST /forgot-account/verify          verifyPasswordOtp    name password.otp.submit   throttle:10,1
   POST /forgot-account/verify/resend   resendPasswordOtp    name password.otp.resend   throttle:5,1
   GET  /forgot-account/new-password    showNewPassword      name password.new
   POST /forgot-account/new-password    updatePassword       name password.new.submit   throttle:5,1
3. showPasswordOtp: auth/otp with title "Reset your password", masked number, expiresMinutes 10.
4. verifyPasswordOtp: OtpService::verify(phone,'password_reset',code).
   - 5 wrong -> code invalidated + 30-minute lockout (handled by OtpService). Show "Too many incorrect attempts. Try again in 30 minutes." and disable resend until the lockout ends.
   - Success -> session pwreset_verified_user_id + pwreset_verified_at (now); forget pwreset_user_id; redirect password.new.
5. showNewPassword / updatePassword: only allowed when pwreset_verified_user_id is set AND pwreset_verified_at is within 10 minutes; otherwise redirect to login with "Your reset session expired. Start again."
   updatePassword: validate new_password required|string|min:8|confirmed (keep current messages). Save Hash::make, set active_session_token = null (logs out other devices), AccountLog 'Password Reset' ("Password reset via SMS OTP."), forget all pwreset_* keys, redirect login with "Password updated successfully! You can now sign in with your new password."
6. reset-password.blade.php: Tailwind, matches login page look, two password fields with show/hide like the login page.
7. findAccount stays as is (masked hint via PhoneNumber::mask from P1).

STEP C CHECKS
- php -l, php artisan route:list --path=forgot-account.
- Manual checklist: wrong details -> same message as before; correct -> code page with masked number; wrong x5 -> invalidated + 30-min lockout, resend blocked, new request from the modal also blocked; code expires at 10 min; new-password page refuses direct access without a verified OTP; after reset, the other logged-in device is kicked out.
```

---

## P5: Login OTP for all roles (5 min, 5 tries, then a 30-min lockout)

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = per-file diff summary first, then apply. Touch only listed files.
- Never print .env values. No migrations. No commits. Never log OTP codes or passwords.
- Step C = php -l, route:list, report + manual checklist.
- Prerequisite: P1-P4 done.

GOAL: Every login (resident, BHW, nurse) needs an SMS OTP after the password check. The user is NOT authenticated until the code is verified.

FILES: app/Http/Controllers/AuthController.php, routes/web.php, resources/views/auth/otp.blade.php (reuse), resources/views/auth/login.blade.php (only for flash messages if needed).

1. login(): keep all existing checks in the same order (phone lookup using normalized phone, password, role, pending, inactive). Registration-unverified accounts go to the P3 flow.
   Instead of Auth::login / completeLogin:
   - If lockout active for phone+'login' -> back with "Too many incorrect codes. Try again in N minutes." (onlyInput phone_number).
   - OtpService::issue(phone, 'login', $user). On error -> back with errorMessage.
   - session (regenerate session id first): login_otp_user_id, login_otp_remember (bool), login_otp_format (detected from what the user typed), login_otp_started_at. Redirect route('login.otp').
2. Routes (guest, outside auth middleware):
   GET  /login/verify         showLoginOtp     name login.otp
   POST /login/verify         verifyLoginOtp   name login.otp.submit  throttle:10,1
   POST /login/verify/resend  resendLoginOtp   name login.otp.resend  throttle:5,1
   POST /login/verify/cancel  cancelLoginOtp   name login.otp.cancel  (forget login_otp_* and go back to login)
   If login_otp_user_id is missing, or login_otp_started_at is older than 15 minutes -> redirect to login "Please sign in again."
3. showLoginOtp: auth/otp, title "Confirm it's you", masked = PhoneNumber::mask(phone, login_otp_format), expiresMinutes 5.
4. verifyLoginOtp: OtpService::verify(phone,'login',code).
   - 5 wrong -> invalidated + 30-min lockout; forget login_otp_* and redirect to login with the lockout message.
   - Success: re-check the user still exists and is not inactive; set phone_verified_at if null; forget login_otp_*; completeLogin($request, $user, remember, 'Successful authentication (password + SMS OTP).'). Pending residents still land on pending.approval.
5. Make sure the `auth` / `single.device` route groups are unreachable during the OTP step, and the "remember me" cookie is only set after verification.
6. Keep the existing ?kicked=1 / session_kicked message on the login page working.

STEP C CHECKS
- php -l, php artisan route:list --path=login.
- Manual checklist: each role (resident, BHW, nurse) asked for a code; mask matches typed format (+63 vs 09); wrong x5 -> 30-min lockout and back to login; code expires at 5 min; resend 60s cooldown; direct visit to /dashboard during OTP step redirects to login; pending resident -> pending page after OTP; second device login still kicks the first.
- Warn me: if httpSMS is down, staff cannot log in (only OTP_LOG_FALLBACK on localhost helps).
```

---

## P6: Phone change needs OTP

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = per-file diff summary first, then apply. Touch only listed files.
- Never print .env values. No migrations. No commits. Never log OTP codes.
- Step C = php -l, route:list, npm run build, report + manual checklist.
- Prerequisite: P1-P5 done.

GOAL: When a logged-in user changes their OWN phone number, the new number must be verified by OTP before it replaces the old one. Other profile fields save immediately as today.

FILES: app/Http/Controllers/ResidentDashboardController.php (updateProfile), app/Http/Controllers/NurseDashboardController.php (updateProfile), app/Http/Controllers/BhwDashboardController.php (updateProfile), new app/Http/Controllers/PhoneChangeController.php, routes/web.php, resources/js/components/resident/Profile.vue, resources/js/components/bhw/BhwProfile.vue, resources/views/dashboards/nurse-profile.blade.php, new resources/js/components/shared/PhoneOtpModal.vue (or equivalent), new Blade partial for the nurse modal.

1. In each updateProfile: if the normalized submitted phone_number differs from the current one:
   - Validate it (PhilippineMobile + unique among users excluding self and registration_unverified rows).
   - Do NOT change phone_number. Set pending_phone_number = new number. Save the other fields normally.
   - If lockout active for (new phone,'phone_change') -> return error with remaining minutes.
   - OtpService::issue(new phone, 'phone_change', $user). Store session phone_change_format (typed format).
   - Respond in the existing response style: JSON {success: true, otp_required: true, masked: "...", message: "Profile saved. Enter the code sent to {masked} to confirm your new number."} for AJAX/Vue; for Blade redirect back with session flags so the modal opens.
2. PhoneChangeController (inside auth + single.device group, any role):
   POST /account/phone/verify  (code)  name account.phone.verify  throttle:10,1
   POST /account/phone/resend          name account.phone.resend  throttle:5,1
   POST /account/phone/cancel          name account.phone.cancel  -> pending_phone_number = null
   verify: requires pending_phone_number. OtpService::verify(pending,'phone_change',code). 5 wrong -> invalidated + 30-min lockout.
   Success: re-check uniqueness inside a transaction; phone_number = pending; pending_phone_number = null; phone_verified_at = now(); AccountLog 'Phone Number Changed' with details "Changed from {old masked} to {new masked} via SMS OTP." Respond JSON/redirect like siblings.
3. UI: a modal "Confirm your new number" with the masked number header, 6-digit input, Verify, Resend (60s countdown), Cancel. Vue components share one modal component; nurse Blade page gets the same behaviour with inline JS + fetch (CSRF from meta tag).
4. UserManagementController::update (a nurse editing ANOTHER user's phone): no OTP; set phone_verified_at = null when the phone changes, and add an AccountLog note. Their next login OTP verifies the new number.

STEP C CHECKS
- php -l, route:list --path=account/phone, npm run build.
- Manual checklist: resident/BHW/nurse change phone -> modal with mask in typed format; other fields saved immediately; phone not changed until verified; wrong x5 -> lockout; cancel clears pending; duplicate number rejected; AccountLog entry has masked numbers only.
```

---

## P7: Pickup, delivery and home-service SMS

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = per-file diff summary first, then apply. Touch only listed files.
- Never print .env values. No migrations. No commits. No health data in SMS.
- Step C = php -l, report + manual checklist.
- Prerequisite: P2 done (SmsService, columns approved_sms_at / on_the_way_sms_at).

GOAL: Send SMS for medicine pickup, medicine delivery (on approve) and home service "BHW on the way". SMS is extra; keep all existing AppNotifications. A failed or skipped SMS never blocks or fails the action.

FILES: app/Support/SmsTemplates.php, app/Http/Controllers/MedicineRequestController.php (approve), app/Http/Controllers/BhwDashboardController.php (acceptHomeService, approveHomeService, confirmHomeService).

1. SmsTemplates additions (each passed through fit(..., 160); shorten the item/service name first, never the instructions):
   - pickupReady(reqId, item, qty):
     "MedLink: Your request {REQ-012} ({item} x{qty}) is approved and ready for pickup at Barangay Honrado Health Center."
   - deliveryOnTheWay(reqId, ?bhwName):
     with name: "MedLink: BHW {name} is on the way with your medicine ({REQ-012}). Please be ready for a short consultation before it is handed over."
     without:   "MedLink: A BHW is on the way with your medicine ({REQ-012}). Please be ready for a short consultation before it is handed over."
   - homeServiceOnTheWay(hsId, bhwName, serviceType):
     "MedLink: {bhwName} is on the way for your home service ({HS-004}, {service}). Please be ready at your address."
2. MedicineRequestController::approve, AFTER the status update succeeds and only if approved_sms_at is null:
   - pickup  -> pickupReady
   - delivery -> deliveryOnTheWay (bhwName = approver's name when the approver is a BHW, else null)
   - recipient: $medicineRequest->user->phone_number
   - set approved_sms_at = now() BEFORE dispatching (prevents duplicates on double-click), then SmsService::sendAfterResponse(..., purpose 'medicine_pickup' | 'medicine_delivery', $user, $medicineRequest).
3. BhwDashboardController acceptHomeService / approveHomeService / confirmHomeService, after save, only if on_the_way_sms_at is null:
   - recipient: $homeService->user->phone_number if user_id, else $homeService->contact_number.
   - set on_the_way_sms_at = now(), then sendAfterResponse(..., purpose 'home_service_on_the_way', ...).
   - Nurse-side HomeServiceController actions send NO SMS (assignment is not "on the way").
4. SmsService already skips WALKIN-/invalid numbers and an unconfigured key; do not add extra checks that throw.

STEP C CHECKS
- php -l on touched files.
- Show me the three rendered example messages with their character counts (all <= 160).
- Manual checklist: approve pickup -> 1 SMS; approve delivery by nurse and by BHW -> 1 SMS each with the right wording; approve twice/double-click -> still 1 SMS; BHW accept then confirm on a home service -> only 1 SMS; walk-in placeholder -> sms_logs 'skipped'; action still succeeds with SMS key blank.
```

---

## P8: Announcement SMS toggle

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1. Match existing code style.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = per-file diff summary first, then apply. Touch only listed files.
- Never print .env values. No migrations. No commits.
- Step C = php -l, npm run build, report + manual checklist.
- Prerequisite: P2 done (announcements.sms_sent_at, sms_recipient_count).

GOAL: An optional slide/toggle switch on the CREATE announcement form (nurse and BHW) to also send the announcement by SMS to all registered residents.

FILES: app/Http/Controllers/AnnouncementController.php (store, nurseIndex if counts needed), app/Support/SmsTemplates.php, resources/views/dashboards/nurse-announcements.blade.php, resources/js/components/bhw/BhwAnnouncements.vue, plus wherever BHW/resident announcement lists render cards (show the badge only on staff views).

1. UI: toggle switch "Also send via SMS to all registered residents", OFF by default, with helper text "Sends a text message to every active resident with a valid mobile number. Uses the health center phone's SMS load." Only shown on CREATE, never on edit. Disabled (with tooltip "SMS is not configured") when SmsService::isConfigured() is false (pass a boolean to the view / Vue prop).
2. store(): validate 'send_sms' => ['nullable','boolean']. Only when send_sms is true AND the announcement status is 'active':
   - recipients: users role 'resident', status 'active', registration_unverified = false, PhoneNumber::isSendable(phone_number). Unique numbers only.
   - message: SmsTemplates::announcement(title, content) = "MedLink Announcement: {title}. {content}" sanitized, newlines collapsed, fit to 306 characters (2 SMS segments).
   - set sms_sent_at = now(), sms_recipient_count = count BEFORE dispatch.
   - dispatch ONE afterResponse closure that calls set_time_limit(0) and loops recipients calling SmsService::send(..., purpose 'announcement', $user, $announcement) with a 200 ms pause between sends. Pass ids into the closure, not models.
   - Add to the success message: " SMS is being sent to {N} residents."
3. update(): never sends SMS (ignore send_sms if present).
4. Staff cards: show a small badge "SMS sent to {N} residents - {date}" when sms_sent_at is set.
5. Note in your Step C report: if the resident count grows past ~300, a queued job with `php artisan queue:work` is more reliable than afterResponse.

STEP C CHECKS
- php -l, npm run build.
- Example rendered message + length for a long announcement.
- Manual checklist: toggle off -> no SMS; toggle on (nurse and BHW) -> sms_logs rows = recipient count; inactive/pending/unverified/walk-in residents excluded; archived-status announcement with toggle on -> no SMS; edit never resends; toggle disabled when SMS key is blank.
```

---

## P9: Tests and full sweep

```
HARD RULES
- Project: MedLink, Laravel 13, C:\laragon\www\techyknowloadeia1.
- GUARDRAILS: .claude hooks (Claude Code) / .github/copilot-instructions.md (Copilot) gate every file write and database command and run checks after each edit. If a hook blocks, asks, or reports a failed check: stop, show me the message, fix the cause. Never work around a guard (no alternate commands, no editing .claude/ or .vscode/).
- Step A = read-only plan, then STOP and wait for "APPROVED".
- Step B = create test files only (tests/Unit, tests/Feature) plus factories if needed. Do not change application code; if a test exposes a bug, report it and ask before fixing.
- Tests use phpunit.xml's SQLite :memory: DB with RefreshDatabase. NEVER point tests at the Laragon MySQL database.
- No real SMS: Config::set('services.httpsms.api_key','test-key'), Config::set('services.httpsms.from','+639000000000') and Http::fake() for the httpSMS URL. Assert with Http::assertSent / assertNothingSent.
- If a MySQL-only migration (ENUM alter) breaks on SQLite, report the file and propose a driver guard; do not weaken the MySQL migration.
- Do not loosen assertions. No commits.

TESTS TO WRITE
1. tests/Unit/PhoneNumberTest.php: normalize/detectFormat/toE164/mask/isSendable cases incl. +639..., 09..., 639..., 9..., landline, spaces/dashes, WALKIN-.
2. tests/Unit/PhilippineMobileRuleTest.php.
3. tests/Feature/OtpServiceTest.php: issue stores a hash not the code; cooldown 60s; 5 sends/hour; expiry per purpose (use $this->travel()); 5 wrong -> invalidated; lockout 30 min for login/password_reset/phone_change but NOT registration; consumed code cannot be reused; log fallback logs only when not production (Log::spy) and never in production.
4. tests/Feature/RegistrationOtpTest.php: register -> not logged in, nurse not notified; verify -> logged in, phone_verified_at set, nurse notified; re-register replaces unverified row; unverified login redirected to verify.
5. tests/Feature/PasswordResetOtpTest.php: full happy path; new-password page blocked without OTP; lockout after 5 wrong; active_session_token cleared.
6. tests/Feature/LoginOtpTest.php: each role needs OTP; /dashboard unreachable mid-step; success sets session token; lockout; masked number format follows typed format.
7. tests/Feature/PhoneChangeOtpTest.php: phone unchanged until verify; cancel; duplicate rejected.
8. tests/Feature/SmsNotificationTest.php: pickup/delivery approve sends once; home service accept+confirm sends once; walk-in skipped; unconfigured key -> action still 200/302.
9. tests/Feature/AnnouncementSmsTest.php: toggle off/on, recipient filtering, edit never sends.

STEP C
- Run `php artisan test` and paste the summary (passed/failed counts, failing test names + reason).
- Run php -l on all test files.
- Final manual checklist for me (real phone): one real OTP per flow, one pickup SMS, one delivery SMS, one home-service SMS, one announcement to a test resident; check sms_logs has masked numbers and no bodies; confirm OTP_LOG_FALLBACK=false before the defense/demo.
```
