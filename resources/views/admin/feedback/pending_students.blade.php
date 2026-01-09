@extends('admin.layouts.master')

@section('title', 'Pending Feedback Students | Sargam')

@section('setup_content')

    <div class="container-fluid">

        <x-breadcrum title="Pending Feedback Students" />

        <div class="card border-start border-danger border-4">
            <div class="card-body">

                <!-- Header -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-6">
                        <h4 class="fw-bold mb-0">
                            Students Pending Feedback
                        </h4>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                            ← Back
                        </a>
                    </div>
                </div>

                <hr>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="enrolledTable">
                        <thead class="table">
                            <tr>
                                <th width="60">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact No</th>
                                <th>OT Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->full_name }}</td>
                                    <td>{{ $student->email ?? '-' }}</td>
                                    <td>{{ $student->contact_no ?? '-' }}</td>
                                    <td>{{ $student->generated_OT_code ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-success fw-semibold">
                                        All students have submitted feedback
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>


            </div>
        </div>

    </div>

@endsection
