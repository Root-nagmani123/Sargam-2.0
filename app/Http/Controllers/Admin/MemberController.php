<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Member\{
    StoreMemberStep1Request,
    StoreMemberStep2Request,
    StoreMemberStep3Request,
    StoreMemberStep4Request,
    StoreMemberStep5Request,
};
use Illuminate\Support\Facades\Validator;

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

    public function validateStep(Request $request, $step)
{
    $rules = [];

    switch ($step) {
        case 1:
            $validator = Validator::make($request->all(), (new StoreMemberStep1Request())->rules());
            break;
        case 2:
            $validator = Validator::make($request->all(), (new StoreMemberStep2Request())->rules());
            break;
        case 3:
            $validator = Validator::make($request->all(), (new StoreMemberStep3Request())->rules());
            break;
        case 4:
            $validator = Validator::make($request->all(), (new StoreMemberStep4Request())->rules());
            break;
        case 5:
            $validator = Validator::make($request->all(), (new StoreMemberStep5Request())->rules());
            break;
    }

    

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    return response()->json(['message' => 'Step validated'], 200);
}

    
public function store(Request $request)
{
    $rules = [
        // Step 1: Member Info
        'name' => 'required|string|max:255',
        'dob' => 'required|date',

        // Step 2: Employment Details
        'designation' => 'required|string|max:255',
        'department' => 'required|string|max:255',

        // Step 3: Role Assignment
        'role' => 'required|in:admin,user,member',

        // Step 4: Contact Info
        'email' => 'required|email|unique:members,email',
        'phone' => 'required|digits:10',

        // Step 5: Additional
        'bio' => 'nullable|string|max:1000',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Optional: Save photo/file if any
    // $photoPath = null;
    // if ($request->hasFile('photo')) {
    //     $photoPath = $request->file('photo')->store('members', 'public');
    // }

    // Save the data
    Member::create([
        'name'        => $request->name,
        'dob'         => $request->dob,
        'designation' => $request->designation,
        'department'  => $request->department,
        'role'        => $request->role,
        'email'       => $request->email,
        'phone'       => $request->phone,
        'bio'         => $request->bio,
        // 'photo'       => $photoPath,
    ]);

    return response()->json(['message' => 'Member successfully created']);
}
    

    public function loadStep($step)
{
    return view("admin.member.steps.step{$step}");
}
}
