@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Create Faculty</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Faculty
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
            <h4 class="card-title mb-3">Create Faculty</h4>
            <hr>
            <form>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="facultytype">Faculty Type :</label>
                            <select class="form-select" id="facultytype" name="facultytype">
                                <option value="">Select</option>
                                <option value="internal">Internal</option>
                                <option value="guest">Guest</option>
                                <option value="research">Research</option>
                            </select>
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
                            <label class="form-label" for="landline">Landline Number :</label>
                            <input type="text" class="form-control" id="landline">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="mobile">Mobile Number :</label>
                            <input type="text" class="form-control" id="mobile">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="country">Country :</label>
                            <select class="form-select" id="country" name="country">
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
                            <label class="form-label" for="state">State :</label>
                            <select class="form-select" id="state" name="state">
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
                            <label class="form-label" for="city">City :</label>
                            <select class="form-select" id="city" name="city">
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
                            <label class="form-label" for="email">Email :</label>
                            <input type="email" class="form-control" id="email">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="altemail">Alternate Email :</label>
                            <input type="email" class="form-control" id="altemail">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="photo">Photo upload :</label>
                            <input type="file" class="form-control" id="photo">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="document">Document upload :</label>
                            <input type="file" class="form-control" id="document">
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="card-subtitle mb-3 mt-3">Qualification Details</h4>
                    <hr>
                    <div id="education_fields" class="my-4"></div>
                    <div class="row" id="education_fields">
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Degree :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Schoolname" name="Schoolname"
                                    placeholder="Degree Name">
                                    <small>Bachelors, Masters, PhD</small>
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">University/Institution Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Age" name="Age" placeholder="University/Institution Name">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Year of Passing :</label>
                            <div class="mb-3">
                                <input type="date" class="form-control" id="Degree" name="Degree" placeholder="Year of Passing">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Percentage/CGPA :</label>
                            <div class="mb-3">
                                <input type="text" name="percentage" placeholder="Percentage/CGPA" id="percentage" class="form-control">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="certificate" class="form-label">Certificates/Documents Upload :</label>
                            <div class="mb-3">
                                <input type="file" name="certificate" placeholder="Certificates/Documents Upload" id="certificate" class="form-control">
                            </div>
                        </div>
                        <div class="col-9">
                            <label for="Schoolname" class="form-label"></label>
                            <div class="mb-3 float-end">
                                <button onclick="education_fields();" class="btn btn-success fw-medium" type="button">
                                    <i class="material-icons menu-icon">add</i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <h4 class="card-subtitle mb-3 mt-3">Experience Details</h4>
                    <hr>
                    <div id="experience_fields" class="my-4"></div>
                    <div class="row" id="experience_fields">
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Years of Experience :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="experience" name="experience"
                                    placeholder="Years of Experience">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Area of Specialization :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="specialization" name="specialization" placeholder="Area of Specialization">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Previous Institutions :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="institution" name="institution" placeholder="Previous Institutions">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Position Held :</label>
                            <div class="mb-3">
                                <input type="text" name="position" placeholder="Position Held" id="position" class="form-control">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Duration :</label>
                            <div class="mb-3">
                                <input type="text" name="duration" placeholder="Duration" id="duration" class="form-control">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">Nature of Work :</label>
                            <div class="mb-3">
                                <input type="text" name="work" placeholder="Nature of Work" id="work" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="Schoolname" class="form-label"></label>
                            <div class="mb-3 float-end">
                                <button onclick="experience_fields();" class="btn btn-success btn-sm" type="button">
                                    <i class="material-icons menu-icon">add</i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="card-subtitle mb-3 mt-3">Bank Details</h4>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <label for="bankname" class="form-label">Bank Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="bankname" name="bankname"
                                    placeholder="Bank Name">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="accountnumber" class="form-label">Account Number :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="accountnumber" name="accountnumber" placeholder="Account Number">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="ifsccode" class="form-label">IFSC Code :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="ifsccode" name="ifsccode" placeholder="IFSC Code">
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="pannumber" class="form-label">PAN Number :</label>
                            <div class="mb-3">
                                <input type="text" name="pannumber" placeholder="PAN Number" id="pannumber" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="card-subtitle mb-3 mt-3">Other information</h4>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <label for="researchpublications" class="form-label">Research Publications :</label>
                            <div class="mb-3">
                                <input type="file" class="form-control" id="researchpublications" name="researchpublications"
                                    placeholder="Research Publications ">
                                    <small>Please upload your research publications, if any</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="professionalmemberships" class="form-label">Professional Memberships :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="professionalmemberships" name="professionalmemberships" placeholder="Professional Memberships">
                                    <small>Please upload your professional memberships, if any</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="recommendationdetails" class="form-label">Reference/Recommendation Details :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="recommendationdetails" name="recommendationdetails" placeholder="Reference/Recommendation Details">
                                    <small>Please upload your reference/recommendation details, if any</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="joiningdate" class="form-label">Joining Date :</label>
                            <div class="mb-3">
                                <input type="text" name="joiningdate" placeholder="Joining Date" id="joiningdate" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <label for="sector" class="form-label">Current Sector :</label>
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input success" type="radio" name="radio-solid-success"
                                    id="success-radio" value="option1">
                                <label class="form-check-label" for="success-radio">Goverment Sector</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input success" type="radio" name="radio-solid-success"
                                    id="success2-radio" value="option1" checked="">
                                <label class="form-check-label" for="success2-radio">Private Sector</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="expertise" class="form-label">Area of Expertise :</label>
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Energy</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                    <i class="material-icons menu-icon">send</i>
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection