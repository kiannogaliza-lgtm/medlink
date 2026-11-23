<?php

namespace App\Http\Controllers;

use App\Models\MedicineRequest;
use App\Models\HomeServiceRequest;
use App\Models\HomeServiceType;
use App\Models\Schedule;
use App\Models\Consultation;
use App\Models\ChildVaccination;
use App\Models\AppNotification;
use App\Models\AccountLog;
use App\Models\Medicine;
use App\Models\MedicineReturn;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * Return a composite version hash based on all real-time transaction activities.
     * When any transaction occurs across any module (medicine requests, home services,
     * announcements, returns, walk-ins, consultations, inventory, etc.),
     * all connected devices auto-refresh seamlessly.
     */
    public function version(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['authenticated' => false]);
        }

        $role = $user->role;

        // Collect max timestamps and counts across active domains
        $signatures = [];

        // Operational notification counters for Nurse & BHW badges
        $nurseReadAt = $user->notifications_read_at;
        $nursePendingMed = MedicineRequest::where('status', 'Pending')->when($nurseReadAt, fn($q) => $q->where('updated_at', '>', $nurseReadAt))->count();
        $nursePendingPat = User::where('role', 'resident')->where('status', 'pending')->when($nurseReadAt, fn($q) => $q->where('created_at', '>', $nurseReadAt))->count();
        $nursePendingSch = Schedule::where('status', 'Pending')->when($nurseReadAt, fn($q) => $q->where('updated_at', '>', $nurseReadAt))->count();
        $nursePendingHome = HomeServiceRequest::where('status', 'Pending')->when($nurseReadAt, fn($q) => $q->where('updated_at', '>', $nurseReadAt))->count();
        $nurseTotalNotifs = $nursePendingMed + $nursePendingPat + $nursePendingSch + $nursePendingHome;
        $signatures[] = "NURSE_NOTIFS:{$nurseTotalNotifs}:{$nursePendingMed}:{$nursePendingPat}:{$nursePendingSch}:{$nursePendingHome}";

        // 1. Medicine Requests & Fulfillment
        $medReqMax = MedicineRequest::max('updated_at');
        $medReqCount = MedicineRequest::count();
        $signatures[] = "MR:{$medReqCount}:{$medReqMax}";

        // 2. Home Services Appointments
        $hsMax = HomeServiceRequest::max('updated_at');
        $hsCount = HomeServiceRequest::count();
        $signatures[] = "HS:{$hsCount}:{$hsMax}";

        // 3. Home Service Types (Maintenance categories)
        $hstMax = HomeServiceType::max('updated_at');
        $hstCount = HomeServiceType::count();
        $signatures[] = "HST:{$hstCount}:{$hstMax}";

        // 4. Schedules (Walk-ins & Health Center Appointments)
        $schMax = Schedule::max('updated_at');
        $schCount = Schedule::count();
        $signatures[] = "SC:{$schCount}:{$schMax}";

        // 5. Community Announcements & Health Alerts
        $annMax = Announcement::max('updated_at');
        $annCount = Announcement::count();
        $signatures[] = "AN:{$annCount}:{$annMax}";

        // 6. Returned / Expired Medicines Log
        $retMax = MedicineReturn::max('updated_at');
        $retCount = MedicineReturn::count();
        $signatures[] = "MR_RET:{$retCount}:{$retMax}";

        // 7. Notifications for this specific user
        $notifMax = AppNotification::where('user_id', $user->id)->max('created_at');
        $notifCount = AppNotification::where('user_id', $user->id)->count();
        $unreadPersonalNotifs = AppNotification::where('user_id', $user->id)->where('is_read', false)->count();
        $signatures[] = "NO:{$notifCount}:{$unreadPersonalNotifs}:{$notifMax}:{$nurseReadAt}";

        // 8. User Profile changes / status updates / permissions / single-device session check
        $userSelf = User::find($user->id);
        if (!$userSelf || $userSelf->status === 'inactive') {
            \Illuminate\Support\Facades\Auth::logout();
            return response()->json([
                'authenticated' => false,
                'reason'        => !$userSelf ? 'deleted' : 'deactivated',
            ]);
        }

        $sessionToken = $request->session()->get('active_session_token');
        if (!empty($userSelf->active_session_token) && $userSelf->active_session_token !== $sessionToken) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json([
                'authenticated' => false,
                'reason'        => 'other_device',
                'message'       => 'There is another device that opened this account. You have been automatically logged out.',
            ]);
        }
        $signatures[] = "USR_SELF:{$userSelf?->updated_at}:{$userSelf?->status}:{$userSelf?->patient_classification}:{$userSelf?->can_manage_nurses}";

        // Role-specific signals:
        if ($role === 'nurse') {
            // Consultations & communicable disease monitoring
            $consMax = Consultation::max('updated_at');
            $consCount = Consultation::count();
            $signatures[] = "CO:{$consCount}:{$consMax}";

            // Child & adult vaccinations
            $vacMax = ChildVaccination::max('updated_at');
            $vacCount = ChildVaccination::count();
            $signatures[] = "VA:{$vacCount}:{$vacMax}";

            // Global user registrations / patient verifications
            $userMax = User::max('updated_at');
            $userCount = User::count();
            $signatures[] = "US:{$userCount}:{$userMax}";

            // Account history audit logs
            $logMax = AccountLog::max('logged_at');
            $logCount = AccountLog::count();
            $signatures[] = "AL:{$logCount}:{$logMax}";

            // Medicines inventory & stock quantity
            $medMax = Medicine::max('updated_at');
            $medCount = Medicine::count();
            $signatures[] = "MI:{$medCount}:{$medMax}";
        } elseif ($role === 'bhw') {
            // BHW sees assignments & walk-in registrations
            $bhwHsMax = HomeServiceRequest::where('assigned_to', $user->id)->max('updated_at');
            $signatures[] = "BHW_HS:{$bhwHsMax}";

            $bhwMedMax = MedicineRequest::where('delivery_method', 'delivery')->max('updated_at');
            $signatures[] = "BHW_MR:{$bhwMedMax}";

            // Global registered walk-ins / residents
            $bhwUsersCount = User::where('role', 'resident')->count();
            $bhwUsersMax = User::where('role', 'resident')->max('updated_at');
            $signatures[] = "BHW_RES:{$bhwUsersCount}:{$bhwUsersMax}";
        } elseif ($role === 'resident') {
            // Personal medicine requests & home services (include per-status counts so same-second updates always change hash)
            $myMedStats = MedicineRequest::where('user_id', $user->id)
                ->selectRaw("COUNT(*) as cnt, MAX(updated_at) as max_upd, SUM(status = 'Pending') as p, SUM(status = 'Accepted') as ac, SUM(status = 'Approved') as ap, SUM(status = 'Rejected') as rj, SUM(status = 'Completed') as co")
                ->first();
            $myHsStats = HomeServiceRequest::where('user_id', $user->id)
                ->selectRaw("COUNT(*) as cnt, MAX(updated_at) as max_upd, SUM(status = 'Pending') as p, SUM(status = 'Accepted') as ac, SUM(status = 'Approved') as ap, SUM(status = 'Confirmed') as cf, SUM(status = 'Rejected') as rj, SUM(status = 'Completed') as co")
                ->first();
            $signatures[] = "RES:{$myMedStats?->cnt}:{$myMedStats?->max_upd}:{$myMedStats?->p}:{$myMedStats?->ac}:{$myMedStats?->ap}:{$myMedStats?->rj}:{$myMedStats?->co}|HS:{$myHsStats?->cnt}:{$myHsStats?->max_upd}:{$myHsStats?->p}:{$myHsStats?->ac}:{$myHsStats?->ap}:{$myHsStats?->cf}:{$myHsStats?->rj}:{$myHsStats?->co}";
        }

        $versionHash = md5(implode('|', $signatures));

        $latestNotif = AppNotification::where('user_id', $user->id)->latest('created_at')->first();

        return response()->json([
            'authenticated'  => true,
            'role'           => $role,
            'status'         => $userSelf?->status ?? 'active',
            'version'        => $versionHash,
            'unread_notifs'  => $unreadPersonalNotifs,
            'pending_total'  => $role === 'nurse' ? ($nurseTotalNotifs + $unreadPersonalNotifs) : 0,
            'latest_notif'   => $latestNotif ? [
                'id'         => $latestNotif->id,
                'title'      => $latestNotif->title,
                'message'    => $latestNotif->message,
                'type'       => $latestNotif->type,
                'is_read'    => (bool) $latestNotif->is_read,
                'time_ago'   => $latestNotif->time_ago,
                'created_at' => $latestNotif->created_at->toIso8601String(),
            ] : null,
            'timestamp'      => now()->timestamp,
        ]);
    }
}
