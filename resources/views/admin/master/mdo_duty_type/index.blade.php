@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="MDO Duty Type" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>MDO Duty Type</h4>
                        </div>
                        @can('master.mdo_duty_type.create')
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{route('master.mdo_duty_type.create')}}" class="btn btn-primary">+ Add MDO Duty Type</a>
                                </div>
                            </div>
                        @endcan
                    </div>
                    <hr>
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