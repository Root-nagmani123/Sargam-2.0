@extends('admin.layouts.master')

@section('title', 'Stream - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid stream-index">
    <x-breadcrum title="Stream" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row stream-header-row">
                        <div class="col-6">
                            <h4>Stream</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <a href="{{ route('stream.create') }}" class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Stream
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table']) !!}
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush