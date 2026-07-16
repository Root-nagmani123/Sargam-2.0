@extends('admin.layouts.master')

@section('title', 'City - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>City</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3">
                                <div class="d-flex align-items-center gap-2">

                                    <!-- Add New Button -->
                                    <a href="{{ route('master.city.create') }}"
                                        class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New City
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
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

@push('scripts')
{{ $dataTable->scripts() }}
@endpush