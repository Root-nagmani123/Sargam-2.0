@extends('admin.layouts.master')
@section('title', 'Activity Reports Summary')
@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities - Report Summary"></x-breadcrum>
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">Department Report Summary</h4>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0 js-fc-datatable" data-export-title="FC Activities - Department Summary">
            <thead><tr><th>Admin</th><th>Security</th><th>IT</th><th>Training</th><th>Medical</th><th>Shop</th></tr></thead>
            <tbody><tr>
                @foreach(['admin','security','it','trg','medical','shop'] as $dept)
                    <td><a href="{{ route('fc-reg.admin.activities.reports.department', $dept) }}">{{ $counts[$dept] ?? 0 }}</a></td>
                @endforeach
            </tr></tbody>
        </table>
    </div></div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')
