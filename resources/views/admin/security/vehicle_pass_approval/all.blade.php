@extends('admin.layouts.master')
@section('title', 'All Vehicle Pass Applications - Security Management')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'All Vehicle Pass Applications']) 
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">All Vehicle Pass Applications</h4>
                <a href="{{ route('admin.security.vehicle_pass.create') }}" class="btn btn-primary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Apply for New Pass
                </a>
            </div>

            <div class="alert alert-info">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">info</i>
                This page displays all vehicle pass applications regardless of status.
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover mb-0" id="allApplicationsTable">
                    <thead>
                        <tr>
                            <th style="width:70px;">S.No.</th>
                            <th style="width:140px;">Request ID</th>
                            <th>Employee</th>
                            <th>Vehicle Type</th>
                            <th style="width:140px;">Reg. Number</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:110px;">Forward</th>
                            <th style="width:120px;">Created Date</th>
                            <th style="width:140px;">Actions</th>
                        </tr>
                    </thead>
                    {{-- Rows are served page-by-page by the server-side grid below. --}}
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@include('components.mess-master-datatables', [
    'tableId' => 'allApplicationsTable',
    'searchPlaceholder' => 'Search vehicle pass applications...',
    'orderColumn' => 7,
    'orderDir' => 'desc',
    'actionColumnIndex' => 8,
    'infoLabel' => 'applications',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.security.vehicle_pass_approval.all'),
])
@endsection
