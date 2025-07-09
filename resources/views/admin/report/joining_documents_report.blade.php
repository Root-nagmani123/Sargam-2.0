{{-- @extends('admin.layouts.master')

@section('title', 'Joining Documents')
@section('content')
<div class="container-fluid mt-4">
        <div class="card-body">
            <h5 class="fw-bold text-primary mb-3">Reports</h5>
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search OT Name..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="complete" {{ request('status') == 'complete' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered text-center table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>OT Name</th>
                            <th>Programme Structure</th>
                            @php
                                $fields = [
                                    'admin_family_details_form' => 'Family Details',
                                    'admin_close_relation_declaration' => 'Close Relation',
                                    'admin_dowry_declaration' => 'Dowry',
                                    'admin_marital_status' => 'Marital',
                                    'admin_home_town_declaration' => 'Hometown',
                                    'admin_property_immovable' => 'Immovable Property',
                                    'admin_property_movable' => 'Movable Property',
                                    'admin_property_liabilities' => 'Debts & Liabilities',
                                    'admin_bond_ias_ips_ifos' => 'Surety Bond (IAS/IPS)',
                                    'admin_bond_other_services' => 'Surety Bond (Other)',
                                    'admin_oath_affirmation' => 'Oath',
                                    'admin_certificate_of_charge' => 'Certificate',
                                    'accounts_nomination_form' => 'Nomination',
                                    'accounts_nps_registration' => 'NPS',
                                    'accounts_employee_info_sheet' => 'Employee Info',
                                ];
                            @endphp
                            @foreach ($fields as $key => $label)
                                <th>{{ $label }}</th>
                            @endforeach
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Download All</th>
                        </tr>
                    </thead>
                    <tbody>
                        @dd($reports);
                        @foreach ($reports as $index => $report)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $report->full_name }}</td>
                                <td>{{ $report->programme_structure_code }}</td>
                                @foreach ($fields as $field => $label)
                                    <td>
                                        @if (!empty($report->$field))
                                            <a href="{{ asset('storage/' . $report->$field) }}" class="btn btn-link p-0"
                                                target="_blank">View</a>
                                            <a href="{{ asset('storage/' . $report->$field) }}" download
                                                class="btn btn-sm btn-outline-primary ms-1"><i
                                                    class="bi bi-download"></i></a>
                                        @else
                                            <span class="text-danger">Pending</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td>
                                    @php
                                        $isComplete = collect($fields)->every(function ($_, $key) use ($report) {
                                            return !empty($report->$key);
                                        });
                                    @endphp
                                    <span
                                        class="badge bg-{{ $isComplete ? 'success' : 'warning' }}">{{ $isComplete ? 'Complete' : 'Pending' }}</span>
                                </td>
                                <td>
                                    <textarea class="form-control" rows="1"></textarea>
                                </td>
                                <td>
                                    <a href="{{ route('admin.joining-documents.download-all', $report->user_id) }}"
                                        class="btn btn-sm btn-primary">
                                        Download All
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection --}}

{{-- @extends('admin.layouts.master')

@section('title', 'Joining Documents Report')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-primary fw-bold">Joining Documents Report</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle table-hover table-striped text-nowrap">
                <thead class="table-light text-center">
                    <tr>
                        <th>OT Name</th>
                        @foreach ($fields as $key => $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($uploads as $upload)
                        <tr>
                            <td class="fw-semibold">{{ $upload->display_name }}</td>
                            @php $isComplete = true; @endphp
                            @foreach ($fields as $key => $label)
                                <td class="text-center">
                                    @if (!empty($upload->$key))
                                        <a href="{{ asset('storage/' . $upload->$key) }}" class="btn btn-sm btn-primary" target="_blank">View</a>
                                        <a href="{{ asset('storage/' . $upload->$key) }}" download class="btn btn-sm btn-success">Download</a>
                                    @else
                                        <span class="text-muted">-</span>
                                        @php $isComplete = false; @endphp
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center">
                                <span class="badge {{ $isComplete ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $isComplete ? 'Complete' : 'Pending' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($fields) + 2 }}" class="text-center text-muted">No records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection --}}

@extends('admin.layouts.master')
@section('title', 'Report Joining Documents')

@section('content')
    <x-session_message />

    <form method="GET" class="mb-3 row">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search OT Name..."
                value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">-- All Status --</option>
                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Complete</option>
                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a href="{{ route('admin.joining-documents.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
    <div class="container-fluid mt-4">
        {{-- <div class="card-header bg-success text-white">
        <h5 class="mb-0 fw-bold">99<sup>th</sup> Foundation Course Report</h5>
        <small class="text-white">[ August 26, 2024 to November 29, 2024 ]</small>
    </div> --}}
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Serial Number</th>
                        <th>OT Name</th>
                        <th>Programme Structure</th>
                        @foreach ($fields as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th>Check Status</th>
                        <th>Download All</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $index => $student)
                        @php
                            $upload = $uploads[$student->pk] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student->display_name }}</td>
                            <td>{{ $student->schema_id }}</td>

                            @foreach ($fields as $fieldKey => $fieldLabel)
                                <td>
                                    @if ($upload && !empty($upload->$fieldKey))
                                        <a href="{{ asset('storage/' . $upload->$fieldKey) }}" target="_blank">View</a> |
                                        <a href="{{ asset('storage/' . $upload->$fieldKey) }}" download>Download</a>
                                    @else
                                        <span class="text-danger">Pending</span>
                                    @endif
                                </td>
                            @endforeach

                            {{-- <td>
                                @php
                                    $allDone =
                                        $upload && collect($fields)->every(fn($label, $key) => !empty($upload->$key));
                                        
                                @endphp
                                <span class="badge {{ $allDone ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $allDone ? 'Success' : 'Pending' }}
                                </span>
                            </td> --}}

                            <td>
                                @php
                                    $upload = $uploads[$student->pk] ?? null;

                                    // Check if all fields are uploaded (non-empty)
                                    $allDone =
                                        $upload &&
                                        collect($fields)->every(function ($label, $key) use ($upload) {
                                            return !empty($upload->$key);
                                        });
                                @endphp

                                <span class="badge {{ $allDone ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $allDone ? 'Success' : 'Pending' }}
                                </span>
                            </td>
                            <td>
                                {{-- ✅ Place the button here --}}
                                <a href="{{ route('admin.joining-documents.download-all', $student->pk) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-download"></i> Download All
                                </a>
                            </td>

                            <td><input type="text" class="form-control form-control-sm" placeholder="Enter remarks"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-4">
                {!! $students->links() !!}

                {{-- {!! $uploads->appends(request()->query())->links() !!} --}}
            </div>
        </div>
    </div>
@endsection
