@extends('admin.layouts.master')

@section('title', 'User Registration and Exemption Summary - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">

        <!-- Header Card -->
        <x-breadcrum title="User Registration and Exemption Summary" />
        <x-session_message />
        <div class="datatables">
            <!-- start Zero Configuration -->
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <!-- Filters and Export -->
                    <form method="GET" action="{{ route('admin.exemption.export') }}">
                        @csrf
                        <div class="row align-items-end mb-4">
                            <!-- Exemption Category -->
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label for="exemption_category" class="form-label">Exemption Category</label>
                                <select name="exemption_category" class="form-select" id="exemption_category">
                                    <option value="">-- All Categories --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->Exemption_name }}"
                                            {{ request('exemption_category') == $category->Exemption_name ? 'selected' : '' }}>
                                            {{ $category->Exemption_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Application Type -->
                            <div class="col-md-2 col-sm-6 mb-2">
                                <label for="application_type" class="form-label">Application Type</label>
                                <select name="application_type" id="application_type" class="form-select">
                                    <option value="">-- All Types --</option>
                                    <option value="1" {{ request('application_type') == '1' ? 'selected' : '' }}>
                                        Registration</option>
                                    <option value="2" {{ request('application_type') == '2' ? 'selected' : '' }}>
                                        Exemption</option>
                                </select>
                            </div>

                            <!-- Filter Button -->
                            <div class="col-md-1 col-sm-6 mb-2">
                                <button type="submit" formaction="{{ route('exemptions.datalist') }}"
                                    class="btn btn-primary w-100">
                                    Filter
                                </button>
                            </div>

                            <!-- Reset Button -->
                            <div class="col-md-1 col-sm-6 mb-2">
                                <a href="{{ route('exemptions.datalist') }}"
                                    class="btn btn-outline-secondary w-100">Reset</a>
                            </div>

                            <!-- Export Format -->
                            <div class="col-md-2 col-sm-6 mb-2">
                                <label for="format" class="form-label">Export Format</label>
                                <select name="format" id="format" class="form-select">
                                    <option value="">-- All Formats --</option>
                                    <option value="pdf" {{ request('format') == 'pdf' ? 'selected' : '' }}>PDF</option>
                                    <option value="xlsx" {{ request('format') == 'xlsx' ? 'selected' : '' }}>Excel
                                    </option>
                                    <option value="csv" {{ request('format') == 'csv' ? 'selected' : '' }}>CSV</option>
                                </select>
                            </div>

                            <!-- Export Button -->
                            <div class="col-md-3 col-sm-12 mb-2">
                                <button type="submit" class="btn btn-success w-100">Export</button>
                            </div>
                        </div>

                    </form>
                    <div class="table-responsive">
                        <div id="zero_config_wrapper" class="dataTables_wrapper">
                            <table id="zero_config"
                                class="table table-striped table-bordered text-nowrap align-middle dataTable"
                                aria-describedby="zero_config_info">
                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>User Name</th>
                                        <th>Mobile No</th>
                                        <th>Web Code</th>
                                        <th>Exemption Category</th>
                                        <th>Medical Document</th>
                                        <th>Type</th>
                                        <th>Exemption Count</th>
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
                                            <td>
                                                @if ($data->application_type == 2)
                                                    {{ $data->Exemption_name ?? 'N/A' }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($data->application_type == 2)
                                                    @if ($data->medical_exemption_doc)
                                                        <a href="{{ asset('storage/' . $data->medical_exemption_doc) }}"
                                                            target="_blank" class="btn btn-sm btn-info">View</a>
                                                    @else
                                                        <span class="text-muted">No Document</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($data->application_type == 1)
                                                    Registration
                                                @elseif ($data->application_type == 2)
                                                    Exemption
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if ($data->exemption_count)
                                                    {{ $data->exemption_count }}
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($data->created_date)->format('d-m-Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No data found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>

@endsection
