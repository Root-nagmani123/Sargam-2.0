<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MonthlyBillController extends Controller
{
    public function index() { return redirect()->route('admin.dashboard'); }
    public function create() { return redirect()->route('admin.dashboard'); }
    public function store(Request $request) { return redirect()->back(); }
    public function show($id) { return redirect()->route('admin.dashboard'); }
    public function edit($id) { return redirect()->route('admin.dashboard'); }
    public function update(Request $request, $id) { return redirect()->back(); }
    public function destroy($id) { return redirect()->back(); }
    public function generateBills(Request $request) { return redirect()->back(); }
}
