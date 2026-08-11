@extends('admin.layouts.master')
@section('title', 'Visitor Pass Management')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Visitor Pass Management']) 
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Visitor Pass Management</h4>
                <a href="{{ route('admin.security.visitor_pass.create') }}" class="btn btn-primary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">add</i>
                    Register New Visitor
                </a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped mb-0" id="visitorPassTable">
                    <thead>
                        <tr>
                            <th>Pass #</th>
                            <th>Visitor(s)</th>
                            <th>Company</th>
                            <th>Purpose</th>
                            <th>Host Employee</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Actions</th>
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
    'tableId' => 'visitorPassTable',
    'searchPlaceholder' => 'Search visitor passes...',
    'orderColumn' => 0,
    'actionColumnIndex' => 7,
    'infoLabel' => 'visitor passes',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.security.visitor_pass.index'),
])
@endsection
