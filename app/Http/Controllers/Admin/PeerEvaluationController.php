<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PeerGroupMembersImport;
use App\Exports\PeerEvaluationExport;
use PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\PeerGroup;

class PeerEvaluationController extends Controller
{

    // i code 

    // public function index()
    // {
    //     $groups = DB::table('peer_groups')->get();
    //     $columns = DB::table('peer_columns')->where('is_visible', 1)->get();
    //     // $members = DB::table('peer_members')
    //     //     ->select('peer_members.*', 'peer_groups.group_name')
    //     //     ->leftJoin('peer_groups', 'peer_members.group_id', '=', 'peer_groups.id')
    //     //     ->get();
    //     // Fetch registered members (from fc_registration_master)
    //     $members = DB::table('fc_registration_master')
    //         ->select('pk', 'first_name') // Assuming columns: firstname, group_name
    //         ->orderBy('first_name')
    //         ->get();

    //     return view('admin.forms.peer_evaluation.index', compact('groups', 'columns', 'members'));
    // }

    // public function storeGroup(Request $request)
    // {
    //     DB::table('peer_groups')->insert(['group_name' => $request->group_name]);
    //     return back()->with('success', 'Group added successfully!');
    // }

    // public function storeColumn(Request $request)
    // {
    //     DB::table('peer_columns')->insert(['column_name' => $request->column_name]);
    //     return back()->with('success', 'Column added successfully!');
    // }

    // public function toggleColumn($id)
    // {
    //     $column = DB::table('peer_columns')->where('id', $id)->first();
    //     DB::table('peer_columns')->where('id', $id)->update(['is_visible' => !$column->is_visible]);
    //     return back()->with('success', 'Column visibility updated!');
    // }


    // 2 code 
    // public function index()
    // {
    //     $groups = DB::table('peer_groups')->get();
    //     $columns = DB::table('peer_columns')->get();

    //     return view('admin.forms.peer_evaluation.admin', compact('groups', 'columns'));
    // }

    // public function storeGroup(Request $request)
    // {
    //     DB::table('peer_groups')->insert(['group_name' => $request->group_name]);
    //     return back()->with('success', 'Group added successfully!');
    // }

    // public function storeColumn(Request $request)
    // {
    //     DB::table('peer_columns')->insert([
    //         'column_name' => $request->column_name,
    //         'is_visible' => 1
    //     ]);
    //     return back()->with('success', 'Column added successfully!');
    // }

    // public function toggleColumn($id)
    // {
    //     $column = DB::table('peer_columns')->where('id', $id)->first();
    //     DB::table('peer_columns')->where('id', $id)->update(['is_visible' => !$column->is_visible]);
    //     return response()->json(['new_state' => !$column->is_visible]);
    // }

    // public function deleteGroup($id)
    // {
    //     DB::table('peer_groups')->where('id', $id)->delete();
    //     return back()->with('success', 'Group deleted successfully!');
    // }

    // public function deleteColumn($id)
    // {
    //     DB::table('peer_columns')->where('id', $id)->delete();
    //     return back()->with('success', 'Column deleted successfully!');
    // }


    // public function user_index()
    // {
    //     $columns = DB::table('peer_columns')->where('is_visible', 1)->get();
    //     $members = DB::table('fc_registration_master')
    //         ->select('pk', 'first_name')
    //         ->orderBy('first_name')
    //         ->limit(15)
    //         ->whereNotNull('first_name')
    //         ->get();

    //     return view('admin.forms.peer_evaluation.index', compact('columns', 'members'));
    // }

    public function index()
    {
        // $groups = DB::table('peer_groups')->where('is_active', 1)->get();
        $groups = DB::table('peer_groups as g')
            ->leftJoin('peer_group_members as m', 'g.id', '=', 'm.group_id')
            ->select(
                'g.*',
                DB::raw('COUNT(m.member_pk) as member_count')
            )
            ->where('g.is_active', 1)
            ->groupBy('g.id')
            ->get();

        $columns = DB::table('peer_columns')->get();

        // Get selected group ID from request or use first group
        $selectedGroupId = request('group_id', $groups->first()->id ?? null);

        // Get members for selected group
        $members = [];
        if ($selectedGroupId) {
            $members = DB::table('peer_group_members')
                ->where('group_id', $selectedGroupId)
                ->select('member_pk', 'user_name as first_name', 'user_id')
                ->get();
        }

        return view('admin.forms.peer_evaluation.admin', compact('groups', 'columns', 'members', 'selectedGroupId'));
    }

    public function getGroupMembers($groupId)
    {
        $members = DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->select('member_pk', 'user_name as first_name', 'user_id')
            ->get();

        return response()->json($members);
    }

    // public function store(Request $request)
    // {
    //     // dd($request->all());
    //     $scores = $request->input('scores', []);
    //     $groupId = $request->input('group_id');

    //     foreach ($scores as $memberPk => $columns) {
    //         foreach ($columns as $columnId => $score) {
    //             DB::table('peer_scores')->updateOrInsert(
    //                 [
    //                     'member_id' => $memberPk,
    //                     'column_id' => $columnId,
    //                     'group_id' => $groupId,
    //                     'evaluator_id' => auth()->id()
    //                 ],
    //                 [
    //                     'score' => $score,
    //                     'created_at' => now(),
    //                     'updated_at' => now()
    //                 ]
    //             );
    //         }
    //     }

    //     return back()->with('success', 'Evaluation submitted successfully!');
    // }

    public function store(Request $request)
    {
        $scores = $request->input('scores', []);
        $groupId = $request->input('group_id');

        foreach ($scores as $memberId => $columns) {
            foreach ($columns as $columnId => $score) {
                DB::table('peer_scores')->updateOrInsert(
                    [
                        'member_id' => $memberId,      // MUST match peer_group_members.id
                        'column_id' => $columnId,
                        'group_id' => $groupId,
                        'evaluator_id' => auth()->id()
                    ],
                    [
                        'score' => $score,
                        'updated_at' => now()
                    ]
                );
            }
        }

        return back()->with('success', 'Evaluation submitted successfully!');
    }


    // public function store(Request $request)
    // {
    //     $scores = $request->input('scores', []);

    //     foreach ($scores as $memberPk => $columns) {
    //         foreach ($columns as $columnId => $score) {
    //             DB::table('peer_scores')->updateOrInsert(
    //                 [
    //                     'member_pk' => $memberPk,
    //                     'column_id' => $columnId,
    //                     'evaluator_id' => auth()->id() // Track who submitted the evaluation
    //                 ],
    //                 [
    //                     'score' => $score,
    //                     'created_at' => now(),
    //                     'updated_at' => now()
    //                 ]
    //             );
    //         }
    //     }

    //     return back()->with('success', 'Evaluation submitted successfully!');
    // }

    // 3 code

    public function user_index(Request $request)
    {

        $groups = DB::table('peer_groups')
            ->select(
                'peer_groups.*',
                DB::raw('COUNT(peer_group_members.id) as member_count')
            )
            ->leftJoin('peer_group_members', 'peer_groups.id', '=', 'peer_group_members.group_id')
            ->groupBy('peer_groups.id')
            ->get();

        $columns = DB::table('peer_columns')->where('is_visible', 1)->get();
        $allUsers = DB::table('fc_registration_master')
            ->select('pk', 'first_name')
            ->orderBy('first_name')
            ->get();

        // Get selected group ID from request or use first group
        $selectedGroupId = $request->query('group_id', $groups->first()->id ?? null);

        // Get members for selected group
        $members = [];
        if ($selectedGroupId) {
            $members = DB::table('peer_group_members')
                ->where('group_id', $selectedGroupId)
                ->select('id', 'member_pk', 'user_name as first_name', 'user_id', 'ot_code')
                ->get();
        }


        return view('admin.forms.peer_evaluation.index', compact('groups', 'columns', 'allUsers', 'members', 'selectedGroupId'));
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string|max:255'
        ]);

        DB::table('peer_groups')->insert([
            'group_name' => $request->group_name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Group added successfully!');
    }

    public function storeColumn(Request $request)
    {
        $request->validate([
            'column_name' => 'required|string|max:255'
        ]);

        DB::table('peer_columns')->insert([
            'column_name' => $request->column_name,
            'is_visible' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Column added successfully!');
    }

    public function toggleColumn($id)
    {
        $column = DB::table('peer_columns')->where('id', $id)->first();
        DB::table('peer_columns')->where('id', $id)->update(['is_visible' => !$column->is_visible]);
        return response()->json(['new_state' => !$column->is_visible]);
    }

    public function toggleGroup($id)
    {
        $group = DB::table('peer_groups')->where('id', $id)->first();
        DB::table('peer_groups')->where('id', $id)->update(['is_active' => !$group->is_active]);
        return response()->json(['new_state' => !$group->is_active]);
    }

    public function deleteGroup($id)
    {
        // First delete group members
        DB::table('peer_group_members')->where('group_id', $id)->delete();
        // Then delete the group
        DB::table('peer_groups')->where('id', $id)->delete();

        return back()->with('success', 'Group deleted successfully!');
    }

    public function deleteColumn($id)
    {
        DB::table('peer_columns')->where('id', $id)->delete();
        return back()->with('success', 'Column deleted successfully!');
    }

    public function showGroupMembers($groupId)
    {
        $group = DB::table('peer_groups')->where('id', $groupId)->first();
        $members = DB::table('peer_group_members')
            ->join('fc_registration_master', 'peer_group_members.member_pk', '=', 'fc_registration_master.pk')
            ->where('peer_group_members.group_id', $groupId)
            ->select('fc_registration_master.pk', 'fc_registration_master.first_name')
            ->get();

        return view('admin.forms.peer_evaluation.group_members', compact('group', 'members'));
    }

    public function addMembersToGroup(Request $request, $groupId)
    {
        $request->validate([
            'member_pks' => 'required|array',
            'member_pks.*' => 'required|integer'
        ]);

        foreach ($request->member_pks as $memberPk) {
            // Check if member already exists in this group
            $exists = DB::table('peer_group_members')
                ->where('group_id', $groupId)
                ->where('member_pk', $memberPk)
                ->exists();

            if (!$exists) {
                DB::table('peer_group_members')->insert([
                    'group_id' => $groupId,
                    'member_pk' => $memberPk,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return back()->with('success', 'Members added to group successfully!');
    }

    public function removeMemberFromGroup($groupId, $memberPk)
    {
        DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->where('member_pk', $memberPk)
            ->delete();

        return back()->with('success', 'Member removed from group successfully!');
    }


    //working with select and add to group 

    // public function importMembersView($groupId)
    // {
    //     $group = DB::table('peer_groups')->where('id', $groupId)->first();
    //     $availableUsers = DB::table('fc_registration_master')
    //         ->whereNotIn('pk', function ($query) use ($groupId) {
    //             $query->select('member_pk')
    //                 ->from('peer_group_members')
    //                 ->where('group_id', $groupId);
    //         })
    //         ->select('pk', 'first_name')
    //         ->orderBy('first_name')
    //         ->get();

    //     return view('admin.forms.peer_evaluation.import_members', compact('group', 'availableUsers'));
    // }

    public function importMembersView($groupId)
    {
        $group = DB::table('peer_groups')->where('id', $groupId)->first();
        return view('admin.forms.peer_evaluation.import_members', compact('group'));
    }

    public function importExcel(Request $request, $groupId)
    {

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new PeerGroupMembersImport($groupId), $request->file('excel_file'));

            return back()->with('success', 'Excel file imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function PeerDownloadTemplate(): StreamedResponse
    {
        // Define your headers for peer group members template
        $headers = [
            // 'group_id',
            'user_id',
            'user_name',
            'ot_code',
            'course_name',
            'event_name'
        ];

        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        $sheet->fromArray($headers, NULL, 'A1');

        // Get column and row range
        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        // Add borders + alignment
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

        // Header style (bold + background color)
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '99CCFF'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer
        $writer = new Xlsx($spreadsheet);

        // Stream the download
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'peer_group_members_template.xlsx');
    }



    // Show user all their assigned groups

    // query method in PeerGroup model used instead
    // public function user_groups()
    // {
    //     $userId = auth()->id();

    //     // Get all active groups
    //     $groups = DB::table('peer_groups')
    //         ->where('is_form_active', 1)
    //         ->select('id', 'group_name')
    //         ->get();

    //     // Get groups where user already belongs
    //     $userGroups = DB::table('peer_group_members')
    //         ->where('user_id', $userId)
    //         ->pluck('group_id')
    //         ->toArray();

    //     return view('admin.forms.peer_evaluation.user_groups', compact('groups', 'userGroups'));
    // }


    // Elequent method in PeerGroup model used instead

    public function user_groups()
    {
        $userId = auth()->id();

        $groups = DB::table('peer_groups as g')
            ->leftJoin('peer_group_members as m', 'g.id', '=', 'm.group_id')
            ->where('g.is_form_active', 1)
            ->select(
                'g.id',
                'g.group_name',
                DB::raw('MAX(m.course_name) as course_name'),
                DB::raw('MAX(m.event_name) as event_name'),
                DB::raw('GROUP_CONCAT(m.ot_code SEPARATOR ", ") as ot_codes') // OT code may differ per user
            )
            ->groupBy('g.id', 'g.group_name')
            ->get();

        $userGroups = DB::table('peer_group_members')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        return view('admin.forms.peer_evaluation.user_groups', compact('groups', 'userGroups'));
    }

    // Show evaluation form for a specific group
    public function user_evaluation($groupId)
    {
        $userId = auth()->id();

        // Check if user belongs to this group
        $group = DB::table('peer_groups')
            ->where('id', $groupId)
            ->where('is_form_active', 1)
            ->first();

        if (!$group || !DB::table('peer_group_members')->where('group_id', $groupId)->where('user_id', $userId)->exists()) {
            abort(403, 'You are not authorized for this group.');
        }

        $columns = DB::table('peer_columns')->where('is_visible', 1)->get();
        $members = DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->select('member_pk', 'user_name as first_name', 'user_id')
            ->get();

        return view('admin.forms.peer_evaluation.user_evaluation', compact('group', 'columns', 'members'));
    }

    public function viewSubmissions($groupId)
    {
        // Get group info
        $groups = DB::table('peer_groups')
            ->select('id', 'group_name', 'is_active')
            ->get();

        if ($groups->isEmpty()) {
            return redirect()->back()->with('error', 'Group not found.');
        }

        // Get members of this group
        $members = DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->select('id', 'member_pk', 'user_name as first_name', 'user_id', 'ot_code')
            ->get();

        // Get all columns
        $columns = DB::table('peer_columns')->get();

        // Get all scores for this group
        $scores = DB::table('peer_scores')
            ->where('group_id', $groupId)
            ->get()
            ->keyBy(function ($item) {
                return $item->member_id . '-' . $item->column_id;
            }); // key by member-column for easy lookup

        // dd($scores);

        // Pass $selectedGroupId to keep Blade consistent
        $selectedGroupId = $groupId;

        return view('admin.forms.peer_evaluation.view_submissions', compact(
            'groups',
            'members',
            'columns',
            'scores',
            'selectedGroupId'
        ));
    }


    public function exportSubmissions(Request $request, $groupId)
    {
        $format = $request->input('format');

        $members = DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->select('id', 'member_pk', 'user_name as first_name', 'user_id', 'ot_code')
            ->get();

        $columns = DB::table('peer_columns')->get();

        $scores = DB::table('peer_scores')
            ->where('group_id', $groupId)
            ->get()
            ->keyBy(function ($item) {
                return $item->member_id . '-' . $item->column_id;
            });

        $group = DB::table('peer_groups')->where('id', $groupId)->first();
        $groupName = $group->group_name ?? 'Group';

        if ($format === 'pdf') {
            $pdf = PDF::loadView('admin.forms.peer_evaluation.export_pdf', [
                'members' => $members,
                'columns' => $columns,
                'scores' => $scores,
                'groupName' => $groupName
            ]);
            return $pdf->download($groupName . '_submissions.pdf');
        } else {
            return Excel::download(new PeerEvaluationExport($members, $columns, $scores, $groupName), $groupName . '_submissions.' . $format);
        }
    }

    // Toggle form active/inactive
    public function toggleForm($id)
    {
        $group = PeerGroup::findOrFail($id);
        $group->is_form_active = request('is_form_active'); // 1 or 0
        $group->save();

        return response()->json(['status' => 'success']);
    }
}
