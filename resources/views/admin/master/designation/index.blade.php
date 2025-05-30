@extends('admin.layouts.master')

@section('title', 'Designation Master')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Designation Master" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Designation Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.designation.create')}}" class="btn btn-primary">+ Add Designation</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-striped table-bordered']) }}
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