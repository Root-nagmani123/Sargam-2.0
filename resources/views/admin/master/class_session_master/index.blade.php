@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Class Session Master" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>Class Session Master</h4>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end align-items-center gap-2">

                            <!-- Add Group Mapping -->
                            <a href="{{route('master.class.session.create')}}"
                                class="btn btn-primary d-flex align-items-center">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Class Session
                            </a>


                        </div>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table w-100 nowrap']) !!}
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