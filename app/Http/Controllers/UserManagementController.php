<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AccountLog;
use App\Models\AppNotification;
use App\Rules\PhilippineMobile;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display the user management page with filtering and search.
     */
    public function index(Request $request)
    {
        $query = User::registered();

        // Role filter
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $phoneSearch = PhoneNumber::normalize($search) ?? $search;
            $query->where(function ($q) use ($search, $phoneSearch) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$phoneSearch}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        // Summary counts
        $totalNurses = User::where('role', 'nurse')->count();
        $totalBhw = User::where('role', 'bhw')->count();
        $totalResidents = User::registered()->where('role', 'resident')->count();
        $activeUsers = User::where('status', 'active')->count();

        return view('dashboards.nurse-users', compact(
            'users',
            'totalNurses',
            'totalBhw',
            'totalResidents',
            'activeUsers'
        ));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        PhoneNumber::mergeNormalized($request, ['phone_number']);

        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'phone_number'  => [
                'required',
                new PhilippineMobile,
                Rule::unique('users', 'phone_number')->where(fn ($q) => $q->where('registration_unverified', false)),
            ],
            'dob'           => ['nullable', 'date', 'before_or_equal:today'],
            'age'           => ['nullable', 'integer', 'min:0', 'max:130'],
            'role'          => ['required', 'in:resident,bhw,nurse'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'profile_photo' => ['nullable', 'file', 'mimetypes:image/*', 'max:51200'],
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $dob = !empty($validated['dob']) ? $validated['dob'] : null;
        $age = $dob !== null
            ? \Carbon\Carbon::parse($dob)->age
            : (isset($validated['age']) && $validated['age'] !== null ? (int) $validated['age'] : null);

        User::purgeUnverifiedRegistration($validated['phone_number']);

        $newUser = User::create([
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'phone_number'      => $validated['phone_number'],
            'dob'               => $dob,
            'age'               => $age,
            'age_category'      => User::resolveAgeCategory($age),
            'role'              => $validated['role'],
            'can_manage_nurses' => $validated['role'] === 'bhw' ? $request->boolean('can_manage_nurses') : false,
            'password'          => Hash::make($validated['password']),
            'status'            => 'active',
            'profile_photo'     => $photoPath,
        ]);

        AccountLog::record(
            $newUser,
            'Account Created',
            "Account manually created by Nurse " . (auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Staff') . " with role {$validated['role']}.",
            auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse',
            'nurse'
        );

        return redirect()->route('nurse.users')->with('success', 'User account created successfully.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        PhoneNumber::mergeNormalized($request, ['phone_number']);

        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'phone_number'  => [
                'required',
                new PhilippineMobile,
                Rule::unique('users', 'phone_number')
                    ->ignore($user->id)
                    ->where(fn ($q) => $q->where('registration_unverified', false)),
            ],
            'dob'           => ['nullable', 'date', 'before_or_equal:today'],
            'age'           => ['nullable', 'integer', 'min:0', 'max:130'],
            'role'          => ['required', 'in:resident,bhw,nurse'],
            'status'        => ['required', 'in:active,inactive'],
            'is_maintenance'=> ['nullable', 'boolean'],
            'profile_photo' => ['nullable', 'file', 'mimetypes:image/*', 'max:51200'],
        ]);

        $validated['is_maintenance'] = $request->boolean('is_maintenance');
        $previousAccess = (bool) $user->can_manage_nurses;
        $validated['can_manage_nurses'] = $validated['role'] === 'bhw' ? $request->boolean('can_manage_nurses') : false;

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        if (!empty($validated['dob'])) {
            $validated['age'] = \Carbon\Carbon::parse($validated['dob'])->age;
            $validated['age_category'] = User::resolveAgeCategory($validated['age']);
        } elseif (array_key_exists('age', $validated)) {
            $validated['age'] = $validated['age'] !== null && $validated['age'] !== '' ? (int) $validated['age'] : null;
            $validated['age_category'] = User::resolveAgeCategory($validated['age']);
        }

        $previousStatus = $user->status;
        $previousPhone = $user->phone_number;
        $phoneChanged = $validated['phone_number'] !== $previousPhone;

        if ($phoneChanged) {
            $validated['phone_verified_at'] = null;
            $validated['pending_phone_number'] = null;
        }

        $user->update($validated);

        // Notify BHW if Nurse Account access permission changed
        if ($user->role === 'bhw' && $previousAccess !== (bool) $user->can_manage_nurses) {
            AppNotification::create([
                'user_id' => $user->id,
                'type'    => $user->can_manage_nurses ? 'success' : 'info',
                'title'   => $user->can_manage_nurses ? 'Nurse Account Access Granted' : 'Nurse Account Access Revoked',
                'message' => $user->can_manage_nurses
                    ? 'A Nurse has granted you permission to create and manage Nurse accounts.'
                    : 'Your permission to manage Nurse accounts has been revoked by a Nurse.',
            ]);
        }

        $actorName = auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse';
        if ($phoneChanged) {
            $oldMasked = PhoneNumber::mask($previousPhone);
            $newMasked = PhoneNumber::mask($user->phone_number);
            AccountLog::record(
                $user,
                'Phone Number Changed',
                "Phone number changed from {$oldMasked} to {$newMasked} by Nurse {$actorName} (phone verification reset).",
                $actorName,
                'nurse'
            );
        }
        if ($previousStatus !== 'inactive' && $user->status === 'inactive') {
            AccountLog::record(
                $user,
                'Account Deactivated',
                "Account deactivated by Nurse {$actorName}.",
                $actorName,
                'nurse'
            );
        } elseif ($previousStatus === 'inactive' && $user->status === 'active') {
            AccountLog::record(
                $user,
                'Account Reactivated',
                "Account reactivated by Nurse {$actorName}.",
                $actorName,
                'nurse'
            );
        } else {
            AccountLog::record(
                $user,
                'Account Updated',
                "User details updated by Nurse {$actorName}.",
                $actorName,
                'nurse'
            );
        }

        // Optional password change
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['string', 'min:8', 'confirmed'],
            ]);
            $user->update(['password' => Hash::make($request->password)]);

            AccountLog::record(
                $user,
                'Password Reset',
                "Account password reset by Nurse " . (auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Staff') . ".",
                auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse',
                'nurse'
            );
        }

        return redirect()->route('nurse.users')->with('success', 'User account updated successfully.');
    }

    /**
     * Toggle a BHW user's permission to create/manage Nurse accounts.
     */
    public function toggleNurseAccess(Request $request, User $user)
    {
        if ($user->role !== 'bhw') {
            return redirect()->back()->with('error', 'Nurse account access can only be toggled for BHW users.');
        }

        $newValue = !((bool) $user->can_manage_nurses);
        $user->update(['can_manage_nurses' => $newValue]);

        $actorName = auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse';

        AccountLog::record(
            $user,
            'Account Updated',
            ($newValue ? 'Granted' : 'Revoked') . " Nurse Account Management access by Nurse {$actorName}.",
            $actorName,
            'nurse'
        );

        AppNotification::create([
            'user_id' => $user->id,
            'type'    => $newValue ? 'success' : 'info',
            'title'   => $newValue ? 'Nurse Account Access Granted' : 'Nurse Account Access Revoked',
            'message' => $newValue
                ? "Nurse {$actorName} granted you access to create and manage Nurse accounts."
                : "Nurse {$actorName} revoked your access to Nurse Account Management.",
        ]);

        $statusLabel = $newValue ? 'granted' : 'revoked';
        return redirect()->back()->with('success', "Nurse account access {$statusLabel} for BHW {$user->first_name} {$user->last_name}.");
    }

    /**
     * Delete the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('nurse.users')->with('error', 'You cannot delete your own account.');
        }

        $actorName = auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse';
        AccountLog::record(
            $user,
            'Account Deleted',
            "Account permanently deleted by Nurse {$actorName}.",
            $actorName,
            'nurse'
        );

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->delete();

        return redirect()->route('nurse.users')->with('success', 'User account deleted successfully.');
    }
}
