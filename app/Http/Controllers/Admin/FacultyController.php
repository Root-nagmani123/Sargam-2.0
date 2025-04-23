<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index() {
        return view("admin.faculty.index");
    }

    public function create() {
        return view("admin.faculty.create");
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
