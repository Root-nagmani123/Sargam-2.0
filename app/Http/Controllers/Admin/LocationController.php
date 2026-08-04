<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Country, State, District, City};
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class LocationController extends Controller
{
    public function countryIndex()
    {
        // Phase E/F pixel-perfect index redesign: the view is now a client-side
        // DataTable (Store Master pattern) that needs the full set to drive its own
        // search / sort / column-visibility / page-size. Country is a small master, so
        // returning all rows is safe. (Was Country::paginate(10).) Reversible.
        $countries = Country::orderBy('country_name')->get();

        return view('admin.country.index', compact('countries'));
    }

    public function countryCreate()
    {
        return view('admin.country.create');
    }

    /**
     * Branded CSV / PDF / Print export for the Country list. Reuses the shared
     * LBSNAA report chrome (resources/views/exports/lbsnaa-report.blade.php) so the
     * header (logos + academy + course line + blue title + blue table) matches every
     * other export. Read-only; touches no create/update logic.
     */
    public function countryExport(Request $request, $format = 'pdf')
    {
        $headings  = ['S. No.', 'Country Name', 'Status'];
        $rows      = [];
        $i         = 1;
        foreach (Country::orderBy('country_name')->get() as $c) {
            $rows[] = [$i++, $c->country_name, $c->active_inactive == 1 ? 'Active' : 'Inactive'];
        }

        $reportTitle = 'Country List';
        $subtitle    = 'IAS Professional Course, Phase - I (2025 Batch)';
        $subtitle2   = '(8 December 2025 to 17 April, 2026)';
        if ($format === 'csv') {
            $filename = 'country-list-' . date('Ymd_His') . '.csv';
            $colCount = max(count($headings), 1);
            return response()->streamDownload(function () use ($headings, $rows, $reportTitle, $subtitle, $subtitle2, $colCount) {
                $out = fopen('php://output', 'w');
                // UTF-8 BOM so Excel renders the Hindi academy name (and any non-ASCII data) correctly.
                fwrite($out, "\xEF\xBB\xBF");
                // Branded header block — same lines/order as the PDF/Print header (logos aside,
                // which a plain .csv cannot carry). Each line is padded across all columns so it
                // spans the sheet width like the design's centred header.
                $span = static function (string $text) use ($colCount) {
                    return array_merge([$text], array_fill(0, $colCount - 1, ''));
                };
                fputcsv($out, $span('लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी'));
                fputcsv($out, $span('Lal Bahadur Shastri National Academy of Administration, Mussoorie'));
                fputcsv($out, $span($subtitle));
                fputcsv($out, $span($subtitle2));
                fputcsv($out, $span($reportTitle));
                fputcsv($out, []);
                fputcsv($out, $headings);
                foreach ($rows as $r) { fputcsv($out, $r); }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $data = array_merge($this->exportAssets(), [
            'reportTitle' => $reportTitle,
            'subtitle'    => $subtitle,
            'subtitle2'   => $subtitle2,
            'headings'    => $headings,
            'rows'        => $rows,
            'printedOn'   => now()->format('d-m-Y H:i'),
        ]);

        if ($format === 'print') {
            return view('exports.lbsnaa-report', array_merge($data, ['autoPrint' => true]));
        }

        return Pdf::loadView('exports.lbsnaa-report', $data)
            ->setPaper('a4', 'landscape')
            ->download('country-list-' . date('Ymd_His') . '.pdf');
    }

    /** LBSNAA logo/title data-URIs for the branded export chrome (see AttendanceController::pdfHeaderAssets). */
    private function exportAssets(): array
    {
        $toDataUri = static function (string $path): ?string {
            if (! is_file($path) || ! is_readable($path)) return null;
            $raw = @file_get_contents($path);
            if ($raw === false) return null;
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) { 'svg' => 'image/svg+xml', 'jpg', 'jpeg' => 'image/jpeg', default => 'image/png' };
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        };
        $rightLogo = public_path('admin_assets/images/logos/constitution-75.png');
        if (! is_file($rightLogo)) $rightLogo = public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png');
        return [
            'logoLeft'   => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight'  => $toDataUri($rightLogo),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
        ];
    }

    public function countryStore(Request $request)
    {
       /* $validated = $request->validate([
            'country_name.*' => 'required|string|max:100',
            'active_inactive' => 'required',
        ]);*/
        $request->validate(
			[
				'country_name.*' => 'required|string|max:100|unique:country_master,country_name',
				'active_inactive' => 'required',
			],
			[
				'country_name.*.unique' => 'This country name already exists.',
				'country_name.*.required' => 'Country name is required.',
				'country_name.*.max' => 'Country name must not exceed 100 characters.',
			]
		);

        foreach ($request->country_name as $name) {
            Country::create([
                'country_name' => $name,
                'active_inactive' => $request->active_inactive,
                'created_date' => now(),  // Use current timestamp
            ]);
        }

        return redirect()->route('master.country.index')->with('success', 'Countries added successfully!');
    }

    public function countryEdit($id)
    {
        $country = Country::findOrFail($id);
        return view('admin.country.edit', compact('country'));
    }

    public function countryUpdate(Request $request, $id)
    {
        $request->validate([
            'country_name' => 'required|string|max:255',
              'active_inactive' => 'required',
        ]);

        $country = Country::findOrFail($id);
        $country->country_name = $request->country_name;
        $country->active_inactive = $request->active_inactive;
        $country->save();

        return redirect()->route('master.country.index')->with('success', 'Country updated successfully!');
    }

    public function countryDelete($id)
    {
        Country::destroy($id);
        return redirect()->route('master.country.index')->with('success', 'Country deleted successfully');
    }

    // State
    public function stateIndex()
    {
        $states = State::paginate(10);
        // print_r($states);die;
        return view('admin.state.index', compact('states'));
    }

    public function stateCreate()
    {
        $countries = Country::all();
        return view('admin.state.create', compact('countries'));
    }

    public function stateStore(Request $request)
    {
        $request->validate([
            'state_name' => 'required|string|max:255',
            'country_master_pk' => 'required|exists:country_master,pk',
            'active_inactive' => 'required',
        ]);

        $state = new State();
        $state->state_name = $request->state_name;
        $state->country_master_pk = $request->country_master_pk;
        $state->active_inactive = $request->active_inactive;
        $state->created_date = now();
        $state->save();

        return redirect()->route('master.state.index')->with('success', 'State added successfully.');
    }

    public function stateEdit($id)
    {
        $state = State::findOrFail($id);
        $countries = Country::all();
        return view('admin.state.edit', compact('state', 'countries'));
    }

    public function stateUpdate(Request $request, $pk)
    {
        // Validate incoming request
        $request->validate([
            'state_name' => 'required|string|max:255',
            'country_master_pk' => 'required|exists:country_master,pk',  // Validating country
            'active_inactive' => 'required',
        ]);


        $state = State::findOrFail($pk);

        // Update the state data
        $state->state_name = $request->state_name;
        $state->country_master_pk = $request->country_master_pk;
        $state->active_inactive = $request->active_inactive;

        // Optionally, track who is updating

        // Save the state
        $state->save();

        // Redirect with a success message
        return redirect()->route('master.state.index')->with('success', 'State updated successfully.');
    }
    public function stateDelete($id)
    {
        State::destroy($id);
        return redirect()->route('master.state.index')->with('success', 'State deleted successfully');
    }

    // District
    public function districtIndex()
    {
        $districts = District::paginate(10);
        return view('admin.district.index', compact('districts'));
    }

    public function districtCreate()
    {
         $countries = Country::all();
        $states = State::all();
        return view('admin.district.create', compact('states', 'countries'));
    }

    public function districtStore(Request $request)
    {
        $request->validate([
            'state_master_pk' => 'required|numeric',
            'district_name' => 'required|string|max:100',
             'active_inactive' => 'required',
        ]);


        District::create([
            'country_master_pk' => $request->country_master_pk, // Assuming you have a country_master_pk in the request
            'state_master_pk' => $request->state_master_pk,
            'district_name' => $request->district_name,
            'active_inactive' => $request->active_inactive,

        ]);

        return redirect()->route('master.district.index')->with('success', 'District added successfully.');
    }

    public function districtEdit($id)
    {
         $countries = Country::all();

        $district = District::findOrFail($id);
        $states = State::all();
        return view('admin.district.edit', compact('district', 'states', 'countries'));
    }

    public function districtUpdate(Request $request, $id)
    {
        $request->validate([
            'state_master_pk' => 'required|numeric',
            'district_name' => 'required|string|max:100',
             'active_inactive' => 'required',
        ]);

        $district = District::findOrFail($id);
        $district->update([
            'country_master_pk' => $request->country_master_pk, // Assuming you have a country_master_pk in the request

            'state_master_pk' => $request->state_master_pk,
            'district_name' => $request->district_name,
             'active_inactive' => $request->active_inactive,
        ]);


        return redirect()->route('master.district.index')->with('success', 'District updated successfully');
    }

    public function districtDelete($id)
    {
        District::destroy($id);
        return redirect()->route('master.district.index')->with('success', 'District deleted successfully');
    }

    // City
    public function cityIndex()
    {
        $cities = City::with(['state', 'district'])->paginate(10);
        return view('admin.city.index', compact('cities'));
    }

    public function cityCreate()
    {
         $countries = Country::all();

        $states = State::all();  // Fetch all states
        $districts = District::all();
        return view('admin.city.create', compact('states', 'districts', 'countries'));
    }

    public function cityStore(Request $request)
    {
        $request->validate([
            'state_master_pk' => 'required',
            'district_master_pk' => 'required',
            'city_name' => 'required|string|max:100',
            'active_inactive' => 'required',
        ]);

        City::create([
            'country_master_pk' => $request->country_master_pk,
            'state_master_pk' => $request->state_master_pk,
            'district_master_pk' => $request->district_master_pk,
            'city_name' => $request->city_name,
            'active_inactive' => $request->active_inactive,
        ]);

        return redirect()->route('master.city.index')->with('success', 'City added successfully');
    }

    public function cityEdit($id)
    {
        $city = City::findOrFail($id);  // This will automatically handle finding by primary key 'pk'

         $countries = Country::all();

        $states = State::all();  // Get all states
        $districts = District::all();  // Get all districts
        return view('admin.city.edit', compact('city', 'districts', 'states', 'countries'));
    }

    public function cityUpdate(Request $request, $id)
    {
        $request->validate([
            'state_master_pk' => 'required',
            'district_master_pk' => 'required',
            'city_name' => 'required|string|max:100',
            'active_inactive' => 'required',
        ]);

        $city = City::findOrFail($id);

        // Update the city details using the model
        $city->update([
            'country_master_pk' => $request->country_master_pk,
            'state_master_pk' => $request->state_master_pk,
            'district_master_pk' => $request->district_master_pk,
            'city_name' => $request->city_name,
            'active_inactive' => $request->active_inactive,
        ]);

        return redirect()->route('master.city.index')->with('success', 'City updated successfully');
    }

    public function cityDelete($id)
    {
        City::destroy($id);
        return redirect()->route('master.city.index')->with('success', 'City deleted successfully');
    }

    function getStatesByCountry(Request $request)
    {
        $countryId = $request->input('country_id');
        $states = State::where('country_master_pk', $countryId)->select('pk', 'state_name')->get()->toArray();
        return response()->json(['status' => true, 'states' => $states]);
    }

    function getDistrictsByState(Request $request)
    {
        $stateId = $request->input('state_id');
        $districts = District::where('state_master_pk', $stateId)->select('pk', 'district_name')->get()->toArray();
        return response()->json(['status' => true, 'districts' => $districts]);
    }

    function getCitiesByDistrict(Request $request)
    {
        $districtId = $request->input('district_id');
        $cities = City::where('district_master_pk', $districtId)->select('pk', 'city_name')->get()->toArray();
        return response()->json(['status' => true, 'cities' => $cities]);
    }
    public function getStates(Request $request)
{
    $states = State::where('country_master_pk', $request->country_id)->get();
    return response()->json($states);
}

public function getDistricts(Request $request)
{
    $districts = District::where('state_master_pk', $request->state_id)->get();
    return response()->json($districts);
}

}
