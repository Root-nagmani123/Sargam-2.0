<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BuildingMaster;

class ApiController extends Controller
{
    public function getBuilding()
    {
        $buildings = BuildingMaster::where('active_inactive', 1)->get(['pk', 'building_name']);
        return response()->json($buildings);
    }
}
