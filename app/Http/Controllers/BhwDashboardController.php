<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomeServiceRequest;
use App\Models\Schedule;
use App\Models\AppNotification;
use Carbon\Carbon;

class BhwDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $now = Carbon::now();

        // 1. Assigned Services Today
        $assignedServicesToday = HomeServiceRequest::where('assigned_to', $user->id)
            ->whereDate('preferred_date', $today)
            ->whereIn('status', ['Accepted', 'Approved', 'Confirmed', 'assigned'])
            ->count();

        // 2. Completed Services
        $completedServices = HomeServiceRequest::where('assigned_to', $user->id)
            ->where('status', 'Completed')
            ->count();

        // 3. Walk-in Patients Today (overall or BHW specific?)
        $walkInPatientsToday = Schedule::where('type', 'Walk-in')
            ->whereDate('scheduled_date', $today)
            ->count();

        // 4. Pending Tasks
        $pendingTasks = HomeServiceRequest::where('assigned_to', $user->id)
            ->whereIn('status', ['Accepted', 'Approved', 'Confirmed', 'assigned'])
            ->count();

        // My Assigned Services (next upcoming 5) — home service requests
        $myAssignedHomeServices = HomeServiceRequest::where('assigned_to', $user->id)
            ->whereIn('status', ['Accepted', 'Approved', 'Confirmed', 'assigned'])
            ->orderBy('preferred_date', 'asc')
            ->orderBy('preferred_time', 'asc')
            ->take(5)
            ->get()
            ->map(function ($hs) {
                return array_merge($hs->toArray(), ['item_type' => 'home_service']);
            });

        // My Assigned Schedules (nurse-assigned appointments)
        $myAssignedSchedules = Schedule::where('assigned_to', $user->id)
            ->whereIn('assignment_status', ['assigned', 'confirmed'])
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('scheduled_time', 'asc')
            ->take(5)
            ->get()
            ->map(function ($s) {
                return [
                    'id'                => $s->id,
                    'item_type'         => 'schedule',
                    'patient_name'      => $s->patient_name,
                    'service_type'      => $s->service . ' (' . $s->type . ')',
                    'service'           => $s->service,
                    'type'              => $s->type,
                    'preferred_date'    => $s->scheduled_date->format('Y-m-d'),
                    'preferred_time'    => $s->scheduled_time,
                    'status'            => $s->status,
                    'assignment_status' => $s->assignment_status,
                    'formatted_id'      => $s->formatted_id,
                    'formatted_time'    => $s->formatted_time,
                ];
            });

        $myAssignedServices = $myAssignedHomeServices->concat($myAssignedSchedules)->values();

        // Recent Activity (we can just fetch recent AppNotifications for the user, or system-wide depending on role)
        $recentActivities = AppNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Walk-in registration
        $walkinStoreUrl = route('bhw.walkin.store');

        // Recent walk-in registrations
        $recentWalkins = \App\Models\Schedule::where('type', 'Walk-in')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id'           => $schedule->id,
                    'patient_name' => $schedule->patient_name,
                    'service'      => $schedule->service,
                    'date'         => $schedule->scheduled_date->format('Y-m-d'),
                    'time'         => \Carbon\Carbon::parse($schedule->scheduled_time)->format('g:i A'),
                    'status'       => $schedule->status,
                    'notes'        => $schedule->notes,
                ];
            });

        // View Inventory (Medicines)
        $medicines = \App\Models\Medicine::orderBy('name')->get();
        $expiredMedicinesCount = \App\Models\Medicine::whereNotNull('expiration_date')->where('expiration_date', '<=', \Carbon\Carbon::today())->count();

        $dashboardData = [
            'assignedServicesToday' => $assignedServicesToday,
            'completedServices' => $completedServices,
            'walkInPatientsToday' => $walkInPatientsToday,
            'pendingTasks' => $pendingTasks,
            'expiredMedicinesCount' => $expiredMedicinesCount,
        ];

        // All Pending Home Services (unassigned, created by residents)
        $pendingHomeServices = HomeServiceRequest::where('status', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // All Accepted Home Services (claimed/accepted by BHW)
        $acceptedHomeServices = HomeServiceRequest::where('assigned_to', $user->id)
            ->where('status', 'Accepted')
            ->orderBy('preferred_date', 'asc')
            ->orderBy('preferred_time', 'asc')
            ->get();

        // All Approved Home Services for the logged-in BHW (including Accepted if nurse assigned to BHW)
        $assignedHomeServices = HomeServiceRequest::where('assigned_to', $user->id)
            ->whereIn('status', ['Approved', 'Confirmed', 'assigned', 'Accepted'])
            ->orderBy('preferred_date', 'asc')
            ->orderBy('preferred_time', 'asc')
            ->get();

        $completedHomeServices = HomeServiceRequest::where('assigned_to', $user->id)
            ->where('status', 'Completed')
            ->orderBy('updated_at', 'desc')
            ->get();

        // ── BHW Reports Data (synced with Nurse & Resident data) ────────

        // Current month stats
        $thisMonth = $now->month;
        $thisYear = $now->year;

        $walkInThisMonth = Schedule::where('type', 'Walk-in')
            ->whereMonth('scheduled_date', $thisMonth)
            ->whereYear('scheduled_date', $thisYear)
            ->count();

        $homeServicesThisMonth = HomeServiceRequest::whereIn('status', ['Completed', 'Approved', 'Confirmed', 'assigned', 'Pending'])
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->count();

        $completedHomeServicesThisMonth = HomeServiceRequest::where('status', 'Completed')
            ->whereMonth('updated_at', $thisMonth)
            ->whereYear('updated_at', $thisYear)
            ->count();

        $totalServicesThisMonth = $walkInThisMonth + $homeServicesThisMonth;

        // Monthly Performance (last 5 months)
        $monthlyPerformance = [];
        for ($i = 4; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthName = $month->format('F');
            $m = $month->month;
            $y = $month->year;

            $walkins = Schedule::where('type', 'Walk-in')
                ->whereMonth('scheduled_date', $m)
                ->whereYear('scheduled_date', $y)
                ->count();

            $homeServices = HomeServiceRequest::whereMonth('created_at', $m)
                ->whereYear('created_at', $y)
                ->count();

            $monthlyPerformance[] = [
                'month' => $monthName,
                'walk_in_patients' => $walkins,
                'home_services' => $homeServices,
                'total' => $walkins + $homeServices,
            ];
        }

        // Recent Activities for Reports (combined walk-ins + home services + vaccinations)
        // Pull recent walk-in registrations
        $recentWalkinActivities = Schedule::where('type', 'Walk-in')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->toBase()
            ->map(function ($schedule) {
                return [
                    'patient_name' => $schedule->patient_name,
                    'activity_type' => 'Walk-in Registration',
                    'service' => $schedule->service,
                    'status' => $schedule->status,
                    'date' => $schedule->scheduled_date ? $schedule->scheduled_date->format('Y-m-d') : ($schedule->created_at ? $schedule->created_at->format('Y-m-d') : null),
                    'created_at' => $schedule->created_at,
                ];
            });

        // Pull recent home service activities
        $recentHomeServiceActivities = HomeServiceRequest::orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->toBase()
            ->map(function ($hs) {
                $statusLabel = $hs->status === 'Completed'
                    ? $hs->service_type . ' - Completed'
                    : $hs->service_type;

                return [
                    'patient_name' => $hs->patient_name,
                    'activity_type' => 'Home Service',
                    'service' => $statusLabel,
                    'status' => $hs->status,
                    'date' => $hs->created_at ? $hs->created_at->format('Y-m-d') : null,
                    'created_at' => $hs->created_at,
                ];
            });

        // Pull recent child vaccination activities
        $recentVaccinationActivities = \App\Models\ChildVaccination::orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->toBase()
            ->map(function ($vac) {
                return [
                    'patient_name' => $vac->child_name . ' (' . ($vac->age_category ?? 'Child') . ')',
                    'activity_type' => 'Child Vaccination',
                    'service' => $vac->vaccine_name . ' — ' . $vac->dose_number,
                    'status' => $vac->status,
                    'date' => $vac->date_administered ? $vac->date_administered->format('Y-m-d') : ($vac->created_at ? $vac->created_at->format('Y-m-d') : null),
                    'created_at' => $vac->created_at,
                ];
            });

        // Combine using base Support\Collection concat (avoids Eloquent\Collection::merge getKey() crash on arrays)
        $reportActivities = collect()
            ->concat($recentWalkinActivities)
            ->concat($recentHomeServiceActivities)
            ->concat($recentVaccinationActivities)
            ->sortByDesc('created_at')
            ->take(15)
            ->values()
            ->toArray();

        $reportData = [
            'totalServicesThisMonth' => $totalServicesThisMonth,
            'walkInThisMonth' => $walkInThisMonth,
            'completedHomeServicesThisMonth' => $completedHomeServicesThisMonth,
            'monthlyPerformance' => $monthlyPerformance,
            'reportActivities' => $reportActivities,
        ];

        // Operational counts for BHW notifications (like Nurse portal)
        $readAt = $user->notifications_read_at;

        $pendingHomeServicesQuery = HomeServiceRequest::where('status', 'Pending');
        $assignedHomeServicesQuery = HomeServiceRequest::where('assigned_to', $user->id)
            ->whereIn('status', ['Accepted', 'Approved', 'Confirmed', 'assigned']);
        $pendingAssignedSchedulesQuery = Schedule::where('assigned_to', $user->id)
            ->where('assignment_status', 'assigned');
        $unassignedSchedulesQuery = Schedule::where('status', 'Confirmed')
            ->where('assignment_status', 'unassigned');
        $pendingMedicineRequestsQuery = \App\Models\MedicineRequest::where('delivery_method', 'delivery')
            ->where('status', 'Pending');
        $pendingPatientVerificationsQuery = \App\Models\User::where('role', 'resident')->where('status', 'pending')->where('registration_unverified', false);

        if ($readAt) {
            $pendingHomeServicesQuery->where('updated_at', '>', $readAt);
            $assignedHomeServicesQuery->where('updated_at', '>', $readAt);
            $pendingAssignedSchedulesQuery->where('updated_at', '>', $readAt);
            $unassignedSchedulesQuery->where('updated_at', '>', $readAt);
            $pendingMedicineRequestsQuery->where('updated_at', '>', $readAt);
            $pendingPatientVerificationsQuery->where('created_at', '>', $readAt);
        }

        $pendingHomeServicesCount = $pendingHomeServicesQuery->count();
        $assignedHomeServicesCount = $assignedHomeServicesQuery->count();
        $pendingAssignedSchedulesCount = $pendingAssignedSchedulesQuery->count();
        $unassignedSchedulesCount = $unassignedSchedulesQuery->count();
        $pendingMedicineRequestsCount = $pendingMedicineRequestsQuery->count();
        $pendingPatientVerificationsCount = $pendingPatientVerificationsQuery->count();

        // All medicine delivery requests for BHW (strictly delivery only, never pickup)
        $medicineDeliveryRequests = \App\Models\MedicineRequest::with(['user', 'medicine'])
            ->where('delivery_method', 'delivery')
            ->latest()
            ->get();

        // Personal notifications
        $notifications = AppNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $notificationSummary = [
            'pendingHomeServices' => $pendingHomeServicesCount,
            'assignedHomeServices' => $assignedHomeServicesCount,
            'pendingAssignedSchedules' => $pendingAssignedSchedulesCount,
            'unassignedSchedules' => $unassignedSchedulesCount,
            'pendingMedicineRequests' => $pendingMedicineRequestsCount,
            'pendingPatientVerifications' => $pendingPatientVerificationsCount,
        ];

        // Announcements (synced across all portals)
        $announcements = \App\Models\Announcement::orderBy('created_at', 'desc')->get();

        // Returned & Damaged Medicines Ledger
        $medicineReturns = \App\Models\MedicineReturn::with(['user', 'medicineRequest', 'medicine', 'recorder'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ret) {
                return [
                    'id'                => $ret->id,
                    'medicine_name'     => $ret->medicine_name,
                    'patient_name'      => $ret->user ? ($ret->user->first_name . ' ' . $ret->user->last_name) : 'Unknown Resident',
                    'previous_quantity' => $ret->previous_quantity,
                    'new_quantity'      => $ret->new_quantity,
                    'quantity_returned' => $ret->quantity_returned,
                    'reason'            => $ret->reason,
                    'unit'              => $ret->medicine ? $ret->medicine->unit : 'unit(s)',
                    'request_id'        => $ret->medicineRequest ? $ret->medicineRequest->request_id : ('REQ-' . str_pad($ret->medicine_request_id, 3, '0', STR_PAD_LEFT)),
                    'recorded_by'       => $ret->recorder ? ($ret->recorder->first_name . ' ' . $ret->recorder->last_name) : 'Healthcare Staff',
                    'date'              => $ret->created_at->format('M d, Y h:i A'),
                ];
            });

        // Nurse Accounts list
        $nurseAccounts = \App\Models\User::where('role', 'nurse')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboards.bhw', compact(
            'dashboardData',
            'myAssignedServices',
            'recentActivities',
            'walkinStoreUrl',
            'recentWalkins',
            'medicines',
            'expiredMedicinesCount',
            'pendingHomeServices',
            'acceptedHomeServices',
            'assignedHomeServices',
            'completedHomeServices',
            'reportData',
            'notifications',
            'notificationSummary',
            'announcements',
            'medicineDeliveryRequests',
            'medicineReturns',
            'nurseAccounts'
        ));
    }
    
    private function dispatchHomeServiceOnTheWaySms(HomeServiceRequest $homeService, string $workerName): void
    {
        if ($homeService->on_the_way_sms_at !== null) {
            return;
        }

        $claimed = HomeServiceRequest::where('id', $homeService->id)
            ->whereNull('on_the_way_sms_at')
            ->update(['on_the_way_sms_at' => now()]);

        if ($claimed !== 1) {
            return;
        }

        $residentUser = $homeService->user_id ? \App\Models\User::find($homeService->user_id) : null;
        $phone = $residentUser
            ? (string) ($residentUser->phone_number ?? '')
            : (string) ($homeService->contact_number ?? '');

        $smsMessage = \App\Support\SmsTemplates::homeServiceOnTheWay(
            $homeService->formatted_id,
            $workerName,
            (string) $homeService->service_type
        );

        app(\App\Services\SmsService::class)->sendAfterResponse(
            $phone,
            $smsMessage,
            'home_service_on_the_way',
            $residentUser,
            $homeService
        );
    }

    /**
     * Mark a pending home service request as accepted by BHW (AJAX)
     * Notifies resident that home service is coming/preparing.
     */
    public function acceptHomeService(Request $request, HomeServiceRequest $homeService)
    {
        $homeService->status = 'Accepted';
        $homeService->assigned_to = auth()->id();
        $homeService->assigned_bhw_name = 'BHW ' . auth()->user()->first_name . ' ' . auth()->user()->last_name;
        $homeService->save();

        // Notify the resident that the BHW accepted and is coming
        if ($homeService->user_id) {
            AppNotification::create([
                'user_id' => $homeService->user_id,
                'type' => 'info',
                'title' => 'Home Service Accepted — On the Way',
                'message' => "{$homeService->assigned_bhw_name} has accepted your {$homeService->service_type} home service request and is on the way to your location.",
            ]);
        }

        // Notify Nurses in real-time
        foreach (\App\Models\User::where('role', 'nurse')->get() as $nurse) {
            AppNotification::create([
                'user_id' => $nurse->id,
                'type' => 'info',
                'title' => 'Home Service Accepted by BHW',
                'message' => "{$homeService->assigned_bhw_name} accepted {$homeService->service_type} for {$homeService->patient_name}.",
            ]);
        }

        $this->dispatchHomeServiceOnTheWaySms($homeService, $homeService->assigned_bhw_name);

        return response()->json([
            'success' => true,
            'message' => 'Service request accepted successfully. Resident has been notified that you are on the way.'
        ]);
    }

    /**
     * Mark a home service request as confirmed by BHW (AJAX)
     */
    public function confirmHomeService(Request $request, HomeServiceRequest $homeService)
    {
        if ($homeService->assigned_to !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $homeService->status = 'Confirmed';
        $homeService->save();

        // Notify the resident that the BHW is coming
        $workerName = $homeService->assigned_bhw_name ?: ('BHW ' . auth()->user()->first_name . ' ' . auth()->user()->last_name);
        if ($homeService->user_id) {
            AppNotification::create([
                'user_id' => $homeService->user_id,
                'type' => 'info',
                'title' => 'Home Service — On the Way',
                'message' => "The home service, {$workerName} is coming wait for a minute.",
            ]);
        }

        // Notify Nurses in real-time
        foreach (\App\Models\User::where('role', 'nurse')->get() as $nurse) {
            AppNotification::create([
                'user_id' => $nurse->id,
                'type' => 'info',
                'title' => 'Home Service Confirmed by BHW',
                'message' => "{$workerName} confirmed and is on the way for {$homeService->patient_name}'s {$homeService->service_type}.",
            ]);
        }

        $this->dispatchHomeServiceOnTheWaySms($homeService, $workerName);

        return response()->json([
            'success' => true, 
            'message' => 'Service assignment confirmed successfully.'
        ]);
    }

    /**
     * Mark a pending home service request as approved and claim it (AJAX)
     */
    public function approveHomeService(Request $request, HomeServiceRequest $homeService)
    {
        $homeService->status = 'Approved';
        $homeService->assigned_to = auth()->id();
        $homeService->assigned_bhw_name = 'BHW ' . auth()->user()->first_name . ' ' . auth()->user()->last_name;
        $homeService->save();

        // Notify the resident that the BHW is coming
        $workerName = $homeService->assigned_bhw_name;
        if ($homeService->user_id) {
            AppNotification::create([
                'user_id' => $homeService->user_id,
                'type' => 'success',
                'title' => 'Home Service Approved — On the Way',
                'message' => "The home service, {$workerName} is coming wait for a minute.",
            ]);
        }

        // Notify Nurses in real-time
        foreach (\App\Models\User::where('role', 'nurse')->get() as $nurse) {
            AppNotification::create([
                'user_id' => $nurse->id,
                'type' => 'success',
                'title' => 'Home Service Approved by BHW',
                'message' => "{$workerName} approved and claimed {$homeService->service_type} for {$homeService->patient_name}.",
            ]);
        }

        $this->dispatchHomeServiceOnTheWaySms($homeService, $workerName);

        return response()->json([
            'success' => true, 
            'message' => 'Service request approved and claimed successfully.'
        ]);
    }

    /**
     * Mark a home service request as completed (AJAX)
     */
    public function completeHomeService(Request $request, HomeServiceRequest $homeService)
    {
        if ($homeService->assigned_to !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $homeService->status = 'Completed';
        $homeService->save();

        // Notify the resident
        $workerName = $homeService->assigned_bhw_name ?: ('BHW ' . auth()->user()->first_name . ' ' . auth()->user()->last_name);
        if ($homeService->user_id) {
            AppNotification::create([
                'user_id' => $homeService->user_id,
                'type' => 'info',
                'title' => 'Home Service Completed',
                'message' => "The home service, {$workerName} has completed your {$homeService->service_type} home visit.",
            ]);
        }

        // Notify Nurses in real-time
        foreach (\App\Models\User::where('role', 'nurse')->get() as $nurse) {
            AppNotification::create([
                'user_id' => $nurse->id,
                'type' => 'success',
                'title' => 'Home Service Completed',
                'message' => "{$workerName} completed {$homeService->service_type} home visit for {$homeService->patient_name}.",
            ]);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Service marked as completed successfully.'
        ]);
    }

    /**
     * Return list of nurse accounts for BHW
     */
    public function nursesList()
    {
        if (!auth()->user() || !auth()->user()->can_manage_nurses) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You do not have permission to manage Nurse accounts.'], 403);
        }

        $nurses = \App\Models\User::where('role', 'nurse')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['success' => true, 'nurses' => $nurses]);
    }

    /**
     * Store a newly created nurse account created by BHW
     */
    public function storeNurse(Request $request)
    {
        if (!auth()->user() || !auth()->user()->can_manage_nurses) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You do not have permission to create Nurse accounts.'], 403);
        }

        \App\Support\PhoneNumber::mergeNormalized($request, ['phone_number']);

        $validated = $request->validate([
            'first_name'   => ['required', 'string', 'max:255'],
            'last_name'    => ['required', 'string', 'max:255'],
            'phone_number' => [
                'required',
                new \App\Rules\PhilippineMobile,
                \Illuminate\Validation\Rule::unique('users', 'phone_number')->where(fn ($q) => $q->where('registration_unverified', false)),
            ],
            'age'          => ['nullable', 'integer', 'min:18', 'max:130'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $age = isset($validated['age']) && $validated['age'] !== null ? (int) $validated['age'] : null;

        \App\Models\User::purgeUnverifiedRegistration($validated['phone_number']);

        $nurse = \App\Models\User::create([
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'phone_number' => $validated['phone_number'],
            'age'          => $age,
            'age_category' => \App\Models\User::resolveAgeCategory($age),
            'role'         => 'nurse',
            'status'       => 'active',
            'password'     => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Nurse account for {$nurse->first_name} {$nurse->last_name} created successfully.",
            'nurse'   => $nurse,
        ]);
    }

    /**
     * Delete a nurse account
     */
    public function destroyNurse(\App\Models\User $user)
    {
        if (!auth()->user() || !auth()->user()->can_manage_nurses) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: You do not have permission to delete Nurse accounts.'], 403);
        }

        if ($user->role !== 'nurse') {
            return response()->json(['success' => false, 'message' => 'User is not a nurse.'], 403);
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'Nurse account deleted successfully.']);
    }

    /**
     * Mark all notifications as read for the BHW
     */
    public function markNotificationsRead(Request $request)
    {
        $user = auth()->user();
        AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $user->update(['notifications_read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Update BHW Staff Profile information and optional photo.
     */
    public function updateProfile(Request $request, \App\Services\OtpService $otpService)
    {
        $user = auth()->user();

        $typedFormat = \App\Support\PhoneNumber::detectFormat($request->input('phone_number'));
        \App\Support\PhoneNumber::mergeNormalized($request, ['phone_number']);

        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'phone_number'  => [
                'required',
                new \App\Rules\PhilippineMobile,
                \Illuminate\Validation\Rule::unique('users', 'phone_number')
                    ->ignore($user->id)
                    ->where(fn ($q) => $q->where('registration_unverified', false)),
            ],
            'gender'        => ['nullable', 'string', 'in:Male,Female,Other'],
            'address'       => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')->store('profile_photos', 'public');
        } else {
            unset($validated['profile_photo']);
        }

        $newPhone = $validated['phone_number'];
        $phoneChanged = $newPhone !== $user->phone_number;

        if (!$phoneChanged) {
            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'BHW staff profile updated successfully.',
                'user'    => $user->fresh(),
            ]);
        }

        // Save all other profile fields immediately; keep old phone_number until OTP verified
        unset($validated['phone_number']);
        $user->update($validated);

        $lockoutSeconds = $otpService->lockoutRemaining($newPhone, 'phone_change');
        if ($lockoutSeconds !== null) {
            $msg = $otpService->errorMessage(['error' => 'locked', 'retry_after' => $lockoutSeconds]);
            return response()->json([
                'success' => false,
                'message' => $msg,
                'errors'  => ['phone_number' => [$msg]],
                'user'    => $user->fresh(),
            ], 422);
        }

        $user->update(['pending_phone_number' => $newPhone]);
        $otpResult = $otpService->issue($newPhone, 'phone_change', $user);

        if (!$otpResult['ok'] && ($otpResult['error'] ?? null) !== 'cooldown') {
            $msg = $otpService->errorMessage($otpResult);
            return response()->json([
                'success' => false,
                'message' => $msg,
                'errors'  => ['phone_number' => [$msg]],
                'user'    => $user->fresh(),
            ], 422);
        }

        $request->session()->put('phone_change_format', $typedFormat);
        $masked = \App\Support\PhoneNumber::mask($newPhone, $typedFormat);
        $resendIn = ($otpResult['error'] ?? null) === 'cooldown'
            ? (int) ($otpResult['retry_after'] ?? 60)
            : (int) config('otp.resend_cooldown_seconds', 60);

        return response()->json([
            'success'             => true,
            'otp_required'        => true,
            'masked'              => $masked,
            'resend_available_in' => $resendIn,
            'message'             => "Profile saved. Enter the code sent to {$masked} to confirm your new number.",
            'user'                => $user->fresh(),
        ]);
    }

    /**
     * Update BHW Staff account password.
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current password is incorrect.',
                'errors'  => ['current_password' => ['Your current password is incorrect.']],
            ], 422);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
