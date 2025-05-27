<?php
namespace App\Http\Controllers\Admin\Registration;
use App\Models\FcExemptionMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class FcExemptionMasterController extends Controller
{
    public function index()
    {
        $exemptions = FcExemptionMaster::all();
        return view('admin.forms.exemption.index', compact('exemptions'));
    }

    public function create()
    {
        return view('fc_exemption.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Exemption_name' => 'required|string|max:500',
            'Exemption_short_name' => 'required|string|max:100',
        ]);

        FcExemptionMaster::create([
            'Exemption_name' => $request->Exemption_name,
            'Exemption_short_name' => $request->Exemption_short_name,
            'Created_by' => Auth::id(),
            'Created_date' => now(),
        ]);

        return redirect()->route('fc_exemption.index')->with('success', 'Exemption created successfully.');
    }

    public function edit($id)
    {
        $exemption = FcExemptionMaster::findOrFail($id);
        return view('fc_exemption.edit', compact('exemption'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Exemption_name' => 'required|string|max:500',
            'Exemption_short_name' => 'required|string|max:100',
        ]);

        $exemption = FcExemptionMaster::findOrFail($id);
        $exemption->update([
            'Exemption_name' => $request->Exemption_name,
            'Exemption_short_name' => $request->Exemption_short_name,
            'Modified_by' => Auth::id(),
            'Modified_date' => now(),
        ]);

        return redirect()->route('fc_exemption.index')->with('success', 'Exemption updated successfully.');
    }
}
