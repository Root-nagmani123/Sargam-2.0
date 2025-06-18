@extends('admin.layouts.master')

@section('title', 'Exemption Data - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <!-- Header Card -->
    <x-breadcrum title="Exemption Data" />
    <x-session_message />

    <!-- Main Card -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">

            <!-- Filter Form -->


            <!-- Export Options -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" action="{{ url()->current() }}" class="mb-4">
                        <div class="row align-items-end g-3">
                            <div class="col-md-9">
                                <!-- <label for="exemption_category" class="form-label">Filter by Exemption Category</label> -->
                                <select name="exemption_category" id="exemption_category" class="form-select">
                                    <option value="">-- All Categories --</option>
                                    @foreach ($categories as $category)
                                    <option value="{{ $category->Exemption_name }}"
                                        {{ isset($filter) && $filter == $category->Exemption_name ? 'selected' : '' }}>
                                        {{ $category->Exemption_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('admin.exemption.export') }}" method="GET"
                        class="d-flex justify-content-end align-items-center gap-2">
                        <input type="hidden" name="exemption_category" value="{{ request('exemption_category') }}">
                        <label for="format" class="form-label mb-0 fw-semibold">Export:</label>
                        <select name="format" id="format" class="form-select w-auto" required>
                            <option value="">Select Format</option>
                            <option value="xlsx">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Download</button>
                    </form>
                </div>
            </div>

            <hr>

            <!-- Data Table -->
            <div class="table-responsive">
                <table class="table table-striped table-bordered text-nowrap align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>S.No</th>
                            <th>User Name</th>
                            <th>Mobile No</th>
                            <th>Web Code</th>
                            <th>Exemption Category</th>
                            <th>Medical Document</th>
                            <th>Submitted On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $index => $data)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ $data->username ?? trim(($data->first_name ?? '') . ' ' . ($data->middle_name ?? '') . ' ' . ($data->last_name ?? '')) ?: 'N/A' }}
                            </td>
                            <td>{{ $data->contact_no }}</td>
                            <td>{{ $data->web_auth }}</td>
                            <td>{{ $data->Exemption_name ?? 'N/A' }}</td>
                            <td>
                                @if ($data->medical_exemption_doc)
                                <a href="{{ asset('storage/' . $data->medical_exemption_doc) }}" target="_blank"
                                    class="btn btn-sm btn-info">View</a>
                                @else
                                <span class="text-muted">No Document</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($data->created_date)->format('d-m-Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No data found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
@endsection