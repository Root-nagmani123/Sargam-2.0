@extends('admin.layouts.master')

@section('title', 'Faculty Details – Blank Form')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/faculty-detail-admin.css') }}?v={{ @filemtime(public_path('css/faculty-detail-admin.css')) ?: time() }}">
@endpush

@section('setup_content')

<div class="container-fluid faculty-detail-page pb-4">
    <x-breadcrum title="Print Blank Faculty Form" />

    <div class="fc-detail-card print-area">
        <div class="fc-detail-header">
            <div class="row fc-detail-grid g-3 align-items-center">
                <div class="col-md-2 text-center">
                    <div class="fc-detail-photo fc-detail-photo--blank mx-auto"></div>
                </div>
                <div class="col-md-10">
                    <div class="row fc-detail-grid g-3">
                        <div class="col-md-8">
                            <div class="fc-field-label">Full Name</div>
                            <div class="fc-field-value fc-field-value--blank"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="fc-field-label">Faculty Code</div>
                            <div class="fc-field-value fc-field-value--blank"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="fc-field-label">Faculty Type</div>
                            <div class="fc-field-value fc-field-value--blank"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Personal Information</div>
            <div class="row fc-detail-grid g-3">
                <div class="col-md-4">
                    <div class="fc-field-label">Gender</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Mobile Number</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Email ID</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Current Designation</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Current Department</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-4">
                    <div class="fc-field-label">Address</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Qualifications Details</div>
            @for ($i = 1; $i <= 2; $i++)
            <div class="fc-panel-block">
                <div class="row fc-detail-grid g-3">
                    <div class="col-md-6">
                        <div class="fc-field-label">Degree</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">University / Institution</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Year of Passing</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Percentage / CGPA</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Experience Details</div>
            @for ($i = 1; $i <= 2; $i++)
            <div class="fc-panel-block">
                <div class="row fc-detail-grid g-3">
                    <div class="col-md-6">
                        <div class="fc-field-label">Years of Experience</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Area of Specialisation</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Previous Institution</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="fc-field-label">Position Held</div>
                        <div class="fc-field-value fc-field-value--blank"></div>
                    </div>
                </div>
            </div>
            @endfor
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Bank Details</div>
            <div class="row fc-detail-grid g-3">
                <div class="col-md-6">
                    <div class="fc-field-label">Bank Name</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">Account Number</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">IFSC Code</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">PAN Number</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Area of Expertise</div>
            <div class="fc-expertise-list">
                @for ($i = 1; $i <= 6; $i++)
                <span class="fc-expertise-pill fc-expertise-pill--blank"></span>
                @endfor
            </div>
        </div>

        <div class="fc-section">
            <div class="fc-section-title">Other Information</div>
            <div class="row fc-detail-grid g-3">
                <div class="col-md-6">
                    <div class="fc-field-label">Joining Date</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
                <div class="col-md-6">
                    <div class="fc-field-label">Current Sector</div>
                    <div class="fc-field-value fc-field-value--blank"></div>
                </div>
            </div>
        </div>

        <div class="fc-detail-actions no-print">
            <a href="{{ route('faculty.index') }}" class="fc-btn-outline">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back
            </a>
            <button type="button" class="fc-btn-primary" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i>
                Print
            </button>
        </div>
    </div>
</div>

@endsection
