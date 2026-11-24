<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Models\OtpCode;
use App\Models\User;
use App\Rules\PhilippineMobile;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private function completeLogin(Request $request, User $user, bool $remember, string $logDetails): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Single-device enforcement: issue a fresh session token (invalidating any other logged-in device)
        $sessionToken = Str::random(64);
        $user->update([
            'last_login_at'        => now(),
            'active_session_token' => $sessionToken,
        ]);
        $request->session()->put('active_session_token', $sessionToken);

        AccountLog::record($user, 'Logged In', $logDetails);

        if ($user->role === 'resident' && $user->status === 'pending') {
            return redirect()->route('pending.approval');
        }

        return redirect()->route('dashboard');
    }

    private function resolvePendingRegistrationUser(Request $request): ?User
    {
        $userId = $request->session()->get('reg_otp_user_id');
        if (!$userId) {
            return null;
        }

        $user = User::find($userId);
        if (!$user || !$user->registration_unverified) {
            $request->session()->forget(['reg_otp_user_id', 'reg_otp_format']);
            return null;
        }

        return $user;
    }

    public function register(Request $request, OtpService $otpService)
    {
        $role = 'resident'; // Public registration is strictly for residents only
        $isResident = true;

        $typedFormat = PhoneNumber::detectFormat($request->input('phone_number'));
        PhoneNumber::mergeNormalized($request, ['phone_number']);

        $rules = [
            'first_name'             => ['required', 'string', 'max:255'],
            'last_name'              => ['required', 'string', 'max:255'],
            'phone_number'           => [
                'required',
                new PhilippineMobile,
                Rule::unique('users', 'phone_number')->where(fn ($q) => $q->where('registration_unverified', false)),
            ],
            'dob'                    => ['required', 'date', 'before_or_equal:today'],
            'age'                    => ['nullable', 'integer', 'min:0', 'max:130'],
            'gender'                 => ['nullable', 'string', 'in:Male,Female,Other'],
            'patient_classification' => ['required', 'in:Regular Patient,PWD,Senior Citizen'],
            'password'               => ['required', 'string', 'min:8', 'confirmed'],
            'id_photo'               => ['required', 'file', 'mimetypes:image/*', 'max:51200'],
        ];

        $validated = $request->validate($rules, [
            'dob.before_or_equal' => 'Birthday cannot be in the future.',
        ]);

        // Replace any previous unverified registration for this same phone number
        User::purgeUnverifiedRegistration($validated['phone_number']);

        // Store the ID photo on private storage if uploaded
        $idPhotoPath = null;
        if ($request->hasFile('id_photo')) {
            $idPhotoPath = $request->file('id_photo')->store('id_photos', 'local');
        }

        $age = \Carbon\Carbon::parse($validated['dob'])->age;
        $ageCategory = User::resolveAgeCategory($age);

        $user = User::create([
            'first_name'              => $validated['first_name'],
            'last_name'               => $validated['last_name'],
            'phone_number'            => $validated['phone_number'],
            'phone_verified_at'       => null,
            'registration_unverified' => true,
            'dob'                     => $validated['dob'],
            'age'                     => $age,
            'age_category'            => $ageCategory,
            'gender'                  => $validated['gender'] ?? null,
            'patient_classification'  => $validated['patient_classification'] ?? 'Regular Patient',
            'password'                => Hash::make($validated['password']),
            'role'                    => $role,
            'status'                  => $isResident ? 'pending' : 'active',
            'id_photo'                => $idPhotoPath,
        ]);

        // Record Account History Log: Account Created
        AccountLog::record(
            $user,
            'Account Created',
            "Resident account registered via Online Portal. Classification: " . ($user->patient_classification ?? 'Regular Patient'),
            trim($user->first_name . ' ' . $user->last_name),
            'resident'
        );

        $otpResult = $otpService->issue($user->phone_number, 'registration', $user);

        $request->session()->put('reg_otp_user_id', $user->id);
        $request->session()->put('reg_otp_format', $typedFormat);

        $redirect = redirect()->route('register.verify');
        if (!$otpResult['ok']) {
            $redirect->withErrors(['code' => $otpService->errorMessage($otpResult)]);
        }

        return $redirect;
    }

    public function showRegisterOtp(Request $request)
    {
        $user = $this->resolvePendingRegistrationUser($request);
        if (!$user) {
            return redirect()->route('register')
                ->with('status', 'Please complete registration to verify your phone number.');
        }

        $cooldownSeconds = (int) config('otp.resend_cooldown_seconds', 60);
        $latestOtp = OtpCode::where('phone', $user->phone_number)
            ->where('purpose', 'registration')
            ->latest('sent_at')
            ->first();

        $resendAvailableIn = 0;
        if ($latestOtp && $latestOtp->sent_at) {
            $elapsed = (int) $latestOtp->sent_at->diffInSeconds(now(), false);
            if ($elapsed < $cooldownSeconds) {
                $resendAvailableIn = max(0, $cooldownSeconds - $elapsed);
            }
        }

        return view('auth.otp', [
            'title'             => 'Verify your phone number',
            'masked'            => PhoneNumber::mask($user->phone_number, $request->session()->get('reg_otp_format')),
            'verifyUrl'         => route('register.verify.submit'),
            'resendUrl'         => route('register.verify.resend'),
            'cancelUrl'         => route('register'),
            'expiresMinutes'    => (int) config('otp.expiry_minutes.registration', 30),
            'resendAvailableIn' => $resendAvailableIn,
        ]);
    }

    public function verifyRegisterOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolvePendingRegistrationUser($request);
        if (!$user) {
            return redirect()->route('register')
                ->with('status', 'Please complete registration to verify your phone number.');
        }

        $result = $otpService->verify($user->phone_number, 'registration', (string) $request->input('code', ''));

        if (!$result['ok']) {
            $message = $otpService->errorMessage($result);
            if (($result['error'] ?? null) === 'too_many_attempts' && empty($result['retry_after'])) {
                $message .= ' Please request a new code.';
            }

            return back()->withErrors(['code' => $message]);
        }

        $user->update([
            'registration_unverified' => false,
            'phone_verified_at'       => now(),
        ]);

        AccountLog::record(
            $user,
            'Phone Verified',
            'Phone verified via SMS OTP during registration.',
            trim($user->first_name . ' ' . $user->last_name),
            'resident'
        );

        // Notify all active Nurses now that the resident's phone is verified
        foreach (User::where('role', 'nurse')->where('status', 'active')->get() as $nurse) {
            \App\Models\AppNotification::create([
                'user_id' => $nurse->id,
                'type'    => 'info',
                'title'   => 'New Resident Verification Needed',
                'message' => trim($user->first_name . ' ' . $user->last_name) . ' (' . ($user->phone_number ?? 'No phone') . ') registered and is awaiting account verification.',
            ]);
        }

        $request->session()->forget(['reg_otp_user_id', 'reg_otp_format']);

        return $this->completeLogin($request, $user, false, 'User logged in after phone verification.');
    }

    public function resendRegisterOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolvePendingRegistrationUser($request);
        if (!$user) {
            return redirect()->route('register')
                ->with('status', 'Please complete registration to verify your phone number.');
        }

        $result = $otpService->issue($user->phone_number, 'registration', $user);

        if (!$result['ok']) {
            return back()->withErrors(['code' => $otpService->errorMessage($result)]);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    private function resolveLoginOtpUser(Request $request): ?User
    {
        $userId = $request->session()->get('login_otp_user_id');
        $startedAt = $request->session()->get('login_otp_started_at');

        if (!$userId || !$startedAt) {
            $this->forgetLoginOtpSession($request);
            return null;
        }

        if (\Carbon\Carbon::createFromTimestamp((int) $startedAt)->addMinutes(15)->isPast()) {
            $this->forgetLoginOtpSession($request);
            return null;
        }

        $user = User::where('id', $userId)
            ->where('registration_unverified', false)
            ->first();

        if (!$user) {
            $this->forgetLoginOtpSession($request);
            return null;
        }

        return $user;
    }

    private function forgetLoginOtpSession(Request $request): void
    {
        $request->session()->forget([
            'login_otp_user_id',
            'login_otp_remember',
            'login_otp_format',
            'login_otp_started_at',
        ]);
    }

    public function login(Request $request, OtpService $otpService)
    {
        $request->validate([
            'phone_number' => ['required'],
            'password'     => ['required'],
            'role'         => ['required', 'in:resident,bhw,nurse'],
        ]);

        $typedFormat = PhoneNumber::detectFormat($request->input('phone_number'));
        $lookupPhone = PhoneNumber::normalize($request->phone_number) ?? $request->phone_number;
        $user = User::where('phone_number', $lookupPhone)->first();

        if (!$user) {
            return back()->withErrors([
                'phone_number' => 'Incorrect number.',
            ])->onlyInput('phone_number');
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ])->onlyInput('phone_number');
        }

        if ($user->role !== $request->role) {
            return back()->withErrors([
                'role' => 'Incorrect role selected for this account.',
            ])->onlyInput('phone_number');
        }

        if (!PhoneNumber::isSendable($user->phone_number)) {
            return back()->withErrors([
                'phone_number' => 'Your account has no valid mobile number on file. Please visit Barangay Honrado Health Center to update it.',
            ])->onlyInput('phone_number');
        }

        // Unverified self-registration: send back to phone OTP verification
        if ($user->registration_unverified) {
            $request->session()->put('reg_otp_user_id', $user->id);
            $request->session()->put('reg_otp_format', $typedFormat);

            $otpResult = $otpService->issue($user->phone_number, 'registration', $user);

            $redirect = redirect()->route('register.verify')
                ->with('status', 'Please verify your phone number to finish registration.');

            if (!$otpResult['ok'] && ($otpResult['error'] ?? null) !== 'cooldown') {
                $redirect->withErrors(['code' => $otpService->errorMessage($otpResult)]);
            }

            return $redirect;
        }

        // Block deactivated / rejected / inactive accounts across all roles
        if ($user->status === 'inactive') {
            return back()->withErrors([
                'phone_number' => 'Your account is deactivated or inactive. Please contact Barangay Honrado Health Center for assistance.',
            ])->onlyInput('phone_number');
        }

        // Check login OTP lockout
        $lockoutSeconds = $otpService->lockoutRemaining($user->phone_number, 'login');
        if ($lockoutSeconds !== null) {
            return back()->withErrors([
                'phone_number' => $otpService->errorMessage(['error' => 'locked', 'retry_after' => $lockoutSeconds]),
            ])->onlyInput('phone_number');
        }

        $otpResult = $otpService->issue($user->phone_number, 'login', $user);
        if (!$otpResult['ok']) {
            return back()->withErrors([
                'phone_number' => $otpService->errorMessage($otpResult),
            ])->onlyInput('phone_number');
        }

        // Regenerate session ID before storing pending login OTP state (user is NOT logged in yet)
        $remember = $request->boolean('remember');
        $request->session()->regenerate();
        $request->session()->put([
            'login_otp_user_id'    => $user->id,
            'login_otp_remember'   => $remember,
            'login_otp_format'     => $typedFormat,
            'login_otp_started_at' => now()->timestamp,
        ]);

        return redirect()->route('login.otp');
    }

    public function showLoginOtp(Request $request)
    {
        $user = $this->resolveLoginOtpUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Please sign in again.']);
        }

        $cooldownSeconds = (int) config('otp.resend_cooldown_seconds', 60);
        $latestOtp = OtpCode::where('phone', $user->phone_number)
            ->where('purpose', 'login')
            ->latest('sent_at')
            ->first();

        $resendAvailableIn = 0;
        if ($latestOtp && $latestOtp->sent_at) {
            $elapsed = (int) $latestOtp->sent_at->diffInSeconds(now(), false);
            if ($elapsed < $cooldownSeconds) {
                $resendAvailableIn = max(0, $cooldownSeconds - $elapsed);
            }
        }

        return view('auth.otp', [
            'title'             => "Confirm it's you",
            'masked'            => PhoneNumber::mask($user->phone_number, $request->session()->get('login_otp_format')),
            'verifyUrl'         => route('login.otp.submit'),
            'resendUrl'         => route('login.otp.resend'),
            'cancelUrl'         => route('login.otp.cancel'),
            'cancelMethod'      => 'POST',
            'expiresMinutes'    => (int) config('otp.expiry_minutes.login', 5),
            'resendAvailableIn' => $resendAvailableIn,
        ]);
    }

    public function verifyLoginOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolveLoginOtpUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Please sign in again.']);
        }

        $result = $otpService->verify($user->phone_number, 'login', (string) $request->input('code', ''));

        if (!$result['ok']) {
            if (in_array($result['error'] ?? null, ['too_many_attempts', 'locked'], true)) {
                $this->forgetLoginOtpSession($request);

                return redirect()->route('login')
                    ->withErrors(['phone_number' => $otpService->errorMessage($result)]);
            }

            return back()->withErrors(['code' => $otpService->errorMessage($result)]);
        }

        $freshUser = User::find($user->id);
        if (!$freshUser || $freshUser->status === 'inactive' || $freshUser->registration_unverified) {
            $this->forgetLoginOtpSession($request);

            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Your account is deactivated or inactive. Please contact Barangay Honrado Health Center for assistance.']);
        }

        if ($freshUser->phone_verified_at === null) {
            $freshUser->update(['phone_verified_at' => now()]);
        }

        $remember = (bool) $request->session()->get('login_otp_remember', false);
        $this->forgetLoginOtpSession($request);

        return $this->completeLogin(
            $request,
            $freshUser,
            $remember,
            'Successful authentication (password + SMS OTP).'
        );
    }

    public function resendLoginOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolveLoginOtpUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Please sign in again.']);
        }

        $result = $otpService->issue($user->phone_number, 'login', $user);

        if (!$result['ok']) {
            return back()->withErrors(['code' => $otpService->errorMessage($result)]);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function cancelLoginOtp(Request $request)
    {
        $this->forgetLoginOtpSession($request);

        return redirect()->route('login');
    }

    private function resolvePasswordResetUser(Request $request): ?User
    {
        $userId = $request->session()->get('pwreset_user_id');
        if (!$userId) {
            return null;
        }

        $user = User::where('id', $userId)
            ->where('registration_unverified', false)
            ->first();

        if (!$user) {
            $request->session()->forget(['pwreset_user_id', 'pwreset_format']);
            return null;
        }

        return $user;
    }

    private function resolveVerifiedPasswordResetUser(Request $request): ?User
    {
        $userId = $request->session()->get('pwreset_verified_user_id');
        $verifiedAt = $request->session()->get('pwreset_verified_at');

        if (!$userId || !$verifiedAt) {
            $request->session()->forget(['pwreset_user_id', 'pwreset_format', 'pwreset_verified_user_id', 'pwreset_verified_at']);
            return null;
        }

        $verifiedCarbon = \Carbon\Carbon::createFromTimestamp((int) $verifiedAt);
        if ($verifiedCarbon->copy()->addMinutes(10)->isPast()) {
            $request->session()->forget(['pwreset_user_id', 'pwreset_format', 'pwreset_verified_user_id', 'pwreset_verified_at']);
            return null;
        }

        $user = User::where('id', $userId)
            ->where('registration_unverified', false)
            ->first();

        if (!$user) {
            $request->session()->forget(['pwreset_user_id', 'pwreset_format', 'pwreset_verified_user_id', 'pwreset_verified_at']);
            return null;
        }

        return $user;
    }

    public function requestPasswordReset(Request $request, OtpService $otpService)
    {
        $typedFormat = PhoneNumber::detectFormat($request->input('recover_phone_number'));
        PhoneNumber::mergeNormalized($request, ['recover_phone_number']);

        $validated = $request->validateWithBag('forgot', [
            'recover_role'         => ['required', 'in:resident,bhw,nurse'],
            'recover_first_name'   => ['required', 'string', 'max:255'],
            'recover_last_name'    => ['required', 'string', 'max:255'],
            'recover_phone_number' => ['required', new PhilippineMobile],
        ]);

        $user = User::where('phone_number', $validated['recover_phone_number'])
            ->where('role', $validated['recover_role'])
            ->where('registration_unverified', false)
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim($validated['recover_first_name']))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim($validated['recover_last_name']))])
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['forgot' => 'No matching account found. Please check your name, role, and phone number, or contact the Barangay Health Center.'], 'forgot')
                ->withInput()
                ->with('open_forgot_modal', 'reset');
        }

        if (!PhoneNumber::isSendable($user->phone_number)) {
            return back()
                ->withErrors(['forgot' => 'Your account has no valid mobile number on file. Please visit Barangay Honrado Health Center to update it.'], 'forgot')
                ->withInput()
                ->with('open_forgot_modal', 'reset');
        }

        $lockoutSeconds = $otpService->lockoutRemaining($user->phone_number, 'password_reset');
        if ($lockoutSeconds !== null) {
            return back()
                ->withErrors(['forgot' => $otpService->errorMessage(['error' => 'locked', 'retry_after' => $lockoutSeconds])], 'forgot')
                ->withInput()
                ->with('open_forgot_modal', 'reset');
        }

        $otpResult = $otpService->issue($user->phone_number, 'password_reset', $user);
        if (!$otpResult['ok']) {
            return back()
                ->withErrors(['forgot' => $otpService->errorMessage($otpResult)], 'forgot')
                ->withInput()
                ->with('open_forgot_modal', 'reset');
        }

        $request->session()->forget(['pwreset_verified_user_id', 'pwreset_verified_at']);
        $request->session()->put('pwreset_user_id', $user->id);
        $request->session()->put('pwreset_format', $typedFormat);

        return redirect()->route('password.otp');
    }

    public function showPasswordOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolvePasswordResetUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Your reset session expired. Start again.']);
        }

        $cooldownSeconds = (int) config('otp.resend_cooldown_seconds', 60);
        $latestOtp = OtpCode::where('phone', $user->phone_number)
            ->where('purpose', 'password_reset')
            ->latest('sent_at')
            ->first();

        $cooldownRemaining = 0;
        if ($latestOtp && $latestOtp->sent_at) {
            $elapsed = (int) $latestOtp->sent_at->diffInSeconds(now(), false);
            if ($elapsed < $cooldownSeconds) {
                $cooldownRemaining = max(0, $cooldownSeconds - $elapsed);
            }
        }

        $lockoutRemaining = $otpService->lockoutRemaining($user->phone_number, 'password_reset') ?? 0;

        return view('auth.otp', [
            'title'             => 'Reset your password',
            'masked'            => PhoneNumber::mask($user->phone_number, $request->session()->get('pwreset_format')),
            'verifyUrl'         => route('password.otp.submit'),
            'resendUrl'         => route('password.otp.resend'),
            'cancelUrl'         => route('login'),
            'expiresMinutes'    => (int) config('otp.expiry_minutes.password_reset', 10),
            'resendAvailableIn' => max($cooldownRemaining, $lockoutRemaining),
        ]);
    }

    public function verifyPasswordOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolvePasswordResetUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Your reset session expired. Start again.']);
        }

        $result = $otpService->verify($user->phone_number, 'password_reset', (string) $request->input('code', ''));

        if (!$result['ok']) {
            return back()->withErrors(['code' => $otpService->errorMessage($result)]);
        }

        $request->session()->forget('pwreset_user_id');
        $request->session()->put('pwreset_verified_user_id', $user->id);
        $request->session()->put('pwreset_verified_at', now()->timestamp);

        return redirect()->route('password.new');
    }

    public function resendPasswordOtp(Request $request, OtpService $otpService)
    {
        $user = $this->resolvePasswordResetUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Your reset session expired. Start again.']);
        }

        $result = $otpService->issue($user->phone_number, 'password_reset', $user);

        if (!$result['ok']) {
            return back()->withErrors(['code' => $otpService->errorMessage($result)]);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function showNewPassword(Request $request)
    {
        $user = $this->resolveVerifiedPasswordResetUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Your reset session expired. Start again.']);
        }

        return view('auth.reset-password');
    }

    public function updatePassword(Request $request)
    {
        $user = $this->resolveVerifiedPasswordResetUser($request);
        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['phone_number' => 'Your reset session expired. Start again.']);
        }

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.min'       => 'New password must be at least 8 characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
        ]);

        $user->update([
            'password'             => Hash::make($validated['new_password']),
            'active_session_token' => null,
        ]);

        AccountLog::record(
            $user,
            'Password Reset',
            'Password reset via SMS OTP.',
            trim($user->first_name . ' ' . $user->last_name),
            $user->role
        );

        $request->session()->forget([
            'pwreset_user_id',
            'pwreset_format',
            'pwreset_verified_user_id',
            'pwreset_verified_at',
        ]);

        return redirect()->route('login')
            ->with('status', 'Password updated successfully! You can now sign in with your new password.');
    }

    public function findAccount(Request $request)
    {
        $validated = $request->validateWithBag('find', [
            'find_role'       => ['required', 'in:resident,bhw,nurse'],
            'find_first_name' => ['required', 'string', 'max:255'],
            'find_last_name'  => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('role', $validated['find_role'])
            ->where('registration_unverified', false)
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim($validated['find_first_name']))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim($validated['find_last_name']))])
            ->first();

        if (!$user || PhoneNumber::isPlaceholder($user->phone_number)) {
            return back()
                ->withErrors(['find' => 'No account matched that name and role. Please visit Barangay Honrado Health Center for assistance.'], 'find')
                ->withInput()
                ->with('open_forgot_modal', 'find');
        }

        $masked = PhoneNumber::mask($user->phone_number);

        return back()
            ->with('found_account_hint', "Account found! Your registered phone number is {$masked} (Role: " . strtoupper($user->role) . ').')
            ->withInput()
            ->with('open_forgot_modal', 'find');
    }

    public function deactivateAccount(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'reason'   => ['nullable', 'string', 'max:500'],
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password. Please enter your current password to confirm.',
                ], 422);
            }
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $reason = trim($validated['reason'] ?? '');
        $details = 'User deactivated their own account via portal.' . ($reason !== '' ? " Reason: {$reason}" : '');

        $user->update(['status' => 'inactive']);

        AccountLog::record(
            $user,
            'Account Deactivated',
            $details,
            trim($user->first_name . ' ' . $user->last_name),
            $user->role
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('status', 'Your account has been deactivated. Contact Barangay Honrado Health Center anytime to reactivate it.');

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Your account has been deactivated.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login');
    }

    public function deleteAccount(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'reason'   => ['nullable', 'string', 'max:500'],
        ]);

        if (!Hash::check($validated['password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password. Please enter your current password to confirm.',
                ], 422);
            }
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $reason = trim($validated['reason'] ?? '');
        $details = 'User permanently deleted their own account via portal.' . ($reason !== '' ? " Reason: {$reason}" : '');

        // Record in Account History BEFORE deleting user (user_id is nullOnDelete so log is preserved permanently)
        AccountLog::record(
            $user,
            'Account Deleted',
            $details,
            trim($user->first_name . ' ' . $user->last_name),
            $user->role
        );

        if ($user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo);
        }
        User::deleteIdPhoto($user->id_photo);

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('status', 'Your account has been permanently deleted.');

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Your account has been permanently deleted.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $sessionToken = $request->session()->get('active_session_token');
        if ($user && $sessionToken && $user->active_session_token === $sessionToken) {
            $user->update(['active_session_token' => null]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
