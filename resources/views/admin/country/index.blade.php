@extends('admin.layouts.master')

@section('title', 'Country List')

@section('setup_content')
<div class="container-fluid country-index">
    <x-breadcrum title="Country List" variant="glass" />
    <x-session_message />
    <div class="datatables">
        <div class="card overflow-hidden">
            <div class="card-body p-4">
                <div class="row align-items-center mb-0 g-2">
                    <div class="col-12 col-md-6">
                        <h4 class="mb-0 fw-bold">Country List</h4>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex justify-content-start justify-content-md-end align-items-center gap-2">
                            <a href="{{ route('master.country.create') }}"
                                class="btn btn-primary px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
                                <i class="material-icons material-symbols-rounded fs-5 align-middle">add</i>
                                Add Country
                            </a>
                        </div>
                    </div>
                </div>
                <hr class="my-3">

                <div class="table-responsive overflow-x-auto">
                    {!! $dataTable->table(['class' => 'table w-100 text-nowrap align-middle mb-0']) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
@endpush
