<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\MealMapping;

class MealMappingController extends Controller
{
    public function index()
    {
        $mealmappings = MealMapping::all();
        return view('mess.mealmappings.index', compact('mealmappings'));
    }

    public function create()
    {
        return view('mess.mealmappings.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'meal_name' => 'required',
            'item_name' => 'required',
            'date' => 'required|date',
        ]);
        MealMapping::create($request->all());
        return redirect()->route('admin.mess.mealmappings.index')->with('success', 'Meal mapping added successfully');
    }
}
