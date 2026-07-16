@extends('admin.layouts.master')

@section('title', 'Country List')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Country List" />
    <div class="card" >
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Country List</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-end mb-3">
                        <div class="d-flex align-items-center gap-2">

                            <!-- Add New Button -->
                            <a href="{{ route('master.country.create') }}"
                                class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Country
                            </a>

                            <!-- Export Button -->
                            <a href="" class="px-3 py-2">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">search</i>
                            </a>

                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                {{ $dataTable->table(['class' => 'table w-100 text-nowrap']) }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{ $dataTable->scripts() }}
@endpush