<?php

namespace App\Http\Controllers;

use App\Models\HomeServiceRequest;
use App\Models\HomeServiceType;
use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class HomeServiceController extends Controller
{
    /**
     * Display the Home Service Management page.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        $query = HomeServiceRequest::with(['user', 'assignedBhw'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%" . preg_replace('/[^0-9]/', '', $search) . "%");
            });
        }

        $allRequests = $query->get();

        $pendingRequests = $allRequests->where('status', 'Pending');
        $acceptedRequests = $allRequests->where('status', 'Accepted');
        $approvedRequests = $allRequests->whereIn('status', ['Approved', 'Confirmed']);
        $completedRequests = $allRequests->where('status', 'Completed');
        $rejectedRequests = $allRequests->where('status', 'Rejected');

        $pendingCount = HomeServiceRequest::where('status', 'Pending')->count();
        $acceptedCount = HomeServiceRequest::where('status', 'Accepted')->count();
        $approvedCount = HomeServiceRequest::whereIn('status', ['Approved', 'Confirmed'])->count();
        $completedCount = HomeServiceRequest::where('status', 'Completed')->count();
        $rejectedCount = HomeServiceRequest::where('status', 'Rejected')->count();

        // Get all BHW users for assignment dropdown
        $bhwUsers = User::where('role', 'bhw')->get();

        // Get all service types
        $serviceTypes = HomeServiceType::orderBy('name')->get();

        return view('dashboards.nurse-home-services', compact(
            'allRequests',
            'pendingRequests',
            'acceptedRequests',
            'approvedRequests',
            'completedRequests',
            'rejectedRequests',
            'pendingCount',
            'acceptedCount',
            'approvedCount',
            'completedCount',
            'rejectedCount',
            'bhwUsers',
            'serviceTypes',
            'status',
            'search'
        ));
    }

    /**
     * Store a new home service request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_name' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'preferred_date' => 'required|date',
            'preferred_time' => 'required|string|max:100',
            'reason_symptoms' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'assigned_bhw_name' => 'nullable|string|max:255',
        ], [
            'contact_number.regex' => 'The contact number must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).',
        ]);

        if (!empty($validated['assigned_to'])) {
            $bhw = User::find($validated['assigned_to']);
            if ($bhw && empty(trim($validated['assigned_bhw_name'] ?? ''))) {
                $validated['assigned_bhw_name'] = 'BHW ' . $bhw->first_name . ' ' . $bhw->last_name;
            }
            $validated['status'] = 'Accepted';
        }

        $hsRequest = HomeServiceRequest::create($validated);

        if (!empty($hsRequest->assigned_to)) {
            AppNotification::create([
                'user_id' => $hsRequest->assigned_to,
                'type' => 'info',
                'title' => 'New Home Service Assignment',
                'message' => "You have been assigned to {$hsRequest->service_type} for {$hsRequest->patient_name} on " . ($hsRequest->preferred_date ? $hsRequest->preferred_date->format('M d, Y') : $validated['preferred_date']) . " ({$hsRequest->preferred_time}).",
            ]);
        }

        return redirect()->route('nurse.home-services')->with('success', 'Home service request created successfully.');
    }

    /**
     * Store a new home service request from a resident.
     */
    public function storeResident(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'preferred_date' => 'required|date',
            'preferred_time' => 'required|string|max:100',
            'reason_symptoms' => 'nullable|string',
        ], [
            'contact_number.regex' => 'The contact number must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).',
        ]);

        $user = auth()->user();

        // Restriction: Only PWD and Senior Citizens can access / request Home Health Services
        $isSenior = ($user->age !== null && $user->age >= 60) || strtolower($user->age_category ?? '') === 'senior citizen';
        $classification = strtolower($user->patient_classification ?? '');
        $isEligible = $classification === 'pwd' || $classification === 'senior citizen' || $isSenior;

        if (!$isEligible) {
            $msg = 'Home health services are exclusively reserved for PWD and Senior Citizen residents. Regular patients may visit the Barangay Health Center.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        // 24-hour cooldown: a resident can only submit one home service request every 24 hours
        $lastRequest = HomeServiceRequest::where('user_id', $user->id)
            ->where('status', '!=', 'Rejected')
            ->latest('created_at')
            ->first();

        if ($lastRequest && $lastRequest->created_at && $lastRequest->created_at->gt(now()->subHours(24))) {
            $nextAvailableAt = $lastRequest->created_at->copy()->addHours(24);
            $hoursLeft = max(1, (int) ceil(now()->diffInMinutes($nextAvailableAt) / 60));
            $msg = "You can only request a home service once every 24 hours. Please wait about {$hoursLeft} hour(s) — available again on " . $nextAvailableAt->format('M d, Y h:i A') . '.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'cooldown_until' => $nextAvailableAt->toIso8601String(),
                ], 429);
            }
            return redirect()->back()->with('error', $msg);
        }

        $hsRequest = HomeServiceRequest::create([
            'user_id' => $user->id,
            'patient_name' => $user->first_name . ' ' . $user->last_name,
            'service_type' => $validated['service_type'],
            'address' => $validated['address'],
            'contact_number' => $validated['contact_number'],
            'preferred_date' => $validated['preferred_date'],
            'preferred_time' => $validated['preferred_time'],
            'reason_symptoms' => $validated['reason_symptoms'] ?? null,
            'status' => 'Pending',
        ]);

        // Notify all Nurses and BHWs in real-time about the new Home Service request
        $staff = User::whereIn('role', ['nurse', 'bhw'])->get();
        foreach ($staff as $member) {
            AppNotification::create([
                'user_id' => $member->id,
                'type' => 'info',
                'title' => 'New Home Service Request',
                'message' => "{$hsRequest->patient_name} requested {$hsRequest->service_type} on " . ($hsRequest->preferred_date ? $hsRequest->preferred_date->format('M d, Y') : $validated['preferred_date']) . " ({$hsRequest->preferred_time}).",
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Home service request submitted successfully.');
    }

    /**
     * Accept a home service request (Nurse assigns a BHW & notifies resident and BHW).
     */
    public function accept(Request $request, HomeServiceRequest $homeServiceRequest)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'assigned_bhw_name' => 'nullable|string|max:255',
        ]);

        $customBhwName = trim($validated['assigned_bhw_name'] ?? '');

        if (empty($validated['assigned_to']) && $customBhwName === '') {
            $msg = 'Please select a BHW or enter the name of the BHW to assign this patient for home service.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $homeServiceRequest->status = 'Accepted';

        if (!empty($validated['assigned_to'])) {
            $homeServiceRequest->assigned_to = $validated['assigned_to'];
            $bhw = User::find($validated['assigned_to']);
            if ($customBhwName !== '') {
                $homeServiceRequest->assigned_bhw_name = $customBhwName;
            } elseif ($bhw) {
                $homeServiceRequest->assigned_bhw_name = 'BHW ' . $bhw->first_name . ' ' . $bhw->last_name;
            }
        } else {
            $homeServiceRequest->assigned_to = null;
            $homeServiceRequest->assigned_bhw_name = $customBhwName;
        }

        $homeServiceRequest->save();

        $workerName = $homeServiceRequest->assigned_name ?: 'Our healthcare team';

        // Notify the resident that the home service request has been accepted & assigned
        if ($homeServiceRequest->user_id) {
            AppNotification::create([
                'user_id' => $homeServiceRequest->user_id,
                'type' => 'info',
                'title' => 'Home Service Accepted & Assigned',
                'message' => "Your {$homeServiceRequest->service_type} home service request has been accepted and assigned to {$workerName}.",
            ]);
        }

        // Notify the assigned BHW
        if (!empty($homeServiceRequest->assigned_to)) {
            AppNotification::create([
                'user_id' => $homeServiceRequest->assigned_to,
                'type' => 'info',
                'title' => 'New Home Service Assignment',
                'message' => "You have been assigned to {$homeServiceRequest->service_type} for {$homeServiceRequest->patient_name} on " . ($homeServiceRequest->preferred_date ? $homeServiceRequest->preferred_date->format('M d, Y') : 'schedule') . " ({$homeServiceRequest->preferred_time}).",
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Request accepted and assigned to {$workerName}."
            ]);
        }

        return redirect()->back()->with('success', 'Request ' . $homeServiceRequest->formatted_id . ' accepted and assigned to ' . $workerName . '.');
    }

    /**
     * Approve a home service request.
     */
    public function approve(Request $request, HomeServiceRequest $homeServiceRequest)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'assigned_bhw_name' => 'nullable|string|max:255',
            'nurse_notes' => 'nullable|string',
        ]);

        $homeServiceRequest->status = 'Approved';
        
        // Custom assigned name takes priority if explicitly typed, otherwise dropdown user
        if (!empty($validated['assigned_bhw_name'])) {
            $homeServiceRequest->assigned_bhw_name = $validated['assigned_bhw_name'];
            if (!empty($validated['assigned_to'])) {
                $homeServiceRequest->assigned_to = $validated['assigned_to'];
            }
        } elseif (!empty($validated['assigned_to'])) {
            $homeServiceRequest->assigned_to = $validated['assigned_to'];
            $bhw = User::find($validated['assigned_to']);
            if ($bhw) {
                $homeServiceRequest->assigned_bhw_name = 'BHW ' . $bhw->first_name . ' ' . $bhw->last_name;
            }
        }

        if (!empty($validated['nurse_notes'])) {
            $homeServiceRequest->nurse_notes = $validated['nurse_notes'];
        }

        $homeServiceRequest->save();

        // Notify the resident that the home service is assigned to the worker
        if ($homeServiceRequest->user_id) {
            $workerName = $homeServiceRequest->assigned_bhw_name ?: 'Our healthcare team';
            AppNotification::create([
                'user_id' => $homeServiceRequest->user_id,
                'type' => 'success',
                'title' => 'Home Service Assigned',
                'message' => "The home service assign to {$workerName}.",
            ]);
        }

        // Notify the assigned BHW
        if (!empty($homeServiceRequest->assigned_to)) {
            AppNotification::create([
                'user_id' => $homeServiceRequest->assigned_to,
                'type' => 'info',
                'title' => 'New Home Service Assignment',
                'message' => "You have been assigned to {$homeServiceRequest->service_type} for {$homeServiceRequest->patient_name} on " . ($homeServiceRequest->preferred_date ? $homeServiceRequest->preferred_date->format('M d, Y') : 'schedule') . " ({$homeServiceRequest->preferred_time}).",
            ]);
        }

        return redirect()->back()->with('success', 'Request ' . $homeServiceRequest->formatted_id . ' approved successfully.');
    }

    /**
     * Reject a home service request.
     */
    public function reject(Request $request, HomeServiceRequest $homeServiceRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $homeServiceRequest->status = 'Rejected';
        $homeServiceRequest->rejection_reason = $validated['rejection_reason'] ?? 'Request rejected by nurse.';
        $homeServiceRequest->save();

        // Notify the resident
        if ($homeServiceRequest->user_id) {
            AppNotification::create([
                'user_id' => $homeServiceRequest->user_id,
                'type' => 'error',
                'title' => 'Home Service Rejected',
                'message' => "Your {$homeServiceRequest->service_type} home service request has been rejected. Reason: " . ($validated['rejection_reason'] ?? 'Not specified.'),
            ]);
        }

        return redirect()->back()->with('success', 'Request ' . $homeServiceRequest->formatted_id . ' rejected.');
    }

    /**
     * Mark a home service request as completed.
     */
    public function complete(Request $request, HomeServiceRequest $homeServiceRequest)
    {
        $homeServiceRequest->status = 'Completed';
        $homeServiceRequest->save();

        // Notify the resident
        if ($homeServiceRequest->user_id) {
            $workerName = $homeServiceRequest->assigned_bhw_name ?: 'Our healthcare team';
            AppNotification::create([
                'user_id' => $homeServiceRequest->user_id,
                'type' => 'info',
                'title' => 'Home Service Completed',
                'message' => "The home service, {$workerName} has completed your {$homeServiceRequest->service_type} home visit.",
            ]);
        }

        return redirect()->back()->with('success', 'Request ' . $homeServiceRequest->formatted_id . ' marked as completed.');
    }

    /**
     * Update assignment for an approved request.
     */
    public function assign(Request $request, HomeServiceRequest $homeServiceRequest)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
            'assigned_bhw_name' => 'nullable|string|max:255',
        ]);

        $customBhwName = trim($validated['assigned_bhw_name'] ?? '');

        if (empty($validated['assigned_to']) && $customBhwName === '') {
            return redirect()->back()->with('error', 'Please select a BHW or enter the name of the BHW.');
        }

        if (!empty($validated['assigned_to'])) {
            $homeServiceRequest->assigned_to = $validated['assigned_to'];
            $bhw = User::find($validated['assigned_to']);
            if ($customBhwName !== '') {
                $homeServiceRequest->assigned_bhw_name = $customBhwName;
            } elseif ($bhw) {
                $homeServiceRequest->assigned_bhw_name = 'BHW ' . $bhw->first_name . ' ' . $bhw->last_name;
            }
        } else {
            $homeServiceRequest->assigned_to = null;
            $homeServiceRequest->assigned_bhw_name = $customBhwName;
        }

        $homeServiceRequest->save();

        // Notify the newly assigned BHW
        if (!empty($homeServiceRequest->assigned_to)) {
            AppNotification::create([
                'user_id' => $homeServiceRequest->assigned_to,
                'type' => 'info',
                'title' => 'Home Service Assignment Updated',
                'message' => "You have been assigned to {$homeServiceRequest->service_type} for {$homeServiceRequest->patient_name} on " . ($homeServiceRequest->preferred_date ? $homeServiceRequest->preferred_date->format('M d, Y') : 'schedule') . " ({$homeServiceRequest->preferred_time}).",
            ]);
        }

        // Notify the resident about the updated assignment
        if ($homeServiceRequest->user_id) {
            $workerName = $homeServiceRequest->assigned_bhw_name ?: 'Our healthcare team';
            AppNotification::create([
                'user_id' => $homeServiceRequest->user_id,
                'type' => 'info',
                'title' => 'Home Service Worker Updated',
                'message' => "Your {$homeServiceRequest->service_type} home service is now assigned to {$workerName}.",
            ]);
        }

        return redirect()->back()->with('success', 'Assigned worker updated.');
    }

    /**
     * Delete a single home service request.
     */
    public function destroy(HomeServiceRequest $homeServiceRequest)
    {
        $id = $homeServiceRequest->formatted_id;
        $homeServiceRequest->delete();

        return redirect()->back()->with('success', 'Request ' . $id . ' has been deleted.');
    }

    /**
     * Clear home service requests by status or all.
     */
    public function clear(Request $request)
    {
        $status = $request->input('status', 'Completed');

        if (strtolower($status) === 'all') {
            HomeServiceRequest::truncate();
            return redirect()->back()->with('success', 'All home service requests have been cleared.');
        }

        $formattedStatus = ucfirst(strtolower($status));
        $validStatuses = ['Pending', 'Accepted', 'Approved', 'Completed', 'Rejected'];

        if (in_array($formattedStatus, $validStatuses)) {
            $count = HomeServiceRequest::where('status', $formattedStatus)->count();
            HomeServiceRequest::where('status', $formattedStatus)->delete();
            return redirect()->back()->with('success', $count . ' ' . strtolower($formattedStatus) . ' request(s) cleared successfully.');
        }

        return redirect()->back()->with('error', 'Invalid status provided.');
    }
}
