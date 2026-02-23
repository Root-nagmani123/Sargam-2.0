@extends('admin.layouts.master')

@section('title', 'Medical Exception Faculty View - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid medical-exception-faculty-view">
    <x-breadcrum title="Medical Exception Faculty View"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-12 col-md-6">
                    <h4>Medical Exception Faculty View</h4>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-info d-flex align-items-center" onclick="window.print()">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">print</i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
            <form class="row" role="search" aria-label="Medical exception filters" method="GET" action="{{ route('medical.exception.faculty.view') }}">
                <div class="col-12 col-md-5">
                    <div class="mb-3">
                        <label for="filter_course" class="form-label">Course Name</label>
                        <select name="course" id="filter_course" class="form-control"
                            aria-describedby="filter_course_help">
                            <option value="">Select</option>
                            @foreach($courses ?? [] as $course)
                                <option value="{{ $course->pk }}" {{ (isset($courseFilter) && $courseFilter == $course->pk) ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                        <small id="filter_course_help" class="form-text text-muted">Choose the course to filter
                            records.</small>
                    </div>
                </div>
                <div class="col-12 col-md-5">
                    <div class="mb-3">
                        <label for="filter_date_from" class="form-label">Date From</label>
                        <input type="date" name="date_from" id="filter_date_from" class="form-control" value="{{ $dateFromFilter ?? '' }}">
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="mb-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-success flex-fill">Filter</button>
                        <a href="{{ route('medical.exception.faculty.view') }}" class="btn btn-primary flex-fill">Reset</a>
                    </div>
                </div>
            </form>
            <hr>

            <!-- Course Summary Table -->
            <div class="table-responsive">
                <table class="table text-nowrap" role="table">
                    <thead>
                        <tr>
                            <th scope="col">S.No.</th>
                            <th scope="col">Course Name</th>
                            <th scope="col">Faculty Name</th>
                            <th scope="col">Topics</th>
                            <th scope="col">Student Name</th>
                            <th scope="col">Medical Exception Date</th>
                            <th scope="col">Medical Exception Time</th>
                            <th scope="col">OT Code</th>
                            <th scope="col">Medical Document</th>
                            <th scope="col">Application Type</th>
                           {{--<th scope="col">Exemption Count</th>--}}
                            <th scope="col">Submitted On</th>
                        </tr>
                    </thead>
                    <tbody id="facultyAccordion">
                        @forelse($data ?? [] as $index => $record)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $record->course_name ?? 'N/A' }}</td>
                                <td>{{ $record->faculty_name ?? 'N/A' }}</td>
                                <td>{{ $record->topics ?? 'N/A' }}</td>
                                <td>{{ $record->student_name ?? 'N/A' }}</td>
                                <td>
                                    @if($record->from_date)
                                        {{ \Carbon\Carbon::parse($record->from_date)->format('d-m-Y') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @if($record->from_date)
                                        {{ \Carbon\Carbon::parse($record->from_date)->format('h:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $record->ot_code ?? 'N/A' }}</td>
                                <td>
                                    @if($record->medical_document)
                                        <a href="{{ asset('storage/' . $record->medical_document) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="material-icons" style="font-size: 18px;">visibility</i> View
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $record->application_type ?? 'N/A' }}</td>
                                {{--<td>{{ $record->exemption_count ?? 0 }}</td>--}}
                                <td>
                                    @if($record->submitted_on)
                                        {{ \Carbon\Carbon::parse($record->submitted_on)->format('d-m-Y h:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center">No medical exception records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Mobile responsive - desktop unchanged */
    @media (max-width: 767.98px) {
        .medical-exception-faculty-view .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

@endsection
