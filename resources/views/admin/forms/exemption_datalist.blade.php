@extends('admin.layouts.master')

@section('title', 'Exemption Data - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Exemption Data</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        Exemption Submissions
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="datatables">
            <!-- start Table -->
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h4>Submitted Exemptions</h4>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <form action="{{ route('admin.exemption.export') }}" method="GET"
                                    class="d-flex align-items-center gap-2">
                                    <label for="format" class="form-label me-2 mb-0 fw-semibold">Export:</label>
                                    <select name="format" id="format" class="form-select w-auto" required>
                                        <option value="">Select Format</option>
                                        <option value="xlsx">Excel (.xlsx)</option>
                                        <option value="csv">CSV (.csv)</option>
                                        <option value="pdf">PDF (.pdf)</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary ms-2">Download</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="table-responsive">
                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable">
                            <thead>
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
                                        <td>{{ $data->username ?? trim(($data->first_name ?? '') . ' ' . ($data->middle_name ?? '') . ' ' . ($data->last_name ?? '')) ?: 'N/A' }}
                                        </td>
                                        <td>{{ $data->contact_no }}</td>
                                        <td>{{ $data->web_auth }}</td>
                                        <td>{{ $data->Exemption_short_name ?? 'N/A' }}</td>
                                        <td>
                                            @if ($data->medical_exemption_doc)
                                                <a href="{{ asset('storage/' . $data->medical_exemption_doc) }}"
                                                    target="_blank" class="btn btn-sm btn-info">View</a>
                                            @else
                                                <span class="text-muted">No Document</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($data->created_date)->format('d-m-Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No data found.</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- end Table -->
        </div>
    </div>
@endsection
