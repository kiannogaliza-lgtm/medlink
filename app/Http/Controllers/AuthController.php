<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $role = 'resident'; // Public registration is strictly for residents only
        $isResident = true;

        $rules = [
            'first_name'             => ['required', 'string', 'max:255'],
            'last_name'              => ['required', 'string', 'max:255'],
            'phone_number'           => ['required', 'regex:/^09[0-9]{9}$/', 'unique:users'],
            'dob'                    => ['required', 'date', 'before_or_equal:today'],
            'age'                    => ['nullable', 'integer', 'min:0', 'max:130'],
            'patient_classification' => ['required', 'in:Regular Patient,PWD,Senior Citizen'],
            'password'               => ['required', 'string', 'min:8', 'confirmed'],
            'id_photo'               => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ];

        $validated = $request->validate($rules, [
            'phone_number.regex' => 'The phone number must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).',
            'dob.before_or_equal' => 'Birthday cannot be in the future.',
        ]);

        // Store the ID photo if uploaded
        $idPhotoPath = null;
        if ($request->hasFile('id_photo')) {
            $idPhotoPath = $request->file('id_photo')->store('id_photos', 'public');
        }

        $age = \Carbon\Carbon::parse($validated['dob'])->age;
        $ageCategory = \App\Models\User::resolveAgeCategory($age);

        $user = \App\Models\User::create([
            'first_name'             => $validated['first_name'],
            'last_name'              => $validated['last_name'],
            'phone_number'           => $validated['phone_number'],
            'dob'                    => $validated['dob'],
            'age'                    => $age,
            'age_category'           => $ageCategory,
            'patient_classification' => $validated['patient_classification'] ?? 'Regular Patient',
            'password'               => Hash::make($validated['password']),
            'role'                   => $role,
            'status'                 => $isResident ? 'pending' : 'active',
            'id_photo'               => $idPhotoPath,
        ]);

        // Record Account History Log: Account Created
        AccountLog::record(
            $user,
            'Account Created',
            "Resident account registered via Online Portal. Classification: " . ($user->patient_classification ?? 'Regular Patient'),
            trim($user->first_name . ' ' . $user->last_name),
            'resident'
        );

        Auth::login($user);
        $request->session()->regenerate();

        // Record Account History Log: First Logged In
        AccountLog::record($user, 'Logged In', 'User logged in upon online registration.');

        if ($isResident) {
            // New residents go to the pending-approval waiting page
            return redirect()->route('pending.approval');
        }

        // Newly registered nurses go directly to dashboard
        $user->update(['last_login_at' => now()]);
        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => ['required'],
            'password'     => ['required'],
            'role'         => ['required', 'in:resident,bhw,nurse'],
        ]);

        $user = \App\Models\User::where('phone_number', $request->phone_number)->first();

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

        // Block pending residents — redirect to waiting page after login
        if ($user->role === 'resident' && $user->status === 'pending') {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            $user->update(['last_login_at' => now()]);
            AccountLog::record($user, 'Logged In', 'Resident logged in (Status: Pending Verification).');
            return redirect()->route('pending.approval');
        }

        // Block deactivated / rejected / inactive accounts across all roles
        if ($user->status === 'inactive') {
            return back()->withErrors([
                'phone_number' => 'Your account is deactivated or inactive. Please contact Barangay Honrado Health Center for assistance.',
            ])->onlyInput('phone_number');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Track last login timestamp
        $user->update(['last_login_at' => now()]);
        AccountLog::record($user, 'Logged In', 'Successful authentication.');

        return redirect()->route('dashboard');
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validateWithBag('forgot', [
            'recover_role'         => ['required', 'in:resident,bhw,nurse'],
            'recover_first_name'   => ['required', 'string', 'max:255'],
            'recover_last_name'    => ['required', 'string', 'max:255'],
            'recover_phone_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'new_password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'recover_phone_number.regex' => 'Phone number must be an 11-digit Philippine number starting with 09.',
            'new_password.min'           => 'New password must be at least 8 characters.',
            'new_password.confirmed'     => 'New password confirmation does not match.',
        ]);

        $user = \App\Models\User::where('phone_number', $validated['recover_phone_number'])
            ->where('role', $validated['recover_role'])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim($validated['recover_first_name']))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim($validated['recover_last_name']))])
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['forgot' => 'No matching account found. Please check your name, role, and phone number, or contact the Barangay Health Center.'], 'forgot')
                ->withInput()
                ->with('open_forgot_modal', 'reset');
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        AccountLog::record(
            $user,
            'Password Reset',
            'Password reset via Login Forgot Account recovery.',
            trim($user->first_name . ' ' . $user->last_name),
            $user->role
        );

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

        $user = \App\Models\User::where('role', $validated['find_role'])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim($validated['find_first_name']))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim($validated['find_last_name']))])
            ->first();

        if (!$user || empty($user->phone_number)) {
            return back()
                ->withErrors(['find' => 'No account matched that name and role. Please visit Barangay Honrado Health Center for assistance.'], 'find')
                ->withInput()
                ->with('open_forgot_modal', 'find');
        }

        $phone = $user->phone_number;
        $masked = strlen($phone) >= 7
            ? substr($phone, 0, 4) . str_repeat('•', max(0, strlen($phone) - 7)) . substr($phone, -3)
            : $phone;

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
        if ($user->id_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->id_photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->id_photo);
        }

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
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
