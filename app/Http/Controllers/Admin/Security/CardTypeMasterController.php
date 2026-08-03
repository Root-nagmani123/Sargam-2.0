<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class CardTypeMasterController extends Controller
{
    /** Must match DB column `sec_id_cardno_master.sec_card_name` (e.g. varchar(11)). */
    private const SEC_CARD_NAME_MAX_LENGTH = 11;

    public function index(Request $request)
    {
        $hasStatusColumn = Schema::hasColumn('sec_id_cardno_master', 'active_inactive');

        if ($request->ajax()) {
            $query = DB::table('sec_id_cardno_master')->orderBy('sec_card_name');
            $query->select($hasStatusColumn ? ['pk', 'sec_card_name', 'active_inactive'] : ['pk', 'sec_card_name']);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('status', function ($ct) use ($hasStatusColumn) {
                    if (! $hasStatusColumn) {
                        return '<span class="badge bg-secondary">N/A</span>';
                    }
                    $isActive = (int) ($ct->active_inactive ?? 1) === 1;

                    return '<div class="form-check form-switch d-inline-block">'
                        . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                        . ' data-table="sec_id_cardno_master" data-column="active_inactive" data-id="' . $ct->pk . '" data-id_column="pk"'
                        . ($isActive ? ' checked' : '') . '>'
                        . '</div>';
                })
                ->addColumn('actions', function ($ct) use ($hasStatusColumn) {
                    $editUrl = route('admin.security.idcard_card_type.edit', encrypt($ct->pk));
                    $isActive = $hasStatusColumn ? ((int) ($ct->active_inactive ?? 1) === 1) : true;
                    $canDelete = ! $hasStatusColumn || ! $isActive;

                    $html = '<div class="d-flex gap-2">'
                        . '<a href="' . e($editUrl) . '" class="text-success openEditCardType" title="Edit">'
                        . '<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>';

                    if ($canDelete) {
                        $deleteUrl = route('admin.security.idcard_card_type.delete', encrypt($ct->pk));
                        $token = csrf_token();
                        $html .= '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Delete this Card Type?\');">'
                            . '<input type="hidden" name="_token" value="' . e($token) . '">'
                            . '<input type="hidden" name="_method" value="DELETE">'
                            . '<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                            . '<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                            . '</form>';
                    } else {
                        $html .= '<span class="text-muted" style="cursor:not-allowed;" title="Set status to Inactive before delete">'
                            . '<i class="material-icons material-symbols-rounded" style="font-size:22px;opacity:0.4;">delete</i></span>';
                    }

                    return $html . '</div>';
                })
                ->rawColumns(['status', 'actions'])
                ->setRowAttr(['data-pk' => fn ($ct) => $ct->pk])
                ->make(true);
        }

        return view('admin.security.idcard_master.card_type.index');
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return view('admin.security.idcard_master.card_type._form', [
                'secCardNameMaxLength' => self::SEC_CARD_NAME_MAX_LENGTH,
            ]);
        }

        return redirect()->route('admin.security.idcard_card_type.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sec_card_name' => [
                'required',
                'string',
                'max:' . self::SEC_CARD_NAME_MAX_LENGTH,
                Rule::unique('sec_id_cardno_master', 'sec_card_name'),
            ],
        ]);

        $now = now()->format('Y-m-d H:i:s');

        $pk = DB::table('sec_id_cardno_master')->insertGetId([
            'sec_card_name' => $validated['sec_card_name'],
            'created_date'  => $now,
            
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action'  => 'create',
                'data'    => [
                    'pk'           => $pk,
                    'encrypted_pk' => encrypt($pk),
                    'sec_card_name'=> $validated['sec_card_name'],
                ],
            ]);
        }

        return redirect()
            ->route('admin.security.idcard_card_type.index')
            ->with('success', 'Card Type created successfully.');
    }

    public function edit(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $cardType = DB::table('sec_id_cardno_master')->where('pk', $pk)->first();
        if (!$cardType) {
            abort(404);
        }

        if ($request->ajax()) {
            return view('admin.security.idcard_master.card_type._form', [
                'cardType' => $cardType,
                'secCardNameMaxLength' => self::SEC_CARD_NAME_MAX_LENGTH,
            ]);
        }

        return redirect()->route('admin.security.idcard_card_type.index');
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $exists = DB::table('sec_id_cardno_master')->where('pk', $pk)->exists();
        if (!$exists) {
            abort(404);
        }

        $validated = $request->validate([
            'sec_card_name' => [
                'required',
                'string',
                'max:' . self::SEC_CARD_NAME_MAX_LENGTH,
                Rule::unique('sec_id_cardno_master', 'sec_card_name')->ignore($pk, 'pk'),
            ],
        ]);

        DB::table('sec_id_cardno_master')
            ->where('pk', $pk)
            ->update([
                'sec_card_name' => $validated['sec_card_name'],
            ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action'  => 'update',
                'data'    => [
                    'pk'           => $pk,
                    'encrypted_pk' => encrypt($pk),
                    'sec_card_name'=> $validated['sec_card_name'],
                ],
            ]);
        }

        return redirect()
            ->route('admin.security.idcard_card_type.index')
            ->with('success', 'Card Type updated successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        if (! Schema::hasColumn('sec_id_cardno_master', 'active_inactive')) {
            return response()->json([
                'success' => false,
                'message' => 'Status column not available. Please run migrations.',
            ], 400);
        }

        $row = DB::table('sec_id_cardno_master')->where('pk', $pk)->first(['pk', 'active_inactive']);
        if (! $row) {
            abort(404);
        }

        $newStatus = ((int) ($row->active_inactive ?? 1)) === 1 ? 0 : 1;
        DB::table('sec_id_cardno_master')->where('pk', $pk)->update(['active_inactive' => $newStatus]);

        return response()->json([
            'success' => true,
            'data' => [
                'pk' => $pk,
                'active_inactive' => $newStatus,
            ],
        ]);
    }

    public function delete(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $row = DB::table('sec_id_cardno_master')->where('pk', $pk)->first();
        if (! $row) {
            abort(404);
        }

        if (Schema::hasColumn('sec_id_cardno_master', 'active_inactive')) {
            $isActive = (int) ($row->active_inactive ?? 1) === 1;
            if ($isActive) {
                $message = 'Active card types cannot be deleted. Set status to Inactive first.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()
                    ->route('admin.security.idcard_card_type.index')
                    ->with('error', $message);
            }
        }

        DB::table('sec_id_cardno_master')->where('pk', $pk)->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'deleted' => true]);
        }

        return redirect()
            ->route('admin.security.idcard_card_type.index')
            ->with('success', 'Card Type deleted successfully.');
    }
}

