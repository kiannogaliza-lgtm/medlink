<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        $pendingQuery = User::where('role', 'resident')->where('status', 'pending');
        $verifiedQuery = User::where('role', 'resident')->where('status', 'active');

        if ($search) {
            $pendingQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
            $verifiedQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
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
        // Only residents can be verified through this flow
        if ($user->role !== 'resident') {
            return back()->with('error', 'Only resident accounts can be verified.');
        }

        $user->update(['status' => 'active']);

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

        return back()->with('success', "Patient {$user->first_name} {$user->last_name} has been verified and can now access the system.");
    }

    /**
     * Reject a resident — set status to inactive.
     */
    public function reject(User $user)
    {
        if ($user->role !== 'resident') {
            return back()->with('error', 'Only resident accounts can be rejected.');
        }

        $user->update(['status' => 'inactive']);

        // Record Account History Log
        \App\Models\AccountLog::record(
            $user,
            'Account Rejected',
            "Resident account rejected by Nurse " . (auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Staff') . ".",
            auth()->check() ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse',
            'nurse'
        );

        return back()->with('success', "Patient {$user->first_name} {$user->last_name} has been rejected.");
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
        if ($user->id_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->id_photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->id_photo);
        }

        $user->delete();

        return back()->with('success', "Patient {$user->first_name} {$user->last_name} has been deleted.");
    }
}
