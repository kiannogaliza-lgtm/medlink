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
            $phoneSearch = \App\Support\PhoneNumber::normalize($search) ?? $search;
            $query->where(function ($q) use ($search, $phoneSearch) {
                $q->where('item_service', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  ->orWhere('claimed_by', 'like', "%{$search}%")
                  ->orWhere('delivered_by', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search, $phoneSearch) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$phoneSearch}%")
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

    /**
     * Export delivery & claiming reports to Excel.
     */
    public function exportExcel(Request $request)
    {
        $today = Carbon::today();
        $dateFilter = $request->query('date_filter', 'all');
        $methodFilter = $request->query('method', 'all');
        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = MedicineRequest::with(['user', 'medicine'])->latest('created_at');

        if (!empty($search)) {
            $phoneSearch = \App\Support\PhoneNumber::normalize($search) ?? $search;
            $query->where(function ($q) use ($search, $phoneSearch) {
                $q->where('item_service', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  ->orWhere('claimed_by', 'like', "%{$search}%")
                  ->orWhere('delivered_by', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search, $phoneSearch) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone_number', 'like', "%{$phoneSearch}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  });
            });
        }

        if ($dateFilter === 'today') {
            $query->whereDate('created_at', $today);
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($dateFilter === 'this_month') {
            $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
        }

        if ($methodFilter !== 'all') {
            $query->where('delivery_method', $methodFilter);
        }

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $requests = $query->get();

        $rowsHtml = '';
        foreach ($requests as $r) {
            $residentName = $r->user ? ($r->user->first_name . ' ' . $r->user->last_name) : 'Unknown Resident';
            $medName = $r->medicine->name ?? ($r->item_service ?? 'Medicine');
            $patientName = ($r->patient_type === 'other' && !empty($r->patient_name)) ? $r->patient_name : $residentName;
            $handler = $r->delivery_method === 'delivery' ? ($r->delivered_by ?? 'Health Worker') : ($r->claimed_by ?? 'Resident / Picked up');
            $date = $r->created_at ? $r->created_at->format('Y-m-d H:i') : 'N/A';

            $rowsHtml .= '<tr>
                <td style="font-weight:bold;">' . htmlspecialchars($r->receipt_number ?? ('REC-' . $r->id)) . '</td>
                <td>' . htmlspecialchars($residentName) . '</td>
                <td>' . htmlspecialchars($patientName) . '</td>
                <td>' . htmlspecialchars($r->user->phone_number ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($r->user->address ?? 'Barangay Honrado') . '</td>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($medName) . '</td>
                <td style="text-align:center; font-weight:bold;">' . ($r->quantity ?? 1) . '</td>
                <td style="text-align:center;">' . htmlspecialchars(ucfirst($r->delivery_method ?? 'pickup')) . '</td>
                <td>' . htmlspecialchars($handler) . '</td>
                <td style="text-align:center;">' . htmlspecialchars($r->status ?? 'Pending') . '</td>
                <td style="text-align:center;">' . $date . '</td>
            </tr>';
        }

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="content-type" content="text/plain; charset=UTF-8"/>
            <style>
                body { font-family: Calibri, Arial, sans-serif; color: #1e293b; }
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #0c2d48; color: #ffffff; font-weight: bold; padding: 10px 14px; border: 1px solid #cbd5e1; text-align: left; font-size: 13px; }
                td { padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 12px; }
                tr:nth-child(even) td { background-color: #f8fafc; }
            </style>
        </head>
        <body>
            <h3 style="color:#0c2d48; margin-bottom:4px;">MEDLINK BARANGAY HEALTH SYSTEM &mdash; Medicine Delivery & Distribution Report</h3>
            <p style="font-size:11px; color:#64748b; margin-top:0; margin-bottom:16px;">
                Generated: ' . Carbon::now()->format('F d, Y h:i A') . ' | Filter: ' . htmlspecialchars(ucfirst($dateFilter)) . '
            </p>
            <table border="1">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Requester (Resident)</th>
                        <th>Actual Patient</th>
                        <th>Phone</th>
                        <th>Address / Purok</th>
                        <th>Medicine</th>
                        <th>Qty</th>
                        <th>Method</th>
                        <th>Delivered / Claimed By</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>' . ($rowsHtml ?: '<tr><td colspan="11" style="text-align:center;">No delivery records found.</td></tr>') . '</tbody>
            </table>
        </body>
        </html>';

        $filename = 'Delivery_Report_' . Carbon::now()->format('Ymd_His') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
