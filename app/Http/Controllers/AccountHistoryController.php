<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use Illuminate\Http\Request;

class AccountHistoryController extends Controller
{
    /**
     * Display the Account History Logs list in the Nurse Portal.
     */
    public function index(Request $request)
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

        $logs = $query->paginate(20)->withQueryString();

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
}
