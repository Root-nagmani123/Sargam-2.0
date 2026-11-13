@extends('admin.layouts.master')

@section('title', 'Inhouse Faculty')

@section('content')
@include('admin.dashboard.partials.faculty_table', [
    'faculties'    => $inhouse_faculty,
    'tableId'      => 'inhouse',
    'cssFile'      => 'css/inhouse_faculty.css',
    'cardClass'    => 'inhouse-faculty-card',
    'pageTitle'    => 'Inhouse Faculty',
    'exportTitle'  => 'Inhouse Faculty',
    'exportUrl'    => route('admin.dashboard.inhouse_faculty.export'),
    'emptyMessage' => 'No inhouse faculty found',
])
@endsection
