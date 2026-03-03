<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\EligibilityCriterion;
use App\Models\PayScale;
use App\Models\UnitType;
use App\Models\UnitSubType;
use Illuminate\Http\Request;

class EligibilityCriteriaController extends Controller
{
    public function index()
    {
        $items = EligibilityCriterion::with(['payScale', 'unitType', 'unitSubType'])
            ->orderBy('pk')
            ->paginate(request('per_page', 10));
        return view('admin.estate.eligibility_criteria.index', compact('items'));
    }

    public function create()
    {
        $item = null;
        $payScales = PayScale::orderBy('pay_scale_range')->get()->mapWithKeys(fn ($p) => [$p->pk => $p->display_label_text]);
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        $unitSubTypes = UnitSubType::orderBy('unit_sub_type')->pluck('unit_sub_type', 'pk');
        return view('admin.estate.eligibility_criteria.form', compact('item', 'payScales', 'unitTypes', 'unitSubTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pay_scale_master_pk' => 'required|exists:estate_pay_scale_master,pk',
            'estate_unit_type_master_pk' => 'required|exists:estate_unit_type_master,pk',
            'estate_unit_sub_type_master_pk' => 'required|exists:estate_unit_sub_type_master,pk',
        ]);
        EligibilityCriterion::create($validated);
        return redirect()->route('admin.estate.eligibility-criteria.index')->with('success', 'Eligibility unit mapping added successfully.');
    }

    public function edit(string $id)
    {
        $item = EligibilityCriterion::findOrFail($id);
        $payScales = PayScale::orderBy('pay_scale_range')->get()->mapWithKeys(fn ($p) => [$p->pk => $p->display_label_text]);
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        $unitSubTypes = UnitSubType::orderBy('unit_sub_type')->pluck('unit_sub_type', 'pk');
        return view('admin.estate.eligibility_criteria.form', compact('item', 'payScales', 'unitTypes', 'unitSubTypes'));
    }

    public function update(Request $request, string $id)
    {
        $item = EligibilityCriterion::findOrFail($id);
        $validated = $request->validate([
            'pay_scale_master_pk' => 'required|exists:estate_pay_scale_master,pk',
            'estate_unit_type_master_pk' => 'required|exists:estate_unit_type_master,pk',
            'estate_unit_sub_type_master_pk' => 'required|exists:estate_unit_sub_type_master,pk',
        ]);
        $item->update($validated);
        return redirect()->route('admin.estate.eligibility-criteria.index')->with('success', 'Eligibility unit mapping updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        EligibilityCriterion::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Eligibility mapping deleted successfully.']);
        }
        return redirect()->route('admin.estate.eligibility-criteria.index')->with('success', 'Eligibility mapping deleted successfully.');
    }
}
