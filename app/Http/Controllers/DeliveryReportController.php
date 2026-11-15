<?php

namespace App\Http\Controllers;

use App\Models\MedicineRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryReportController extends Controller
{
    /**
     * Display Delivery Reports & Analytics for Nurse.
     * Features:
     * - View official receipt & proof of delivery
     * - Printable delivery reports
     * - Frequent visitor / requester analysis
     * - Today's requests with timestamps
     * - Complete history of all medicine requests with filter & search
     */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $dateFilter = $request->query('date_filter', 'all'); // 'today', 'this_week', 'this_month', 'all'
        $methodFilter = $request->query('method', 'all'); // 'all', 'delivery', 'pickup'
        $statusFilter = $request->query('status', 'all'); // 'all', 'Completed', 'Approved', 'Pending', 'Rejected'
        $search = $request->query('search', '');

        // 1. Base Query for History
        $query = MedicineRequest::with(['user', 'medicine'])->latest('created_at');

        // Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('item_service', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  ->orWhere('claimed_by', 'like', "%{$search}%")
                  ->orWhere('delivered_by', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$search}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  });
            });
        }

        // Date Filter
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', $today);
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($dateFilter === 'this_month') {
            $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
        }

        // Method Filter
        if ($methodFilter !== 'all') {
            $query->where('delivery_method', $methodFilter);
        }

        // Status Filter
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $allRequests = $query->paginate(20)->withQueryString();

        // 2. Summary KPI Metrics
        $totalTodayRequests = MedicineRequest::whereDate('created_at', $today)->count();
        $completedToday = MedicineRequest::whereDate('created_at', $today)
            ->whereIn('status', ['Completed', 'Approved'])
            ->count();
        $deliveriesCount = MedicineRequest::where('delivery_method', 'delivery')->count();
        $pickupsCount = MedicineRequest::where('delivery_method', 'pickup')->count();
        $totalCompletedAll = MedicineRequest::where('status', 'Completed')->count();

        // 3. Frequent Visitors / Frequent Requesters
        // Rank residents by total medicine requests made
        $frequentVisitors = MedicineRequest::select('user_id', DB::raw('COUNT(*) as total_requests'), DB::raw('MAX(created_at) as last_request_date'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->with('user')
            ->get();

        // 4. Requests submitted today (dedicated fast view)
        $todayRequestsList = MedicineRequest::with(['user', 'medicine'])
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->get();

        return view('dashboards.nurse-delivery-reports', compact(
            'allRequests',
            'totalTodayRequests',
            'completedToday',
            'deliveriesCount',
            'pickupsCount',
            'totalCompletedAll',
            'frequentVisitors',
            'todayRequestsList',
            'dateFilter',
            'methodFilter',
            'statusFilter',
            'search'
        ));
    }
}
