@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@section('setup_content')
<div class="container-fluid">

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Discipline Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <!-- Add Group Mapping -->
                        <a href="{{ route('master.discipline.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Discipline
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