<?php

namespace App\Http\Controllers\Admin\Estate;

use App\DataTables\PayScaleDataTable;
use App\Http\Controllers\Controller;
use App\Models\PayScale;
use Illuminate\Http\Request;

class PayScaleController extends Controller
{
    public function index(PayScaleDataTable $dataTable)
    {
        return $dataTable->render('admin.estate.define_pay_scale.index');
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_pay_scale.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'salary_grade' => 'required|string|max:200',
        ]);
        PayScale::create($validated);
        return redirect()->route('admin.estate.define-pay-scale.index')->with('success', 'Pay scale added successfully.');
    }

    public function edit(string $id)
    {
        $item = PayScale::findOrFail($id);
        return view('admin.estate.define_pay_scale.form', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $item = PayScale::findOrFail($id);
        $validated = $request->validate([
            'salary_grade' => 'required|string|max:200',
        ]);
        $item->update($validated);
        return redirect()->route('admin.estate.define-pay-scale.index')->with('success', 'Pay scale updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        PayScale::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Pay scale deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-pay-scale.index')->with('success', 'Pay scale deleted successfully.');
    }
}
