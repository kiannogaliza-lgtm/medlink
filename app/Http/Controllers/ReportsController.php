<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Medicine;
use App\Models\MedicineRequest;
use App\Models\Consultation;
use App\Models\HomeServiceRequest;
use App\Models\ChildVaccination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Show the Reports & Analytics page.
     */
    public function index()
    {
        // ── Stat cards ──────────────────────────────────────────────
        $totalPatients     = User::registered()->where('role', 'resident')->count();
        $totalRequests     = MedicineRequest::count();
        $homeServiceCount  = HomeServiceRequest::count();
        $immunizationCount = ChildVaccination::count() + \App\Models\Schedule::where('type', 'Immunization')->count();

        // Month-over-month change helpers (current vs previous month)
        $now       = Carbon::now();
        $thisMonth = $now->month;
        $thisYear  = $now->year;
        $lastMonth = $now->copy()->subMonth();

        $patientsThisMonth = User::registered()->where('role', 'resident')
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->count();
        $patientsLastMonth = User::registered()->where('role', 'resident')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();
        $patientChange = $patientsLastMonth > 0
            ? round(($patientsThisMonth - $patientsLastMonth) / $patientsLastMonth * 100)
            : ($patientsThisMonth > 0 ? 100 : 0);

        $requestsThisMonth = MedicineRequest::whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->count();
        $requestsLastMonth = MedicineRequest::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();
        $requestChange = $requestsLastMonth > 0
            ? round(($requestsThisMonth - $requestsLastMonth) / $requestsLastMonth * 100)
            : ($requestsThisMonth > 0 ? 100 : 0);

        $hsThisMonth = HomeServiceRequest::whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->count();
        $hsLastMonth = HomeServiceRequest::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();
        $hsChange = $hsLastMonth > 0
            ? round(($hsThisMonth - $hsLastMonth) / $hsLastMonth * 100)
            : ($hsThisMonth > 0 ? 100 : 0);

        $immThisMonth = ChildVaccination::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            + \App\Models\Schedule::where('type', 'Immunization')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count();
        $immLastMonth = ChildVaccination::whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count()
            + \App\Models\Schedule::where('type', 'Immunization')->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count();
        $immChange = $immLastMonth > 0
            ? round(($immThisMonth - $immLastMonth) / $immLastMonth * 100)
            : ($immThisMonth > 0 ? 100 : 0);

        // ── Monthly trends (last 5 months) ──────────────────────────
        $monthLabels   = [];
        $patientTrend  = [];
        $requestTrend  = [];

        for ($i = 4; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthLabels[] = $month->format('M');

            $patientTrend[] = User::registered()->where('role', 'resident')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $requestTrend[] = MedicineRequest::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
        }

        // ── Service comparison (last 5 months, stacked bar) ─────────
        $homeServiceTrend   = [];
        $immunizationTrend  = [];
        $ultrasoundTrend    = [];

        for ($i = 4; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            $homeServiceTrend[] = HomeServiceRequest::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $immunizationTrend[] = ChildVaccination::whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count()
                + \App\Models\Schedule::where('type', 'Immunization')->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();

            $ultrasoundTrend[] = \App\Models\Schedule::where('type', 'Ultrasound')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
        }

        // Pre-aggregate medicine request counts per user (eliminates N+1 query)
        $userMedRequestCounts = MedicineRequest::where('type', 'Medicine')
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        // ── Resident Medicine Requests (for Resident Requests Report Table) ──────
        $residentRequests = MedicineRequest::with(['user', 'medicine'])
            ->where('type', 'Medicine')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) use ($userMedRequestCounts) {
                $residentName = $req->user ? ($req->user->first_name . ' ' . $req->user->last_name) : 'Unknown Resident';
                $medName = $req->medicine->name ?? ($req->item_service ?? 'Medicine');
                
                // Requester age
                $requesterAge = $req->user?->age !== null ? "{$req->user->age} yrs" : null;
                $requesterCategory = $req->user?->age_category ?? null;

                // Actual patient details
                $isForOther = ($req->patient_type === 'other' && !empty($req->patient_name));
                $patientName = $isForOther ? $req->patient_name : $residentName;
                $patientAge = $isForOther ? ($req->patient_age !== null ? "{$req->patient_age} yrs" : null) : $requesterAge;
                $patientRel = $isForOther ? ($req->patient_relationship ?? 'Dependent') : 'Self';

                // Total requests made by this resident
                $userTotalRequests = $req->user_id
                    ? (int) ($userMedRequestCounts[$req->user_id] ?? 1)
                    : 1;

                return (object)[
                    'id'                 => $req->id,
                    'resident_name'      => $residentName,
                    'requester_age'      => $requesterAge,
                    'requester_category' => $requesterCategory,
                    'is_for_other'       => $isForOther,
                    'patient_name'       => $patientName,
                    'patient_age'        => $patientAge,
                    'patient_rel'        => $patientRel,
                    'medicine_name'      => $medName,
                    'quantity'           => $req->quantity ?? 1,
                    'delivery_method'    => ucfirst($req->delivery_method ?? 'pickup'),
                    'status'             => $req->status ?? 'Pending',
                    'date'               => $req->created_at ? $req->created_at->format('M d, Y h:i A') : 'N/A',
                    'total_requests'     => $userTotalRequests,
                    'is_maintenance'     => (bool) ($req->user?->is_maintenance),
                ];
            });

        // ── Child / Dependent Vaccination Records for Reports ─────────────────
        $childVaccinations = ChildVaccination::with('parent')
            ->orderBy('date_administered', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboards.nurse-reports', compact(
            'totalPatients',
            'totalRequests',
            'homeServiceCount',
            'immunizationCount',
            'patientChange',
            'requestChange',
            'hsChange',
            'immChange',
            'monthLabels',
            'patientTrend',
            'requestTrend',
            'homeServiceTrend',
            'immunizationTrend',
            'ultrasoundTrend',
            'residentRequests',
            'childVaccinations'
        ));
    }

    /**
     * Export Excel reports for Consultations, Medicine Inventory, Resident Records, or Healthcare Services.
     */
    public function exportExcel(Request $request)
    {
        $type = $request->query('type', 'all');

        switch ($type) {
            case 'consultations':
                return $this->exportConsultationsExcel();
            case 'inventory':
                return $this->exportInventoryExcel();
            case 'residents':
                return $this->exportResidentsExcel();
            case 'healthcare-services':
                return $this->exportHealthcareServicesExcel();
            case 'resident-requests':
                return $this->exportResidentRequestsExcel();
            case 'all':
            default:
                return $this->exportMasterExcel();
        }
    }

    private function wrapExcelHtml(string $title, string $tablesHtml): \Illuminate\Http\Response
    {
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="content-type" content="text/plain; charset=UTF-8"/>
            <style>
                body { font-family: Calibri, Arial, sans-serif; color: #1e293b; }
                table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
                th { background-color: #0c2d48; color: #ffffff; font-weight: bold; padding: 10px 14px; border: 1px solid #cbd5e1; text-align: left; font-size: 13px; }
                td { padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 12px; }
                tr:nth-child(even) td { background-color: #f8fafc; }
                .report-title { font-size: 18px; font-weight: bold; color: #0c2d48; margin-bottom: 4px; }
                .report-meta { font-size: 11px; color: #64748b; margin-bottom: 16px; }
                .section-header { font-size: 15px; font-weight: bold; color: #1a5a89; margin-top: 24px; margin-bottom: 8px; }
            </style>
        </head>
        <body>
            <div class="report-title">MEDLINK BARANGAY HEALTH SYSTEM &mdash; ' . htmlspecialchars($title) . '</div>
            <div class="report-meta">Generated On: ' . Carbon::now()->format('F d, Y h:i A') . ' | Barangay Honrado Health Center</div>
            ' . $tablesHtml . '
        </body>
        </html>';

        $filename = 'MedLink_' . str_replace(' ', '_', $title) . '_' . Carbon::now()->format('Ymd_His') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    private function exportConsultationsExcel()
    {
        $consultations = Consultation::orderBy('consultation_date', 'desc')->get();

        $rowsHtml = '';
        foreach ($consultations as $c) {
            $date = $c->consultation_date ? $c->consultation_date->format('Y-m-d') : 'N/A';
            $rowsHtml .= '<tr>
                <td>' . htmlspecialchars($c->patient_name ?? 'N/A') . '</td>
                <td style="text-align:center;">' . ($c->patient_age ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($c->patient_gender ?? 'N/A') . '</td>
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

        $tablesHtml = '<table border="1">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Address / Purok</th>
                    <th>Disease Case / Category</th>
                    <th>Symptoms</th>
                    <th>Diagnosis</th>
                    <th>Treatment Notes</th>
                    <th>Consultation Date</th>
                    <th>Attending Nurse</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>' . ($rowsHtml ?: '<tr><td colspan="11" style="text-align:center;">No consultation records found.</td></tr>') . '</tbody>
        </table>';

        return $this->wrapExcelHtml('Consultations_Report', $tablesHtml);
    }

    private function exportInventoryExcel()
    {
        $medicines = Medicine::orderBy('name', 'asc')->get();

        $rowsHtml = '';
        foreach ($medicines as $m) {
            $release = $m->release_date ? Carbon::parse($m->release_date)->format('Y-m-d') : 'N/A';
            $expiry = $m->expiration_date ? Carbon::parse($m->expiration_date)->format('Y-m-d') : 'N/A';

            $stockStatus = 'In Stock';
            if ($m->stock <= 0) {
                $stockStatus = 'Out of Stock';
            } elseif ($m->reorder_level && $m->stock <= $m->reorder_level) {
                $stockStatus = 'Low Stock';
            }

            $rowsHtml .= '<tr>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($m->name) . '</td>
                <td>' . htmlspecialchars($m->category ?? 'General') . '</td>
                <td style="text-align:center; font-weight:bold;">' . $m->stock . '</td>
                <td style="text-align:center;">' . htmlspecialchars($m->unit ?? 'pcs') . '</td>
                <td>' . htmlspecialchars($m->dosage ?? 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($m->age_limit_label ?? 'All ages') . '</td>
                <td style="text-align:center;">' . ($m->reorder_level ?? 0) . '</td>
                <td style="text-align:center;">' . ($m->request_limit ?? 'N/A') . '</td>
                <td style="text-align:center;">' . $release . '</td>
                <td style="text-align:center;">' . $expiry . '</td>
                <td style="text-align:center; font-weight:bold;">' . $stockStatus . '</td>
            </tr>';
        }

        $tablesHtml = '<table border="1">
            <thead>
                <tr>
                    <th>Medicine Name</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Unit</th>
                    <th>Dosage</th>
                    <th>Age Limit</th>
                    <th>Reorder Level</th>
                    <th>Max Request Limit</th>
                    <th>Release Date</th>
                    <th>Expiration Date</th>
                    <th>Stock Status</th>
                </tr>
            </thead>
            <tbody>' . ($rowsHtml ?: '<tr><td colspan="11" style="text-align:center;">No medicine records found.</td></tr>') . '</tbody>
        </table>';

        return $this->wrapExcelHtml('Medicine_Inventory_Report', $tablesHtml);
    }

    private function exportResidentsExcel()
    {
        $residents = User::registered()->where('role', 'resident')->orderBy('last_name', 'asc')->get();

        $rowsHtml = '';
        foreach ($residents as $r) {
            $dob = $r->dob ? Carbon::parse($r->dob)->format('Y-m-d') : 'N/A';
            $maintenance = !empty($r->is_maintenance) ? 'Yes' : 'No';

            $rowsHtml .= '<tr>
                <td style="font-weight:bold;">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</td>
                <td style="text-align:center;">' . ($r->age ?? 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($r->age_category ?? 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($r->gender ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($r->phone_number ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($r->email ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($r->address ?? 'Barangay Honrado') . '</td>
                <td style="text-align:center;">' . $dob . '</td>
                <td style="text-align:center;">' . htmlspecialchars($r->blood_type ?? 'N/A') . '</td>
                <td style="text-align:center; font-weight:bold;">' . $maintenance . '</td>
                <td>' . htmlspecialchars($r->chronic_conditions ?? 'None') . '</td>
                <td>' . htmlspecialchars($r->allergies ?? 'None') . '</td>
                <td>' . htmlspecialchars($r->emergency_contact_name ?? 'N/A') . ' (' . htmlspecialchars($r->emergency_contact_phone ?? 'N/A') . ')</td>
                <td style="text-align:center;">' . htmlspecialchars(ucfirst($r->status ?? 'active')) . '</td>
            </tr>';
        }

        $tablesHtml = '<table border="1">
            <thead>
                <tr>
                    <th>Resident Full Name</th>
                    <th>Age</th>
                    <th>Age Category</th>
                    <th>Gender</th>
                    <th>Phone Number</th>
                    <th>Email</th>
                    <th>Address / Purok</th>
                    <th>Date of Birth</th>
                    <th>Blood Type</th>
                    <th>Maintenance Patient</th>
                    <th>Chronic Conditions</th>
                    <th>Allergies</th>
                    <th>Emergency Contact</th>
                    <th>Account Status</th>
                </tr>
            </thead>
            <tbody>' . ($rowsHtml ?: '<tr><td colspan="14" style="text-align:center;">No resident records found.</td></tr>') . '</tbody>
        </table>';

        return $this->wrapExcelHtml('Resident_Records_Report', $tablesHtml);
    }

    private function exportHealthcareServicesExcel()
    {
        $homeServices = HomeServiceRequest::with(['user', 'assignedBhw'])
            ->orderBy('created_at', 'desc')
            ->get();

        $rowsHtml = '';
        foreach ($homeServices as $hs) {
            $prefDate = $hs->preferred_date ? $hs->preferred_date->format('Y-m-d') : 'N/A';
            $bhwName = $hs->assignedBhw ? ($hs->assignedBhw->first_name . ' ' . $hs->assignedBhw->last_name) : ($hs->assigned_bhw_name ?? 'Unassigned');

            $rowsHtml .= '<tr>
                <td style="text-align:center; font-weight:bold;">' . ($hs->formatted_id ?? ('HS-' . $hs->id)) . '</td>
                <td style="font-weight:bold;">' . htmlspecialchars($hs->patient_name) . '</td>
                <td style="color:#0369a1; font-weight:bold;">' . htmlspecialchars($hs->service_type) . '</td>
                <td>' . htmlspecialchars($hs->address) . '</td>
                <td>' . htmlspecialchars($hs->contact_number ?? 'N/A') . '</td>
                <td style="text-align:center;">' . $prefDate . '</td>
                <td style="text-align:center;">' . htmlspecialchars($hs->preferred_time ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($hs->reason_symptoms ?? 'None') . '</td>
                <td>' . htmlspecialchars($bhwName) . '</td>
                <td style="text-align:center; font-weight:bold;">' . htmlspecialchars($hs->status ?? 'Pending') . '</td>
                <td>' . htmlspecialchars($hs->nurse_notes ?? 'None') . '</td>
            </tr>';
        }

        $tablesHtml = '<table border="1">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Patient Name</th>
                    <th>Service Type</th>
                    <th>Address / Purok</th>
                    <th>Contact Number</th>
                    <th>Preferred Date</th>
                    <th>Preferred Time</th>
                    <th>Reason / Symptoms</th>
                    <th>Assigned BHW</th>
                    <th>Status</th>
                    <th>Nurse Notes</th>
                </tr>
            </thead>
            <tbody>' . ($rowsHtml ?: '<tr><td colspan="11" style="text-align:center;">No home service records found.</td></tr>') . '</tbody>
        </table>';

        return $this->wrapExcelHtml('Healthcare_Home_Services_Report', $tablesHtml);
    }

    private function exportResidentRequestsExcel()
    {
        $residentRequests = MedicineRequest::with(['user', 'medicine'])
            ->where('type', 'Medicine')
            ->orderBy('created_at', 'desc')
            ->get();

        $rowsHtml = '';
        foreach ($residentRequests as $req) {
            $residentName = $req->user ? ($req->user->first_name . ' ' . $req->user->last_name) : 'Unknown Resident';
            $medName = $req->medicine->name ?? ($req->item_service ?? 'Medicine');
            $patientName = ($req->patient_type === 'other' && !empty($req->patient_name)) ? $req->patient_name : $residentName;
            $maintenance = !empty($req->user?->is_maintenance) ? 'Yes' : 'No';

            $rowsHtml .= '<tr>
                <td style="font-weight:bold;">' . htmlspecialchars($residentName) . '</td>
                <td>' . htmlspecialchars($patientName) . '</td>
                <td style="text-align:center;">' . $maintenance . '</td>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($medName) . '</td>
                <td style="text-align:center; font-weight:bold;">' . ($req->quantity ?? 1) . '</td>
                <td style="text-align:center;">' . htmlspecialchars(ucfirst($req->delivery_method ?? 'pickup')) . '</td>
                <td style="text-align:center;">' . htmlspecialchars($req->status ?? 'Pending') . '</td>
                <td style="text-align:center;">' . ($req->created_at ? $req->created_at->format('Y-m-d H:i') : 'N/A') . '</td>
            </tr>';
        }

        $tablesHtml = '<table border="1">
            <thead>
                <tr>
                    <th>Requester (Resident)</th>
                    <th>Actual Patient</th>
                    <th>Maintenance Patient</th>
                    <th>Medicine Requested</th>
                    <th>Quantity</th>
                    <th>Delivery Method</th>
                    <th>Status</th>
                    <th>Date Requested</th>
                </tr>
            </thead>
            <tbody>' . ($rowsHtml ?: '<tr><td colspan="8" style="text-align:center;">No resident request records found.</td></tr>') . '</tbody>
        </table>';

        return $this->wrapExcelHtml('Resident_Medicine_Requests_Report', $tablesHtml);
    }

    private function exportMasterExcel()
    {
        // 1. Consultations
        $consultations = Consultation::orderBy('consultation_date', 'desc')->get();
        $consHtml = '';
        foreach ($consultations as $c) {
            $consHtml .= '<tr>
                <td>' . htmlspecialchars($c->patient_name ?? 'N/A') . '</td>
                <td style="text-align:center;">' . ($c->patient_age ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($c->patient_gender ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($c->address ?? 'N/A') . '</td>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($c->disease_case ?? 'General') . '</td>
                <td>' . htmlspecialchars($c->diagnosis ?? 'None') . '</td>
                <td>' . htmlspecialchars($c->treatment_notes ?? 'None') . '</td>
                <td style="text-align:center;">' . ($c->consultation_date ? $c->consultation_date->format('Y-m-d') : 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($c->status ?? 'Completed') . '</td>
            </tr>';
        }

        // 2. Medicine Inventory
        $medicines = Medicine::orderBy('name', 'asc')->get();
        $medsHtml = '';
        foreach ($medicines as $m) {
            $stockStatus = ($m->stock <= 0) ? 'Out of Stock' : (($m->reorder_level && $m->stock <= $m->reorder_level) ? 'Low Stock' : 'In Stock');
            $medsHtml .= '<tr>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($m->name) . '</td>
                <td>' . htmlspecialchars($m->category ?? 'General') . '</td>
                <td style="text-align:center; font-weight:bold;">' . $m->stock . ' ' . htmlspecialchars($m->unit ?? 'pcs') . '</td>
                <td>' . htmlspecialchars($m->dosage ?? 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($m->age_limit_label ?? 'All ages') . '</td>
                <td style="text-align:center;">' . ($m->expiration_date ? Carbon::parse($m->expiration_date)->format('Y-m-d') : 'N/A') . '</td>
                <td style="text-align:center; font-weight:bold;">' . $stockStatus . '</td>
            </tr>';
        }

        // 3. Residents
        $residents = User::registered()->where('role', 'resident')->orderBy('last_name', 'asc')->get();
        $resHtml = '';
        foreach ($residents as $r) {
            $resHtml .= '<tr>
                <td style="font-weight:bold;">' . htmlspecialchars($r->first_name . ' ' . $r->last_name) . '</td>
                <td style="text-align:center;">' . ($r->age ?? 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($r->gender ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($r->phone_number ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($r->address ?? 'Barangay Honrado') . '</td>
                <td style="text-align:center;">' . (!empty($r->is_maintenance) ? 'Yes' : 'No') . '</td>
                <td>' . htmlspecialchars($r->chronic_conditions ?? 'None') . '</td>
                <td style="text-align:center;">' . htmlspecialchars(ucfirst($r->status ?? 'active')) . '</td>
            </tr>';
        }

        // 4. Healthcare / Home Services
        $homeServices = HomeServiceRequest::with(['user', 'assignedBhw'])->orderBy('created_at', 'desc')->get();
        $hsHtml = '';
        foreach ($homeServices as $hs) {
            $bhwName = $hs->assignedBhw ? ($hs->assignedBhw->first_name . ' ' . $hs->assignedBhw->last_name) : ($hs->assigned_bhw_name ?? 'Unassigned');
            $hsHtml .= '<tr>
                <td style="text-align:center; font-weight:bold;">' . ($hs->formatted_id ?? ('HS-' . $hs->id)) . '</td>
                <td style="font-weight:bold;">' . htmlspecialchars($hs->patient_name) . '</td>
                <td style="color:#0369a1; font-weight:bold;">' . htmlspecialchars($hs->service_type) . '</td>
                <td>' . htmlspecialchars($hs->address) . '</td>
                <td>' . htmlspecialchars($hs->contact_number ?? 'N/A') . '</td>
                <td style="text-align:center;">' . ($hs->preferred_date ? $hs->preferred_date->format('Y-m-d') : 'N/A') . '</td>
                <td>' . htmlspecialchars($bhwName) . '</td>
                <td style="text-align:center; font-weight:bold;">' . htmlspecialchars($hs->status ?? 'Pending') . '</td>
            </tr>';
        }

        $allHtml = '
            <div class="section-header">1. Consultations & Communicable Disease Records</div>
            <table border="1">
                <thead><tr><th>Patient Name</th><th>Age</th><th>Gender</th><th>Address</th><th>Disease Case</th><th>Diagnosis</th><th>Treatment</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>' . ($consHtml ?: '<tr><td colspan="9" style="text-align:center;">No records</td></tr>') . '</tbody>
            </table>

            <div class="section-header">2. Medicine Inventory & Stock Levels</div>
            <table border="1">
                <thead><tr><th>Medicine Name</th><th>Category</th><th>Stock</th><th>Dosage</th><th>Age Limit</th><th>Expiration Date</th><th>Status</th></tr></thead>
                <tbody>' . ($medsHtml ?: '<tr><td colspan="7" style="text-align:center;">No records</td></tr>') . '</tbody>
            </table>

            <div class="section-header">3. Resident & Patient Master Records</div>
            <table border="1">
                <thead><tr><th>Full Name</th><th>Age</th><th>Gender</th><th>Phone</th><th>Address / Purok</th><th>Maintenance</th><th>Conditions</th><th>Status</th></tr></thead>
                <tbody>' . ($resHtml ?: '<tr><td colspan="8" style="text-align:center;">No records</td></tr>') . '</tbody>
            </table>

            <div class="section-header">4. Healthcare Services (Home Service Requests & Delivery)</div>
            <table border="1">
                <thead><tr><th>Request ID</th><th>Patient Name</th><th>Service Type</th><th>Address</th><th>Contact</th><th>Preferred Date</th><th>Assigned BHW</th><th>Status</th></tr></thead>
                <tbody>' . ($hsHtml ?: '<tr><td colspan="8" style="text-align:center;">No records</td></tr>') . '</tbody>
            </table>
        ';

        return $this->wrapExcelHtml('Complete_Health_Center_Report', $allHtml);
    }
}
