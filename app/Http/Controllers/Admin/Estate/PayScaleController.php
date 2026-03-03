<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\PayScale;
use Illuminate\Http\Request;

class PayScaleController extends Controller
{
    public function index()
    {
        $items = PayScale::orderBy('pay_scale_range')->paginate(request('per_page', 10));
        return view('admin.estate.define_pay_scale.index', compact('items'));
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_pay_scale.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pay_scale_range' => 'required|string|max:100',
            'pay_scale_level' => 'required|string|max:50',
            'display_label' => 'nullable|string|max:255',
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
            'pay_scale_range' => 'required|string|max:100',
            'pay_scale_level' => 'required|string|max:50',
            'display_label' => 'nullable|string|max:255',
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
