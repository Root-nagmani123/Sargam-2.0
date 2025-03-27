@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Edit Faculty</h4>
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
            <h4 class="card-title mb-3">Edit Faculty</h4>
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
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Schoolname" name="Schoolname"
                                    placeholder="School Name">
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Age" name="Age" placeholder="Age">
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Degree" name="Degree" placeholder="Degree">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <select class="form-select" id="educationDate" name="educationDate">
                                    <option>Date</option>
                                    <option value="2015">2015</option>
                                    <option value="2016">2016</option>
                                    <option value="2023">2023</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="Schoolname" class="form-label"></label>
                            <div class="mb-3">
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
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Schoolname" name="Schoolname"
                                    placeholder="School Name">
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Age" name="Age" placeholder="Age">
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Degree" name="Degree" placeholder="Degree">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <select class="form-select" id="educationDate" name="educationDate">
                                    <option>Date</option>
                                    <option value="2015">2015</option>
                                    <option value="2016">2016</option>
                                    <option value="2023">2023</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-2">
                            <label for="Schoolname" class="form-label"></label>
                            <div class="mb-3">
                                <button onclick="experience_fields();" class="btn btn-success fw-medium" type="button">
                                    <i class="material-icons menu-icon">add</i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>
                </div>
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
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Health</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Education</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Land Administration</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Waste Management</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Mobility</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Water</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Environment</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Financial Services</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Agriculture/farmers</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Gender</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Housing</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Law And Order</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Infrastructure</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Citizen Services /
                                            Governance</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Others</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3 gap-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                    <i class="material-icons menu-icon">send</i>
                        Update
                    </button>
                    <button class="btn btn-secondary hstack gap-6" type="submit">
                    <i class="material-icons menu-icon">arrow_back</i>
                        Back
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection