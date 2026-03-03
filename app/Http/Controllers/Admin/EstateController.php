<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EstateOtherRequest;
use Illuminate\Http\Request;

class EstateController extends Controller
{
    /**
     * Add Other Estate Request - Form page.
     */
    public function addOtherEstateRequest(Request $request)
    {
        $prefill = [
            'employee_name' => $request->query('employee_name'),
            'father_name' => $request->query('father_name'),
            'section' => $request->query('section'),
            'doj_academy' => $request->query('doj_academy'),
        ];

        return view('admin.estate.add_other_estate_request', compact('prefill'));
    }

    /**
     * Store Other Estate Request - saves to estate_other_req table (from SQL import).
     */
    public function storeOtherEstateRequest(Request $request)
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:500',
            'father_name' => 'required|string|max:500',
            'section' => 'required|string|max:500',
            'doj_academy' => 'required|date',
        ]);

        $requestNo = $this->generateRequestNo();

        EstateOtherRequest::create([
            'emp_name' => $validated['employee_name'],
            'f_name' => $validated['father_name'],
            'section' => $validated['section'],
            'doj_acad' => $validated['doj_academy'],
            'status' => 0,
            'request_no_oth' => $requestNo,
        ]);

        return redirect()
            ->route('admin.estate.add-other-estate-request')
            ->with('success', 'Estate request successfully saved.');
    }

    /**
     * Generate next request number (oth-req-1, oth-req-2, ...)
     */
    private function generateRequestNo(): string
    {
        $nextPk = (int) EstateOtherRequest::max('pk') + 1;
        return 'oth-req-' . $nextPk;
    }
}
