<?php

namespace Modules\Protocol\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Routing\Controller;


class CommonController extends Controller
{
    public function getCountries()
    {
        return response()->json([
            'success' => true,
            'data' => Country::select('pk','country_name')->where(['pk'=>1,'active_inactive'=>1])->orderBy('country_name')->get(),
        ]);
    }

    public function getStates($countryId)
    {
        return response()->json([
            'success' => true,
            'data' =>State::select('pk','state_name')->where(['country_master_pk'=>$countryId,'active_inactive'=>1])->orderBy('state_name')->get(),
        ]);
    }

    public function getCities($stateId)
    {
        return response()->json([
            'success' => true,
            'data' => City::select('pk','city_name')->where(['state_master_pk'=>$stateId,'active_inactive'=>1])->orderBy('city_name')->get(),
        ]);
    }

}