<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\District;
use App\Models\City;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function countryIndex() {
        $countries = Country::paginate(10);

        return view('admin.country.index', compact('countries'));
    }

    public function countryCreate() {
        return view('admin.country.create');
    }

    public function countryStore(Request $request) {
        $validated = $request->validate([
            'country_name.*' => 'required|string|max:100',
        ]);
    
        foreach ($request->country_name as $name) {
            Country::create([
                'country_name' => $name,
                'created_by' => auth()->id() ?? 1,  // Use current logged-in user ID or fallback to 1
                'created_date' => now(),  // Use current timestamp
            ]);
        }
    
        return redirect()->route('country.index')->with('success', 'Countries added successfully!');
    }

    public function countryEdit($id) {
        $country = Country::findOrFail($id);
        return view('admin.country.edit', compact('country'));
    }

    public function countryUpdate(Request $request, $id) {
        $request->validate([
            'country_name' => 'required|string|max:255',
        ]);
    
        $country = Country::findOrFail($id);
        $country->country_name = $request->country_name;
        $country->save();
    
        return redirect()->route('country.index')->with('success', 'Country updated successfully!');
    }

    public function countryDelete($id) {
        Country::destroy($id);
        return redirect()->route('country.index')->with('success', 'Country deleted successfully');
    }

    // State
    public function stateIndex() {
        // $state = State::with('country')->paginate(10);
        $states = State::paginate(10);
        // print_r($state);die;
        return view('admin.state.index', compact('states'));
    }

    public function stateCreate() {
        $countries = Country::all();
        return view('admin.state.create', compact('countries'));
    }

    public function stateStore(Request $request) {
        $request->validate([
            'state_name' => 'required|string|max:255',
            'country_master_pk' => 'required|exists:country_master,pk',
        ]);
    
        $state = new State();
        $state->state_name = $request->state_name;
        $state->country_master_pk = $request->country_master_pk;
        $state->created_by = auth()->id();
        $state->created_date = now();
        $state->save();
    
        return redirect()->route('state.index')->with('success', 'State added successfully.');
    }

    public function stateEdit($id) {
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
    ]);

    // Find the state record
    $state = State::findOrFail($pk);// Ensure we are getting the correct state by its ID

    // Debugging point: check if state is found
    // dd($state); // Uncomment this to check if the state is found.

    // Update the state data
    $state->state_name = $request->state_name;
    $state->country_master_pk = $request->country_master_pk;
    
    // Optionally, track who is updating
    $state->modified_by = auth()->id();  // Assuming you're using authentication
    $state->modified_date = now();  // Update the timestamp
    
    // Save the state
    $state->save(); 

    // Redirect with a success message
    return redirect()->route('state.index')->with('success', 'State updated successfully.');
}
    public function stateDelete($id) {
        State::destroy($id);
        return redirect()->route('state.index')->with('success', 'State deleted successfully');
    }

    // District
    public function districtIndex() {
        $districts = District::paginate(10);
        // print_r($districts);die;
        return view('admin.district.index', compact('districts'));
    }

    public function districtCreate() {
        $states = State::all();
        return view('admin.district.create', compact('states'));
    }

    public function districtStore(Request $request) {
        $request->validate([
            'state_master_pk' => 'required|numeric',
            'district_name' => 'required|string|max:100',
        ]);
    
       
        District::create([
            'state_master_pk' => $request->state_master_pk,
            'district_name' => $request->district_name,
        ]);
    
        return redirect()->route('district.index')->with('success', 'District added successfully.');
    }

    public function districtEdit($id) {
        $district = District::findOrFail($id);
        $states = State::all();
        return view('admin.district.edit', compact('district', 'states'));
    }

    public function districtUpdate(Request $request, $id) {
        $request->validate([
            'state_master_pk' => 'required|numeric',
            'district_name' => 'required|string|max:100',
        ]);
    
        $district = District::findOrFail($id);
        $district->update([
            'state_master_pk' => $request->state_master_pk,
            'district_name' => $request->district_name,
        ]);
    
    
        return redirect()->route('district.index')->with('success', 'District updated successfully');
    }

    public function districtDelete($id) {
        District::destroy($id);
        return redirect()->route('district.index')->with('success', 'District deleted successfully');
    }

    // City
    public function cityIndex() {
      
        $cities = City::with(['state', 'district'])->get();
        return view('admin.city.index', compact('cities'));
    }

    public function cityCreate() {
        $states = State::all();  // Fetch all states
        $districts = District::all(); 
        return view('admin.city.create', compact('states','districts'));
    }

    public function cityStore(Request $request) {
        $request->validate([
            'state_master_pk' => 'required',
            'district_master_pk' => 'required',
            'city_name' => 'required|string|max:100',
        ]);
    
        City::create([
            'state_master_pk' => $request->state_master_pk,
            'district_master_pk' => $request->district_master_pk,
            'city_name' => $request->city_name,
        ]);
    
        return redirect()->route('city.index')->with('success', 'City added successfully');
    }

    public function cityEdit($id) {
        $city = City::findOrFail($id);  // This will automatically handle finding by primary key 'pk'

    
        $states = State::all();  // Get all states
        $districts = District::all();  // Get all districts
        return view('admin.city.edit', compact('city', 'districts','states'));
    }

    public function cityUpdate(Request $request, $id) {
        $request->validate([
            'state_master_pk' => 'required',
            'district_master_pk' => 'required',
            'city_name' => 'required|string|max:100',
        ]);
    
        $city = City::findOrFail($id);

    // Update the city details using the model
    $city->update([
        'state_master_pk' => $request->state_master_pk,
        'district_master_pk' => $request->district_master_pk,
        'city_name' => $request->city_name,
    ]);
    
        return redirect()->route('city.index')->with('success', 'City updated successfully');
    }

    public function cityDelete($id) {
        City::destroy($id);
        return redirect()->route('city.index')->with('success', 'City deleted successfully');
    }
}