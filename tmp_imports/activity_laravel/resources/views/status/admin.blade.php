@extends('layouts.app')
@section('title','Admin Status')
@section('content')
<h2 class="mb-4">Admin Status — Joined</h2>
<table width="100%">
    <tr>
        <th>Joined: {{ $count }}</th>
        <th>Not Joined: <a href="{{ route('reports.not-joined') }}" target="_blank">{{ $total - $count }}</a></th>
    </tr>
</table>
<table class="tbl">
    <tr><th>#</th><th>Name</th><th>OT Code</th><th>Joined</th></tr>
    @foreach($rows as $i => $row)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $row['otname'] }}</td>
        <td>{{ $row['otcode'] }}</td>
        <td class="{{ $row['done'] ? '' : 'cell-empty' }}">
            {{ $row['done'] ? 'Joined' : '' }}
        </td>
    </tr>
    @endforeach
</table>
@endsection
