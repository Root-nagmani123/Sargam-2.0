@extends('admin.layouts.master')
@section('title', 'All Family ID Card Applications')
@section('setup_content')
@php
    $familyApprovalReturn = in_array(request('return'), ['approval2', 'approval3'], true) ? request('return') : null;
    $familyShowReturnQs = $familyApprovalReturn ? ('?return=' . $familyApprovalReturn) : '';
@endphp
<div class="container-fluid">
    <x-breadcrum title="All Family ID Card Applications"></x-breadcrum>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">All Family ID Card Applications</h4>
                <a href="{{ route('admin.security.family_idcard_approval.index', array_filter(['return' => $familyApprovalReturn])) }}" class="btn btn-primary">
                    Pending Approvals
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped mb-0" id="allFamilyIdcardTable">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Family Member</th>
                            <th>Employee ID</th>
                            <th>Relation</th>
                            <th>Status</th>
                            <th>Applied On</th>
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
    'tableId' => 'allFamilyIdcardTable',
    'searchPlaceholder' => 'Search family ID card applications...',
    'orderColumn' => 5,
    'orderDir' => 'desc',
    'actionColumnIndex' => 6,
    'infoLabel' => 'applications',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.security.family_idcard_approval.all', array_filter(['return' => $familyApprovalReturn])),
])
@endsection
