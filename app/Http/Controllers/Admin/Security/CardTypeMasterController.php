<?php

namespace App\Http\Controllers\Admin\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CardTypeMasterController extends Controller
{
    public function index()
    {
        $query = DB::table('sec_id_cardno_master')->orderBy('sec_card_name');
        // If status column exists, include it for display/toggle.
        if (Schema::hasColumn('sec_id_cardno_master', 'active_inactive')) {
            $query->select(['pk', 'sec_card_name', 'active_inactive']);
        }
        $cardTypes = $query->paginate(15);

        return view('admin.security.idcard_master.card_type.index', compact('cardTypes'));
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return view('admin.security.idcard_master.card_type._form');
        }

        return redirect()->route('admin.security.idcard_card_type.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sec_card_name' => [
                'required',
                'string',
                'max:255',
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
            return view('admin.security.idcard_master.card_type._form', compact('cardType'));
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
                'max:255',
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

        DB::table('sec_id_cardno_master')->where('pk', $pk)->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'deleted' => true]);
        }

        return redirect()
            ->route('admin.security.idcard_card_type.index')
            ->with('success', 'Card Type deleted successfully.');
    }
}

