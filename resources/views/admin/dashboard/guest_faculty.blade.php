@extends('admin.layouts.master')

@section('title', 'Guest Faculty')

@section('content')
@include('admin.dashboard.partials.faculty_table', [
    'faculties'    => $guest_faculty,
    'tableId'      => 'guess_faculty',
    'cssFile'      => 'css/guest_faculty.css',
    'cardClass'    => 'guest-faculty-card',
    'pageTitle'    => 'Guest Faculty',
    'exportTitle'  => 'Guest Faculty',
    'badgeClass'   => 'badge-guest',
    'badgeLabel'   => 'Guest',
    'emptyMessage' => 'No guest faculty found',
])
@endsection
