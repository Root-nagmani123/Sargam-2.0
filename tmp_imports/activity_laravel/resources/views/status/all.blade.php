@extends('layouts.app')
@section('title','All Activity Status')
@section('content')
<h2 class="mb-4">All Activity Status</h2>
<table class="tbl" style="position:relative">
    <tr>
        <th>#</th><th>Name</th><th>OT Code</th><th>Mobile</th><th>Service</th>
        <th>Admin</th><th>Security</th><th>IT</th><th>Training</th><th>Souvenir</th><th>Medical</th>
    </tr>
    @foreach($rows as $i => $row)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $row['otname'] }}</td>
        <td>{{ $row['otcode'] }}</td>
        <td>{{ $row['mobileno'] }}</td>
        <td>{{ $row['service'] }}</td>
        @foreach(['joined','idcard','biometric','trgind','souvenir','height'] as $code)
        @php $val = $row['activities'][$code] ?? null; @endphp
        <td class="{{ $val ? '' : 'cell-empty' }}">{{ $val ? 'Done' : '' }}</td>
        @endforeach
    </tr>
    @endforeach
</table>
@endsection
