@extends('admin.layouts.master')

@section('title', 'Faculty Expertise')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Faculty Expertise"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>Faculty Expertise</h4>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end align-items-center gap-2">

                            <!-- Add Group Mapping -->
                            <a href="{{route('master.faculty.expertise.create')}}"
                                class="btn btn-primary d-flex align-items-center">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 24px;">add</i>
                                Add Faculty Expertise
                            </a>


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