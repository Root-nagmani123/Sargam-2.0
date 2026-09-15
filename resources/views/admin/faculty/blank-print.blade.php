@extends('admin.layouts.master')
@section('title', 'Faculty Details – Blank Form')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
{{-- The paper counterpart of admin/faculty/show.blade.php. Keep the two in step:
     every field here has a matching one on the detail view, so a sheet filled in
     by hand maps straight onto the record. --}}
<div class="container-fluid mst-page print-area">
    <x-print-letterhead title="Faculty Details — Blank Form" />
    <x-breadcrum title="Faculty Details — Blank Form" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm no-print"
                onclick="window.print()">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">print</i>
            <span>Print Form</span>
        </button>
    </x-breadcrum>

    {{-- Header: photograph box + the identifying fields --}}
    <div class="mst-hero">
        <div class="mst-blank-photo">Affix recent<br>passport-size<br>photograph</div>

        <div class="mst-hero__main">
            <div class="mst-facts">
                <div>
                    <span class="mst-fact__label">Full Name</span>
                    <span class="mst-blank-line"></span>
                </div>
                <div>
                    <span class="mst-fact__label">Faculty Code</span>
                    <span class="mst-blank-line"></span>
                </div>
                <div>
                    <span class="mst-fact__label">Faculty Type</span>
                    <span class="mst-blank-line"></span>
                </div>
                <div>
                    <span class="mst-fact__label">Faculty (PA)</span>
                    <span class="mst-blank-line"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Personal information --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Personal Information</h3>
        <div class="mst-facts">
            @foreach ([
                'Gender',
                'Mobile Number',
                'Email ID',
                'Landline Number',
                'Alternate Email',
                'Current Designation',
                'Current Department',
                'Location',
                'Residence Address',
                'Permanent Address',
            ] as $label)
                <div>
                    <span class="mst-fact__label">{{ $label }}</span>
                    <span class="mst-blank-line"></span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Qualifications --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Qualification Details</h3>
        <div class="table-responsive">
            <table class="mst-blank-table">
                <thead>
                    <tr>
                        <th scope="col">S. No.</th>
                        <th scope="col">Degree</th>
                        <th scope="col">University / Institution</th>
                        <th scope="col">Year of Passing</th>
                        <th scope="col">Percentage / CGPA</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 1; $i <= 3; $i++)
                        <tr>
                            <td>{{ $i }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </section>

    {{-- Experience --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Experience Details</h3>
        <div class="table-responsive">
            <table class="mst-blank-table">
                <thead>
                    <tr>
                        <th scope="col">S. No.</th>
                        <th scope="col">Years of Experience</th>
                        <th scope="col">Area of Specialisation</th>
                        <th scope="col">Previous Institution</th>
                        <th scope="col">Position Held</th>
                        <th scope="col">Duration</th>
                        <th scope="col">Nature of Work</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 1; $i <= 3; $i++)
                        <tr>
                            <td>{{ $i }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </section>

    {{-- Bank details --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Bank Details</h3>
        <div class="mst-facts">
            @foreach (['Bank Name', 'Account Number', 'IFSC Code', 'PAN Number'] as $label)
                <div>
                    <span class="mst-fact__label">{{ $label }}</span>
                    <span class="mst-blank-line"></span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Area of expertise --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Area of Expertise</h3>
        <div class="mst-facts">
            @for($i = 1; $i <= 8; $i++)
                <div>
                    <span class="mst-fact__label">{{ $i }}.</span>
                    <span class="mst-blank-line"></span>
                </div>
            @endfor
        </div>
    </section>

    {{-- Other information --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Other Information</h3>
        <div class="mst-facts">
            @foreach ([
                'Joining Date',
                'Current Sector',
                'Service',
                'Reference / Recommendation',
            ] as $label)
                <div>
                    <span class="mst-fact__label">{{ $label }}</span>
                    <span class="mst-blank-line"></span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Declaration: a paper form needs somewhere to sign --}}
    <section class="mst-card">
        <h3 class="mst-card__title">Declaration</h3>
        <p class="mst-fact__value mb-4">
            I certify that the information furnished above is true and correct to the best of my knowledge.
        </p>
        <div class="mst-facts">
            <div>
                <span class="mst-fact__label">Place</span>
                <span class="mst-blank-line"></span>
            </div>
            <div>
                <span class="mst-fact__label">Date</span>
                <span class="mst-blank-line"></span>
            </div>
            <div>
                <span class="mst-fact__label">Signature</span>
                <span class="mst-blank-line"></span>
            </div>
        </div>
    </section>
</div>

@endsection
