@extends('admin.layouts.master')

@section('title', 'Leave Nature Master')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Leave Nature Master"></x-breadcrum>
    <div class="card" >
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Leave Nature Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <a href="{{ route('master.leave-nature.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Leave Nature
                        </a>
                    </div>
                </div>
            </div>
            <hr>

            {!! $dataTable->table(['class' => 'table']) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
