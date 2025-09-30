<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FcRegistrationImport; //  must match namespace & file
use App\Imports\PreviewImport;
use App\Models\FcRegistrationMaster;
use Illuminate\Support\Facades\Session;
use App\Exports\FcRegistrationExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\DataTables\FC\FcRegistrationMasterListDaTable;
use App\Models\FcRegistrationExportMaster as ModelsFcRegistrationExportMaster;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;



class RegistrationImportController extends Controller
{
    public function showForm()
    {
        return view('admin.registration.fcregistration_import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:,xls,csv,xlsx'
        ]);

        $path = $request->file('file')->store('temp');
        $data = Excel::toArray(new PreviewImport, storage_path('app/' . $path));

        Session::put('import_data', $data[0]);

        return view('admin.registration.fcregistration_preview', ['rows' => $data[0]]);
    }

    public function importConfirmed()
    {
        $importData = Session::get('import_data');

        if (!$importData) {
            return redirect()->back()->with('error', 'No data to import.');
        }

        foreach ($importData as $row) {
            FcRegistrationMaster::updateOrCreate(
                ['email' => $row['email']],
                [
                    'contact_no'        => $row['contact_no'] ?? null,
                    'display_name'      => $row['display_name'] ?? null,
                    'schema_id'         => $row['schema_id'] ?? null,
                    'first_name'        => $row['first_name'] ?? null,
                    'middle_name'       => $row['middle_name'] ?? null,
                    'last_name'         => $row['last_name'] ?? null,
                    'rank'              => $row['rank'] ?? null,
                    'exam_year'         => $row['exam_year'] ?? null,
                    'service_master_pk' => $row['service_master_pk'] ?? 0,
                    'web_auth'          => $row['web_auth'] ?? null,
                ]
            );
        }

        Session::forget('import_data');

        return redirect()->route('admin.registration.index')->with('success', 'Data imported successfully.');
    }

    public function fc_masterindex(FcRegistrationMasterListDaTable $dataTable)
    {
        $courses = DB::table('local_form')->where('visible', 1)->where('parent_id', '=', null)->pluck('name', 'id');
        $exemptionCategories = DB::table('fc_exemption_master')->pluck('Exemption_name', 'Pk');
        $applicationTypes = [1 => 'Registration', 2 => 'Exemption'];
        $serviceMasters = DB::table('service_master')->pluck('service_name', 'pk');
        $years = DB::table('fc_registration_master')
            ->select(DB::raw('DISTINCT exam_year'))
            ->orderBy('exam_year', 'desc')
            ->pluck('exam_year', 'exam_year');


        return $dataTable->render('admin.registration.fcregistrationmaster_list', [
            'courses' => $courses,
            'exemptionCategories' => $exemptionCategories,
            'applicationTypes' => $applicationTypes,
            'serviceMasters' => $serviceMasters,
            'years' => $years
        ]);
    }


    // {
    //     $registrations = FcRegistrationMaster::select('pk', 'email', 'contact_no','display_name','schema_id','first_name', 'middle_name', 'last_name', 'rank', 'exam_year','service_master_pk', 'web_auth', 'dob')->get();
    //     return view('admin.registration.fcregistrationmaster_list', compact('registrations'));
    // }
    public function fc_masteredit($id)
    {
        $registration = FcRegistrationMaster::findOrFail($id);
        return view('admin.registration.fcregistrationmaster_edit', compact('registration'));
    }


    public function fc_masterupdate(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email',
            'contact_no' => 'required',
            'first_name' => 'required',
            'dob' => 'nullable|date',
            'display_name' => 'nullable|string|max:255',
            'schema_id' => 'nullable|string|max:255',
            'service_master_pk' => 'nullable|string|max:255',
            'exam_year' => 'nullable|string|max:255',
        ]);

        $record = FcRegistrationMaster::findOrFail($id);
        $record->update($request->only([
            'email',
            'contact_no',
            'first_name',
            'middle_name',
            'last_name',
            'rank',
            'exam_year',
            'web_auth',
            'dob',
            'display_name',
            'schema_id',
            'service_master_pk'
        ]));

        return redirect()->route('admin.registration.index')->with('success', 'Record updated successfully.');
    }


    public function fc_masterdestroy($id)
    {
        FcRegistrationMaster::destroy($id);
        return back()->with('success', 'Record deleted.');
    }

    // export fc master

    // public function export(Request $request)
    // {
    //     $format = $request->get('format');

    //     if ($format === 'xlsx') {
    //         return Excel::download(new FcRegistrationExport(), 'fc-registrations.xlsx');
    //     } elseif ($format === 'csv') {
    //         return Excel::download(new FcRegistrationExport(), 'fc-registrations.csv');
    //     } elseif ($format === 'pdf') {
    //         $registrations = ModelsFcRegistrationExportMaster::all();
    //         $pdf = Pdf::loadView('admin.forms.export.fcregistrationmaster_pdf', compact('registrations'))->setPaper('a4', 'landscape');
    //         return $pdf->download('fc-registrations.pdf');
    //     } else {
    //         return redirect()->back()->with('error', 'Invalid format selected.');
    //     }
    // }

    public function export(Request $request)
    {
        $query = FcRegistrationMaster::query()
            ->leftJoin('service_master as s', 'fc_registration_master.service_master_pk', '=', 's.pk')
            ->leftJoin('fc_exemption_master as e', 'fc_registration_master.fc_exemption_master_pk', '=', 'e.Pk')
            ->leftJoin('cadre_master as c', 'fc_registration_master.cadre_master_pk', '=', 'c.pk')
            ->select(
                'fc_registration_master.formid as course_master_pk',
                'fc_registration_master.application_type',
                'fc_registration_master.fc_exemption_master_pk',
                's.service_short_name',     // optional, if you want short name
                'fc_registration_master.schema_id',
                'fc_registration_master.display_name',
                'fc_registration_master.first_name',
                'fc_registration_master.middle_name',
                'fc_registration_master.last_name',
                'fc_registration_master.email',
                'fc_registration_master.contact_no',
                'fc_registration_master.rank',
                'fc_registration_master.dob',
                'fc_registration_master.web_auth',
                'fc_registration_master.exam_year',
                'e.Exemption_name as exemption_name',
                'c.cadre_name',
                's.group_service_name as group_type',        // <-- alias here
            );

        // Apply filters
        if ($course = $request->course_name) {
            $query->where('fc_registration_master.formid', $course);
        }

        if ($exemption = $request->exemption_category) {
            $query->where('e.Exemption_name', $exemption);
        }

        if ($type = $request->application_type) {
            $query->where('fc_registration_master.application_type', $type);
        }

        if ($service = $request->service_master) {
            $query->where('fc_registration_master.service_master_pk', $service);
        }
        if ($year = $request->year) {
            $query->where('fc_registration_master.exam_year', $year);
        }
        if ($group = $request->group_type) {
            $query->where('s.group_service_name', $group);
        }

        $registrations = $query->get();

        $format = $request->format;

        if ($format === 'xlsx') {
            return Excel::download(new FcRegistrationExport($registrations), 'fc-registrations.xlsx');
        } elseif ($format === 'csv') {
            return Excel::download(new FcRegistrationExport($registrations), 'fc-registrations.csv');
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.forms.export.fcregistrationmaster_pdf', compact('registrations'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('fc-registrations.pdf');
        }

        return redirect()->back()->with('error', 'Invalid format selected.');
    }



    // public function downloadTemplate(): BinaryFileResponse
    // {
    //     $headers = [
    //         'uid', 'email', 'contact_no', 'user_id', 'display_name', 'generated_OT_code', 'rank', 'course_master_pk'
    //     ];

    //     $file = storage_path('app/public/fc_registration_template.xlsx');

    //     $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();
    //     $sheet->fromArray($headers, NULL, 'A1');

    //     $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    //     $writer->save($file);

    //     return response()->download($file);
    // }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'display_name',
            'contact_no',
            'rank',
            'generated_OT_code',
            'service_master_pk',
            'cadre_master_pk'
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, NULL, 'A1');

        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        // Apply border + alignment
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Header row style
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFCC00'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'fc_registration_template.xlsx');
    }



    // public function previewUpload(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,csv,xls|max:2048',
    //     ]);

    //     $rows = Excel::toCollection(new FcRegistrationImport, $request->file('file'))->first();

    //     $previewData = collect($rows)->map(function ($row) {
    //         // Check existence by unique OT code (or enrollment_no if available)
    //         $exists = FcRegistrationMaster::where([
    //             'generated_OT_code'   => $row['generated_ot_code'] ?? null,
    //             'service_master_pk'   => $row['service_master_pk'] ?? null,
    //             'cadre_master_pk'     => $row['cadre_master_pk'] ?? null,
    //         ])->exists();

    //         return [
    //             'display_name'      => $row['display_name'] ?? '',
    //             'contact_no'        => $row['contact_no'] ?? '',
    //             'rank'              => $row['rank'] ?? '',
    //             'generated_OT_code' => $row['generated_ot_code'] ?? '',
    //             'service_master_pk' => $row['service_master_pk'] ?? '',
    //             'cadre_master_pk'   => $row['cadre_master_pk'] ?? '',
    //             'exists'            => $exists ? 'Update' : 'Insert',
    //         ];
    //     });

    //     return view('admin.registration.fclist_preview', compact('previewData'));
    // }

    // public function confirmUpload(Request $request)
    // {
    //     $data = json_decode($request->data, true);

    //     $insertData = [];
    //     foreach ($data as $row) {
    //         $insertData[] = [
    //             'display_name'      => $row['display_name'],
    //             'contact_no'        => $row['contact_no'],
    //             'rank'              => $row['rank'],
    //             'generated_OT_code' => $row['generated_OT_code'],
    //             'service_master_pk' => $row['service_master_pk'],
    //             'cadre_master_pk'   => $row['cadre_master_pk'],
    //             'created_date'      => now(),
    //         ];
    //     }

    //     // Remove duplicates from Excel before upsert
    //     $insertData = collect($insertData)
    //         ->unique(function ($item) {
    //             return $item['generated_OT_code'] . '-' . $item['service_master_pk'] . '-' . $item['cadre_master_pk'];
    //         })
    //         ->values()
    //         ->all();
    //     // Upsert by generated_OT_code (unique identifier)
    //     FcRegistrationMaster::upsert(
    //         $insertData,
    //         ['generated_OT_code', 'service_master_pk', 'cadre_master_pk'], // Unique combination
    //         ['display_name', 'contact_no', 'rank', 'dob', 'exam_year', 'web_auth', 'created_date'] // Columns to update
    //     );


    //     return redirect()->route('admin.registration.index')
    //         ->with('success', 'Bulk upload completed successfully!');
    // }




    //working code

    //     public function previewUpload(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,csv,xls|max:2048',
    //     ]);

    //     $rows = Excel::toCollection(new FcRegistrationImport, $request->file('file'))->first();

    //     $previewData = collect($rows)->map(function ($row) {
    //         $generated_ot_code = trim($row['generated_ot_code'] ?? '');
    //         $service_master_pk = trim($row['service_master_pk'] ?? '');
    //         $cadre_master_pk   = trim($row['cadre_master_pk'] ?? '');
    //         $rank              = trim($row['rank'] ?? '');
    //         $display_name      = trim($row['display_name'] ?? '');
    //         $contact_no        = trim($row['contact_no'] ?? '');

    //         $exists = FcRegistrationMaster::where([
    //             ['generated_OT_code', $generated_ot_code],
    //             ['service_master_pk', $service_master_pk],
    //             ['cadre_master_pk', $cadre_master_pk],
    //         ])->exists();

    //         return [
    //             'display_name'      => $display_name,
    //             'contact_no'        => $contact_no,
    //             'rank'              => $rank,
    //             'generated_OT_code' => $generated_ot_code,
    //             'service_master_pk' => $service_master_pk,
    //             'cadre_master_pk'   => $cadre_master_pk,
    //             'exists'            => $exists ? 'Update' : 'Insert',
    //         ];
    //     });

    //     return view('admin.registration.fclist_preview', compact('previewData'));
    // }

    // public function confirmUpload(Request $request)
    // {
    //     $data = json_decode($request->data, true);

    //     $insertData = [];
    //     $updateData = [];

    //     foreach ($data as $row) {
    //         $generated_ot_code = trim($row['generated_OT_code'] ?? '');
    //         $service_master_pk = trim($row['service_master_pk'] ?? '');
    //         $cadre_master_pk   = trim($row['cadre_master_pk'] ?? '');

    //         if (!$generated_ot_code) continue; // skip invalid

    //         $existing = FcRegistrationMaster::where([
    //             ['generated_OT_code', $generated_ot_code],
    //             ['service_master_pk', $service_master_pk],
    //             ['cadre_master_pk', $cadre_master_pk],
    //         ])->first();

    //         if ($existing) {
    //             // Prepare update
    //             $updateData[] = [
    //                 'id'                => $existing->pk,
    //                 'display_name'      => trim($row['display_name'] ?? $existing->display_name),
    //                 'contact_no'        => trim($row['contact_no'] ?? $existing->contact_no),
    //                 'rank'              => trim($row['rank'] ?? $existing->rank),
    //             ];
    //         } else {
    //             // Prepare insert
    //             $insertData[] = [
    //                 'display_name'      => trim($row['display_name'] ?? ''),
    //                 'contact_no'        => trim($row['contact_no'] ?? ''),
    //                 'rank'              => trim($row['rank'] ?? ''),
    //                 'generated_OT_code' => $generated_ot_code,
    //                 'service_master_pk' => $service_master_pk,
    //                 'cadre_master_pk'   => $cadre_master_pk,
    //                 'created_date'      => now(),
    //             ];
    //         }
    //     }

    //     // Insert new rows
    //     if (!empty($insertData)) {
    //         FcRegistrationMaster::insert($insertData);
    //     }

    //     // Update existing rows
    //     foreach ($updateData as $upd) {
    //         FcRegistrationMaster::where('pk', $upd['id'])->update([
    //             'display_name' => $upd['display_name'],
    //             'contact_no'   => $upd['contact_no'],
    //             'rank'         => $upd['rank'],
    //         ]);
    //     }

    //     return redirect()->route('admin.registration.index')
    //         ->with('success', 'Bulk upload completed successfully!');
    // }

    public function previewUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:2048',
        ]);

        $rows = Excel::toCollection(new FcRegistrationImport, $request->file('file'))->first();

        $previewData = collect($rows)->map(function ($row) {
            $display_name = trim($row['display_name'] ?? '');
            $rank         = trim($row['rank'] ?? '');
            $contact_no   = trim($row['contact_no'] ?? '');
            $generated_ot_code = trim($row['generated_ot_code'] ?? '');
            $service_master_pk = trim($row['service_master_pk'] ?? '');
            $cadre_master_pk   = trim($row['cadre_master_pk'] ?? '');

            $exists = FcRegistrationMaster::where([
                ['display_name', $display_name],
                ['rank', $rank],
                ['contact_no', $contact_no],
            ])->exists();

            return [
                'display_name'      => $display_name,
                'contact_no'        => $contact_no,
                'rank'              => $rank,
                'generated_OT_code' => $generated_ot_code,
                'service_master_pk' => $service_master_pk,
                'cadre_master_pk'   => $cadre_master_pk,
                'exists'            => $exists ? 'Update' : 'Not Found',
            ];
        });

        return view('admin.registration.fclist_preview', compact('previewData'));
    }


    public function confirmUpload(Request $request)
    {
        $data = json_decode($request->data, true);

        foreach ($data as $row) {
            $display_name = trim($row['display_name'] ?? '');
            $rank         = trim($row['rank'] ?? '');
            $contact_no   = trim($row['contact_no'] ?? '');
            $generated_ot_code = trim($row['generated_OT_code'] ?? '');
            $service_master_pk = trim($row['service_master_pk'] ?? '');
            $cadre_master_pk   = trim($row['cadre_master_pk'] ?? '');

            if (!$display_name || !$contact_no) continue; // skip invalid

            // Only update if record exists based on display_name + rank + contact_no
            $existing = FcRegistrationMaster::where([
                ['display_name', $display_name],
                ['rank', $rank],
                ['contact_no', $contact_no],
            ])->first();

            if ($existing) {
                FcRegistrationMaster::where('pk', $existing->pk)->update([
                    'generated_OT_code' => $generated_ot_code ?: $existing->generated_OT_code,
                    'service_master_pk' => $service_master_pk ?: $existing->service_master_pk,
                    'cadre_master_pk'   => $cadre_master_pk ?: $existing->cadre_master_pk,
                ]);
            }
            // Skip if not exists
        }

        return redirect()->route('admin.registration.index')
            ->with('success', 'Bulk update completed successfully!');
    }
}
