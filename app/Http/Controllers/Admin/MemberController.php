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
use App\Models\EmployeeMaster;

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
    private function saveStepData(Request $request, $step)
    {
        if($step == 1) {
            EmployeeMaster::create([
                'title'         => $request->title,
                'first_name'    => $request->first_name,
                'middle_name'   => $request->middle_name,
                'last_name'     => $request->last_name,
                'father_name'   => $request->father_husband_name,
                'marital_status'=> $request->marital_status,
                'gender'        => $request->gender,
                'caste_category_pk' => $request->caste_category,
                'height'        => $request->height,
                'dob'           => $request->date_of_birth,
            ]);
        }

        if($step == 2) {
            // dd($request->all());
            [
                'emp_type'      => $request->type, // Employee Type
                'emp_id'        => $request->id, // Employee ID
                'emp_group_pk'  => $request->group, // Employee Group
                'designation_master_pk' =>$request->designation, // Designation
                '' =>$request->userid,
                'department_master_pk' => $request->section // department
            ];
        }
    }
    public function validateStep(Request $request, $step)
    {
        $rules = [];

        switch ($step) {
            case 1:
                $validator = Validator::make($request->all(), (new StoreMemberStep1Request())->rules());
                $this->saveStepData($request, 1);
                break;
            case 2:
                $validator = Validator::make($request->all(), (new StoreMemberStep2Request())->rules());
                $this->saveStepData($request, 2);
                break;
            case 3:
                $validator = Validator::make($request->all(), (new StoreMemberStep3Request())->rules());
                $this->saveStepData($request, 3);
                break;
            case 4:
                $validator = Validator::make($request->all(), (new StoreMemberStep4Request())->rules());
                $this->saveStepData($request, 4);
                break;
            case 5:
                $validator = Validator::make($request->all(), (new StoreMemberStep5Request())->rules());
                break;
        }



        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }

        // return response()->json(['message' => 'Step validated'], 200);
    }


    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), (new StoreMemberStep5Request())->rules());

        if ($validator->fails()) {
            // echo "inside the if"; die;
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Store All Step Data in the database with transaction
        // Make this code it clearner and more readable

        // Optional: Save photo/file if any
        // $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('members', 'public');
        }

        if ($request->hasFile('signature')) {
            $signaturePath = $request->file('signature')->store('members', 'public');
        }

        

        return response()->json(['message' => 'Member successfully created']);
    }


    public function loadStep($step)
    {
        return view("admin.member.steps.step{$step}");
    }
}
