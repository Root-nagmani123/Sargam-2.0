@extends('admin.layouts.master')

@section('title', 'OT Directory - Sargam')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="OT Directory"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.directory.ot') }}" class="mb-3">
                <div class="border rounded-3 p-3 bg-white">
                    <div class="row g-2 align-items-end ot-toolbar-row">
                        <div class="col-12 col-lg-3">
                            <label for="otCourseSelect" class="form-label mb-1 fw-semibold">Program Name*</label>
                            <select name="course_id" class="form-select" id="otCourseSelect">
                                <option value="">Select Program</option>
                                @foreach($activeCourses as $course)
                                    <option value="{{ $course->pk }}" {{ (int) $selectedCourseId === (int) $course->pk ? 'selected' : '' }}>
                                        {{ $course->couse_short_name ?: $course->course_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label for="otSearchInput" class="form-label mb-1 fw-semibold">Search</label>
                            <input
                                id="otSearchInput"
                                type="text"
                                name="search"
                                value="{{ $search ?? '' }}"
                                class="form-control"
                                placeholder="Name, OT code, email, cadre"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>

                        <div class="col-6 col-lg-1 d-grid">
                            <a href="{{ route('admin.directory.ot', ['course_id' => $selectedCourseId]) }}" class="btn btn-outline-secondary">Reset</a>
                        </div>

                        <div class="col-6 col-lg-1 d-grid">
                            <button type="submit" name="export" value="csv" class="btn btn-outline-success btn-sm">CSV</button>
                        </div>

                        <div class="col-6 col-lg-1 d-grid">
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success btn-sm">Excel</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive ot-directory-scroll">
                <table class="table align-middle datatable" id="otDirectoryTable" data-export="false">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>OT Code</th>
                            <th>Room No.</th>
                            <th>Room Extension No.</th>
                            <th>Email ID</th>
                            <th>Course Name</th>
                            <th>Cadre Name</th>
                            <th>Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            <tr>
                                <td>{{ ($students->firstItem() ?? 0) + $index }}</td>
                                <td>{{ $student->display_name ?: '-' }}</td>
                                <td>{{ $student->generated_OT_code ?: '-' }}</td>
                                <td>-</td>
                                <td>-</td>
                                <td>{{ $student->email ?: '-' }}</td>
                                <td>{{ $student->course_name ?: '-' }}</td>
                                <td>{{ $student->cadre_name ?: '-' }}</td>
                                <td>
                                    @if(!empty($student->photo_path))
                                        <img src="{{ asset('storage/' . $student->photo_path) }}" alt="photo" class="directory-photo" loading="lazy" decoding="async">
                                    @else
                                        <img src="{{ asset('images/dummypic.jpeg') }}" alt="photo" class="directory-photo" loading="lazy" decoding="async">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .ot-toolbar-row .btn {
        white-space: nowrap;
    }
    @media (min-width: 992px) {
        .ot-toolbar-row {
            flex-wrap: nowrap;
        }
        .ot-toolbar-row > [class*="col-lg-"] {
            min-width: 0;
        }
    }
    @media (max-width: 991.98px) {
        .ot-toolbar-row .btn {
            width: 100%;
        }
    }
    #otDirectoryTable thead th {
        background: #2f7fc0;
        color: #fff;
        font-size: 12px;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    #otDirectoryTable tbody td {
        font-size: 12px;
        vertical-align: middle;
    }
    .directory-photo {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 6px;
    }
    @media (max-width: 768px) {
        .ot-directory-scroll {
            max-height: 65vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
</style>
@endpush

@endsection

