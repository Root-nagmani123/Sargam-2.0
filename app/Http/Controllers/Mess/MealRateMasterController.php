<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\MealRateMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MealRateMasterController extends Controller
{
    public function index()
    {
        $rates = MealRateMaster::orderBy('meal_type')->orderBy('category_type')->paginate(config('mess.defaults.pagination', 15));
        return view('admin.mess.meal-rate-master.index', compact('rates'));
    }

    public function create()
    {
        return view('admin.mess.meal-rate-master.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        MealRateMaster::create($data);
        return redirect()->route('admin.mess.meal-rate-master.index')->with('success', 'Meal rate added successfully.');
    }

    public function edit($id)
    {
        $rate = MealRateMaster::findOrFail($id);
        return view('admin.mess.meal-rate-master.edit', compact('rate'));
    }

    public function update(Request $request, $id)
    {
        $rate = MealRateMaster::findOrFail($id);
        $data = $this->validatedData($request, $rate);
        $rate->update($data);
        return redirect()->route('admin.mess.meal-rate-master.index')->with('success', 'Meal rate updated successfully.');
    }

    public function destroy($id)
    {
        $rate = MealRateMaster::findOrFail($id);
        $rate->delete();
        return redirect()->route('admin.mess.meal-rate-master.index')->with('success', 'Meal rate deleted successfully.');
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus($id)
    {
        $rate = MealRateMaster::findOrFail($id);
        $rate->update(['is_active' => !$rate->is_active]);
        $status = $rate->is_active ? 'Active' : 'Inactive';
        return redirect()->route('admin.mess.meal-rate-master.index')->with('success', "Meal rate set to {$status}.");
    }

    protected function validatedData(Request $request, ?MealRateMaster $rate = null): array
    {
        $uniqueRule = Rule::unique('mess_meal_rate_master', 'category_type')
            ->where(function ($q) use ($request) {
                $q->where('meal_type', $request->input('meal_type'));
            });
        if ($rate !== null) {
            $uniqueRule->ignore($rate->id);
        }

        $validated = $request->validate([
            'meal_type' => ['required', 'string', 'in:breakfast,lunch,dinner'],
            'category_type' => ['required', 'string', 'in:govrt,ot,faculty,alumni', $uniqueRule],
            'rate' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'meal_type' => $validated['meal_type'],
            'category_type' => $validated['category_type'],
            'rate' => $validated['rate'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }
}
