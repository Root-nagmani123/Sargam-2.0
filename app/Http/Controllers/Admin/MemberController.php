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
    


    // public function loadStep(Request $request)
    // {
    //     $step = $request->input('step', 1);
        
    //     switch ($step) {
    //         case 1:
    //             return view('admin.member.steps.step1')->render();
    //         case 2:
    //             return view('admin.member.steps.step2')->render();
    //         case 3:
    //             return view('admin.member.steps.step3')->render();
    //         case 4:
    //             return view('admin.member.steps.step4')->render();
    //         case 5:
    //             return view('admin.member.steps.step5')->render();
    //         default:
    //             return response()->json(['error' => 'Invalid step'], 400);
    //     }
    // }

    public function loadStep($step)
{
    return view("admin.member.steps.step{$step}");
}


    public function step1(Request $request)
    {
        return view('admin.member.steps.step1')->render();
    }
    
    public function step1Store(StoreMemberStep1Request $request)
    {
        return view('admin.member.steps.step1')->render();
    }

    public function step2()
    {
        // Save step 1 data
        
        return view('admin.member.steps.step2')->render();
    }

    public function step3()
    {
        return view('admin.member.steps.step3')->render();
    }
    

    public function step4()
    {
        return view('admin.member.steps.step4')->render();
    }

    public function step5()
    {
        return view('admin.member.steps.step5')->render();
    }
    

    // public function store(Request $request)
    // {
    //     return view('admin.member.store');
    // }

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
