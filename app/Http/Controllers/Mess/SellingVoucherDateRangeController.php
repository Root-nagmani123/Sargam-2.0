<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SellingVoucherDateRangeController extends Controller
{
    public function index() { return redirect()->route('admin.dashboard'); }
    public function create() { return redirect()->route('admin.dashboard'); }
    public function store(Request $request) { return redirect()->back(); }
    public function show($id) { return redirect()->route('admin.dashboard'); }
    public function edit($id) { return redirect()->route('admin.dashboard'); }
    public function update(Request $request, $id) { return redirect()->back(); }
    public function destroy($id) { return redirect()->back(); }
    public function getStudentsByCourse($course_pk) { return response()->json([]); }
    public function getStoreItems($storeIdentifier) { return response()->json([]); }
    public function returnData($id) { return redirect()->back(); }
    public function updateReturn(Request $request, $id) { return redirect()->back(); }
}
