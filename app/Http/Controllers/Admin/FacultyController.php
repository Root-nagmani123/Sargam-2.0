<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyRequest;
use App\Http\Requests\FacultyUpdateRequest;
use App\Models\{Country, State, City, District, FacultyMaster, FacultyQualificationMap, FacultyExperienceMap, FacultyExpertiseMaster, FacultyExpertiseMap, FacultyTypeMaster};
use App\DataTables\FacultyDataTable;
use Maatwebsite\Excel\Facades\Excel;
class FacultyController extends Controller
{
    public function index(FacultyDataTable $dataTable)
    {
        return $dataTable->render('admin.faculty.index');
    }

    public function create()
    {
        $facultyTypeList    = FacultyTypeMaster::pluck('faculty_type_name', 'pk')->toArray();
        $faculties          = FacultyExpertiseMaster::where('active_inactive', 1)->pluck('expertise_name', 'pk')->toArray();
        $country            = Country::pluck('country_name', 'pk')->toArray();
        $state              = State::pluck('state_name', 'pk')->toArray();
        $district           = District::pluck('district_name', 'pk')->toArray();
        $city               = City::pluck('city_name', 'pk')->toArray();
        
        $years = [];
        for ($i = date('Y'); $i >= 1950; $i--) {
            $years[$i] = $i;
        }

        return view("admin.faculty.create", compact('faculties', 'country', 'state', 'city', 'district', 'facultyTypeList', 'years'));
    }

    public function store(FacultyRequest $request)
    { //FacultyRequest

        try {


            DB::beginTransaction();

            # Step : 1
            // Store Faculty Details

            $facultyDetails = [
                'faculty_type' => $request->facultyType,
                'first_name' => $request->firstName,
                'middle_name' => $request->middlename,
                'last_name' => $request->lastname,
                'full_name' => $request->fullname,
                'gender' => $request->gender,
                'landline_no' => $request->landline,
                'mobile_no' => $request->mobile,
                'country_master_pk' => $request->country,
                'state_master_pk' => $request->state,
                'state_district_mapping_pk' => $request->district,
                'city_master_pk' => $request->city,
                'email_id' => $request->email,
                'alternate_email_id' => $request->alternativeEmail,
                'residence_address' => $request->residence_address,
                'permanent_address' => $request->permanent_address,

                // Store Bank Details
                'bank_name' => $request->bankname,
                'Account_No' => $request->accountnumber,
                'IFSC_Code' => $request->ifsccode,
                'PAN_No' => $request->pannumber,
            ];


            if ($request->hasFile('photo')) {
                $facultyDetails['photo_uplode_path'] = $request->file('photo')->store('faculty/faculty_photos', 'public');
            }

            if ($request->hasFile('document')) {
                $facultyDetails['Doc_uplode_path'] = $request->file('document')->store('faculty/faculty_documents', 'public');
            }

            if ($request->hasFile('researchpublications')) {
                $facultyDetails['Rech_Publi_Upload_path'] = $request->file('researchpublications')->store('faculty/research_publications', 'public');
            }

            if ($request->hasFile('professionalmemberships')) {
                $facultyDetails['Professional_Memberships_doc_upload_path'] = $request->file('professionalmemberships')->store('faculty/professional_memberships', 'public');
            }

            if ($request->hasFile('recommendationdetails')) {
                $facultyDetails['Reference_Recommendation'] = $request->file('recommendationdetails')->store('faculty/recommendation_details', 'public');
            }

            // Joining Date
            $facultyDetails['joining_date'] = Carbon::parse($request->joiningdate);

            $facultyDetails['created_by'] = Auth::id();
            $facultyDetails['faculty_sector'] = $request->current_sector;

            $facultyDetails['last_update'] = now();
            $facultyDetails['active_inactive'] = 1;

            $faculty = FacultyMaster::create($facultyDetails);

            $this->generateFacultyCode($faculty, $request->facultyType);

            if ($faculty) {

                # Step : 2

                $degreeDetails = [];

                if (!empty($request->degree) && $request->degree[0] != null) {

                    foreach ($request->degree as $key => $degree) {

                        $degreeDetails[] = [
                            'Degree_name' => $degree,
                            'University_Institution_Name' => $request->university_institution_name[$key],
                            'Year_of_passing' => $request->year_of_passing[$key],
                            'Percentage_CGPA' => $request->percentage_CGPA[$key],
                            'faculty_master_pk' => $faculty->pk,
                        ];

                        if ($request->hasFile('certificate')) {
                            $degreeDetails[$key]['Certifcates_upload_path'] = $request->file('certificate')[$key]->store('faculty/certificates', 'public');
                        }
                    }

                    FacultyQualificationMap::insert($degreeDetails);
                }

                # Step : 3
                $experienceDetails = [];

                if (!empty($request->experience) && $request->experience[0] != null) {
                    foreach ($request->experience as $key => $experience) {

                        $experienceDetails[] = [
                            'Years_Of_Experience' => $experience,
                            'Specialization' => $request->specialization[$key],
                            'pre_Institutions' => $request->institution[$key],
                            'Position_hold' => $request->position[$key],
                            'duration' => $request->duration[$key], // 
                            'Nature_of_Work' => $request->work[$key],
                            'faculty_master_pk' => $faculty->pk
                        ];
                    }

                    FacultyExperienceMap::insert($experienceDetails);
                }

                # Step : 4
                $expertiseDetails = [];

                if (!empty($request->faculties) && $request->faculties[0] != null) {
                    foreach ($request->faculties as $key => $expertise) {

                        $expertiseDetails[] = [
                            'faculty_master_pk' => $faculty->pk,
                            'faculty_expertise_pk' => $expertise,
                            'created_by' => 1,
                            'created_date' => now(),
                            'updated_date' => now(),
                        ];
                    }

                    FacultyExpertiseMap::insert($expertiseDetails);
                }
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Faculty created successfully',
                'data' => $faculty
            ]);

            // return redirect()->route('faculty.index')->with('success', 'Faculty created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            dump($e->getLine());
            dd('' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }


    public function edit(Request $request, $id)
    {
        $faculty = FacultyMaster::with(['facultyExpertiseMap', 'facultyExperienceMap', 'facultyQualificationMap'])->find(decrypt($id));
        if (!$faculty) {
            return redirect()->route('faculty.index')->with('error', 'Faculty not found');
        }
        $faculties  = FacultyExpertiseMaster::where('active_inactive', 1)->pluck('expertise_name', 'pk')->toArray();
        $country    = Country::pluck('country_name', 'pk')->toArray();
        $state      = State::pluck('state_name', 'pk')->toArray();
        $district   = District::pluck('district_name', 'pk')->toArray();
        $city       = City::pluck('city_name', 'pk')->toArray();
        $years = [];
        for ($i = date('Y'); $i >= 1950; $i--) {
            $years[$i] = $i;
        }

        $facultExpertise = $faculty->facultyExpertiseMap->isNotEmpty() ? $faculty->facultyExpertiseMap->pluck('faculty_expertise_pk')->toArray() : [];
        return view('admin.faculty.edit', compact('faculties', 'faculty', 'country', 'state', 'district', 'city', 'facultExpertise', 'years'));
    }

    public function update(FacultyUpdateRequest $request)
    {
        try {
            DB::beginTransaction();

            # Step : 1
            // Store Faculty Details

            $facultyDetails = [
                'faculty_type'  => $request->facultyType,
                'first_name'    => $request->firstName,
                'middle_name'   => $request->middlename,
                'last_name'     => $request->lastname,
                'full_name'     => $request->fullname,
                'gender'        => $request->gender,
                'landline_no'   => $request->landline,
                'mobile_no'        => $request->mobile,
                'country_master_pk' => $request->country,
                'state_master_pk'   => $request->state,
                'state_district_mapping_pk' => $request->district,
                'city_master_pk'        => $request->city,
                'email_id'              => $request->email,
                'alternate_email_id'    => $request->alternativeEmail,
                'residence_address'     => $request->residence_address,
                'permanent_address'     => $request->permanent_address,

                // Store Bank Details
                'bank_name'             => $request->bankname,
                'Account_No'            => $request->accountnumber,
                'IFSC_Code'             => $request->ifsccode,
                'PAN_No'                => $request->pannumber,
            ];


            if ($request->hasFile('photo')) {
                $facultyDetails['photo_uplode_path'] = $request->file('photo')->store('faculty/faculty_photos', 'public');
            }

            if ($request->hasFile('document')) {
                $facultyDetails['Doc_uplode_path'] = $request->file('document')->store('faculty/faculty_documents', 'public');
            }

            if ($request->hasFile('researchpublications')) {
                $facultyDetails['Rech_Publi_Upload_path'] = $request->file('researchpublications')->store('faculty/research_publications', 'public');
            }

            if ($request->hasFile('professionalmemberships')) {
                $facultyDetails['Professional_Memberships_doc_upload_path'] = $request->file('professionalmemberships')->store('faculty/professional_memberships', 'public');
            }

            if ($request->hasFile('recommendationdetails')) {
                $facultyDetails['Reference_Recommendation'] = $request->file('recommendationdetails')->store('faculty/recommendation_details', 'public');
            }

            // Joining Date
            $facultyDetails['joining_date'] = Carbon::parse($request->joiningdate);
            $facultyDetails['created_by']   = Auth::id();
            $facultyDetails['faculty_sector'] = $request->current_sector;

            $facultyDetails['last_update']   = now();
            $facultyDetails['active_inactive'] = 1;

            $faculty = FacultyMaster::find($request->faculty_id);
            if (!$faculty) {
                return redirect()->route('faculty.index')->with('error', 'Faculty not found');
            }
            $faculty->update($facultyDetails);

            $this->generateFacultyCode($faculty, $request->facultyType);

            if ($faculty) {

                # Step : 2

                $degreeDetails = [];

                if (!empty($request->degree) && $request->degree[0] != null) {

                    // Delete old qualifications
                    // FacultyQualificationMap::where('faculty_master_pk', $faculty->pk)->delete();

                    // what we do if user only open edit page and click on save/submit button
                    if (empty($request->degree[0])) {
                        return redirect()->route('faculty.index')->with('error', 'Please add at least one qualification');
                    }

                    foreach ($faculty->facultyQualificationMap as $oldQualification) {
                        if ($oldQualification->Certifcates_upload_path) {
                            Storage::disk('public')->delete($oldQualification->Certifcates_upload_path);
                        }
                    }

                    $faculty->facultyQualificationMap()->delete();

                    foreach ($request->degree as $key => $degree) {

                        $degreeDetails[] = [
                            'Degree_name' => $degree,
                            'University_Institution_Name' => $request->university_institution_name[$key],
                            'Year_of_passing' => $request->year_of_passing[$key],
                            'Percentage_CGPA' => $request->percentage_CGPA[$key],
                            'faculty_master_pk' => $faculty->pk,
                        ];

                        if ($request->hasFile('certificate')) {
                            $degreeDetails[$key]['Certifcates_upload_path'] = $request->file('certificate')[$key]->store('faculty/certificates', 'public');
                        }
                    }

                    FacultyQualificationMap::insert($degreeDetails);
                }

                # Step : 3
                $experienceDetails = [];

                if (!empty($request->experience) && $request->experience[0] != null) {

                    $faculty->facultyExperienceMap()->delete();

                    foreach ($request->experience as $key => $experience) {

                        $experienceDetails[] = [
                            'Years_Of_Experience' => $experience,
                            'Specialization' => $request->specialization[$key],
                            'pre_Institutions' => $request->institution[$key],
                            'Position_hold' => $request->position[$key],
                            'duration' => $request->duration[$key], // 
                            'Nature_of_Work' => $request->work[$key],
                            'faculty_master_pk' => $faculty->pk
                        ];
                    }

                    FacultyExperienceMap::insert($experienceDetails);
                }

                # Step : 4
                $expertiseDetails = [];

                # Delete First Then Insert

                if (!empty($request->faculties) && $request->faculties[0] != null) {

                    // Delete old expertise
                    FacultyExpertiseMap::where('faculty_master_pk', $faculty->pk)->delete();

                    foreach ($request->faculties as $key => $expertise) {

                        $expertiseDetails[] = [
                            'faculty_master_pk' => $faculty->pk,
                            'faculty_expertise_pk' => $expertise,
                            'created_by' => 1,
                            'created_date' => now(),
                            'updated_date' => now(),
                        ];
                    }

                    FacultyExpertiseMap::insert($expertiseDetails);
                }
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Faculty created successfully',
                'data' => $faculty
            ]);

            // return redirect()->route('faculty.index')->with('success', 'Faculty created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            dump($e);
            dd('' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }



    }
    function generateFacultyCode($faculty, $facultyType)
    {
        $prefix = FacultyTypeMaster::where('pk', $facultyType)->pluck('shot_faculty_type_name')->first();

        // Fetch latest code with this prefix
        $latestFaculty = FacultyMaster::where('faculty_code', 'like', $prefix . '-%')
            ->orderByDesc('faculty_code')
            ->first();

        if ($latestFaculty && preg_match('/\d+$/', $latestFaculty->faculty_code, $matches)) {
            $nextNumber = (int) $matches[0] + 1;
        } else {
            $nextNumber = 1;
        }

        $facultyCode = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $faculty->faculty_code = $facultyCode;
        $faculty->save(); // or update() if you prefer
    }

    function show(String $id)
    {
        $faculty = FacultyMaster::with([
            'cityMaster:pk,city_name',
            'stateMaster:Pk,state_name',
            'countryMaster:pk,country_name',
            'districtMaster:pk,district_name',
            'facultyTypeMaster:pk,faculty_type_name',
            'facultyExpertiseMap.facultyExpertise:pk,expertise_name', 
            'facultyExpertiseMap:faculty_master_pk,faculty_expertise_pk',
            'facultyExperienceMap:pk,Years_Of_Experience,specialization,pre_Institutions,Position_hold,duration,Nature_of_Work,faculty_master_pk', 
            'facultyQualificationMap:faculty_master_pk,Degree_name,University_Institution_Name,Year_of_passing,Percentage_CGPA'
        ])->find(decrypt($id));
        
        if (!$faculty) {
            return redirect()->route('faculty.index')->with('error', 'Faculty not found');
        }
        return view('admin.faculty.show', compact('faculty'));
    }

    function excelExportFaculty()
    {
        return Excel::download(new \App\Exports\FacultyExport(), 'faculty_list_'.time().'.xlsx');
    }
}