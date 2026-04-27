@extends('layouts.app')
@section('title','Not Joined Report')
@section('content')
<h2 class="mb-4">Joining Status</h2>
<table width="100%">
    <tr>
        <th>Joined: {{ $joinedCount }}</th>
        <th>Not Joined: {{ $notJoinedCount }}</th>
    </tr>
</table>
<table class="tbl">
    <tr><th>#</th><th>Name</th><th>OT Code</th><th>Joined</th></tr>
    @foreach($rows as $i => $row)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $row['otname'] }}</td>
        <td>{{ $row['otcode'] }}</td>
        <td class="{{ $row['joined'] ? '' : 'cell-empty' }}">
            {{ $row['joined'] ? 'Joined' : '' }}
        </td>
    </tr>
    @endforeach
</table>
@endsection
