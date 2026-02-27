<?php

namespace App\Http\Controllers\Admin\Estate;

use App\Http\Controllers\Controller;
use App\Models\EstateCampus;
use Illuminate\Http\Request;

class EstateCampusController extends Controller
{
    public function index()
    {
        $items = EstateCampus::orderBy('campus_name')->get();
        return view('admin.estate.define_campus.index', compact('items'));
    }

    public function create()
    {
        $item = null;
        return view('admin.estate.define_campus.form', compact('item'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        EstateCampus::create($validated);
        return redirect()->route('admin.estate.define-campus.index')->with('success', 'Campus added successfully.');
    }

    public function edit(string $id)
    {
        $item = EstateCampus::findOrFail($id);
        return view('admin.estate.define_campus.form', compact('item'));
    }

    public function update(Request $request, string $id)
    {
        $item = EstateCampus::findOrFail($id);
        $validated = $request->validate([
            'campus_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $item->update($validated);
        return redirect()->route('admin.estate.define-campus.index')->with('success', 'Campus updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        EstateCampus::findOrFail($id)->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Campus deleted successfully.']);
        }
        return redirect()->route('admin.estate.define-campus.index')->with('success', 'Campus deleted successfully.');
    }
}
