<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GroupMappingController extends Controller
{
    function index()
    {
        return view('admin.group_mapping.index');
    }


    function create()
    {
        return view('admin.group_mapping.create');
    }

    function store(Request $request)
    {
        try {
            $request->validate([
                'type_name' => 'required|string|max:255',
            ]);

            return redirect()->route('master.group.mapping.index')->with('success', 'Group Mapping created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
