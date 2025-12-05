@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Memo Type Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <!-- Add Group Mapping -->
                                <a href="{{ route('master.memo.type.master.create') }}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Memo Type
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