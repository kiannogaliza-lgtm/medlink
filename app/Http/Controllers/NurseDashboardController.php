<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineRequest;
use App\Models\User;
use Illuminate\Http\Request;

class NurseDashboardController extends Controller
{
    /**
     * Show the nurse dashboard with stats, charts, and pending requests.
     */
    public function index()
    {
        $pendingRequests = MedicineRequest::with('user')
            ->where('status', 'Pending')
            ->latest()
            ->get();

        $pendingCount = $pendingRequests->count();

        // Low stock items (stock <= reorder_level and stock > 0)
        $lowStockCount = Medicine::whereColumn('stock', '<=', 'reorder_level')
            ->where('stock', '>', 0)
            ->count();

        // Critical stock (stock = 0)
        $criticalCount = Medicine::where('stock', '<=', 0)->count();

        $totalLowStock = $lowStockCount + $criticalCount;

        // Expired medicines
        $expiredCount = Medicine::whereNotNull('expiration_date')->where('expiration_date', '<=', \Carbon\Carbon::today())->count();

        // Medicine data for bar chart
        $medicines = Medicine::orderBy('stock', 'desc')->limit(6)->get();

        // Request distribution for pie chart
        $medicineRequestCount = MedicineRequest::where('type', 'Medicine')->count();
        $homeServiceCount = \App\Models\HomeServiceRequest::count();
        $totalRequests = $medicineRequestCount + $homeServiceCount;

        // Patients verified today
        $verifiedTodayCount = \App\Models\User::where('role', 'resident')
            ->where('status', 'active')
            ->whereDate('updated_at', \Carbon\Carbon::today())
            ->count();

        // Scheduled Appointments (Upcoming)
        $scheduledAppointmentsCount = \App\Models\Schedule::where('scheduled_date', '>=', \Carbon\Carbon::today())
            ->whereIn('status', ['Confirmed', 'Pending'])
            ->count();

        return view('dashboards.nurse', compact(
            'pendingRequests',
            'pendingCount',
            'totalLowStock',
            'medicines',
            'totalRequests',
            'medicineRequestCount',
            'homeServiceCount',
            'verifiedTodayCount',
            'scheduledAppointmentsCount',
            'expiredCount'
        ));
    }

    /**
     * Show the medicine inventory management page.
     */
    public function inventory(Request $request)
    {
        $query = Medicine::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('dosage', 'like', '%' . $search . '%');
            });
        }

        // Category filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'In Stock') {
                $query->whereColumn('stock', '>', 'reorder_level');
            } elseif ($request->status === 'Low Stock') {
                $query->whereColumn('stock', '<=', 'reorder_level')->where('stock', '>', 0);
            } elseif ($request->status === 'Critical') {
                $query->where('stock', '<=', 0);
            } elseif ($request->status === 'Expired') {
                $query->whereNotNull('expiration_date')->where('expiration_date', '<=', \Carbon\Carbon::today());
            }
        }

        // Sort (only actual database columns; 'status' is a computed accessor)
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        $allowedSorts = ['name', 'dosage', 'category', 'stock', 'unit', 'expiration_date'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $medicines = $query->paginate(10)->withQueryString();

        // Summary counts
        $inStockCount = Medicine::whereColumn('stock', '>', 'reorder_level')->count();
        $lowStockCount = Medicine::whereColumn('stock', '<=', 'reorder_level')->where('stock', '>', 0)->count();
        $criticalCount = Medicine::where('stock', '<=', 0)->count();
        $expiredCount = Medicine::whereNotNull('expiration_date')->where('expiration_date', '<=', \Carbon\Carbon::today())->count();
        $totalItems = Medicine::count();

        return view('dashboards.nurse-inventory', compact(
            'medicines',
            'inStockCount',
            'lowStockCount',
            'criticalCount',
            'expiredCount',
            'totalItems'
        ));
    }

    /**
     * Show the Returned & Damaged Medicines ledger for Nurse.
     */
    public function returnedMedicines(Request $request)
    {
        $query = \App\Models\MedicineReturn::with(['user', 'medicineRequest', 'medicine', 'recorder'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('medicine_name', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('medicineRequest', function ($mq) use ($search) {
                      $mq->where('request_id', 'like', "%{$search}%");
                  });
            });
        }

        $returns = $query->paginate(15)->withQueryString();
        $totalReturnedUnits = \App\Models\MedicineReturn::sum('quantity_returned');
        $totalRecords = \App\Models\MedicineReturn::count();

        return view('dashboards.nurse-returned-medicines', compact(
            'returns',
            'totalReturnedUnits',
            'totalRecords'
        ));
    }

    /**
     * Full Notifications Center for Nurse — aggregates all operational alerts & personal notifications.
     */
    public function notifications(Request $request)
    {
        $user = auth()->user();
        $readAt = $user?->notifications_read_at;
        $filter = $request->query('filter', 'all');

        $items = collect();

        // 1. Medicine Requests (Pending & Recent)
        $medRequests = MedicineRequest::with(['user', 'medicine'])
            ->orderBy('updated_at', 'desc')
            ->take(25)
            ->get();
        foreach ($medRequests as $req) {
            $ts = $req->updated_at ?? $req->created_at ?? now();
            $isUnread = $req->status === 'Pending' && (!$readAt || $ts->gt($readAt));
            $patientName = $req->user ? trim($req->user->first_name . ' ' . $req->user->last_name) : 'Resident';
            $medName = $req->medicine->name ?? $req->item_service ?? 'Medicine';
            $items->push([
                'category'  => 'medicine',
                'title'     => "Medicine Request: {$medName}",
                'message'   => "{$patientName} requested {$medName} (Qty: " . ($req->quantity ?? 1) . ") — Status: {$req->status}.",
                'badge'     => $req->status,
                'icon'      => 'medication',
                'color'     => $req->status === 'Pending' ? 'red' : ($req->status === 'Approved' || $req->status === 'Completed' ? 'emerald' : 'blue'),
                'url'       => route('nurse.medicine-requests'),
                'time'      => $ts,
                'timestamp' => $ts->timestamp,
                'is_unread' => $isUnread,
            ]);
        }

        // 2. Pending Patient Verifications
        $pendingPatients = User::where('role', 'resident')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
        foreach ($pendingPatients as $pat) {
            $ts = $pat->created_at ?? now();
            $isUnread = !$readAt || $ts->gt($readAt);
            $items->push([
                'category'  => 'patients',
                'title'     => 'New Resident Verification Needed',
                'message'   => trim($pat->first_name . ' ' . $pat->last_name) . ' (' . ($pat->phone_number ?? 'No phone') . ') registered and is awaiting account approval.',
                'badge'     => 'Pending',
                'icon'      => 'how_to_reg',
                'color'     => 'amber',
                'url'       => route('nurse.patient-verification'),
                'time'      => $ts,
                'timestamp' => $ts->timestamp,
                'is_unread' => $isUnread,
            ]);
        }

        // 3. Home Service Requests
        $homeServices = \App\Models\HomeServiceRequest::with('user')
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();
        foreach ($homeServices as $hs) {
            $ts = $hs->updated_at ?? $hs->created_at ?? now();
            $isUnread = $hs->status === 'Pending' && (!$readAt || $ts->gt($readAt));
            $patientName = $hs->user ? trim($hs->user->first_name . ' ' . $hs->user->last_name) : ($hs->patient_name ?? 'Resident');
            $items->push([
                'category'  => 'home_services',
                'title'     => 'Home Service: ' . ($hs->service_type ?? 'Home Visit'),
                'message'   => "{$patientName} — Status: {$hs->status}" . ($hs->preferred_date ? ' (' . $hs->preferred_date->format('M d, Y') . ')' : '') . '.',
                'badge'     => $hs->status,
                'icon'      => 'home',
                'color'     => $hs->status === 'Pending' ? 'amber' : 'emerald',
                'url'       => route('nurse.home-services'),
                'time'      => $ts,
                'timestamp' => $ts->timestamp,
                'is_unread' => $isUnread,
            ]);
        }

        // 4. Schedules
        $schedules = \App\Models\Schedule::orderBy('updated_at', 'desc')->take(15)->get();
        foreach ($schedules as $sch) {
            $ts = $sch->updated_at ?? $sch->created_at ?? now();
            $isUnread = $sch->status === 'Pending' && (!$readAt || $ts->gt($readAt));
            $items->push([
                'category'  => 'schedules',
                'title'     => 'Schedule: ' . ($sch->service ?? $sch->type ?? 'Appointment'),
                'message'   => ($sch->patient_name ?? 'Resident') . ' — ' . ($sch->scheduled_date ? $sch->scheduled_date->format('M d, Y') : '') . " ({$sch->status}).",
                'badge'     => $sch->status,
                'icon'      => 'calendar_month',
                'color'     => $sch->status === 'Pending' ? 'blue' : 'emerald',
                'url'       => route('nurse.scheduling'),
                'time'      => $ts,
                'timestamp' => $ts->timestamp,
                'is_unread' => $isUnread,
            ]);
        }

        // 5. Inventory Alerts (Critical / Low Stock / Expired)
        $medicines = Medicine::all();
        foreach ($medicines as $med) {
            $isExpired = $med->expiration_date && \Carbon\Carbon::parse($med->expiration_date)->lte(\Carbon\Carbon::today());
            if ($med->status === 'Critical' || $med->status === 'Low Stock' || $isExpired) {
                $ts = $med->updated_at ?? now();
                $alertLabel = $isExpired ? 'Expired' : $med->status;
                $items->push([
                    'category'  => 'inventory',
                    'title'     => "Inventory Alert: {$med->name}",
                    'message'   => $isExpired
                        ? "{$med->name} has reached or passed its expiration date (" . \Carbon\Carbon::parse($med->expiration_date)->format('M d, Y') . ")."
                        : "{$med->name} is currently {$med->status} ({$med->stock} " . ($med->unit ?? 'units') . " remaining).",
                    'badge'     => $alertLabel,
                    'icon'      => 'inventory_2',
                    'color'     => ($med->status === 'Critical' || $isExpired) ? 'red' : 'amber',
                    'url'       => route('nurse.inventory'),
                    'time'      => $ts,
                    'timestamp' => $ts->timestamp,
                    'is_unread' => false,
                ]);
            }
        }

        // 6. Important Account History Events (Deactivated, Deleted, Password Reset)
        $accountEvents = \App\Models\AccountLog::whereIn('action', ['Account Deactivated', 'Account Deleted', 'Account Reactivated', 'Password Reset'])
            ->orderBy('logged_at', 'desc')
            ->take(15)
            ->get();
        foreach ($accountEvents as $log) {
            $ts = $log->logged_at ?? $log->created_at ?? now();
            $isUnread = !$readAt || $ts->gt($readAt);
            $items->push([
                'category'  => 'accounts',
                'title'     => "{$log->action}: {$log->user_name}",
                'message'   => $log->details ?: "{$log->user_name} ({$log->user_role}) — {$log->action}.",
                'badge'     => $log->action,
                'icon'      => $log->action === 'Account Deleted' ? 'delete_forever' : ($log->action === 'Account Deactivated' ? 'person_off' : 'manage_accounts'),
                'color'     => $log->action === 'Account Deleted' ? 'red' : ($log->action === 'Account Deactivated' ? 'amber' : 'purple'),
                'url'       => route('nurse.account-history'),
                'time'      => $ts,
                'timestamp' => $ts->timestamp,
                'is_unread' => $isUnread,
            ]);
        }

        // 7. Personal AppNotifications for this Nurse
        if ($user) {
            $personalNotifs = \App\Models\AppNotification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();
            foreach ($personalNotifs as $pn) {
                $ts = $pn->created_at ?? now();
                $items->push([
                    'category'  => 'system',
                    'title'     => $pn->title,
                    'message'   => $pn->message,
                    'badge'     => $pn->is_read ? 'Read' : 'New',
                    'icon'      => 'notifications_active',
                    'color'     => $pn->type === 'success' ? 'emerald' : ($pn->type === 'error' ? 'red' : 'blue'),
                    'url'       => route('dashboard'),
                    'time'      => $ts,
                    'timestamp' => $ts->timestamp,
                    'is_unread' => !$pn->is_read,
                ]);
            }
        }

        $allNotifications = $items->sortByDesc('timestamp')->values();

        $counts = [
            'all'           => $allNotifications->count(),
            'unread'        => $allNotifications->where('is_unread', true)->count(),
            'medicine'      => $allNotifications->where('category', 'medicine')->count(),
            'patients'      => $allNotifications->where('category', 'patients')->count(),
            'home_services' => $allNotifications->where('category', 'home_services')->count(),
            'schedules'     => $allNotifications->where('category', 'schedules')->count(),
            'inventory'     => $allNotifications->where('category', 'inventory')->count(),
            'accounts'      => $allNotifications->where('category', 'accounts')->count(),
        ];

        $filteredNotifications = match ($filter) {
            'unread'        => $allNotifications->where('is_unread', true)->values(),
            'medicine', 'patients', 'home_services', 'schedules', 'inventory', 'accounts', 'system'
                            => $allNotifications->where('category', $filter)->values(),
            default         => $allNotifications,
        };

        return view('dashboards.nurse-notifications', compact(
            'filteredNotifications',
            'counts',
            'filter'
        ));
    }

    /**
     * Mark all notifications as read for Nurse
     */
    public function markNotificationsRead(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            \App\Models\AppNotification::where('user_id', $user->id)->update(['is_read' => true]);
            $user->update(['notifications_read_at' => now()]);
        }
        return response()->json(['success' => true]);
    }
}
