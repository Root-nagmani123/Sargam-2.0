@extends('admin.layouts.master')

@section('title', 'Exam Building Master')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Exam Building Master"></x-breadcrum>
    <div class="card" >
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Exam Building Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <a href="{{ route('master.exam_building.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Building
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
