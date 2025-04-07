@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Edit Member</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Member
                                </span>
                            </li> 
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-0">Edit Member</h4>
            <h6 class="card-subtitle mb-3"></h6>
            <hr>
            <div id="example-vertical" class="mt-5">
                <h3>Member Information</h3>
                <section id="steps-uid-5-p-0" role="tabpanel" aria-labelledby="steps-uid-5-h-0" class="body current"
                    aria-hidden="false">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="title">Title :</label>
                                <input type="text" class="form-control" id="title" placeholder="Mr./Ms./Dr./Prof. etc.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="firstName1">First Name :</label>
                                <input type="text" class="form-control" id="firstName1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="middleName1">Middle Name :</label>
                                <input type="text" class="form-control" id="middleName1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="lastName1">Last Name :</label>
                                <input type="text" class="form-control" id="lastName1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="fhname">Father's/Husband's Name :</label>
                                <input type="text" class="form-control" id="fhname">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="marital">Marital Status :</label>
                                <select class="form-select" id="marital" name="marital">
                                    <option value="">Select</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="gender">Gender :</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="caste">Caste Category :</label>
                                <select class="form-select" id="caste" name="caste">
                                    <option value="">Select</option>
                                    <option value="general">General</option>
                                    <option value="obc">OBC</option>
                                    <option value="sc">SC</option>
                                    <option value="st">ST</option>
                                    <option value="ews">EWS</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="height">Exact Height by Measurement (Without Shoes)
                                    :</label>
                                <input type="text" class="form-control" id="height">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="date1">Date of Birth :</label>
                                <input type="date" class="form-control" id="date1">
                            </div>
                        </div>
                    </div>
                </section>
                <h3>Employment Details</h3>
                <section id="steps-uid-5-p-0" role="tabpanel" aria-labelledby="steps-uid-5-h-0" class="body current"
                    aria-hidden="false">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="type">Employee Type :</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="id">Employee ID :</label>
                                <input type="text" class="form-control" id="id">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="group">Employee Group :</label>
                                <select class="form-select" id="group" name="group">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="designation ">Designation :</label>
                                <select class="form-select" id="designation" name="designation">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="userid">User ID :</label>
                                <input type="text" class="form-control" id="userid">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="section ">Section :</label>
                                <select class="form-select" id="section" name="section">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
                <h3>Role Assignment</h3>
                <section id="steps-uid-5-p-0" role="tabpanel" aria-labelledby="steps-uid-5-h-0" class="body current"
                    aria-hidden="false">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label" for="userrole">User Role :</label>
                                <select class="form-select" id="userrole" name="userrole">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label" for="role">Role Options :</label>
                                <div class="controls">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        data-validation-maxchecked-maxchecked="2"
                                                        data-validation-maxchecked-message="Don't be greedy!"
                                                        required="" class="form-check-input" id="customCheck4"
                                                        aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck4">Academy
                                                        Staff</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck5" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck5">Academy
                                                        Faculty</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck6" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck6">Guest</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        data-validation-maxchecked-maxchecked="2"
                                                        data-validation-maxchecked-message="Don't be greedy!"
                                                        required="" class="form-check-input" id="customCheck4"
                                                        aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck4">Academy
                                                        Staff</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck5" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck5">Academy
                                                        Faculty</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck6" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck6">Guest</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        data-validation-maxchecked-maxchecked="2"
                                                        data-validation-maxchecked-message="Don't be greedy!"
                                                        required="" class="form-check-input" id="customCheck4"
                                                        aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck4">Academy
                                                        Staff</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck5" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck5">Academy
                                                        Faculty</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck6" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck6">Guest</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        data-validation-maxchecked-maxchecked="2"
                                                        data-validation-maxchecked-message="Don't be greedy!"
                                                        required="" class="form-check-input" id="customCheck4"
                                                        aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck4">Academy
                                                        Staff</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck5" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck5">Academy
                                                        Faculty</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <div class="col-md-4">
                                            <fieldset>
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="styled_max_checkbox"
                                                        class="form-check-input" id="customCheck6" aria-invalid="false">
                                                    <label class="form-check-label" for="customCheck6">Guest</label>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <h3>Contact Information</h3>
                <section id="steps-uid-5-p-0" role="tabpanel" aria-labelledby="steps-uid-5-h-0" class="body current"
                    aria-hidden="false">
                    <p>Current Address</p>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="address">Address :</label>
                                <input type="text" name="address" id="address" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="country">Country :</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="state">State :</label>
                                <select class="form-select" id="state" name="state">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="city ">City :</label>
                                <input type="text" class="form-control" id="city">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="postal">Postal Code :</label>
                                <input type="text" class="form-control" id="postal">
                            </div>
                        </div>
                    </div>
                    <fieldset>
                        <div class="form-check py-2">
                            <input type="checkbox" name="styled_max_checkbox" data-validation-maxchecked-maxchecked="2"
                                data-validation-maxchecked-message="Don't be greedy!" required=""
                                class="form-check-input" id="customCheck4" aria-invalid="false">
                            <label class="form-check-label" for="customCheck4">Current & Permanent Address both are same</label>
                        </div>
                    </fieldset>
                    <p>Permanent Address</p>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="permanentaddress">Address :</label>
                                <input type="text" name="permanentaddress" id="permanentaddress" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="permanentcountry">Country :</label>
                                <select class="form-select" id="permanentcountry" name="permanentcountry">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="permanentstate">State :</label>
                                <select class="form-select" id="permanentstate" name="permanentstate">
                                    <option value="">Select</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="permanentcity ">City :</label>
                                <input type="text" class="form-control" id="permanentcity">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="permanentpostal">Postal Code :</label>
                                <input type="text" class="form-control" id="permanentpostal">
                            </div>
                        </div>
                    </div>
                    <p>Communication Details</p>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="personalemail">Personal Email :</label>
                                <input type="email" name="personalemail" id="personalemail" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="officialemail">Official Email :</label>
                               <input type="email" name="officialemail" id="officialemail" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="mnumber">Mobile Number :</label>
                                <input type="number" name="mnumber" id="mnumber" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="emergencynumber ">Emergency Contact Number :</label>
                                <input type="number" class="form-control" id="emergencynumber">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="landlinenumber">Landline Number :</label>
                                <input type="number" class="form-control" id="landlinenumber">
                            </div>
                        </div>
                    </div>
                </section>
                <h3>Additional Details</h3>
                <section id="steps-uid-5-p-0" role="tabpanel" aria-labelledby="steps-uid-5-h-0" class="body current"
                    aria-hidden="false">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="homeaddress">Home Address Data : (Optional)</label>
                                <input type="text" class="form-control" id="homeaddress">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="residencenumber">Residence Number :</label>
                                <input type="number" class="form-control" id="residencenumber">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="miscellaneous">Other Miscellaneous Fields :</label>
                                <input type="text" class="form-control" id="miscellaneous">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="picture">Upload Picture :</label>
                                <input type="file" class="form-control" id="picture">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="additionaldocument">Additional Document Upload :</label>
                                <input type="file" class="form-control" id="additionaldocument">
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection