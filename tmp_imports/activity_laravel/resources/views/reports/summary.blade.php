@extends('layouts.app')
@section('title','Report Summary')
@section('content')
<h2 class="mb-4">Department Report Summary</h2>
<table class="tbl">
    <tr>
        <th>Admin</th><th>Security</th><th>IT</th>
        <th>Training</th><th>Medical Center</th><th>Souvenir</th>
    </tr>
    <tr>
        @foreach(['admin','security','it','trg','medical','shop'] as $dept)
        <td>
            <a href="{{ route('reports.department', $dept) }}" target="_blank">
                {{ $counts[$dept] ?? 0 }}
            </a>
        </td>
        @endforeach
    </tr>
</table>
@endsection
