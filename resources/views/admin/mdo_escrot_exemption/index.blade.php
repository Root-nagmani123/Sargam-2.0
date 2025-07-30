@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('content')
    <div class="container-fluid">

        <x-breadcrum title="MDO Escrot Exemption" />
        <x-session_message />

        <div class="datatables">
            <!-- start Zero Configuration -->
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-6">
                                <h4>MDO Escrot Exemption</h4>
                            </div>
                            @can('mdo-escrot-exemption.create')
                                <div class="col-6">
                                    <div class="float-end gap-2">
                                        <a href="{{route('mdo-escrot-exemption.create')}}" class="btn btn-primary">+ Add MDO
                                            Escrot Exemption</a>
                                    </div>
                                </div>
                            @endcan
                            
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