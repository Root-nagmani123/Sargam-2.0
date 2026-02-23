<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\EligibilityCriterion;
use App\Models\SalaryGrade;
use App\Models\UnitType;
use App\Models\UnitSubType;
use App\DataTables\EligibilityCriteriaDataTable;
use Illuminate\Http\Request;

class EligibilityCriteriaController extends Controller
{
    public function index(EligibilityCriteriaDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.eligibility_criteria.index');
    }

    public function create()
    {
        $item = null;
        $salaryGrades = SalaryGrade::orderBy('salary_grade')->get()->mapWithKeys(fn ($s) => [$s->pk => $s->display_label_text]);
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        $unitSubTypes = UnitSubType::orderBy('unit_sub_type')->pluck('unit_sub_type', 'pk');
        return view('admin.estate.eligibility_criteria.form', compact('item', 'salaryGrades', 'unitTypes', 'unitSubTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'salary_grade_master_pk' => 'required|exists:salary_grade_master,pk',
            'estate_unit_type_master_pk' => 'required|exists:estate_unit_type_master,pk',
            'estate_unit_sub_type_master_pk' => 'required|exists:estate_unit_sub_type_master,pk',
        ]);
        EligibilityCriterion::create($validated);
        return redirect()->route('admin.estate.eligibility-criteria.index')->with('success', 'Eligibility unit mapping added successfully.');
    }

    public function edit(string $id)
    {
        $item = EligibilityCriterion::findOrFail($id);
        $payScales = SalaryGrade::orderBy('salary_grade')->get()
            ->mapWithKeys(fn ($p) => [$p->pk => $p->display_label_text]);
        $salaryGrades = SalaryGrade::orderBy('salary_grade')->get()->mapWithKeys(fn ($s) => [$s->pk => $s->display_label_text]);
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        $unitSubTypes = UnitSubType::orderBy('unit_sub_type')->pluck('unit_sub_type', 'pk');
        return view('admin.estate.eligibility_criteria.form', compact('item', 'salaryGrades', 'unitTypes', 'unitSubTypes'));
    }

    public function update(Request $request, string $id)
    {
        $item = EligibilityCriterion::findOrFail($id);
        $validated = $request->validate([
            'salary_grade_master_pk' => 'required|exists:salary_grade_master,pk',
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
