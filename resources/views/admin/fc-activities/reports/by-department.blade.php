@extends('admin.layouts.master')
@section('title', 'Department Report')
@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities - Department Report"></x-breadcrum>
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">{{ ucfirst($dept) }} - {{ $actCode }}</h4>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0 js-fc-datatable" data-export-title="FC Activities - Department Report">
            <thead><tr><th>#</th><th>OT Name</th><th>OT Code</th><th>Mobile</th><th>Service</th></tr></thead>
            <tbody>
            @foreach($ots as $i => $ot)
                <tr><td>{{ $i + 1 }}</td><td>{{ $ot->otname }}</td><td>{{ $ot->otcode }}</td><td>{{ $ot->mobileno }}</td><td>{{ $ot->service }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')
