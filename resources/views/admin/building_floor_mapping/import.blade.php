@extends('admin.layouts.master')

@section('title', 'Hostel Building Assign Student')

@section('setup_content')
    <div class="container-fluid">

        <x-breadcrum title="Hostel Building Assign Student" />
        <x-session_message />

        <div class="card shadow-sm border-0">
            <div class="card-body">

                <form action="{{ route('hostel.building.map.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <h4 class="fw-bold mb-3 text-primary">Import Hostel Building Assign Student</h4>

                    {{-- Course Selection --}}
                    <div class="mb-3">
                        <label for="course_master_pk" class="form-label fw-semibold">
                            Select Course <span class="text-danger">*</span>
                        </label>
                        <select name="course_master_pk" id="course_master_pk"
                            class="form-select @error('course_master_pk') is-invalid @enderror">
                            <option value="">-- Select Course --</option>
                            @foreach ($courses as $pk => $name)
                                <option value="{{ $pk }}"
                                    {{ (old('course_master_pk', session('selected_course')) == $pk) ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- File Upload --}}
                    <div class="mb-3">
                        <label for="file" class="form-label fw-semibold">
                            Select Excel / CSV File <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="file" id="file"
                            class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Excel must have 2 columns: <strong>user_name</strong> &amp; <strong>hostel_room_name</strong>
                        </small>
                    </div>

                    <div class="d-flex justify-content-start mt-3 gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Import
                        </button>
                        <a href="{{ route('hostel.building.map.import') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-repeat"></i> Reset
                        </a>
                        <a href="{{ asset('admin_assets/sample/ot_hostel_excel_upload.xlsx') }}"
                            class="btn btn-info" download>
                            <i class="bi bi-download"></i> Download Sample
                        </a>
                    </div>
                </form>

                @if (session('failures'))
                    <div class="alert alert-warning mt-4">
                        <h6 class="fw-bold text-dark mb-2">⚠️ Validation Errors Found</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-danger">
                                    <tr>
                                        <th>Row</th>
                                        <th>Username</th>
                                        <th>Hostel Room</th>
                                        <th>Errors</th>
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
                @endif

            </div>
        </div>

    </div>
@endsection
