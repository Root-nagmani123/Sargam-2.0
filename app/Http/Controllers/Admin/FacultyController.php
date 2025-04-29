<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Country, State, City};
use App\Models\District;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\FacultyRequest;
use App\Models\{FacultyMaster, FacultyQualificationMap, FacultyExperienceMap, FacultyExpertiseMaster, FacultyExpertiseMap};
use Storage;
use Illuminate\Support\Facades\DB;
use Auth;
class FacultyController extends Controller
{
    public function index() {
        $faculties = FacultyMaster::all();
        return view("admin.faculty.index", compact('faculties'));
    }

    public function create() {
        $faculties = FacultyExpertiseMaster::pluck('expertise_name', 'pk')->toArray();
        $country = Country::pluck('country_name', 'pk')->toArray();
        $state = State::pluck('state_name', 'pk')->toArray();
        $district = District::pluck('district_name', 'pk')->toArray();
        $city = City::pluck('city_name', 'pk')->toArray();
        
        return view("admin.faculty.create", compact('faculties', 'country', 'state', 'city', 'district'));
    }

    public function store(FacultyRequest $request) { //FacultyRequest
        
        try {
            // dd($request->all());
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

                // Store Bank Details
                'bank_name' => $request->bankname,
                'Account_No' => $request->accountnumber,
                'IFSC_Code' => $request->ifsccode,
                'PAN_No' => $request->pannumber,
                'research_publications' => $request->hasFile('researchpublications') ? $request->file('researchpublications')->store('faculty/research_publications') : null,
                'professional_memberships' => $request->hasFile('professionalmemberships') ? $request->file('professionalmemberships')->store('faculty/professional_memberships') : null,
                'recommendation_details' => $request->hasFile('recommendationdetails') ? $request->file('recommendationdetails')->store('faculty/recommendation_details') : null,
            ];

            
            if ($request->hasFile('photo')) {
                $facultyDetails['photo_uplode_path'] = $request->file('photo')->store('faculty/faculty_photos');
            }
            
            if ($request->hasFile('document')) {
                $facultyDetails['Doc_uplode_path'] = $request->file('document')->store('faculty/faculty_documents');
            }
            
            if ( $request->hasFile('researchpublications') ) {
                $facultyDetails['Rech_Publi_Upload_path'] = $request->file('researchpublications')->store('faculty/research_publications');
            }

            if ( $request->hasFile('professionalmemberships') ) {
                $facultyDetails['Professional_Memberships_doc_upload_path'] = $request->file('professionalmemberships')->store('faculty/professional_memberships');
            }

            if( $request->hasFile('recommendationdetails') ) {
                $facultyDetails['Reference_Recommendation'] = $request->file('recommendationdetails')->store('faculty/recommendation_details');
            }

            // Joining Date
            $facultyDetails['joining_date'] = Carbon::parse($request->joiningdate);

            $facultyDetails['created_by'] = Auth::id();
            $facultyDetails['faculty_sector'] = $request->current_sector;

            $facultyDetails['last_update'] = now();   
            $facultyDetails['active_deactive'] = 1;

            $faculty = FacultyMaster::create($facultyDetails);

            if( $faculty ) {

                # Step : 2

                $degreeDetails = [];
                
                if( !empty($request->degree) && $request->degree[0] != null ) {
                    
                    foreach( $request->degree as $key => $degree ) {

                        $degreeDetails[] = [
                            'Degree_name' => $degree,
                            'University_Institution_Name'   => $request->university_institution_name[$key],
                            'Year_of_passing'               => $request->year_of_passing[$key],
                            'Percentage_CGPA'               => $request->percentage_CGPA[$key],
                            'faculty_master_pk'             => $faculty->pk,
                        ];
                        
                        if ($request->hasFile('certificate')) {
                            $degreeDetails[$key]['Certifcates_upload_path'] = $request->file('certificate')[$key]->store('faculty/certificates');
                        }
                    }

                    FacultyQualificationMap::insert($degreeDetails);
                }

                # Step : 3
                $experienceDetails = []; 

                if( !empty($request->experience) && $request->experience[0] != null ) {
                    foreach( $request->experience as $key => $experience ) {

                        $experienceDetails[] = [
                            'Years_Of_Experience'   => $experience,
                            'Specialization'        => $request->specialization[$key],
                            'pre_Institutions'      => $request->institution[$key],
                            'Position_hold'         => $request->position[$key],
                            'duration'              => $request->duration[$key], // 
                            'Nature_of_Work'        => $request->work[$key],
                            'faculty_master_pk'     => $faculty->pk
                        ];
                    }

                    FacultyExperienceMap::insert($experienceDetails);
                }

                # Step : 4
                $expertiseDetails = [];

                if( !empty($request->faculties) && $request->faculties[0] != null ) {
                    foreach( $request->faculties as $key => $expertise ) {

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
        }
        catch (\Exception $e) {
            DB::rollBack();
            dump($e->getLine());
            dd(''. $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

}