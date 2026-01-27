<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index() {
        $vendors = Vendor::all();
        return view('mess.vendors.index', compact('vendors'));
    }
    public function create() {
        return view('mess.vendors.create');
    }
    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required',
            'contact_person' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'address' => 'nullable',
        ]);
        Vendor::create($data);
        return redirect()->route('admin.mess.vendors.index')->with('success', 'Vendor created successfully.');
    }
}
