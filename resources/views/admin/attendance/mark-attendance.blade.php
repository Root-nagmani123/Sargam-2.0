@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Mark Attendance Of Officer Trainees" />
        <x-session_message />

        {{-- Session Summary --}}
        <div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="mb-3">Through this page you can manage Attendance of Officer Trainees</h5>

                <div class="row g-3">
                    <div class="col-md-3"><strong>Major Subject:</strong> <span class="text-primary">PA</span></div>
                    <div class="col-md-3"><strong>Topic Name:</strong> <span class="text-primary">Challenges</span></div>
                    <div class="col-md-3"><strong>Faculty Name:</strong> <span class="text-primary">Alok Kumar</span></div>
                    <div class="col-md-3"><strong>Topic Date:</strong> <span class="text-primary">20-05-2025</span></div>
                    <div class="col-md-3"><strong>Session Time:</strong> <span class="text-primary">09:30 to 10:30</span>
                    </div>
                </div>

                <div class="alert customize-alert rounded-pill alert-success bg-success text-white mt-4 mb-0 border-0 fade show text-center fw-bold">
                    Attendance has been Marked for the Session
                </div>
            </div>
        </div>

        {{-- Attendance Table --}}
        <div class="card shadow">
            <div class="card-body">
                {{-- <div class="d-flex justify-content-between mb-2">
                    <div>
                        <label for="entries">Show</label>
                        <select id="entries" class="form-select form-select-sm d-inline-block w-auto mx-2">
                            <option>All</option>
                            <option>10</option>
                            <option>25</option>
                        </select>
                        entries
                    </div>
                    <input type="search" class="form-control form-control-sm w-25" placeholder="Search in table...">
                </div> --}}

                <table class="table table-bordered table-hover align-middle text-center">
                    <thead class="table-primary text-uppercase">
                        <tr>
                            <th>S.No.</th>
                            <th>OT Name</th>
                            <th>OT Code</th>
                            <th>20-05-2025<br>(09:30 - 10:30)</th>
                            <th>MDO Duty</th>
                            <th>Escort Duty</th>
                            <th>Medical Exemption</th>
                            <th>Other Exemption</th>
                        </tr>
                    </thead>
                    <tbody class="table-light">
                        @for($i = 1; $i <= 5; $i++)
                            <tr>
                                <td>{{ $i }}</td>
                                <td>Student {{ $i }}</td>
                                <td>A0{{ $i }}</td>
                                <td class="bg-light">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_{{ $i }}"
                                            id="present_{{ $i }}">
                                        <label class="form-check-label text-success" for="present_{{ $i }}">Present</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_{{ $i }}" id="late_{{ $i }}">
                                        <label class="form-check-label text-warning" for="late_{{ $i }}">Late</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_{{ $i }}"
                                            id="absent_{{ $i }}">
                                        <label class="form-check-label text-danger" for="absent_{{ $i }}">Absent</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_{{ $i }}" id="mdo_duty_{{ $i }}">
                                        <label class="form-check-label" for="mdo_duty_{{ $i }}">MDO Duty</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_{{ $i }}" id="escort_{{ $i }}">
                                        <label class="form-check-label" for="escort_{{ $i }}">Escort</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_{{ $i }}" id="medical_exempted_{{ $i }}">
                                        <label class="form-check-label" for="medical_exempted_{{ $i }}">Medical Exempted</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status_{{ $i }}" id="other_exempted_{{ $i }}">
                                        <label class="form-check-label" for="other_exempted_{{ $i }}">Other Exempted</label>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection