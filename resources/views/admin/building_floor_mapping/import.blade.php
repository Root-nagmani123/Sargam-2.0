@extends('admin.layouts.master')

@section('title', 'Hostel Building Assign Student')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Hostel Building Assign Student" />
        <x-session_message />

        
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">

                    <form action="{{ route('hostel.building.map.import') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <h4 class="fw-bold mb-3 text-primary">Import Hostel Building Assign Student</h4>

                        <div class="mb-3">
                            <label for="file" class="form-label fw-semibold">Select Excel/CSV File</label>
                            <input type="file" name="file" id="file"
                                class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv">
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-start mt-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-upload"></i> Import
                            </button>
                            <a href="{{ route('hostel.building.map.import') }}" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </a>
                            <a href="{{ asset('admin_assets/sample/ot_hostel_excel_upload.xlsx') }}" class="btn btn-info"
                                download>
                                <i class="mdi mdi-download"></i> Download Sample
                            </a>
                        </div>
                    </form>


                    @if (session('failures'))
                        <div class="alert alert-warning mt-4">
                            <h6 class="fw-bold text-dark mb-2">⚠️ Some rows failed to import:</h6>
                            <div class="table-responsive">
                                <table class="table table-dark table-borderless">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Row</th>
                                            <th>Username</th>
                                            <th>Hostel Room</th>
                                            <th>Course</th>
                                            <th>Errors</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (session('failures') as $failure)
                                            <tr>
                                                <td>{{ $failure['row'] }}</td>
                                                <td class="{{ in_array('Username', $failure['errors']) ? 'bg-danger text-white' : '' }}">
                                                    {{ $failure['user_name'] }}
                                                </td>
                                                <td class="{{ in_array('Hostel Room', $failure['errors']) ? 'bg-danger text-white' : '' }}">
                                                    {{ $failure['hostel_room_name'] }}
                                                </td>
                                                <td>{{ $failure['course_master_pk'] }}</td>
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
        
    </div>
@endsection