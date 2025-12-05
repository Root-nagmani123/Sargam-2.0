@extends('admin.layouts.master')
@section('title', 'Calendar - Sargam | Lal Bahadur')

@section('setup_content')
    @include('components.calendar1', compact('courseMaster', 'facultyMaster', 'subjects', 'venueMaster', 'classSessionMaster'))
@endsection

@section('academics_content')
    @include('components.calendar1', compact('courseMaster', 'facultyMaster', 'subjects', 'venueMaster', 'classSessionMaster'))
@endsection