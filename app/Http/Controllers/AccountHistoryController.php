<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountHistoryController extends Controller
{
    /**
     * Build filtered query for Account History Logs.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $query = AccountLog::with('user')->orderBy('logged_at', 'desc');

        // Action filter (e.g. all, Account Created, Logged In)
        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        // Role filter (resident, bhw, nurse)
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('user_role', $request->role);
        }

        // Search by user name or phone number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('actor_name', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Display the Account History Logs list in the Nurse Portal.
     */
    public function index(Request $request)
    {
        $logs = $this->buildFilteredQuery($request)->paginate(20)->withQueryString();

        // Statistics
        $totalLogs           = AccountLog::count();
        $accountCreatedCount = AccountLog::where('action', 'Account Created')->count();
        $loginCount          = AccountLog::where('action', 'Logged In')->count();
        $deactivatedCount    = AccountLog::where('action', 'Account Deactivated')->count();
        $deletedCount        = AccountLog::where('action', 'Account Deleted')->count();
        $todayActivities     = AccountLog::whereDate('logged_at', today())->count();

        return view('dashboards.nurse-account-history', compact(
            'logs',
            'totalLogs',
            'accountCreatedCount',
            'loginCount',
            'deactivatedCount',
            'deletedCount',
            'todayActivities'
        ));
    }

    /**
     * Export Account History Logs to Excel (.xls).
     */
    public function exportExcel(Request $request)
    {
        $logs = $this->buildFilteredQuery($request)->get();
        $actionFilter = $request->query('action', 'all') ?: 'all';
        $roleFilter   = $request->query('role', 'all') ?: 'all';

        $rowsHtml = '';
        foreach ($logs as $log) {
            $dateStr = $log->logged_at ? $log->logged_at->format('Y-m-d h:i:s A') : 'N/A';
            $rowsHtml .= '<tr>
                <td style="white-space:nowrap;">' . htmlspecialchars($dateStr) . '</td>
                <td style="font-weight:bold;">' . htmlspecialchars($log->user_name ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($log->phone_number ?: 'No phone') . '</td>
                <td style="text-align:center; font-weight:bold; text-transform:uppercase;">' . htmlspecialchars($log->user_role ?? 'N/A') . '</td>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($log->action ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($log->actor_name ?: 'System') . '</td>
                <td style="text-align:center; text-transform:capitalize;">' . htmlspecialchars($log->actor_role ?: '—') . '</td>
                <td>' . htmlspecialchars($log->details ?: 'Normal account event.') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($log->ip_address ?: 'N/A') . '</td>
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
            <h3 style="color:#0c2d48; margin-bottom:4px;">MEDLINK BARANGAY HEALTH SYSTEM &mdash; Account History Logs</h3>
            <p style="font-size:11px; color:#64748b; margin-top:0; margin-bottom:16px;">
                Action Filter: ' . htmlspecialchars(ucfirst($actionFilter)) . ' | Role Filter: ' . htmlspecialchars(ucfirst($roleFilter)) . ' | Generated: ' . Carbon::now()->format('F d, Y h:i A') . '
            </p>
            <table border="1">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>User Account</th>
                        <th>Phone Number</th>
                        <th>Role</th>
                        <th>Event / Action</th>
                        <th>Created By / Actor</th>
                        <th>Actor Role</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>' . ($rowsHtml ?: '<tr><td colspan="9" style="text-align:center;">No account history logs found.</td></tr>') . '</tbody>
            </table>
        </body>
        </html>';

        $filename = 'Account_History_Logs_' . Carbon::now()->format('Ymd_His') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Clear all account history logs.
     */
    public function clear()
    {
        $count = AccountLog::count();
        AccountLog::truncate();

        return redirect()->route('nurse.account-history')
            ->with('success', $count > 0
                ? "All {$count} account history log(s) have been cleared."
                : 'Account history logs are already empty.');
    }
}
