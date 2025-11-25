@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('content')
<style>
/* ====== TABLE HEADER ====== */
.custom-mdo-table thead th {
    background: #b72a2a !important;
    color: #fff !important;
    font-weight: 600;
    padding: 14px 12px !important;
    border: none !important;
    white-space: nowrap;
}

/* ====== TABLE ROW ====== */
.custom-mdo-table {
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
}

.custom-mdo-table tbody tr {
    background: #ffffff !important;
    box-shadow: 0 0 6px rgba(0,0,0,0.07);
    border-radius: 10px !important;
}

.custom-mdo-table tbody td {
    padding: 14px 12px !important;
    border: none !important;
    vertical-align: middle !important;
}

/* S.No LEFT aligned */
.custom-mdo-table tbody td:first-child {
    text-align: left !important;
}

/* All other columns center aligned */
.custom-mdo-table tbody td:not(:first-child) {
    text-align: center !important;
}

/* ===== ACTION BUTTON ===== */
.mdo-edit-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background-color: rgba(183, 42, 42, 0.1);
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}

.mdo-edit-btn:hover {
    background-color: #b72a2a;
    color: #fff !important;
}

.mdo-edit-btn i {
    font-size: 18px;
}

/* ====== DATATABLE CLEANUP ====== */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter {
    margin-bottom: 12px;
}

.dataTables_filter input {
    border-radius: 6px !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 4px 10px !important;
}
/* ====== TABLE HEADER (ROUNDED LIKE ATTACHED) ====== */
.custom-mdo-table thead {
    border-radius: 10px !important;
    overflow: hidden; /* required for rounded effect */
}

.custom-mdo-table thead th {
    background: #b72a2a !important;
    color: #fff !important;
    font-weight: 600;
    padding: 14px 12px !important;
    border: none !important;
    white-space: nowrap;
}

/* Rounded corners on first and last header cell */
.custom-mdo-table thead th:first-child {
    border-top-left-radius: 10px !important;
}

.custom-mdo-table thead th:last-child {
    border-top-right-radius: 10px !important;
}
</style>

   <div class="container-fluid">

    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">

                    <div class="row">
                        <div class="col-6">
                            <h4>MDO Escrot Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('mdo-escrot-exemption.create') }}" class="btn btn-primary">
                                    + Add MDO Escrot Exemption
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {!! $dataTable->table([
                        'class' => 'table custom-mdo-table align-middle w-100 table-bordered'
                    ]) !!}

                </div>
            </div>
        </div>
    </div>

</div>



@endsection
@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush