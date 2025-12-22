@extends('admin.layouts.master')

@section('title', 'Medical Exception Faculty View - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Medical Exception Faculty View"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6">
                    <h4>Medical Exception Faculty View</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <button type="button" class="btn btn-info d-flex align-items-center" onclick="window.print()">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">print</i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
            <form class="row" role="search" aria-label="Medical exception filters">
                <div class="col-3">
                    <div class="mb-3">
                        <label for="filter_course" class="form-label">Course Name</label>
                        <select name="course" id="filter_course" class="form-control"
                            aria-describedby="filter_course_help">
                            <option value="">Select</option>
                            <option value="A01">A01</option>
                            <option value="A02">A02</option>
                        </select>
                        <small id="filter_course_help" class="form-text text-muted">Choose the course to filter
                            records.</small>
                    </div>
                </div>
                <div class="col-4" aria-label="Date range">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_date_from" class="form-label">Date From</label>
                                <input type="date" name="date_from" id="filter_date_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_date_to" class="form-label">Date To</label>
                                <input type="date" name="date_to" id="filter_date_to" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4" aria-label="Time range">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_time_from" class="form-label">Time From</label>
                                <input type="time" name="time_from" id="filter_time_from" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="filter_time_to" class="form-label">Time To</label>
                                <input type="time" name="time_to" id="filter_time_to" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-1">
                    <div class="mb-3">
                        <label for="filter_ot" class="form-label">OT Code</label>
                        <select name="ot_code" id="filter_ot" class="form-control" aria-describedby="filter_ot_help">
                            <option value="">Select</option>
                            <option value="A01">A01</option>
                            <option value="A02">A02</option>
                        </select>
                        <small id="filter_ot_help" class="form-text text-muted">Filter by OT code.</small>
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
                            <th scope="col">Exemption Count</th>
                            <th scope="col">Submitted On</th>
                        </tr>
                    </thead>
                    <tbody id="facultyAccordion">
                        <tr class="accordion-toggle" data-bs-toggle="collapse" data-bs-target="#row1"
                            aria-expanded="false" aria-controls="row1" role="button" tabindex="0">
                            <td>1</td>
                            <td>
                                IAS Professional Course Phase-I 2025 Batch
                            </td>
                            <td>Premkumar VR</td>
                            <td>PRIYANKA DAS</td>
                            <td>BAGADI GAUTHAM</td>
                            <td>Phase-II 2024</td>
                            <td>Premkumar VR</td>
                            <td>PRIYANKA DAS</td>
                            <td>BAGADI GAUTHAM</td>
                            <td>Phase-II 2024</td>
                            <td>20</td>
                            <td>2</td>

                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection