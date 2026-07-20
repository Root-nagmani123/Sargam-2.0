@extends('admin.layouts.master')

@section('title', 'User Registration and Exemption Summary - Sargam | Lal Bahadur')

@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="User Registration and Exemption Summary" />
        <div class="card" >
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
                        <table class="table text-nowrap w-100" id="exemptionDatalistTable">
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
                            </table>
                    </div>
                </div>
            </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    $('#exemptionDatalistTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [],
        ajax: {
            url: "{{ route('exemptions.datalist') }}",
            data: function (d) {
                d.exemption_category = @json(request('exemption_category'));
                d.application_type = @json(request('application_type'));
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'user_name', name: 'user_name', orderable: false, searchable: false },
            { data: 'contact_no', name: 'r.contact_no' },
            { data: 'web_auth', name: 'r.web_auth' },
            { data: 'exemption_category', name: 'exemption_category', orderable: false, searchable: false },
            { data: 'medical_document', name: 'medical_document', orderable: false, searchable: false },
            { data: 'type', name: 'type', orderable: false, searchable: false },
            { data: 'exemption_count', name: 'r.exemption_count' },
            { data: 'created_date', name: 'r.created_date' }
        ],
        language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries found',
            infoFiltered: '(filtered from _MAX_ total)',
            zeroRecords: 'No data found.',
            paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
        }
    });
});
</script>
@endpush
