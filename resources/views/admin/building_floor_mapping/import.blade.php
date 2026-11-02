@extends('admin.layouts.master')

@section('title', 'Hostel Building Assign Student')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // old() is an array when the field came back as name[]: keep the default, not a 500.
    $asSelectedCourse = old('course_master_pk', session('selected_course'));
    $asSelectedCourse = is_scalar($asSelectedCourse) ? (string) $asSelectedCourse : '';
@endphp
<div class="container-fluid mst-page assign-student-page">

    <x-breadcrum title="Hostel Building Assign Student" />
    <x-session_message />

    <form action="{{ route('hostel.building.map.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Import Hostel Building Assign Student</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="course_master_pk" class="mst-form-label d-block">
                            Select Course <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="course_master_pk" id="course_master_pk"
                                class="form-select mst-control mst-searchable @error('course_master_pk') is-invalid @enderror"
                                data-placeholder="Select Course" required aria-required="true"
                                @error('course_master_pk') aria-describedby="course_master_pkError" @enderror>
                            <option value="">-- Select Course --</option>
                            @foreach ($courses as $pk => $name)
                                <option value="{{ $pk }}" @selected($asSelectedCourse === (string) $pk)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                            <span class="mst-field-error" id="course_master_pkError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="file" class="mst-form-label d-block">
                            Select Excel / CSV File <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv"
                               class="form-control mst-control @error('file') is-invalid @enderror"
                               required aria-required="true" aria-describedby="fileHelp">
                        @error('file')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted d-block mt-1" id="fileHelp">
                            Excel must have 2 columns: <strong>user_name</strong> &amp; <strong>hostel_room_name</strong>
                        </small>
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ asset('admin_assets/sample/ot_hostel_excel_upload.xlsx') }}"
                       class="btn btn-outline-primary rounded-1 px-4 d-inline-flex align-items-center gap-2 me-auto" download>
                        <i class="bi bi-download" aria-hidden="true"></i><span>Download Sample</span>
                    </a>
                    <a href="{{ route('hostel.building.map.import') }}" class="btn mst-btn-cancel px-4">Reset</a>
                    <button type="submit" class="btn mst-btn-submit px-4 d-inline-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-upload" aria-hidden="true"></i><span>Import</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if (session('failures'))
        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6 text-danger">
                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Validation Errors Found
                </h2>
                {{-- Not .programme-dt-table: master-admin.css right-aligns and
                     no-wraps a grid's last column (its Action column), which
                     would squeeze the Errors text onto one line. --}}
                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100">
                            <caption class="visually-hidden">Rows the file could not import</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">Row</th>
                                    <th scope="col">Username</th>
                                    <th scope="col">Hostel Room</th>
                                    <th scope="col">Errors</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (session('failures') as $failure)
                                    <tr>
                                        <td>{{ $failure['row'] }}</td>
                                        <td>{{ $failure['user_name'] }}</td>
                                        <td>{{ $failure['hostel_room_name'] }}</td>
                                        <td class="text-danger">
                                            @foreach ($failure['errors'] as $error)
                                                {{ $error }}<br>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
