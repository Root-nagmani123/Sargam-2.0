@extends('admin.layouts.master')

@section('title', 'Define Pay Scale - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
<x-breadcrum title="Define Pay Scale" />

    <x-session_message />

    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Pay Scale</h1>
                    <p class="text-muted small mb-0">Manage eligibility mapping (salary grade, unit type and unit sub type) from estate_eligibility_mapping.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-pay-scale.create') }}" class="btn btn-primary"><i class="material-icons material-symbols-rounded">add</i> Add New</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0" id="payScaleTable">
                    <thead>
                        <tr>
                            <th>S.NO.</th>
                            <th>Pay Scale / Salary Grade</th>
                            <th>Unit Type</th>
                            <th>Unit Sub Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    {{-- Rows come from the server-side DataTable (see script below). --}}
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Server-side: search, sort and paging are resolved in SQL.
    $('#payScaleTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.estate.define-pay-scale.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'salary_grade', name: 'salary_grade', orderable: false, searchable: false },
            { data: 'unit_type', name: 'unit_type', orderable: false, searchable: false },
            { data: 'unit_sub_type', name: 'unit_sub_type', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false,
        language: {
            processing: 'Loading data…',
            emptyTable: 'No eligibility mapping found.'
        }
    });
});
</script>
@endpush
