@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('setup_content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<style>
/* Choices.js + Bootstrap in programme-edit */
.programme-edit .choices { width: 100%; }
.programme-edit .choices__inner { min-height: 38px; padding: 0.375rem 2.25rem 0.375rem 0.75rem; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: var(--bs-border-radius); font-size: 1rem; }
.programme-edit .choices[data-type*="select-one"] .choices__inner { padding-bottom: 0.375rem; }
.programme-edit .choices.is-focused .choices__inner { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
.programme-edit .choices__list--dropdown { border: 1px solid var(--bs-border-color); border-radius: var(--bs-border-radius); z-index: 1050; }
.programme-edit .choices__list--dropdown .choices__item--selectable.is-highlighted { background-color: var(--bs-primary-bg-subtle); color: var(--bs-primary); }
.programme-edit .choices__input { background-color: var(--bs-body-bg); }
/* Programme Edit - Responsive */
@media (max-width: 991.98px) {
    .programme-edit .card-body { padding: 1.25rem; }
}

@media (max-width: 767.98px) {
    .programme-edit .container-fluid { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
    .programme-edit .card-body { padding: 1rem; }
    .programme-edit .card-title { font-size: 1.125rem; }
    .programme-edit .qualification-row .col-12,
    .programme-edit .experience-row .col-12 { margin-bottom: 0.5rem; }
    .programme-edit .expertise-grid .col-6 { flex: 0 0 50%; max-width: 50%; }
    .programme-edit .form-check-inline { margin-right: 0.5rem; }
    .programme-edit .form-check.form-check-inline { display: inline-flex; }
}

@media (max-width: 575.98px) {
    .programme-edit .container-fluid { padding-left: 0.375rem !important; padding-right: 0.375rem !important; }
    .programme-edit .card-body { padding: 0.75rem; }
    .programme-edit .expertise-grid .col-6 { flex: 0 0 100%; max-width: 100%; }
    .programme-edit .btn.hstack { width: 100%; justify-content: center; }
    .programme-edit .choices { width: 100% !important; }
}
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4 py-3 programme-edit">
    <x-breadcrum title="Programme" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Faculty</h4>
            <hr>
            <form>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="facultytype">Faculty Type :</label>
                            <select class="form-select choices-select" id="facultytype" name="facultytype">
                                <option value="">Select</option>
                                <option value="internal">Internal</option>
                                <option value="guest">Guest</option>
                                <option value="research">Research</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="firstName1">First Name :</label>
                            <input type="text" class="form-control" id="firstName1">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="middleName1">Middle Name :</label>
                            <input type="text" class="form-control" id="middleName1">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="lastName1">Last Name :</label>
                            <input type="text" class="form-control" id="lastName1">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="gender">Gender :</label>
                            <select class="form-select choices-select" id="gender" name="gender">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="landline">Landline Number :</label>
                            <input type="text" class="form-control" id="landline">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="mobile">Mobile Number :</label>
                            <input type="text" class="form-control" id="mobile">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="country">Country :</label>
                            <select class="form-select choices-select" id="country" name="country">
                                <option value="">Select</option>
                                <option value="general">General</option>
                                <option value="obc">OBC</option>
                                <option value="sc">SC</option>
                                <option value="st">ST</option>
                                <option value="ews">EWS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="state">State :</label>
                            <select class="form-select choices-select" id="state" name="state">
                                <option value="">Select</option>
                                <option value="general">General</option>
                                <option value="obc">OBC</option>
                                <option value="sc">SC</option>
                                <option value="st">ST</option>
                                <option value="ews">EWS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="city">City :</label>
                            <select class="form-select choices-select" id="city" name="city">
                                <option value="">Select</option>
                                <option value="general">General</option>
                                <option value="obc">OBC</option>
                                <option value="sc">SC</option>
                                <option value="st">ST</option>
                                <option value="ews">EWS</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="email">Email :</label>
                            <input type="email" class="form-control" id="email">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="altemail">Alternate Email :</label>
                            <input type="email" class="form-control" id="altemail">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="photo">Photo upload :</label>
                            <input type="file" class="form-control" id="photo">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
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
                    <div class="row g-2 qualification-row" id="education_fields">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Schoolname" name="Schoolname"
                                    placeholder="School Name">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Age" name="Age" placeholder="Age">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Degree" name="Degree" placeholder="Degree">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <select class="form-select choices-select" id="educationDate" name="educationDate">
                                    <option value="">Date</option>
                                    <option value="2015">2015</option>
                                    <option value="2016">2016</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
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
                    <div class="row g-2 experience-row" id="experience_fields">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Schoolname" name="Schoolname"
                                    placeholder="School Name">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Age" name="Age" placeholder="Age">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Degree" name="Degree" placeholder="Degree">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <select class="form-select choices-select" id="educationDateExp" name="educationDateExp">
                                    <option value="">Date</option>
                                    <option value="2015">2015</option>
                                    <option value="2016">2016</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
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
                            <div class="row expertise-grid">
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Energy</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Health</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Education</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Land Administration</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Waste Management</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Mobility</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Water</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Environment</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Financial Services</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Agriculture/farmers</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Gender</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Housing</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Law And Order</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Infrastructure</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="success-check"
                                            value="option1">
                                        <label class="form-check-label" for="success-check">Citizen Services /
                                            Governance</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
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
                <div class="mb-3 d-flex flex-column flex-sm-row justify-content-end gap-2">
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" type="submit">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script src="{{ asset('js/programme-edit.js') }}"></script>
@endpush