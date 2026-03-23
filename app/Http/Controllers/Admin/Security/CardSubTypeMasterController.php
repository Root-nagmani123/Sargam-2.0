<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CardSubTypeMasterController extends Controller
{
    public function index()
    {
        $subTypes = DB::table('sec_id_cardno_config_map as m')
            ->join('sec_id_cardno_master as t', 't.pk', '=', 'm.sec_id_cardno_master')
            ->select('m.*', 't.sec_card_name')
            ->orderBy('t.sec_card_name')
            ->orderBy('m.config_name')
            ->paginate(20);

        return view('admin.security.idcard_master.sub_type.index', compact('subTypes'));
    }

    public function create(Request $request)
    {
        $ctQuery = DB::table('sec_id_cardno_master')->orderBy('sec_card_name');
        if (Schema::hasColumn('sec_id_cardno_master', 'active_inactive')) {
            $ctQuery->where('active_inactive', 1);
        }
        $cardTypes = $ctQuery->pluck('sec_card_name', 'pk');

        if ($request->ajax()) {
            return view('admin.security.idcard_master.sub_type._form', compact('cardTypes'));
        }

        return redirect()->route('admin.security.idcard_sub_type.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sec_id_cardno_master' => ['required', 'integer', 'exists:sec_id_cardno_master,pk'],
            'card_name'            => ['required', 'string', Rule::in(['p', 'c'])],
            'config_name'          => [
                'required',
                'string',
                'max:255',
                Rule::unique('sec_id_cardno_config_map', 'config_name')
                    ->where(function ($q) use ($request) {
                        return $q->where('sec_id_cardno_master', $request->input('sec_id_cardno_master'))
                                 ->where('card_name', $request->input('card_name'));
                    }),
            ],
        ]);

        $now = now()->format('Y-m-d H:i:s');

        $pk = DB::table('sec_id_cardno_config_map')->insertGetId([
            'sec_id_cardno_master' => $validated['sec_id_cardno_master'],
            'card_name'            => $validated['card_name'],
            'config_name'          => $validated['config_name'],
           
        ]);

        if ($request->ajax()) {
            $cardTypeName = DB::table('sec_id_cardno_master')
                ->where('pk', $validated['sec_id_cardno_master'])
                ->value('sec_card_name');

            return response()->json([
                'success' => true,
                'action'  => 'create',
                'data'    => [
                    'pk'              => $pk,
                    'encrypted_pk'    => encrypt($pk),
                    'sec_card_name'   => $cardTypeName,
                    'card_name'       => $validated['card_name'],
                    'config_name'     => $validated['config_name'],
                ],
            ]);
        }

        return redirect()
            ->route('admin.security.idcard_sub_type.index')
            ->with('success', 'Sub Type created successfully.');
    }

    public function edit(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $subType = DB::table('sec_id_cardno_config_map')->where('pk', $pk)->first();
        if (!$subType) {
            abort(404);
        }

        $ctQuery = DB::table('sec_id_cardno_master')->orderBy('sec_card_name');
        if (Schema::hasColumn('sec_id_cardno_master', 'active_inactive')) {
            $ctQuery->where('active_inactive', 1);
        }
        $cardTypes = $ctQuery->pluck('sec_card_name', 'pk');

        if ($request->ajax()) {
            return view('admin.security.idcard_master.sub_type._form', compact('subType', 'cardTypes'));
        }

        return redirect()->route('admin.security.idcard_sub_type.index');
    }

    public function update(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $exists = DB::table('sec_id_cardno_config_map')->where('pk', $pk)->exists();
        if (!$exists) {
            abort(404);
        }

        $validated = $request->validate([
            'sec_id_cardno_master' => ['required', 'integer', 'exists:sec_id_cardno_master,pk'],
            'card_name'            => ['required', 'string', Rule::in(['p', 'c'])],
            'config_name'          => [
                'required',
                'string',
                'max:255',
                Rule::unique('sec_id_cardno_config_map', 'config_name')
                    ->where(function ($q) use ($request) {
                        return $q->where('sec_id_cardno_master', $request->input('sec_id_cardno_master'))
                                 ->where('card_name', $request->input('card_name'));
                    })
                    ->ignore($pk, 'pk'),
            ],
        ]);

        DB::table('sec_id_cardno_config_map')
            ->where('pk', $pk)
            ->update([
                'sec_id_cardno_master' => $validated['sec_id_cardno_master'],
                'card_name'            => $validated['card_name'],
                'config_name'          => $validated['config_name'],
            ]);

        if ($request->ajax()) {
            $cardTypeName = DB::table('sec_id_cardno_master')
                ->where('pk', $validated['sec_id_cardno_master'])
                ->value('sec_card_name');

            return response()->json([
                'success' => true,
                'action'  => 'update',
                'data'    => [
                    'pk'              => $pk,
                    'encrypted_pk'    => encrypt($pk),
                    'sec_card_name'   => $cardTypeName,
                    'card_name'       => $validated['card_name'],
                    'config_name'     => $validated['config_name'],
                ],
            ]);
        }

        return redirect()
            ->route('admin.security.idcard_sub_type.index')
            ->with('success', 'Sub Type updated successfully.');
    }

    public function delete(Request $request, $id)
    {
        try {
            $pk = decrypt($id);
        } catch (\Exception $e) {
            abort(404);
        }

        $row = DB::table('sec_id_cardno_config_map')->where('pk', $pk)->first(['pk', 'active_inactive']);
        if (! $row) {
            abort(404);
        }

        // Restrict deletion of active records.
        if ((int) ($row->active_inactive ?? 1) === 1) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active sub type cannot be deleted. Please set it inactive first.',
                ], 422);
            }

            return redirect()
                ->route('admin.security.idcard_sub_type.index')
                ->with('error', 'Active sub type cannot be deleted. Please set it inactive first.');
        }

        DB::table('sec_id_cardno_config_map')->where('pk', $pk)->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'deleted' => true]);
        }

        return redirect()
            ->route('admin.security.idcard_sub_type.index')
            ->with('success', 'Sub Type deleted successfully.');
    }
}

