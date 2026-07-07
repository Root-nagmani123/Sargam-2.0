@extends('layouts.app')
@section('title','Service Wise Status')
@section('content')
<h2 class="mb-4">Service Wise Joined Count</h2>
<table class="tbl">
    <tr>
        @foreach($services as $svc)
        <th>{{ $svc }}</th>
        @endforeach
    </tr>
    <tr>
        @foreach($services as $svc)
        <td>{{ $counts[$svc] ?? 0 }}</td>
        @endforeach
    </tr>
</table>
@endsection
