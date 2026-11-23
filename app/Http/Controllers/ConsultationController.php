<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\ChildVaccination;
use App\Models\User;
use App\Models\Announcement;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConsultationController extends Controller
{
    /**
     * Display the consultations & disease cases summary page for nurses.
     */
    public function index(Request $request)
    {
        $diseaseFilter = $request->query('disease', 'all');
        $search = $request->query('search', '');

        $query = Consultation::latest('consultation_date');

        if ($diseaseFilter !== 'all' && !empty($diseaseFilter)) {
            $query->where('disease_case', $diseaseFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('disease_case', 'like', "%{$search}%")
                  ->orWhere('diagnosis', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $consultations = $query->paginate(15)->withQueryString();

        // ── Summary statistics per disease/case ──────────────────────────────
        $caseCounts = Consultation::selectRaw('disease_case, COUNT(*) as total')
            ->groupBy('disease_case')
            ->pluck('total', 'disease_case')
            ->toArray();

        $dengueCount  = $caseCounts['Dengue'] ?? 0;
        $hivCount     = $caseCounts['HIV'] ?? 0;
        $tbCount      = $caseCounts['Tuberculosis'] ?? 0;
        $measlesCount = $caseCounts['Measles'] ?? 0;
        $totalCases   = array_sum($caseCounts);

        // Disease Threshold Limit (e.g. 5 cases triggers epidemic warning / automatic announcement)
        $thresholdLimit = 5;
        $dengueAlert = $dengueCount >= $thresholdLimit;
        $hivAlert    = $hivCount >= $thresholdLimit;
        $tbAlert     = $tbCount >= $thresholdLimit;
        $measlesAlert= $measlesCount >= $thresholdLimit;

        // Registered residents for modal dropdown
        $residents = User::where('role', 'resident')->orderBy('first_name')->get();

        return view('dashboards.nurse-consultations', compact(
            'consultations',
            'caseCounts',
            'dengueCount',
            'hivCount',
            'tbCount',
            'measlesCount',
            'totalCases',
            'thresholdLimit',
            'dengueAlert',
            'hivAlert',
            'tbAlert',
            'measlesAlert',
            'diseaseFilter',
            'search',
            'residents'
        ));
    }

    /**
     * Store a new patient consultation record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'           => 'nullable|exists:users,id',
            'patient_name'      => 'required|string|max:255',
            'patient_age'       => 'nullable|integer|min:0|max:130',
            'patient_gender'    => 'nullable|string|in:Male,Female,Other',
            'address'           => 'nullable|string|max:255',
            'disease_case'      => 'required|string|max:100',
            'symptoms'          => 'nullable|string',
            'diagnosis'         => 'nullable|string',
            'treatment_notes'   => 'nullable|string',
            'consultation_date' => 'required|date',
            'status'            => 'required|string|in:Active,Recovered,Referred,Walk-in,Home Delivery',
        ]);

        $attendingNurse = auth()->user() ? (auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Nurse';

        $consultation = Consultation::create(array_merge($validated, [
            'attending_nurse' => $attendingNurse,
        ]));

        // Specifically notify the selected resident if one was chosen
        if (!empty($validated['user_id'])) {
            $resident = User::find($validated['user_id']);
            if ($resident) {
                $patientLabel = $validated['patient_name'] ?: 'Patient';
                $formattedDate = Carbon::parse($validated['consultation_date'])->format('M d, Y');
                $statusText = $validated['status'] ?? 'Active';
                $diseaseText = $validated['disease_case'] ?? 'General Consultation';
                
                $messageParts = [];
                $messageParts[] = "A medical consultation record has been logged for {$patientLabel} on {$formattedDate}.";
                $messageParts[] = "Case: {$diseaseText} (Status: {$statusText}).";
                if (!empty($validated['diagnosis'])) {
                    $messageParts[] = "Diagnosis: {$validated['diagnosis']}.";
                }
                if (!empty($validated['treatment_notes'])) {
                    $messageParts[] = "Instructions/Prescription: {$validated['treatment_notes']}.";
                }

                AppNotification::create([
                    'user_id' => $resident->id,
                    'title'   => "Consultation Record: {$patientLabel} ({$diseaseText})",
                    'message' => implode(' ', $messageParts),
                    'type'    => 'info',
                    'is_read' => false,
                ]);
            }
        }

        // Check if cases of this disease now reach or exceed the threshold limit (5 cases)
        $caseCount = Consultation::where('disease_case', $validated['disease_case'])->count();
        $threshold = 5;

        $autoAnnounced = false;
        if ($caseCount >= $threshold) {
            // Check if an announcement for this threshold was already posted recently
            $recentAnnouncement = Announcement::where(function ($q) use ($validated) {
                    $q->where('title', 'like', "%{$validated['disease_case']} Cases in Barangay%")
                      ->orWhere('title', 'like', "%{$validated['disease_case']} Alert%");
                })
                ->where('created_at', '>=', now()->subDays(3))
                ->first();

            if (!$recentAnnouncement) {
                // Publish community alert announcement automatically to all residents
                Announcement::create([
                    'user_id'     => auth()->id(),
                    'author_name' => $attendingNurse . ' (Health Center)',
                    'author_role' => 'nurse',
                    'title'       => "⚠️ Public Health Advisory: {$validated['disease_case']} Cases in Barangay",
                    'content'     => "Notice to all residents: The Barangay Health Center has logged {$caseCount} cases of {$validated['disease_case']}. Please take necessary preventive measures, eliminate breeding sites, and immediately visit the health center if experiencing symptoms.",
                    'date'        => now()->toDateString(),
                    'status'      => 'active',
                ]);

                // Notify all active resident users
                $residents = User::where('role', 'resident')->where('status', 'active')->get();
                foreach ($residents as $resident) {
                    AppNotification::create([
                        'user_id'    => $resident->id,
                        'title'      => "Health Advisory: {$validated['disease_case']} Alert",
                        'message'    => "The health center has monitored an increase in {$validated['disease_case']} cases ({$caseCount} cases). Please review the official advisory.",
                        'type'       => 'warning',
                        'is_read'    => false,
                    ]);
                }
                $autoAnnounced = true;
            }
        }

        $msg = "Consultation record saved successfully!";
        if ($autoAnnounced) {
            $msg .= " ⚠️ Warning: {$validated['disease_case']} cases reached {$caseCount} (Limit: {$threshold}). An automatic public health announcement has been published to all residents.";
        }

        return redirect()->route('nurse.consultations')->with('success', $msg);
    }

    /**
     * Export consultations & disease monitoring records to Excel.
     */
    public function exportExcel(Request $request)
    {
        $diseaseFilter = $request->query('disease', 'all');
        $search = $request->query('search', '');

        $query = Consultation::latest('consultation_date');

        if ($diseaseFilter !== 'all' && !empty($diseaseFilter)) {
            $query->where('disease_case', $diseaseFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('disease_case', 'like', "%{$search}%")
                  ->orWhere('diagnosis', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $consultations = $query->get();

        $rowsHtml = '';
        foreach ($consultations as $c) {
            $date = $c->consultation_date ? $c->consultation_date->format('Y-m-d') : 'N/A';
            $rowsHtml .= '<tr>
                <td style="font-weight:bold;">' . htmlspecialchars($c->patient_name ?? 'N/A') . '</td>
                <td style="text-align:center;">' . ($c->patient_age ?? 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($c->patient_gender ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($c->address ?? 'N/A') . '</td>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($c->disease_case ?? 'General') . '</td>
                <td>' . htmlspecialchars($c->symptoms ?? 'None') . '</td>
                <td>' . htmlspecialchars($c->diagnosis ?? 'None') . '</td>
                <td>' . htmlspecialchars($c->treatment_notes ?? 'None') . '</td>
                <td style="text-align:center;">' . $date . '</td>
                <td>' . htmlspecialchars($c->attending_nurse ?? 'Health Center Staff') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($c->status ?? 'Completed') . '</td>
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
            <h3 style="color:#0c2d48; margin-bottom:4px;">MEDLINK BARANGAY HEALTH SYSTEM &mdash; Consultations & Disease Monitoring Report</h3>
            <p style="font-size:11px; color:#64748b; margin-top:0; margin-bottom:16px;">
                Filter: ' . htmlspecialchars(ucfirst($diseaseFilter)) . ' | Generated: ' . Carbon::now()->format('F d, Y h:i A') . '
            </p>
            <table border="1">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Address / Purok</th>
                        <th>Disease Case</th>
                        <th>Symptoms</th>
                        <th>Diagnosis</th>
                        <th>Treatment Notes</th>
                        <th>Consultation Date</th>
                        <th>Attending Nurse</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>' . ($rowsHtml ?: '<tr><td colspan="11" style="text-align:center;">No consultation records found.</td></tr>') . '</tbody>
            </table>
        </body>
        </html>';

        $filename = 'Consultations_Report_' . Carbon::now()->format('Ymd_His') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Store or update a consultation record submitted by a BHW for a medicine delivery request.
     */
    public function storeFromBhw(Request $request, \App\Models\MedicineRequest $medicineRequest)
    {
        if ($medicineRequest->delivery_method !== 'delivery') {
            return response()->json([
                'success' => false,
                'message' => 'BHW can only record consultations for Home Delivery requests.',
            ], 403);
        }

        $validated = $request->validate([
            'disease_case'    => 'required|string|max:100',
            'symptoms'        => 'required|string|max:2000',
            'diagnosis'       => 'nullable|string|max:2000',
            'treatment_notes' => 'nullable|string|max:2000',
        ]);

        $resident = User::find($medicineRequest->user_id);
        $patientName = ($medicineRequest->patient_type === 'other' && !empty($medicineRequest->patient_name))
            ? $medicineRequest->patient_name
            : ($resident ? trim(($resident->first_name ?? '') . ' ' . ($resident->last_name ?? '')) : 'Resident');
        $patientAge = ($medicineRequest->patient_type === 'other' && $medicineRequest->patient_age !== null)
            ? (int) $medicineRequest->patient_age
            : ($resident?->age ?? null);
        $patientGender = ($medicineRequest->patient_type === 'other' && !empty($medicineRequest->patient_gender))
            ? $medicineRequest->patient_gender
            : ($resident?->gender ?? null);
        $patientAddress = $resident?->address ?: 'Barangay Honrado';

        $bhwName = auth()->check()
            ? ('BHW ' . trim(auth()->user()->first_name . ' ' . auth()->user()->last_name))
            : 'BHW Staff';

        $reqCode = $medicineRequest->request_id ?: ('REQ-' . str_pad($medicineRequest->id, 3, '0', STR_PAD_LEFT));
        $diagnosisText = !empty(trim($validated['diagnosis'] ?? ''))
            ? trim($validated['diagnosis'])
            : "BHW Home Delivery Consultation ({$medicineRequest->item_service} — {$reqCode})";

        $treatmentText = !empty(trim($validated['treatment_notes'] ?? ''))
            ? trim($validated['treatment_notes'])
            : "Consulted during home delivery of {$medicineRequest->item_service} (x{$medicineRequest->quantity}).";

        $consultation = Consultation::updateOrCreate(
            ['medicine_request_id' => $medicineRequest->id],
            [
                'user_id'           => $medicineRequest->user_id,
                'patient_name'      => $patientName,
                'patient_age'       => $patientAge,
                'patient_gender'    => $patientGender,
                'address'           => $patientAddress,
                'disease_case'      => $validated['disease_case'],
                'symptoms'          => trim($validated['symptoms']),
                'diagnosis'         => $diagnosisText,
                'treatment_notes'   => $treatmentText,
                'consultation_date' => now()->toDateString(),
                'attending_nurse'   => $bhwName,
                'status'            => 'Home Delivery',
            ]
        );

        // Notify all active Nurses about the BHW consultation record
        foreach (User::where('role', 'nurse')->where('status', 'active')->get() as $nurse) {
            AppNotification::create([
                'user_id' => $nurse->id,
                'type'    => 'info',
                'title'   => "New BHW Consultation: {$patientName}",
                'message' => "{$bhwName} recorded a consultation ({$validated['disease_case']}) for {$patientName} ({$reqCode}). Symptoms: " . \Illuminate\Support\Str::limit(trim($validated['symptoms']), 100),
            ]);
        }

        return response()->json([
            'success'      => true,
            'message'      => "Consultation recorded for {$patientName} and sent to Nurse Consultation Records.",
            'consultation' => $consultation,
        ]);
    }

    /**
     * Clear consultation records (either all or filtered by disease case).
     */
    public function clear(Request $request)
    {
        $disease = $request->input('disease', 'all');

        if ($disease !== 'all' && !empty($disease)) {
            $deleted = Consultation::where('disease_case', $disease)->delete();
            return redirect()->route('nurse.consultations', ['disease' => $disease])
                ->with('success', "Cleared {$deleted} {$disease} consultation record(s).");
        }

        $deleted = Consultation::query()->delete();
        return redirect()->route('nurse.consultations')
            ->with('success', "Cleared all {$deleted} consultation record(s).");
    }

    /**
     * Delete a consultation record.
     */
    public function destroy(Consultation $consultation)
    {
        $consultation->delete();
        return redirect()->route('nurse.consultations')->with('success', 'Consultation record deleted.');
    }
}

