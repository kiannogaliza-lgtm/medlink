<?php

namespace App\Http\Controllers;

use App\Models\ChildVaccination;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChildVaccinationController extends Controller
{
    /**
     * Display child vaccinations management for nurses & BHW.
     */
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $doseFilter = $request->query('dose', 'all');

        $query = ChildVaccination::with('parent')->latest('date_administered');

        if ($doseFilter !== 'all' && !empty($doseFilter)) {
            $query->where('dose_number', $doseFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('child_name', 'like', "%{$search}%")
                  ->orWhere('vaccine_name', 'like', "%{$search}%")
                  ->orWhereHas('parent', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $vaccinations = $query->paginate(15)->withQueryString();

        // Counts
        $totalVaccinations = ChildVaccination::count();
        $firstDoseCount    = ChildVaccination::where('dose_number', '1st Dose')->count();
        $secondDoseCount   = ChildVaccination::where('dose_number', '2nd Dose')->count();
        $succeedingCount   = ChildVaccination::whereIn('dose_number', ['3rd Dose', 'Booster 1', 'Booster 2'])->count();

        $parents = User::where('role', 'resident')->orderBy('first_name')->get();

        return view('dashboards.nurse-vaccinations', compact(
            'vaccinations',
            'totalVaccinations',
            'firstDoseCount',
            'secondDoseCount',
            'succeedingCount',
            'doseFilter',
            'search',
            'parents'
        ));
    }

    /**
     * Store a child vaccination dose.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'           => 'nullable|exists:users,id',
            'child_name'        => 'required|string|max:255',
            'child_dob'         => 'nullable|date',
            'child_gender'      => 'nullable|string|in:Male,Female',
            'vaccine_name'      => 'required|string|max:255',
            'dose_number'       => 'required|string|in:1st Dose,2nd Dose,3rd Dose,Booster 1,Booster 2',
            'date_administered' => 'required|date',
            'status'            => 'required|string|in:Completed,Scheduled,Missed',
            'notes'             => 'nullable|string',
        ]);

        $administeredBy = auth()->user() ? (auth()->user()->first_name . ' ' . auth()->user()->last_name) : 'Barangay Health Center';

        ChildVaccination::create(array_merge($validated, [
            'administered_by' => $administeredBy,
        ]));

        return redirect()->route('nurse.vaccinations')->with('success', "Vaccination dose recorded successfully for {$validated['child_name']}!");
    }

    /**
     * Export child vaccinations & immunization records to Excel.
     */
    public function exportExcel(Request $request)
    {
        $search = $request->query('search', '');
        $doseFilter = $request->query('dose', 'all');

        $query = ChildVaccination::with('parent')->latest('date_administered');

        if ($doseFilter !== 'all' && !empty($doseFilter)) {
            $query->where('dose_number', $doseFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('child_name', 'like', "%{$search}%")
                  ->orWhere('vaccine_name', 'like', "%{$search}%")
                  ->orWhereHas('parent', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $vaccinations = $query->get();

        $rowsHtml = '';
        foreach ($vaccinations as $v) {
            $parentName = $v->parent ? ($v->parent->first_name . ' ' . $v->parent->last_name) : 'Walk-in / Guardian';
            $date = $v->date_administered ? $v->date_administered->format('Y-m-d') : 'Pending';

            $rowsHtml .= '<tr>
                <td style="font-weight:bold;">' . htmlspecialchars($v->child_name) . '</td>
                <td style="text-align:center;">' . htmlspecialchars($v->age_category ?? 'N/A') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($v->child_gender ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($parentName) . '</td>
                <td>' . htmlspecialchars($v->parent->phone_number ?? 'N/A') . '</td>
                <td style="font-weight:bold; color:#0369a1;">' . htmlspecialchars($v->vaccine_name) . '</td>
                <td style="text-align:center;">' . htmlspecialchars($v->dose_number) . '</td>
                <td style="text-align:center;">' . $date . '</td>
                <td>' . htmlspecialchars($v->administered_by ?? 'Health Center Staff') . '</td>
                <td style="text-align:center;">' . htmlspecialchars($v->status) . '</td>
                <td>' . htmlspecialchars($v->notes ?? 'None') . '</td>
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
            <h3 style="color:#0c2d48; margin-bottom:4px;">MEDLINK BARANGAY HEALTH SYSTEM &mdash; Child Immunization & Vaccination Masterlist</h3>
            <p style="font-size:11px; color:#64748b; margin-top:0; margin-bottom:16px;">
                Dose Filter: ' . htmlspecialchars($doseFilter) . ' | Generated: ' . Carbon::now()->format('F d, Y h:i A') . '
            </p>
            <table border="1">
                <thead>
                    <tr>
                        <th>Child Patient Name</th>
                        <th>Age / Category</th>
                        <th>Gender</th>
                        <th>Parent / Guardian</th>
                        <th>Parent Phone</th>
                        <th>Vaccine Administered</th>
                        <th>Dose Number</th>
                        <th>Date Administered</th>
                        <th>Health Worker / Provider</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>' . ($rowsHtml ?: '<tr><td colspan="11" style="text-align:center;">No child vaccination records found.</td></tr>') . '</tbody>
            </table>
        </body>
        </html>';

        $filename = 'Child_Vaccinations_Masterlist_' . Carbon::now()->format('Ymd_His') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Delete a child vaccination record.
     */
    public function destroy(ChildVaccination $childVaccination)
    {
        $childVaccination->delete();
        return redirect()->route('nurse.vaccinations')->with('success', 'Vaccination record deleted.');
    }
}

