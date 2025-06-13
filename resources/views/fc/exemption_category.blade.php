@extends('fc.layouts.master')

@section('title', 'Exemption Category - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<!-- Main Content Box -->

<main style="flex: 1;">
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" style="font-size: 20px;">Home</li>
                <li class="breadcrumb-item" aria-current="page" style="font-size: 20px;">Exemption Category</li>
            </ol>
        </nav>
        <div class="text-center mt-5">
            <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Select Exemption Category</h4>
            <div class="col-8 mx-auto">
                <p class="text-muted" style="font-size: 16px;">Choose the appropriate exemption category based on your
                    circumstances. Each
                    category has specific requirements and documentation needs.</p>
            </div>
        </div>
        <div class="row mt-5 g-4">
            <div class="col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="icon-circle" style="background-color: #e5f2ff;">
                            <i class="material-icons menu-icon fs-2" style="color: #2563eb;">school</i>
                        </div>
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;font-size: 20px;">
                            Exemption for CSE
                            Mains
                            2024</h5>
                        <span class="text-muted">For candidates appearing in Civil Services Mains Examination
                            2024</span>
                             <a href="{{ route('fc.exemption_application')}}" class="btn btn-success custom-btn"
                            style="background-color: #2563eb; border: #2563eb;">Apply for Exemption</a>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="icon-circle" style="background-color: #dcfce7;">
                            <i class="material-icons menu-icon fs-2" style="color: #16a32a;">article</i>
                        </div>
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;">Already Attended
                            Foundation Course</h5>
                        <span class="text-muted">For officers who have previously completed the Foundation Course</span>
                         <a href="{{ route('fc.exemption_application')}}" class="btn btn-success custom-btn"
                            style="background-color: #16a32a; border: #16a32a;">Apply for Exemption</a>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="icon-circle" style="background-color: #fee2e2;">
                            <i class="material-icons menu-icon fs-2" style="color: #dc2626;">medical_services</i>
                        </div>
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;">Medical Grounds</h5>
                        <span class="text-muted">For candidates unable to attend due to medical reasons</span>
                        <a href="{{ route('fc.exemption_application')}}" class="btn btn-success custom-btn"
                            style="background-color: #dc2626; border: #dc2626;">Apply for Exemption</a>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-body text-center">
                         <div class="icon-circle" style="background-color: #ffedd5;">
                            <i class="material-icons menu-icon fs-2" style="color: #ea580c;">person_remove</i>
                        </div>
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;">Opting Out After Registration</h5>
                        <span class="text-muted">For candidates who wish to withdraw after initial registration</span>
                        <a href="{{ route('fc.exemption_application')}}" class="btn btn-success custom-btn"
                            style="background-color: #ea580c; border: #ea580c;">Apply for Exemption</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="notice-box mt-4">
            <p class="fw-bold" style="color: #004a93;font-size: 24px;">Important Notice:</p>
            <div class="row">
                <div class="col-6">
                    <p class="fw-bold" style="color: #004a93;font-size: 14px;">Required Information</p>
                    <ul>
                        <li style="color: #004a93; font-size: 14px;">Valid reason for exemption</li>
                        <li style="color: #004a93; font-size: 14px;">Supporting documents</li>
                        <li style="color: #004a93; font-size: 14px;">Contact information</li>
                    </ul>
                </div>
                <div class="col-6">
                    <p class="fw-bold" style="color: #004a93;font-size: 14px;">Required Information</p>
                    <ul>
                        <li style="color: #004a93; font-size: 14px;">Valid reason for exemption</li>
                        <li style="color: #004a93; font-size: 14px;">Supporting documents</li>
                        <li style="color: #004a93; font-size: 14px;">Contact information</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection