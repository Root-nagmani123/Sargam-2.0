<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Country, State, District, City};
use Illuminate\Http\Request;


class LocationController extends Controller
{
    public function countryIndex()
    {
        $countries = Country::paginate(10);

        return view('admin.country.index', compact('countries'));
    }

    public function countryCreate(Request $request)
    {
        if ($request->ajax() || $request->expectsJson()) {
            $countryNameOptions = $this->presetCountryNameOptions();

            return view('admin.country._form', compact('countryNameOptions'));
        }

        return redirect()->route('master.country.index', ['open_cty_modal' => 'add']);
    }

    public function countryStore(Request $request)
    {
        if (is_array($request->country_name)) {
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
                    'created_date' => now(),
                ]);
            }

            $message = 'Countries added successfully!';

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->route('master.country.index')->with('success', $message);
        }

        $request->validate(
            [
                'country_name' => 'required|string|max:100|unique:country_master,country_name',
                'active_inactive' => 'required',
            ],
            [
                'country_name.unique' => 'This country name already exists.',
                'country_name.required' => 'Country name is required.',
                'country_name.max' => 'Country name must not exceed 100 characters.',
            ]
        );

        Country::create([
            'country_name' => $request->country_name,
            'active_inactive' => $request->active_inactive,
            'created_date' => now(),
        ]);

        $message = 'Country added successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.country.index')->with('success', $message);
    }

    public function countryEdit(Request $request, $id)
    {
        $country = Country::findOrFail($id);

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.country._form', compact('country'));
        }

        return redirect()->route('master.country.index', [
            'open_cty_modal' => 'edit',
            'cty_id' => $id,
        ]);
    }

    public function countryUpdate(Request $request, $id)
    {
        $request->validate([
            'country_name' => 'required|string|max:255|unique:country_master,country_name,' . $id . ',pk',
            'active_inactive' => 'required',
        ], [
            'country_name.unique' => 'This country name already exists.',
            'country_name.required' => 'Country name is required.',
        ]);

        $country = Country::findOrFail($id);
        $country->country_name = $request->country_name;
        $country->active_inactive = $request->active_inactive;
        $country->save();

        $message = 'Country updated successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.country.index')->with('success', $message);
    }

    private function presetCountryNameOptions(): array
    {
        return [
            'India',
            'United States',
            'China',
            'United Kingdom',
            'Canada',
            'Australia',
            'Germany',
            'France',
            'Japan',
            'Brazil',
            'Russia',
            'South Africa',
            'Nepal',
            'Bangladesh',
            'Sri Lanka',
            'Pakistan',
            'Afghanistan',
            'Bhutan',
        ];
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

    public function stateCreate(Request $request)
    {
        $countries = Country::all();

        if ($request->ajax() || $request->expectsJson()) {
            $stateNameOptions = $this->presetStateNameOptions();

            return view('admin.state._form', compact('countries', 'stateNameOptions'));
        }

        return redirect()->route('master.state.index', ['open_stt_modal' => 'add']);
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

        $message = 'State added successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.state.index')->with('success', $message);
    }

    public function stateEdit(Request $request, $id)
    {
        $state = State::findOrFail($id);
        $countries = Country::all();

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.state._form', compact('state', 'countries'));
        }

        return redirect()->route('master.state.index', [
            'open_stt_modal' => 'edit',
            'stt_id' => $id,
        ]);
    }

    public function stateUpdate(Request $request, $pk)
    {
        $request->validate([
            'state_name' => 'required|string|max:255',
            'country_master_pk' => 'required|exists:country_master,pk',
            'active_inactive' => 'required',
        ]);

        $state = State::findOrFail($pk);
        $state->state_name = $request->state_name;
        $state->country_master_pk = $request->country_master_pk;
        $state->active_inactive = $request->active_inactive;
        $state->save();

        $message = 'State updated successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.state.index')->with('success', $message);
    }

    private function presetStateNameOptions(): array
    {
        return [
            'Andhra Pradesh',
            'Arunachal Pradesh',
            'Assam',
            'Bihar',
            'Chhattisgarh',
            'Goa',
            'Gujarat',
            'Haryana',
            'Himachal Pradesh',
            'Jharkhand',
            'Karnataka',
            'Kerala',
            'Madhya Pradesh',
            'Maharashtra',
            'Manipur',
            'Meghalaya',
            'Mizoram',
            'Nagaland',
            'Odisha',
            'Punjab',
            'Rajasthan',
            'Sikkim',
            'Tamil Nadu',
            'Telangana',
            'Tripura',
            'Uttar Pradesh',
            'Uttarakhand',
            'West Bengal',
            'Delhi',
            'Jammu and Kashmir',
            'Ladakh',
            'Puducherry',
        ];
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

    public function districtCreate(Request $request)
    {
        $countries = Country::all();
        $states = collect();

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.district._form', compact('states', 'countries'));
        }

        return redirect()->route('master.district.index', ['open_dst_modal' => 'add']);
    }

    public function districtStore(Request $request)
    {
        $request->validate([
            'country_master_pk' => 'required|exists:country_master,pk',
            'state_master_pk' => 'required|numeric',
            'district_name' => 'required|string|max:100',
            'active_inactive' => 'required',
        ]);

        District::create([
            'country_master_pk' => $request->country_master_pk,
            'state_master_pk' => $request->state_master_pk,
            'district_name' => $request->district_name,
            'active_inactive' => $request->active_inactive,
        ]);

        $message = 'District added successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.district.index')->with('success', $message);
    }

    public function districtEdit(Request $request, $id)
    {
        $countries = Country::all();
        $district = District::findOrFail($id);
        $states = State::where('country_master_pk', $district->country_master_pk)->get();

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.district._form', compact('district', 'states', 'countries'));
        }

        return redirect()->route('master.district.index', [
            'open_dst_modal' => 'edit',
            'dst_id' => $id,
        ]);
    }

    public function districtUpdate(Request $request, $id)
    {
        $request->validate([
            'country_master_pk' => 'required|exists:country_master,pk',
            'state_master_pk' => 'required|numeric',
            'district_name' => 'required|string|max:100',
            'active_inactive' => 'required',
        ]);

        $district = District::findOrFail($id);
        $district->update([
            'country_master_pk' => $request->country_master_pk,
            'state_master_pk' => $request->state_master_pk,
            'district_name' => $request->district_name,
            'active_inactive' => $request->active_inactive,
        ]);

        $message = 'District updated successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.district.index')->with('success', $message);
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

    public function cityCreate(Request $request)
    {
        $countries = Country::all();
        $states = collect();
        $districts = collect();

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.city._form', compact('states', 'districts', 'countries'));
        }

        return redirect()->route('master.city.index', ['open_cty_modal' => 'add']);
    }

    public function cityStore(Request $request)
    {
        $request->validate([
            'country_master_pk' => 'required|exists:country_master,pk',
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

        $message = 'City added successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.city.index')->with('success', $message);
    }

    public function cityEdit(Request $request, $id)
    {
        $city = City::findOrFail($id);
        $countries = Country::all();
        $states = State::where('country_master_pk', $city->country_master_pk)->get();
        $districts = District::where('state_master_pk', $city->state_master_pk)->get();

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.city._form', compact('city', 'districts', 'states', 'countries'));
        }

        return redirect()->route('master.city.index', [
            'open_cty_modal' => 'edit',
            'cty_id' => $id,
        ]);
    }

    public function cityUpdate(Request $request, $id)
    {
        $request->validate([
            'country_master_pk' => 'required|exists:country_master,pk',
            'state_master_pk' => 'required',
            'district_master_pk' => 'required',
            'city_name' => 'required|string|max:100',
            'active_inactive' => 'required',
        ]);

        $city = City::findOrFail($id);
        $city->update([
            'country_master_pk' => $request->country_master_pk,
            'state_master_pk' => $request->state_master_pk,
            'district_master_pk' => $request->district_master_pk,
            'city_name' => $request->city_name,
            'active_inactive' => $request->active_inactive,
        ]);

        $message = 'City updated successfully.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('master.city.index')->with('success', $message);
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
