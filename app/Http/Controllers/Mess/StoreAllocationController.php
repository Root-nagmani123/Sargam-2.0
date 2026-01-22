<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\StoreAllocation;

class StoreAllocationController extends Controller
{
    public function index()
    {
        $storeallocations = StoreAllocation::all();
        return view('mess.storeallocations.index', compact('storeallocations'));
    }

    public function create()
    {
        return view('mess.storeallocations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_name' => 'required',
            'allocated_to' => 'required',
            'allocation_date' => 'required|date',
        ]);
        StoreAllocation::create($request->all());
        return redirect()->route('mess.storeallocations.index')->with('success', 'Store allocation added successfully');
    }
}
