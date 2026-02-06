<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\Invoice;
use App\Models\Mess\Vendor;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('vendor')->get();
        return view('mess.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $vendors = Vendor::all();
        return view('mess.invoices.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_no' => 'required',
            'vendor_id' => 'required|exists:mess_vendors,id',
            'invoice_date' => 'required|date',
            'amount' => 'required|numeric',
        ]);
        Invoice::create($request->all());
        return redirect()->route('admin.mess.invoices.index')->with('success', 'Invoice added successfully');
    }
}
