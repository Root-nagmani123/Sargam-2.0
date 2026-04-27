@extends('layouts.app')
@section('title','Medical Status')
@section('content')
<h2 class="mb-4">Medical Status</h2>
<table class="tbl">
    <tr>
        <th>#</th><th>Name</th><th>OT Code</th>
        <th>Height</th><th>Weight</th><th>Pulse</th>
        <th>Blood Pressure</th><th>Vial Tube</th><th>Blood Sample</th>
    </tr>
    @foreach($rows as $i => $row)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $row['otname'] }}</td>
        <td>{{ $row['otcode'] }}</td>
        @foreach(['height','weight','pulse','bp','vialtube','bloodsample'] as $code)
        @php $val = $row['activities'][$code] ?? null; @endphp
        <td class="{{ $val ? '' : 'cell-empty' }}">
            {{ $val ?: '' }}
        </td>
        @endforeach
    </tr>
    @endforeach
</table>
@endsection
