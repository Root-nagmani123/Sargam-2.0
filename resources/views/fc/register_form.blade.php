@extends('fc.layouts.master')

@section('title', 'Registration - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<!-- Main Content Box -->
<style>
.sidebar {
    max-height: 100vh;
    background-color: transparent;
}

.sidebar .nav-pills .nav-link.active {
    font-weight: 500;
    background-color: #004a93;
    border: 1px solid #ddd;
    color: #fff;
    border-radius: 0.25rem;
    transition: background-color 0.3s, color 0.3s;
}

.sidebar .nav-link:hover {
    background-color: #004a93;
    color: #fff !important;
}

.sidebar .nav-link {
    color: #000;
    font-weight: 500;
    transition: background-color 0.3s, color 0.3s;
    background-color: #fff;
    border: 1px solid #ddd;
}
</style>

<main style="flex: 1;">
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Home</li>
                <li class="breadcrumb-item" aria-current="page" style="font-size: 20px;">Registration Form</li>
            </ol>
        </nav>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-4 col-lg-3 sidebar">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active mb-4" id="tab-1-tab" data-bs-toggle="pill" data-bs-target="#tab-1"
                        type="button" role="tab" aria-controls="tab-1" aria-selected="true">Descriptive Roll</button>
                    <button class="nav-link mb-4" id="tab-2-tab" data-bs-toggle="pill" data-bs-target="#tab-2"
                        type="button" role="tab" aria-controls="tab-2" aria-selected="true">Descriptive Roll II</button>
                    <button class="nav-link mb-4" id="tab-3-tab" data-bs-toggle="pill" data-bs-target="#tab-3"
                        type="button" role="tab" aria-controls="tab-3" aria-selected="true">Joining Instructions</button>
                    <button class="nav-link mb-4" id="tab-4-tab" data-bs-toggle="pill" data-bs-target="#tab-4"
                        type="button" role="tab" aria-controls="tab-4" aria-selected="true">Joining Documents</button>
                    <button class="nav-link mb-4" id="tab-5-tab" data-bs-toggle="pill" data-bs-target="#tab-5"
                        type="button" role="tab" aria-controls="tab-5" aria-selected="true">Bank Details</button>
                    <button class="nav-link mb-4" id="tab-6-tab" data-bs-toggle="pill" data-bs-target="#tab-6"
                        type="button" role="tab" aria-controls="tab-6" aria-selected="true">Health Risk Factors</button>
                    <button class="nav-link mb-4" id="tab-7-tab" data-bs-toggle="pill" data-bs-target="#tab-7"
                        type="button" role="tab" aria-controls="tab-7" aria-selected="true">Special Assistance</button>
                    <button class="nav-link mb-4" id="tab-8-tab" data-bs-toggle="pill" data-bs-target="#tab-8"
                        type="button" role="tab" aria-controls="tab-8" aria-selected="true">Vision Statements</button>
                    <button class="nav-link mb-4" id="tab-9-tab" data-bs-toggle="pill" data-bs-target="#tab-9"
                        type="button" role="tab" aria-controls="tab-9" aria-selected="true">Reports (Admin Only)</button>
                </div>
            </div>

            <!-- Form Content -->
            <div class="col-md-8 col-lg-9">
                <div class="tab-content" id="v-pills-tabContent">
                    <div class="tab-pane fade show active" id="tab-1" role="tabpanel" aria-labelledby="tab-1-tab">
                        <!-- Personal Details -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal Details
                                </h5>
                                <p class="text-muted mb-4">Basic personal information</p>
                                <form class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" placeholder="Enter first name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" placeholder="Enter last name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select">
                                            <option selected disabled>Select gender</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Marital Status</label>
                                        <select class="form-select">
                                            <option selected disabled>Select status</option>
                                            <option>Single</option>
                                            <option>Married</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nationality</label>
                                        <select class="form-select">
                                            <option selected disabled>Select state</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Religion</label>
                                        <select class="form-select">
                                            <option selected disabled>Select your Religion</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Background</label>
                                        <select class="form-select">
                                            <option selected disabled>Choose your background</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAN Number</label>
                                        <input type="text" class="form-control" placeholder="Enter PAN number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aadhaar Number</label>
                                        <input type="text" class="form-control" placeholder="Enter Aadhaar number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Passport</label>
                                        <input type="text" class="form-control" placeholder="Enter Passport Details">
                                    </div>
                                    <hr>
                                    <div class="mb-3 d-flex justify-content-end">
                                        <div class="d-flex align-items-center">
                                            <!-- Save Draft Button -->
                                            <button
                                                class="btn btn-outline-primary me-2 d-flex align-items-center justify-content-center"
                                                type="submit" style="color: #004a93; border: 1px solid #004a93;">
                                                <i class="material-icons me-2" style="color: #004a93;">save</i>
                                                Save Draft
                                            </button>

                                            <!-- Next Button -->
                                            <button
                                                class="btn btn-primary d-flex align-items-center justify-content-center"
                                                type="reset"
                                                style="background-color: #004a93; border: 1px solid #004a93;">
                                                Next
                                                <i class="material-icons ms-2" style="color: #fff;">arrow_forward</i>
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-2" role="tabpanel" aria-labelledby="tab-2-tab">
                        <!-- Personal Details -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal Details
                                </h5>
                                <p class="text-muted mb-4">Basic personal information</p>
                                <form class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" placeholder="Enter first name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" placeholder="Enter last name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select">
                                            <option selected disabled>Select gender</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Marital Status</label>
                                        <select class="form-select">
                                            <option selected disabled>Select status</option>
                                            <option>Single</option>
                                            <option>Married</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nationality</label>
                                        <select class="form-select">
                                            <option selected disabled>Select state</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Religion</label>
                                        <select class="form-select">
                                            <option selected disabled>Select your Religion</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Background</label>
                                        <select class="form-select">
                                            <option selected disabled>Choose your background</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAN Number</label>
                                        <input type="text" class="form-control" placeholder="Enter PAN number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aadhaar Number</label>
                                        <input type="text" class="form-control" placeholder="Enter Aadhaar number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Passport</label>
                                        <input type="text" class="form-control" placeholder="Enter Passport Details">
                                    </div>
                                    <hr>
                                    <div class="mb-3 d-flex justify-content-end">
                                        <div class="d-flex align-items-center">
                                            <!-- Save Draft Button -->
                                            <button
                                                class="btn btn-outline-primary me-2 d-flex align-items-center justify-content-center"
                                                type="submit" style="color: #004a93; border: 1px solid #004a93;">
                                                <i class="material-icons me-2" style="color: #004a93;">save</i>
                                                Save Draft
                                            </button>

                                            <!-- Next Button -->
                                            <button
                                                class="btn btn-primary d-flex align-items-center justify-content-center"
                                                type="reset"
                                                style="background-color: #004a93; border: 1px solid #004a93;">
                                                Next
                                                <i class="material-icons ms-2" style="color: #fff;">arrow_forward</i>
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-3" role="tabpanel" aria-labelledby="tab-3-tab">
                        <!-- Personal Details -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal Details
                                </h5>
                                <p class="text-muted mb-4">Basic personal information</p>
                                <form class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" placeholder="Enter first name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" placeholder="Enter last name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select">
                                            <option selected disabled>Select gender</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Marital Status</label>
                                        <select class="form-select">
                                            <option selected disabled>Select status</option>
                                            <option>Single</option>
                                            <option>Married</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nationality</label>
                                        <select class="form-select">
                                            <option selected disabled>Select state</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Religion</label>
                                        <select class="form-select">
                                            <option selected disabled>Select your Religion</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Background</label>
                                        <select class="form-select">
                                            <option selected disabled>Choose your background</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAN Number</label>
                                        <input type="text" class="form-control" placeholder="Enter PAN number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aadhaar Number</label>
                                        <input type="text" class="form-control" placeholder="Enter Aadhaar number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Passport</label>
                                        <input type="text" class="form-control" placeholder="Enter Passport Details">
                                    </div>
                                    <hr>
                                    <div class="mb-3 d-flex justify-content-end">
                                        <div class="d-flex align-items-center">
                                            <!-- Save Draft Button -->
                                            <button
                                                class="btn btn-outline-primary me-2 d-flex align-items-center justify-content-center"
                                                type="submit" style="color: #004a93; border: 1px solid #004a93;">
                                                <i class="material-icons me-2" style="color: #004a93;">save</i>
                                                Save Draft
                                            </button>

                                            <!-- Next Button -->
                                            <button
                                                class="btn btn-primary d-flex align-items-center justify-content-center"
                                                type="reset"
                                                style="background-color: #004a93; border: 1px solid #004a93;">
                                                Next
                                                <i class="material-icons ms-2" style="color: #fff;">arrow_forward</i>
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-4" role="tabpanel" aria-labelledby="tab-4-tab">
                        <!-- Personal Details -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal Details
                                </h5>
                                <p class="text-muted mb-4">Basic personal information</p>
                                <form class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" placeholder="Enter first name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" placeholder="Enter last name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select">
                                            <option selected disabled>Select gender</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Marital Status</label>
                                        <select class="form-select">
                                            <option selected disabled>Select status</option>
                                            <option>Single</option>
                                            <option>Married</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nationality</label>
                                        <select class="form-select">
                                            <option selected disabled>Select state</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Religion</label>
                                        <select class="form-select">
                                            <option selected disabled>Select your Religion</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Background</label>
                                        <select class="form-select">
                                            <option selected disabled>Choose your background</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAN Number</label>
                                        <input type="text" class="form-control" placeholder="Enter PAN number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aadhaar Number</label>
                                        <input type="text" class="form-control" placeholder="Enter Aadhaar number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Passport</label>
                                        <input type="text" class="form-control" placeholder="Enter Passport Details">
                                    </div>
                                    <hr>
                                    <div class="mb-3 d-flex justify-content-end">
                                        <div class="d-flex align-items-center">
                                            <!-- Save Draft Button -->
                                            <button
                                                class="btn btn-outline-primary me-2 d-flex align-items-center justify-content-center"
                                                type="submit" style="color: #004a93; border: 1px solid #004a93;">
                                                <i class="material-icons me-2" style="color: #004a93;">save</i>
                                                Save Draft
                                            </button>

                                            <!-- Next Button -->
                                            <button
                                                class="btn btn-primary d-flex align-items-center justify-content-center"
                                                type="reset"
                                                style="background-color: #004a93; border: 1px solid #004a93;">
                                                Next
                                                <i class="material-icons ms-2" style="color: #fff;">arrow_forward</i>
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-5" role="tabpanel" aria-labelledby="tab-5-tab">
                        <!-- Personal Details -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal Details
                                </h5>
                                <p class="text-muted mb-4">Basic personal information</p>
                                <form class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" placeholder="Enter first name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" class="form-control" placeholder="Enter last name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select">
                                            <option selected disabled>Select gender</option>
                                            <option>Male</option>
                                            <option>Female</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Marital Status</label>
                                        <select class="form-select">
                                            <option selected disabled>Select status</option>
                                            <option>Single</option>
                                            <option>Married</option>
                                            <option>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Nationality</label>
                                        <select class="form-select">
                                            <option selected disabled>Select state</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Religion</label>
                                        <select class="form-select">
                                            <option selected disabled>Select your Religion</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Background</label>
                                        <select class="form-select">
                                            <option selected disabled>Choose your background</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PAN Number</label>
                                        <input type="text" class="form-control" placeholder="Enter PAN number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aadhaar Number</label>
                                        <input type="text" class="form-control" placeholder="Enter Aadhaar number">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Passport</label>
                                        <input type="text" class="form-control" placeholder="Enter Passport Details">
                                    </div>
                                    <hr>
                                    <div class="mb-3 d-flex justify-content-end">
                                        <div class="d-flex align-items-center">
                                            <!-- Save Draft Button -->
                                            <button
                                                class="btn btn-outline-primary me-2 d-flex align-items-center justify-content-center"
                                                type="submit" style="color: #004a93; border: 1px solid #004a93;">
                                                <i class="material-icons me-2" style="color: #004a93;">save</i>
                                                Save Draft
                                            </button>

                                            <!-- Next Button -->
                                            <button
                                                class="btn btn-primary d-flex align-items-center justify-content-center"
                                                type="reset"
                                                style="background-color: #004a93; border: 1px solid #004a93;">
                                                Next
                                                <i class="material-icons ms-2" style="color: #fff;">arrow_forward</i>
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

@endsection