<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        return view('admin.member.index');
    }

    public function create()
    {
        return view('admin.member.create');
    }

    public function store(Request $request)
    {
        return view('admin.member.store');
    }

    public function show($id)
    {
        return view('admin.member.show');
    }

    public function edit($id)
    {
        return view('admin.member.edit');
    }

    public function update(Request $request, $id)
    {   
        return view('admin.member.update');
    }

    public function destroy($id)
    {
        return view('admin.member.destroy');
    }
    
    
    
    
    

}
