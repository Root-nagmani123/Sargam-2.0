@extends('layouts.app')
@section('title','Shop Status')
@section('content')
<h2 class="mb-4">Shop Status — Souvenir Kit</h2>
<table width="100%"><tr>
    <th>Done: {{ $count }}</th>
    <th>Pending: {{ $total - $count }}</th>
</tr></table>
<table class="tbl">
    <tr><th>#</th><th>Name</th><th>OT Code</th><th>Souvenir Kit</th></tr>
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
