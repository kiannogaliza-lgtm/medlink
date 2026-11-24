<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;

class PatientVerificationController extends Controller
{
    /**
     * Show the Patient Verification page.
     * Pending tab: residents with status = 'pending'
     * Verified tab: residents with status = 'active'
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $pendingQuery = User::where('role', 'resident')
            ->where('status', 'pending')
            ->where('registration_unverified', false);
        $verifiedQuery = User::registered()->where('role', 'resident')->where('status', 'active');

        if ($search) {
            $phoneSearch = PhoneNumber::normalize($search) ?? $search;
            $pendingQuery->where(function ($q) use ($search, $phoneSearch) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$phoneSearch}%");
            });
            $verifiedQuery->where(function ($q) use ($search, $phoneSearch) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$phoneSearch}%");
            });
        }

        $pendingResidents  = $pendingQuery->latest()->get();
        $verifiedResidents = $verifiedQuery->latest()->get();

        $pendingCount  = $pendingResidents->count();
        $verifiedCount = $verifiedResidents->count();

        return view('dashboards.nurse-patient-verification', compact(
            'pendingResidents',
            'verifiedResidents',
            'pendingCount',
            'verifiedCount',
            'search'
        ));
    }

    /**
     * Approve / verify a resident — set status to active.
     */
    public function verify(User $user)
    {
        // Only pending, OTP-verified residents can be verified through this flow
        if ($user->role !== 'resident' || $user->status !== 'pending' || $user->registration_unverified) {
            return back()->with('error', 'This account is not awaiting verification.');
        }

        $updated = User::whereKey($user->id)
            ->where('status', 'pending')
            ->where('registration_unverified', false)
            ->update(['status' => 'active', 'updated_at' => now()]);

        if ($updated !== 1) {
            return back()->with('error', 'This account is not awaiting verification.');
        }

        // Record Account History Log
        \App\Models\AccountLog::record(
            $user,
            'Account Verified',
            "Resident account verified and approved by Nurse " . (auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Staff') . ".",
            auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse',
            'nurse'
        );

        // Notify the resident
        \App\Models\AppNotification::create([
            'user_id' => $user->id,
            'type'    => 'success',
            'title'   => 'Account Verified & Approved',
            'message' => 'Congratulations! Your resident account has been verified. You now have full access to the MedLink portal.',
        ]);

        $smsService = app(\App\Services\SmsService::class);
        $freshUser = $user->fresh();
        $smsService->sendAfterResponse(
            (string) $user->phone_number,
            \App\Support\SmsTemplates::accountApproved((string) $user->first_name),
            'account_approved',
            $freshUser,
            $freshUser
        );

        $flash = "Patient {$user->first_name} {$user->last_name} has been verified and can now access the system.";
        if ($smsService->isConfigured() && PhoneNumber::isSendable($user->phone_number)) {
            $flash .= ' An SMS was sent to the resident.';
        }

        return back()->with('success', $flash);
    }

    /**
     * Reject a resident — notify via SMS and remove the pending registration entry.
     */
    public function reject(User $user)
    {
        if ($user->role !== 'resident' || $user->status !== 'pending' || $user->registration_unverified) {
            return back()->with('error', 'This account is not awaiting verification.');
        }

        $updated = User::whereKey($user->id)
            ->where('status', 'pending')
            ->where('registration_unverified', false)
            ->update(['status' => 'inactive', 'updated_at' => now()]);

        if ($updated !== 1) {
            return back()->with('error', 'This account is not awaiting verification.');
        }

        $phoneNumber = (string) $user->phone_number;
        $fullName = trim("{$user->first_name} {$user->last_name}");

        // Record Account History Log before deleting the user row
        \App\Models\AccountLog::record(
            $user,
            'Account Rejected',
            "Resident account rejected and removed by Nurse " . (auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Staff') . ".",
            auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse',
            'nurse'
        );

        if ($user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo);
        }
        User::deleteIdPhoto($user->id_photo);

        $user->delete();

        $smsService = app(\App\Services\SmsService::class);
        $smsService->sendAfterResponse(
            $phoneNumber,
            \App\Support\SmsTemplates::accountRejected(),
            'account_rejected'
        );

        $flash = "Patient {$fullName} has been rejected and removed.";
        if ($smsService->isConfigured() && PhoneNumber::isSendable($phoneNumber)) {
            $flash .= ' An SMS was sent to the resident.';
        }

        return back()->with('success', $flash);
    }

    /**
     * Serve a resident's government ID photo to authenticated nurses only.
     */
    public function idPhoto(User $user)
    {
        if ($user->role !== 'resident' || !$user->id_photo) {
            abort(404);
        }

        $path = $user->id_photo;
        $disk = null;

        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }

        if ($disk === null) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk($disk)->response($path, null, [
            'Cache-Control'          => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Delete a resident completely.
     */
    public function destroy(User $user)
    {
        if ($user->role !== 'resident') {
            return back()->with('error', 'Only resident accounts can be deleted here.');
        }

        $actorName = auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse';
        \App\Models\AccountLog::record(
            $user,
            'Account Deleted',
            "Resident account permanently deleted from Patient Verification by Nurse {$actorName}.",
            $actorName,
            'nurse'
        );

        if ($user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo);
        }
        User::deleteIdPhoto($user->id_photo);

        $user->delete();

        return back()->with('success', "Patient {$user->first_name} {$user->last_name} has been deleted.");
    }
}
