@extends('admin.layouts.master')

@section('title', 'Caste Master')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Caste Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                
                <div class="row">
                    <div class="col-6">
                        <h4>Caste Master</h4>
                    </div>
                    @can('master.caste.category.create')
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.caste.category.create')}}" class="btn btn-primary">+ Add Caste</a>
                            </div>
                        </div>
                    @endcan
                    
                </div>
                <hr>
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table table-striped table-bordered']) }}
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