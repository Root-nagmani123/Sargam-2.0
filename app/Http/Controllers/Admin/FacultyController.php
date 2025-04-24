<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Country, State, City};
use Illuminate\Http\Request;
use App\Http\Requests\FacultyRequest;
class FacultyController extends Controller
{
    public function index() {
        return view("admin.faculty.index");
    }

    public function create() {

        $country = Country::pluck('country_name', 'pk')->toArray();
        $state = State::get();
        $city = City::get();
        
        return view("admin.faculty.create", compact('country', 'state', 'city'));
    }

    public function store(FacultyRequest $request) {
        \Log::alert($request->all());
        // Store logic here
        return redirect()->route('faculty.index')->with('success', 'Faculty created successfully');
    }
//     public function edit($id) {
//         return view("admin.facult.edit", compact('id') );

//     }

//     public function update(Request $request, $id) {
//         // Update logic here
//         return redirect()->route('facult.index')->with('success', 'Facult updated successfully');
//     }

//     public function destroy($id) {
//         // Delete logic here
//         return redirect()->route('facult.index')->with('success', 'Facult deleted successfully');
//     }
// }
}