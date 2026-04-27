@extends('admin.layouts.master')
@section('title', 'Service Wise Report')
@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities - Service Wise Report"></x-breadcrum>
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">Service-wise Joined Count</h4>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0 js-fc-datatable" data-export-title="FC Activities - Service Wise Joined Count">
            <thead><tr>@foreach($services as $svc)<th>{{ $svc }}</th>@endforeach</tr></thead>
            <tbody><tr>@foreach($services as $svc)<td>{{ $counts[$svc] ?? 0 }}</td>@endforeach</tr></tbody>
        </table>
    </div></div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')
