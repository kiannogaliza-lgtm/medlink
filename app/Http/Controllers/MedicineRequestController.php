<?php

namespace App\Http\Controllers;

use App\Models\MedicineRequest;
use App\Models\Medicine;
use App\Models\User;
use App\Models\AppNotification;
use App\Models\MedicineReturn;
use App\Models\Consultation;
use Illuminate\Http\Request;

class MedicineRequestController extends Controller
{
    /**
     * Display a listing of medicine requests for nurses (with tabs: pending, accepted, approved, rejected, all).
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'Pending');
        $search = $request->query('search', '');

        $query = MedicineRequest::with(['user', 'medicine'])->latest();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('item_service', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if (in_array($status, ['Pending', 'Accepted', 'Approved', 'Completed', 'Rejected'])) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15)->withQueryString();

        $counts = [
            'pending'   => MedicineRequest::where('status', 'Pending')->count(),
            'accepted'  => MedicineRequest::where('status', 'Accepted')->count(),
            'approved'  => MedicineRequest::where('status', 'Approved')->count(),
            'completed' => MedicineRequest::where('status', 'Completed')->count(),
            'rejected'  => MedicineRequest::where('status', 'Rejected')->count(),
            'all'       => MedicineRequest::count(),
        ];

        return view('dashboards.nurse-medicine-requests', compact('requests', 'counts', 'status', 'search'));
    }

    /**
     * Store a new medicine request from a resident.
     * Deducts/reserves medicine stock immediately upon submission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medicine_id'          => ['required', 'exists:medicines,id'],
            'quantity'             => ['required', 'integer', 'min:1'],
            'delivery_method'      => ['required', 'in:pickup,delivery'],
            'patient_type'         => ['required', 'in:self,other'],
            'patient_name'         => ['nullable', 'required_if:patient_type,other', 'string', 'max:255'],
            'patient_age'          => ['nullable', 'required_if:patient_type,other', 'integer', 'min:0', 'max:130'],
            'patient_gender'       => ['nullable', 'string', 'in:Male,Female,Other'],
            'patient_relationship' => ['nullable', 'required_if:patient_type,other', 'string', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ]);

        $medicine = Medicine::findOrFail($validated['medicine_id']);

        // Block requesting expired medicines
        if ($medicine->expiration_date && \Carbon\Carbon::parse($medicine->expiration_date)->lte(\Carbon\Carbon::today())) {
            $msg = "{$medicine->name} is expired and cannot be requested.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['medicine_id' => $msg])->withInput();
        }

        // Determine actual patient age
        $actualAge = ($validated['patient_type'] === 'other')
            ? (int) $validated['patient_age']
            : (int) (auth()->user()->age ?? 0);

        $patientDisplayName = ($validated['patient_type'] === 'other')
            ? $validated['patient_name']
            : 'You (requester)';

        // Check age range / limit for this medicine
        if (!$medicine->isAgeAllowed($actualAge)) {
            $msg = "Patient age mismatch: {$patientDisplayName} is {$actualAge} years old. {$medicine->name} is only suitable for patients aged {$medicine->age_limit_label}.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['patient_age' => $msg])->withInput();
        }

        // Check if resident already has a pending or accepted request for this medicine
        $pendingOrAccepted = MedicineRequest::where('user_id', auth()->id())
            ->where(function ($q) use ($medicine) {
                $q->where('medicine_id', $medicine->id)
                  ->orWhere('item_service', $medicine->name);
            })
            ->whereIn('status', ['Pending', 'Accepted'])
            ->first();

        if ($pendingOrAccepted) {
            $existingMethod = $pendingOrAccepted->delivery_method === 'delivery' ? 'Home Delivery' : 'Pickup at Health Center';
            $attemptedMethod = $validated['delivery_method'] === 'delivery' ? 'Home Delivery' : 'Pickup';
            $msg = "You already have an active {$existingMethod} request ({$pendingOrAccepted->status}) for {$medicine->name}. Cannot request {$attemptedMethod} until your active request and dosage are completed.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['medicine_id' => $msg])->withInput();
        }

        // Check dosage cooldown on previous approved request for this medicine
        $lastApproved = MedicineRequest::where('user_id', auth()->id())
            ->where(function ($q) use ($medicine) {
                $q->where('medicine_id', $medicine->id)
                  ->orWhere('item_service', $medicine->name);
            })
            ->where('status', 'Approved')
            ->latest('updated_at')
            ->first();

        if ($lastApproved) {
            $timesPerDay = $medicine->dosage_times_per_day;
            $daysCycle = $medicine->dosage_days ?? 1;

            // If not directly configured on medicine, attempt to parse from dosage string (e.g. "3 times", "3x", "1 day 3 times")
            if (!$timesPerDay && !empty($medicine->dosage)) {
                if (preg_match('/(\d+)\s*(?:times|time|x)/i', $medicine->dosage, $m)) {
                    $timesPerDay = (int) $m[1];
                }
            }

            if ($timesPerDay && $timesPerDay > 0) {
                $prevQty = $lastApproved->quantity ?? 1;
                // Total days needed to finish previous supply: (quantity / times_per_day) * daysCycle
                $daysNeeded = max(1, (int) ceil(($prevQty / $timesPerDay) * $daysCycle));
                $availableDate = $lastApproved->updated_at->copy()->addDays($daysNeeded);

                if (now()->lt($availableDate)) {
                    $remainingDays = max(1, (int) ceil(now()->diffInSeconds($availableDate) / 86400));
                    $dateStr = $availableDate->format('M d, Y');
                    $lastMethodLabel = $lastApproved->delivery_method === 'delivery' ? 'Home Delivery' : 'Pickup';
                    $attemptedMethodLabel = $validated['delivery_method'] === 'delivery' ? 'Home Delivery' : 'Pickup';

                    $msg = "You recently received {$prevQty} {$medicine->unit} of {$medicine->name} via {$lastMethodLabel} (Dosage: {$timesPerDay} times per {$daysCycle} day(s)). Based on your prescribed dosage, your current supply runs until {$dateStr}. Both Pickup and Home Delivery are blocked for this medicine until your dosage supply is finished ({$remainingDays} day(s) remaining).";

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $msg], 422);
                    }
                    return redirect()->back()->withErrors(['medicine_id' => $msg])->withInput();
                }
            }
        }

        $limit = $medicine->request_limit ?? 5;
        if ($validated['quantity'] > $limit) {
            $msg = "You can only request up to {$limit} {$medicine->unit} for this medicine.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['quantity' => $msg])->withInput();
        }

        $qty = (int) $validated['quantity'];

        // Check stock availability
        if ($medicine->stock < $qty) {
            $msg = "Insufficient stock. Only {$medicine->stock} {$medicine->unit} available.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return redirect()->back()->withErrors(['quantity' => $msg])->withInput();
        }

        // Deduct/reserve stock immediately upon request submission
        $medicine->decrement('stock', $qty);

        $medicineRequest = MedicineRequest::create([
            'user_id'              => auth()->id(),
            'medicine_id'          => $medicine->id,
            'patient_type'         => $validated['patient_type'],
            'patient_name'         => $validated['patient_type'] === 'other' ? $validated['patient_name'] : null,
            'patient_age'          => $validated['patient_type'] === 'other' ? (int) $validated['patient_age'] : null,
            'patient_gender'       => $validated['patient_type'] === 'other' ? ($validated['patient_gender'] ?? null) : null,
            'patient_relationship' => $validated['patient_type'] === 'other' ? ($validated['patient_relationship'] ?? null) : null,
            'type'                 => 'Medicine',
            'item_service'         => $medicine->name,
            'quantity'             => $qty,
            'delivery_method'      => $validated['delivery_method'],
            'notes'                => $validated['notes'] ?? null,
            'status'               => 'Pending',
        ]);

        // Notify all active Nurses (and active BHWs if Home Delivery)
        $requesterName = trim((auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'A resident';
        $methodLabel = $validated['delivery_method'] === 'delivery' ? 'Home Delivery' : 'Health Center Pickup';
        $notifyRoles = $validated['delivery_method'] === 'delivery' ? ['nurse', 'bhw'] : ['nurse'];
        $staffRecipients = User::whereIn('role', $notifyRoles)->where('status', 'active')->get();
        foreach ($staffRecipients as $staff) {
            AppNotification::create([
                'user_id' => $staff->id,
                'type'    => 'info',
                'title'   => $validated['delivery_method'] === 'delivery' ? 'New Medicine Delivery Request' : 'New Medicine Request',
                'message' => "{$requesterName} requested {$medicine->name} (x{$qty}) via {$methodLabel}.",
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Medicine request submitted! Stock has been reserved.',
                'data'    => $medicineRequest,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Medicine request submitted! Stock has been reserved.');
    }

    /**
     * Accept a medicine request.
     * Notifies resident to proceed to the barangay health center for consultation first.
     */
    public function accept(Request $request, MedicineRequest $medicineRequest)
    {
        // If current user is BHW, they can only manage delivery requests
        if (auth()->user()->role === 'bhw' && $medicineRequest->delivery_method !== 'delivery') {
            $msg = 'BHW can only manage delivery requests.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        if ($medicineRequest->status !== 'Pending') {
            $msg = 'Only pending requests can be accepted.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $user = User::find($medicineRequest->user_id);
        if (!$user) {
            $msg = 'Cannot accept: the requesting user is no longer registered.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $medicineRequest->update(['status' => 'Accepted']);

        // Record automatically into Consultations & Disease Monitoring so the nurse can conduct the consultation
        $patientName = ($medicineRequest->patient_type === 'other' && !empty($medicineRequest->patient_name))
            ? $medicineRequest->patient_name
            : trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $patientAge = ($medicineRequest->patient_type === 'other' && !empty($medicineRequest->patient_age))
            ? $medicineRequest->patient_age
            : ($user->age ?? null);
        $patientGender = ($medicineRequest->patient_type === 'other' && !empty($medicineRequest->patient_gender))
            ? $medicineRequest->patient_gender
            : ($user->gender ?? null);
        $patientAddress = $user->address ?? 'Barangay Honrado';

        $attendingNurse = auth()->check()
            ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name)
            : 'Barangay Nurse';

        $notesText = !empty($medicineRequest->notes) ? $medicineRequest->notes : 'Medicine Request evaluation';
        $requestIdStr = 'REQ-' . str_pad($medicineRequest->id, 3, '0', STR_PAD_LEFT);

        Consultation::firstOrCreate(
            ['medicine_request_id' => $medicineRequest->id],
            [
                'user_id'           => $medicineRequest->user_id,
                'patient_name'      => $patientName,
                'patient_age'       => $patientAge,
                'patient_gender'    => $patientGender,
                'address'           => $patientAddress,
                'disease_case'      => 'General Consultation',
                'symptoms'          => $notesText,
                'diagnosis'         => "Prescription evaluation: {$medicineRequest->item_service} ({$requestIdStr})",
                'treatment_notes'   => "Requested {$medicineRequest->quantity} unit(s) of {$medicineRequest->item_service}. Pending health center clinical assessment before dispensing.",
                'consultation_date' => now()->toDateString(),
                'attending_nurse'   => $attendingNurse,
                'status'            => $medicineRequest->delivery_method === 'delivery' ? 'Home Delivery' : 'Active',
            ]
        );

        // Notify the resident to come for consultation first
        AppNotification::create([
            'user_id' => $medicineRequest->user_id,
            'type' => 'info',
            'title' => 'Medicine Request Accepted - Consultation Required',
            'message' => "Your request for {$medicineRequest->item_service} has been accepted. Please proceed to the Barangay Health Center for a consultation first before claiming your medicine.",
        ]);

        // If accepted by BHW, notify all active Nurses
        if (auth()->user()->role === 'bhw') {
            $bhwName = 'BHW ' . trim(auth()->user()->first_name . ' ' . auth()->user()->last_name);
            foreach (User::where('role', 'nurse')->where('status', 'active')->get() as $nurse) {
                AppNotification::create([
                    'user_id' => $nurse->id,
                    'type'    => 'info',
                    'title'   => 'Medicine Delivery Accepted by BHW',
                    'message' => "{$bhwName} accepted {$patientName}'s delivery request for {$medicineRequest->item_service} (x{$medicineRequest->quantity}).",
                ]);
            }
        }

        $msg = "Request accepted. Resident notified to visit the health center for consultation.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'data' => $medicineRequest->fresh(['user', 'medicine'])]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Approve a medicine request (typically after consultation).
     * Resident is notified that the medicine is ready for pickup/delivery.
     */
    public function approve(Request $request, MedicineRequest $medicineRequest)
    {
        // If current user is BHW, they can only manage delivery requests
        if (auth()->user()->role === 'bhw' && $medicineRequest->delivery_method !== 'delivery') {
            $msg = 'BHW can only manage delivery requests.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Allow approval from Pending or Accepted status
        if (!in_array($medicineRequest->status, ['Pending', 'Accepted'])) {
            $msg = 'This request has already been processed.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Verify the resident is still registered
        $user = User::find($medicineRequest->user_id);
        if (!$user) {
            $msg = 'Cannot approve: the requesting user is no longer registered.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $qty = $medicineRequest->quantity ?? 1;

        // Mark the request as Approved
        $medicineRequest->update(['status' => 'Approved']);

        // Update consultation status if linked
        if ($consultation = Consultation::where('medicine_request_id', $medicineRequest->id)->first()) {
            $consultation->update([
                'status' => $medicineRequest->delivery_method === 'delivery' ? 'Home Delivery' : 'Recovered',
                'treatment_notes' => trim(($consultation->treatment_notes ?? '') . " | Approved & Dispensed ({$qty} unit(s))."),
            ]);
        }

        // Notify the resident
        AppNotification::create([
            'user_id' => $medicineRequest->user_id,
            'type' => 'success',
            'title' => 'Medicine Request Approved',
            'message' => $medicineRequest->delivery_method === 'pickup'
                ? "Your request for {$medicineRequest->item_service} (x{$qty}) has been approved! Ready for pickup."
                : "Your request for {$medicineRequest->item_service} (x{$qty}) has been approved! Ready for consultation — BHW is coming to deliver.",
        ]);

        $residentName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Resident';
        if (auth()->user()->role === 'bhw') {
            $bhwName = 'BHW ' . trim(auth()->user()->first_name . ' ' . auth()->user()->last_name);
            foreach (User::where('role', 'nurse')->where('status', 'active')->get() as $nurse) {
                AppNotification::create([
                    'user_id' => $nurse->id,
                    'type'    => 'success',
                    'title'   => 'Medicine Delivery Approved by BHW',
                    'message' => "{$bhwName} approved {$residentName}'s delivery request for {$medicineRequest->item_service} (x{$qty}).",
                ]);
            }
        } elseif ($medicineRequest->delivery_method === 'delivery') {
            foreach (User::where('role', 'bhw')->where('status', 'active')->get() as $bhw) {
                AppNotification::create([
                    'user_id' => $bhw->id,
                    'type'    => 'info',
                    'title'   => 'Medicine Approved — Ready for Delivery',
                    'message' => "Nurse approved {$residentName}'s medicine request ({$medicineRequest->item_service} x{$qty}). Ready for home delivery.",
                ]);
            }
        }

        $msg = "Request approved for {$qty} x {$medicineRequest->item_service}.";

        // Send SMS notification after response (idempotent via approved_sms_at)
        if ($medicineRequest->approved_sms_at === null) {
            $claimed = MedicineRequest::where('id', $medicineRequest->id)
                ->whereNull('approved_sms_at')
                ->update(['approved_sms_at' => now()]);

            if ($claimed === 1) {
                $isPickup = $medicineRequest->delivery_method === 'pickup';
                $bhwName = auth()->user()->role === 'bhw'
                    ? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name)
                    : null;

                $smsMessage = $isPickup
                    ? \App\Support\SmsTemplates::pickupReady(
                        $medicineRequest->request_id,
                        (string) $medicineRequest->item_service,
                        (int) $qty
                    )
                    : \App\Support\SmsTemplates::deliveryOnTheWay(
                        $medicineRequest->request_id,
                        $bhwName
                    );

                app(\App\Services\SmsService::class)->sendAfterResponse(
                    (string) ($user->phone_number ?? ''),
                    $smsMessage,
                    $isPickup ? 'medicine_pickup' : 'medicine_delivery',
                    $user,
                    $medicineRequest
                );
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'data' => $medicineRequest->fresh(['user', 'medicine'])]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Reject a medicine request (from Pending or Accepted after consultation) and restore the reserved stock back to inventory.
     */
    public function reject(Request $request, MedicineRequest $medicineRequest)
    {
        // Only Nurse can reject medicine requests (including delivery requests)
        if (auth()->user()->role !== 'nurse') {
            $msg = 'Only nurses can reject medicine requests.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        if (!in_array($medicineRequest->status, ['Pending', 'Accepted'])) {
            $msg = 'This request has already been processed.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $qty = $medicineRequest->quantity ?? 1;

        // Return stock back to inventory if linked to a medicine
        if ($medicineRequest->medicine_id) {
            $medicine = Medicine::find($medicineRequest->medicine_id);
            if ($medicine) {
                $medicine->increment('stock', $qty);
            }
        }

        $previousStatus = $medicineRequest->status;
        $medicineRequest->update(['status' => 'Rejected']);

        $rejectReasonNotice = $previousStatus === 'Accepted'
            ? "Your request for {$medicineRequest->item_service} (x{$qty}) has been evaluated during consultation and was not approved. The requested quantity has been returned to stock."
            : "Your request for {$medicineRequest->item_service} (x{$qty}) has been rejected. The requested quantity has been returned to stock.";

        // Notify the resident
        AppNotification::create([
            'user_id' => $medicineRequest->user_id,
            'type' => 'error',
            'title' => 'Medicine Request Rejected',
            'message' => $rejectReasonNotice,
        ]);

        // If rejected by BHW, notify all active Nurses
        if (auth()->user()->role === 'bhw') {
            $bhwName = 'BHW ' . trim(auth()->user()->first_name . ' ' . auth()->user()->last_name);
            $residentUser = User::find($medicineRequest->user_id);
            $residentName = $residentUser ? trim($residentUser->first_name . ' ' . $residentUser->last_name) : 'Resident';
            foreach (User::where('role', 'nurse')->where('status', 'active')->get() as $nurse) {
                AppNotification::create([
                    'user_id' => $nurse->id,
                    'type'    => 'warning',
                    'title'   => 'Medicine Delivery Rejected by BHW',
                    'message' => "{$bhwName} rejected {$residentName}'s delivery request for {$medicineRequest->item_service} (x{$qty}). Stock restored.",
                ]);
            }
        }

        $msg = "Request rejected. {$qty} item(s) returned back to inventory stock.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'data' => $medicineRequest->fresh(['user', 'medicine'])]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Update only the quantity of a medicine request.
     * Accessible by Nurse and BHW (for delivery requests).
     * Adjusts the reserved medicine stock accordingly.
     */
    public function updateQuantity(Request $request, MedicineRequest $medicineRequest)
    {
        // If current user is BHW, they can only manage delivery requests
        if (auth()->user()->role === 'bhw' && $medicineRequest->delivery_method !== 'delivery') {
            $msg = 'BHW can only manage delivery requests.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Only allow editing quantity for Pending or Accepted requests
        if (!in_array($medicineRequest->status, ['Pending', 'Accepted'])) {
            $msg = 'Only pending or accepted requests can be edited.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'reason'   => ['nullable', 'string', 'max:255'],
        ]);

        $newQty = (int) $validated['quantity'];
        $oldQty = (int) ($medicineRequest->quantity ?? 1);
        $reason = !empty($validated['reason']) ? trim($validated['reason']) : 'Damaged / Broken medicine upon claiming';

        if ($newQty === $oldQty) {
            $msg = 'Quantity is unchanged.';
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'data' => $medicineRequest->fresh(['user', 'medicine'])]);
            }
            return redirect()->back()->with('success', $msg);
        }

        $medicine = $medicineRequest->medicine_id ? Medicine::find($medicineRequest->medicine_id) : null;
        $unitName = $medicine ? $medicine->unit : 'unit(s)';

        if ($medicine) {
            $limit = $medicine->request_limit ?? 5;
            if ($newQty > $limit) {
                $msg = "Quantity exceeds the allowed limit of {$limit} {$medicine->unit}.";
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            $diff = $newQty - $oldQty;

            // Reasons where medicines are reusable / restocked into inventory:
            $isRestockedReason = in_array($reason, [
                'Dosage adjusted during consultation',
                'Dosage adjusted by nurse during consultation',
                'Patient declined / partially returned',
            ]);

            // If increasing quantity (e.g. 5 requested -> nurse adds to 6 or more)
            // Stock is deducted only when adding!
            if ($diff > 0) {
                if ($medicine->stock < $diff) {
                    $msg = "Insufficient stock. Only {$medicine->stock} additional {$medicine->unit} available.";
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }
                    return redirect()->back()->with('error', $msg);
                }
                $medicine->decrement('stock', $diff);

                // Send notification to resident about the added medicine
                AppNotification::create([
                    'user_id' => $medicineRequest->user_id,
                    'type'    => 'info',
                    'title'   => 'Medicine Request Quantity Increased',
                    'message' => "Your request for {$medicineRequest->item_service} has been increased from {$oldQty} to {$newQty} {$unitName}.",
                ]);
            } else {
                // When quantity is reduced (e.g. 5 requested -> edited to 3):
                // 1) Broken / Damaged / Expired items do NOT return to inventory stock.
                // 2) Dosage adjusted / Patient declined items ARE returned back to stock.
                $returnedQty = abs($diff);

                if ($isRestockedReason) {
                    $medicine->increment('stock', $returnedQty);
                }

                MedicineReturn::create([
                    'user_id'             => $medicineRequest->user_id,
                    'medicine_request_id' => $medicineRequest->id,
                    'medicine_id'         => $medicineRequest->medicine_id,
                    'medicine_name'       => $medicineRequest->item_service,
                    'previous_quantity'   => $oldQty,
                    'new_quantity'        => $newQty,
                    'quantity_returned'   => $returnedQty,
                    'reason'              => $reason,
                    'recorded_by'         => auth()->id(),
                ]);

                // Notify resident about the returned/adjusted medicine
                $notificationMsg = $isRestockedReason
                    ? "{$returnedQty} item(s) of {$medicineRequest->item_service} were returned to stock ({$reason}). Your claimable quantity is now {$newQty} {$unitName}."
                    : "{$returnedQty} item(s) of {$medicineRequest->item_service} were marked as damaged/broken/expired ({$reason}). Your claimable quantity is now {$newQty} {$unitName}.";

                AppNotification::create([
                    'user_id' => $medicineRequest->user_id,
                    'type'    => $isRestockedReason ? 'info' : 'warning',
                    'title'   => $isRestockedReason ? 'Medicine Returned to Stock' : 'Medicine Recorded as Returned / Damaged',
                    'message' => $notificationMsg,
                ]);
            }
        } else {
            // No linked medicine entity, but handle return record if reduced
            $diff = $newQty - $oldQty;
            $isRestockedReason = in_array($reason, [
                'Dosage adjusted during consultation',
                'Dosage adjusted by nurse during consultation',
                'Patient declined / partially returned',
            ]);

            if ($diff < 0) {
                $returnedQty = abs($diff);
                MedicineReturn::create([
                    'user_id'             => $medicineRequest->user_id,
                    'medicine_request_id' => $medicineRequest->id,
                    'medicine_id'         => null,
                    'medicine_name'       => $medicineRequest->item_service,
                    'previous_quantity'   => $oldQty,
                    'new_quantity'        => $newQty,
                    'quantity_returned'   => $returnedQty,
                    'reason'              => $reason,
                    'recorded_by'         => auth()->id(),
                ]);

                $notificationMsg = $isRestockedReason
                    ? "{$returnedQty} item(s) of {$medicineRequest->item_service} were returned to stock ({$reason}). Your claimable quantity is now {$newQty}."
                    : "{$returnedQty} item(s) of {$medicineRequest->item_service} were marked as damaged/broken/expired ({$reason}). Your claimable quantity is now {$newQty}.";

                AppNotification::create([
                    'user_id' => $medicineRequest->user_id,
                    'type'    => $isRestockedReason ? 'info' : 'warning',
                    'title'   => $isRestockedReason ? 'Medicine Returned to Stock' : 'Medicine Recorded as Returned / Damaged',
                    'message' => $notificationMsg,
                ]);
            }
        }

        $medicineRequest->update([
            'quantity' => $newQty,
        ]);

        if ($newQty < $oldQty) {
            $diffCount = abs($newQty - $oldQty);
            $msg = $isRestockedReason
                ? "Quantity updated to {$newQty}. {$diffCount} item(s) returned to inventory stock."
                : "Quantity updated to {$newQty}. {$diffCount} broken/damaged item(s) logged to Returned Medicines (not returned to stock).";
        } else {
            $msg = "Quantity successfully updated from {$oldQty} to {$newQty}. Additional stock deducted from inventory.";
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'data' => $medicineRequest->fresh(['user', 'medicine'])]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Mark a medicine request as completed/claimed (records who delivered, who claimed, relationship, proof photo, and receipt).
     */
    public function complete(Request $request, MedicineRequest $medicineRequest)
    {
        // If current user is BHW, they can only complete delivery requests
        if (auth()->user()->role === 'bhw' && $medicineRequest->delivery_method !== 'delivery') {
            $msg = 'BHW can only manage delivery requests.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->back()->with('error', $msg);
        }

        $validated = $request->validate([
            'delivered_by'         => 'required|string|max:255',
            'claimed_by'           => 'required|string|max:255',
            'claimed_relationship' => 'required|string|max:100',
            'proof_photo'          => 'nullable|image|max:4096', // receipt or claiming photo file
            'signature_data'       => 'nullable|string', // Base64 claimant hand signature
            'delivery_signature'   => 'nullable|string', // Base64 BHW delivery hand signature
            'receipt_number'       => 'nullable|string|max:100',
        ]);

        $proofPath = null;
        if (!empty($validated['signature_data']) && str_starts_with($validated['signature_data'], 'data:image')) {
            // Save base64 resident signature as an image file
            $imageParts = explode(';base64,', $validated['signature_data']);
            if (count($imageParts) === 2) {
                $imageDecoded = base64_decode($imageParts[1]);
                $fileName = 'claim_sig_' . uniqid() . '_' . time() . '.png';
                \Illuminate\Support\Facades\Storage::disk('public')->put('delivery_proofs/' . $fileName, $imageDecoded);
                $proofPath = 'delivery_proofs/' . $fileName;
            }
        } elseif ($request->hasFile('proof_photo')) {
            $proofPath = $request->file('proof_photo')->store('delivery_proofs', 'public');
        }

        $deliverySigPath = null;
        if (!empty($validated['delivery_signature']) && str_starts_with($validated['delivery_signature'], 'data:image')) {
            // Save base64 delivery BHW signature as an image file
            $parts = explode(';base64,', $validated['delivery_signature']);
            if (count($parts) === 2) {
                $decoded = base64_decode($parts[1]);
                $fileName = 'deliv_sig_' . uniqid() . '_' . time() . '.png';
                \Illuminate\Support\Facades\Storage::disk('public')->put('delivery_proofs/' . $fileName, $decoded);
                $deliverySigPath = 'delivery_proofs/' . $fileName;
            }
        }

        $receiptNo = !empty($validated['receipt_number'])
            ? $validated['receipt_number']
            : ('RCPT-' . date('Ymd') . '-' . str_pad($medicineRequest->id, 4, '0', STR_PAD_LEFT));

        $medicineRequest->update([
            'status'               => 'Completed',
            'delivered_by'         => $validated['delivered_by'],
            'claimed_by'           => $validated['claimed_by'],
            'claimed_relationship' => $validated['claimed_relationship'],
            'claimed_at'           => now(),
            'proof_photo'          => $proofPath,
            'delivery_signature'   => $deliverySigPath,
            'receipt_number'       => $receiptNo,
        ]);

        // Send confirmation notification to resident
        AppNotification::create([
            'user_id' => $medicineRequest->user_id,
            'type'    => 'success',
            'title'   => 'Medicine Claimed / Delivered Successfully',
            'message' => "Your medicine ({$medicineRequest->item_service} x{$medicineRequest->quantity}) has been successfully received by {$validated['claimed_by']} ({$validated['claimed_relationship']}). Receipt No: {$receiptNo}.",
        ]);

        // If completed by BHW, notify all active Nurses
        if (auth()->user()->role === 'bhw') {
            $bhwName = 'BHW ' . trim(auth()->user()->first_name . ' ' . auth()->user()->last_name);
            foreach (User::where('role', 'nurse')->where('status', 'active')->get() as $nurse) {
                AppNotification::create([
                    'user_id' => $nurse->id,
                    'type'    => 'success',
                    'title'   => 'Medicine Delivery Completed',
                    'message' => "{$bhwName} delivered {$medicineRequest->item_service} (x{$medicineRequest->quantity}) — claimed by {$validated['claimed_by']} ({$validated['claimed_relationship']}). Receipt: {$receiptNo}.",
                ]);
            }
        }

        $msg = "Delivery/Claim recorded successfully! Official Receipt: {$receiptNo}";
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $medicineRequest->fresh(['user', 'medicine']),
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }
}

