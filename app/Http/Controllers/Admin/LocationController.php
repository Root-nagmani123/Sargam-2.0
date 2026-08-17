<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Country, State, District, City};
use Illuminate\Http\Request;


class LocationController extends Controller
{
    /** Whitelist for the footers' rows-per-page select (docs/new-design-index-page.md §4B). */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Readable names for the foreign keys. The dialogs show these messages inline,
     * so "The state master pk field is required." is not good enough.
     */
    private const FIELD_NAMES = [
        'country_master_pk' => 'country',
        'state_master_pk' => 'state',
        'district_master_pk' => 'district',
        'active_inactive' => 'status',
    ];

    /**
     * A whitelisted page size — anything else falls back to 10, so the query
     * string can't ask for an unbounded page.
     */
    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    public function countryIndex(Request $request)
    {
        $perPage = $this->perPage($request);

        // withQueryString(), or the pager links drop per_page and page 2 snaps
        // back to 10 rows.
        $countries = Country::orderBy('country_name')->paginate($perPage)->withQueryString();

        return view('admin.country.index', [
            'countries' => $countries,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    public function countryCreate()
    {
        return view('admin.country.create');
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
    public function stateIndex(Request $request)
    {
        $perPage = $this->perPage($request);

        // with('country'): the listing shows which country each state belongs to,
        // and the Edit dialog needs the country to pre-select.
        $states = State::with('country')
            ->orderBy('state_name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.state.index', [
            'states' => $states,
            // The dialog's country select — 2 rows, so rendering the options is fine.
            'countries' => Country::orderBy('country_name')->get(['pk', 'country_name']),
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
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
        ], [], self::FIELD_NAMES);

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
        ], [], self::FIELD_NAMES);


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
    public function districtIndex(Request $request)
    {
        $perPage = $this->perPage($request);

        $districts = District::with(['country', 'state'])
            ->orderBy('district_name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.district.index', [
            'districts' => $districts,
            'countries' => Country::orderBy('country_name')->get(['pk', 'country_name']),
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
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
        ], [], self::FIELD_NAMES);


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
        ], [], self::FIELD_NAMES);

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
    public function cityIndex(Request $request)
    {
        $perPage = $this->perPage($request);

        $cities = City::with(['country', 'state', 'district'])
            ->orderBy('city_name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.city.index', [
            'cities' => $cities,
            // Only the countries are rendered as options; the state and district
            // selects are filled by the cascade lookups, so 37 states and 850
            // districts never reach the page (§3c).
            'countries' => Country::orderBy('country_name')->get(['pk', 'country_name']),
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
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
        ], [], self::FIELD_NAMES);

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
        ], [], self::FIELD_NAMES);

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
