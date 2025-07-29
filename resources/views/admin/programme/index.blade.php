@extends('admin.layouts.master')

@section('title', 'Course Master - Sargam | Lal Bahadur')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Programme" />
        <x-session_message />

        <div class="datatables">
            <!-- start Zero Configuration -->
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">

                    <div class="row">
                        <div class="col-6">
                            <h4>Course Master</h4>
                        </div>
                        @can('programme.create')
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{route('programme.create')}}" class="btn btn-primary">+ Add Course</a>
                                </div>
                            </div>    
                        @endcan
                    </div>
                    <hr>
                    <div class="table-responsive">

                        {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover']) !!}
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