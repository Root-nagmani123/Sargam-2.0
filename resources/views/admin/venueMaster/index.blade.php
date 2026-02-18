@extends('admin.layouts.master')

@section('title', 'Venue Master - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid venue-master-index">
    <x-breadcrum title="Venue Master" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-6">
                            <h4>Venue Master</h4>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-md-end align-items-md-end mb-3">
                                <div class="d-flex align-items-center gap-2 venue-master-actions">
                                    <!-- Add New Button -->
                                    <a href="{{ route('Venue-Master.create') }}"
                                        class="btn btn-primary px-3 py-2 rounded-1 shadow-sm">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add New Venue
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table w-100 text-nowrap venue-master-table']) !!}
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
