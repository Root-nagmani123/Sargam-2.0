@extends('admin.layouts.master')

@section('title', 'Counsellor Group - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="Counsellor Group" />
        <x-session_message />

        <div class="datatables">
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row mb-3">
                            <div class="col-6">
                                <h4>Counsellor Group</h4>
                            </div>
                            <div class="col-6 d-flex justify-content-end gap-2">
                                <a href="{{ route('counsellor.group.create') }}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <iconify-icon icon="ep:circle-plus-filled" width="1.2em" height="1.2em"
                                        class="me-1"></iconify-icon> Add Counsellor Group
                                </a>
                            </div>
                        </div>
                        <hr>
                        {!! $dataTable->table(['class' => 'table table-striped table-bordered']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush

