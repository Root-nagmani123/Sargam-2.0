<?php

namespace Modules\Protocol\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
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

    public function getCities(Request $request, $stateId)
    {
        $cities = City::select('pk','city_name')->where(['state_master_pk'=>$stateId,'active_inactive'=>1])->orderBy('city_name')->get();

        // city_master has one "Other" row per district; list a single "Other" at the end,
        // keeping the one already saved on the record being edited (?selected=<pk>)
        [$others, $named] = $cities->partition(fn ($city) => strtolower(trim($city->city_name)) === 'other');
        $other = $others->firstWhere('pk', (int) $request->query('selected')) ?? $others->sortBy('pk')->first();

        return response()->json([
            'success' => true,
            'data' => $named->values()->when($other, fn ($list) => $list->push($other)),
        ]);
    }

}