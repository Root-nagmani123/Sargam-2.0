@extends('admin.layouts.master')

@section('title', 'Faculty Expertise')

@section('setup_content')
<div class="container-fluid faculty-expertise-index">
    <x-breadcrum title="Faculty Expertise"></x-breadcrum>
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
            <div class="card-body p-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <div>
                        <h4 class="fw-semibold text-dark mb-1">Faculty Expertise</h4>
                        <p class="text-muted small mb-0">Manage faculty expertise categories and specializations</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{route('master.faculty.expertise.create')}}"
                            class="btn btn-primary px-4 py-2 rounded-1 shadow-sm d-flex align-items-center gap-2 transition-all">
                            <i class="material-icons menu-icon material-symbols-rounded" 
                               style="font-size: 20px; vertical-align: middle;">add</i>
                            <span>Add Faculty Expertise</span>
                        </a>
                    </div>
                </div>
                
                <hr class="my-4">
                <div class="table-responsive rounded overflow-auto">
                    <table class="table text-nowrap mb-0 align-middle" id="faculty-expertise-table">
                        <thead>
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Faculty Expertise</th>
                                <th class="col">Status</th>
                                <th class="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!empty($faculties) && count($faculties) > 0)
                                @foreach ($faculties as $index => $faculty)
                                <tr>
                                    <td>{{ $faculties->firstItem() + $index }}</td>
                                    <td>{{ $faculty->expertise_name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="faculty_expertise_master" data-column="active_inactive"
                                                data-id="{{ $faculty->pk }}"
                                                {{ $faculty->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-2" role="group"
                                            aria-label="Faculty expertise actions">

                                            <!-- Edit -->
                                            <a href="{{ route('master.faculty.expertise.edit', ['id' => encrypt($faculty->pk)]) }}"
                                                class="text-primary d-flex align-items-center gap-1"
                                                aria-label="Edit faculty expertise">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">edit</i>
                                            </a>

                                            <!-- Delete -->
                                            @if($faculty->active_inactive == 1)
                                            <a href="javascript:void(0)"
                                                class="text-primary d-flex align-items-center gap-1"
                                                disabled aria-disabled="true" title="Cannot delete active record">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">delete</i>
                                            </a>
                                            @else
                                            <form
                                                action="{{ route('master.faculty.expertise.delete', ['id' => encrypt($faculty->pk)]) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this record?');">
                                                @csrf
                                                @method('DELETE')

                                                <a 
                                                    href="javascript:void(0)"
                                                    class="text-primary d-flex align-items-center gap-1"
                                                    aria-label="Delete faculty expertise">
                                                    <i class="material-icons material-symbols-rounded"
                                                        style="font-size:18px;" aria-hidden="true">delete</i>
                                                </a>
                                            </form>
                                            @endif

                                        </div>

                                    </td>


                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="material-icons material-symbols-rounded" style="font-size: 48px; opacity: 0.3;">inbox</i>
                                            <p class="mt-2 mb-0">No faculty expertise records found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if (!empty($faculties) && count($faculties) > 0)
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap gap-3">
                    <div class="text-muted small">
                        Showing <span class="fw-semibold">{{ $faculties->firstItem() ?? 0 }}</span>
                        to <span class="fw-semibold">{{ $faculties->lastItem() }}</span>
                        of <span class="fw-semibold">{{ $faculties->total() }}</span> items
                    </div>
                    <div>
                        {{ $faculties->links('vendor.pagination.custom') }}
                    </div>
                </div>
                @endif
                
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<style>
    .faculty-expertise-index .card {
        transition: box-shadow 0.3s ease;
    }
    
    .faculty-expertise-index .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .faculty-expertise-index .btn-primary {
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .faculty-expertise-index .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.3) !important;
    }
    
    /* Table styling */
    .faculty-expertise-index .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .faculty-expertise-index .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .faculty-expertise-index .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .faculty-expertise-index .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .faculty-expertise-index .table tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    
    .faculty-expertise-index .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Action buttons styling */
    .faculty-expertise-index .table tbody td .btn {
        transition: all 0.2s ease;
        margin: 0 2px;
    }
    
    .faculty-expertise-index .table tbody td .btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .faculty-expertise-index .table tbody td .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    
    .faculty-expertise-index .table tbody td .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }
    
    /* Form switch styling */
    .faculty-expertise-index .form-check-input {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .faculty-expertise-index .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    
    .faculty-expertise-index .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
    }
    
    /* Empty state styling */
    .faculty-expertise-index .table tbody td.text-center {
        background-color: #f8f9fa;
    }
    
    @media (max-width: 768px) {
        .faculty-expertise-index .d-flex.justify-content-between {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .faculty-expertise-index .btn-primary {
            width: 100%;
            justify-content: center;
        }
        
        .faculty-expertise-index .card-body {
            padding: 1rem !important;
        }
        
        .faculty-expertise-index .table thead th,
        .faculty-expertise-index .table tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .faculty-expertise-index .table tbody td .d-flex.gap-2 {
            flex-direction: column;
            width: 100%;
        }
        
        .faculty-expertise-index .table tbody td .btn {
            width: 100%;
            margin: 2px 0;
            justify-content: center;
        }
    }
    
    @media (max-width: 576px) {
        .faculty-expertise-index .table thead th {
            font-size: 0.75rem;
            padding: 0.5rem 0.5rem;
        }
        
        .faculty-expertise-index .table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.8125rem;
        }
        
        .faculty-expertise-index .d-flex.justify-content-between.mt-4 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 1rem !important;
        }
    }
</style>

@endsection