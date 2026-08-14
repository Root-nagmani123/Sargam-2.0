<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\PaginatesListings;
use App\Http\Controllers\Admin\Estate\Concerns\AuthorizesEstateMaster;
use App\Models\EligibilityCriterion;
use App\Models\SalaryGrade;
use App\Models\UnitType;
use App\Models\UnitSubType;
use Illuminate\Http\Request;

class PayScaleController extends Controller
{
    use PaginatesListings;
    use AuthorizesEstateMaster;

    public function index(Request $request)
    {
        $search = $this->searchTerm($request);

        $query = EligibilityCriterion::with(['salaryGrade', 'unitType', 'unitSubType'])
            ->orderBy('pk');

        // Every column on this grid comes from a relation, so the search has to go
        // through them — there is nothing searchable on eligibility_criteria itself.
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                // The Pay Scale cell renders SalaryGrade::$display_label_text, which
                // is "salary_grade (GP <grade_3|grade_2|grade_1>)" — so the grade-pay
                // columns are on screen too and have to be searchable.
                $q->whereHas('salaryGrade', fn ($s) => $s->where(fn ($g) => $g
                        ->where('salary_grade', 'like', $like)
                        ->orWhere('grade_1', 'like', $like)
                        ->orWhere('grade_2', 'like', $like)
                        ->orWhere('grade_3', 'like', $like)))
                    ->orWhereHas('unitType', fn ($u) => $u->where('unit_type', 'like', $like))
                    ->orWhereHas('unitSubType', fn ($u) => $u->where('unit_sub_type', 'like', $like));
            });
        }

        // per_page was already read here, but unvalidated — ?per_page=999999 would
        // have been honoured verbatim.
        $items = $query->paginate($this->resolvePerPage($request))->withQueryString();

        return view('admin.estate.define_pay_scale.index', compact('items'));
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
