<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\EligibilityCriterion;
use App\Models\SalaryGrade;
use App\Models\UnitType;
use App\Models\UnitSubType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayScaleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.estate.define_pay_scale.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     * The three related labels are resolved for the visible page only.
     */
    protected function datatable()
    {
        $query = EligibilityCriterion::with(['salaryGrade', 'unitType', 'unitSubType'])
            ->orderBy('pk');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('salary_grade', fn ($row) => e($row->salaryGrade ? $row->salaryGrade->display_label_text : '-'))
            ->addColumn('unit_type', fn ($row) => e($row->unitType ? $row->unitType->name : '-'))
            ->addColumn('unit_sub_type', fn ($row) => e($row->unitSubType ? $row->unitSubType->name : '-'))
            ->addColumn('action', fn ($row) => '<a href="'.route('admin.estate.define-pay-scale.edit', $row->pk).'" class="text-primary" title="Edit">'
                .'<i class="material-icons material-symbols-rounded">edit</i></a>')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $item = null;
        $salaryGrades = SalaryGrade::orderBy('salary_grade')->get()->mapWithKeys(fn ($s) => [$s->pk => $s->display_label_text]);
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        $unitSubTypes = UnitSubType::orderBy('unit_sub_type')->pluck('unit_sub_type', 'pk');
        return view('admin.estate.define_pay_scale.form', compact('item', 'salaryGrades', 'unitTypes', 'unitSubTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'salary_grade_master_pk' => 'required|exists:salary_grade_master,pk',
            'estate_unit_type_master_pk' => 'required|exists:estate_unit_type_master,pk',
            'estate_unit_sub_type_master_pk' => 'required|exists:estate_unit_sub_type_master,pk',
        ]);
        EligibilityCriterion::create($validated);
        return redirect()->route('admin.estate.define-pay-scale.index')->with('success', 'Eligibility mapping added successfully.');
    }

    public function edit(string $id)
    {
        $item = EligibilityCriterion::findOrFail($id);
        $salaryGrades = SalaryGrade::orderBy('salary_grade')->get()->mapWithKeys(fn ($s) => [$s->pk => $s->display_label_text]);
        $unitTypes = UnitType::orderBy('unit_type')->pluck('unit_type', 'pk');
        $unitSubTypes = UnitSubType::orderBy('unit_sub_type')->pluck('unit_sub_type', 'pk');
        return view('admin.estate.define_pay_scale.form', compact('item', 'salaryGrades', 'unitTypes', 'unitSubTypes'));
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
        return redirect()->route('admin.estate.define-pay-scale.index')->with('success', 'Eligibility mapping updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        EligibilityCriterion::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Eligibility mapping deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-pay-scale.index')->with('success', 'Eligibility mapping deleted successfully.');
    }
}
