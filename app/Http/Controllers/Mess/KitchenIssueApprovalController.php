<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KitchenIssueApprovalController extends Controller
{
    public function index() { return redirect()->route('admin.dashboard'); }
    public function show($id) { return redirect()->route('admin.dashboard'); }
    public function approve(Request $request, $id) { return redirect()->back(); }
    public function reject(Request $request, $id) { return redirect()->back(); }
}
