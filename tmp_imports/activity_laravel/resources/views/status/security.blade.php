@extends('layouts.app')
@section('title','Security Status')
@section('content')
<h2 class="mb-4">Security Status — ID Card Issued</h2>
<table width="100%"><tr>
    <th>Done: {{ $count }}</th>
    <th>Pending: {{ $total - $count }}</th>
</tr></table>
<table class="tbl">
    <tr><th>#</th><th>Name</th><th>OT Code</th><th>ID Card Issued</th></tr>
    @foreach($rows as $i => $row)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $row['otname'] }}</td>
        <td>{{ $row['otcode'] }}</td>
        <td class="{{ $row['done'] ? '' : 'cell-empty' }}">{{ $row['done'] ? 'Issued' : '' }}</td>
    </tr>
    @endforeach
</table>
@endsection
