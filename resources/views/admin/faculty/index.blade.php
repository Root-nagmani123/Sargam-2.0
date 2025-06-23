@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Faculty" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Faculty</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('faculty.create')}}" class="btn btn-primary">+ Add Faculty</a>
                                <a href="{{ route('faculty.excel.export') }}" class="btn btn-primary">
                                    <iconify-icon icon="material-symbols:sim-card-download-rounded" ></iconify-icon> Export Excel
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {!! $dataTable->table(['class' => 'table table-striped table-bordered']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection
@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush