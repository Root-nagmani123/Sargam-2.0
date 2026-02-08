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
                        <div class="col-12 col-md-6">
                            <h1 class="h4 mb-0">Stream</h1>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-md-end justify-content-start align-items-center gap-2 mt-2 mt-md-0">
                                <a href="{{ route('stream.create') }}" class="btn btn-primary d-flex align-items-center w-100 w-md-auto justify-content-center justify-content-md-start">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Stream
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table table-striped table-hover align-middle', 'aria-describedby' => 'stream-table-caption']) !!}
                        <div id="stream-table-caption" class="visually-hidden">Stream list</div>
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