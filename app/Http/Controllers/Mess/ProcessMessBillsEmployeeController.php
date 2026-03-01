<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProcessMessBillsEmployeeController extends Controller
{
    public function index() { return redirect()->route('admin.dashboard'); }
    public function modalData() { return response()->json([]); }
    public function generateInvoice(Request $request, $id) { return redirect()->back(); }
    public function generatePayment(Request $request, $id) { return redirect()->back(); }
    public function printReceipt($id) { return redirect()->back(); }
    public function export() { return redirect()->back(); }
}
