@extends('admin.layouts.master')

@section('title', 'Caste Master')

@section('setup_content')
<div class="container-fluid caste-category-index">
<x-breadcrum title="Caste Master"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                
                <div class="row caste-category-header-row">
                    <div class="col-6">
                        <h4>Caste Master</h4>
                    </div>
                    <div class="col-6">
                        <div class="float-end gap-2">
                            <a href="{{route('master.caste.category.create')}}" class="btn btn-primary">+ Add Caste</a>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table']) }}
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