@extends('layouts.app')
@section('title', ucfirst($dept).' — Completed')
@section('content')
<h2 class="mb-4">{{ ucfirst($dept) }} — Activity: {{ $actCode }}</h2>
<table class="tbl">
    <tr><th>#</th><th>OT Name</th><th>OT Code</th><th>Mobile No</th><th>Service</th></tr>
    @foreach($ots as $i => $ot)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $ot->otname }}</td>
        <td>{{ $ot->otcode }}</td>
        <td>{{ $ot->mobileno }}</td>
        <td>{{ $ot->service }}</td>
    </tr>
    @endforeach
</table>
@endsection
