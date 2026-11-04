@extends('admin.layouts.master')

@section('title', 'Define Estate/Campus - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_crud', ['cfg' => [
    'key' => 'campus',
    'title' => 'Define Estate/Campus',
    'singular' => 'Estate/ Campus',
    'addLabel' => 'Add New',
    'routePrefix' => 'admin.estate.define-campus',
    'tableId' => 'campusTable',
    'items' => $items,
    'fields' => [
        ['name' => 'campus_name', 'label' => 'Campus Name', 'column' => 'Estate Campus', 'required' => true, 'maxlength' => 50, 'placeholder' => 'eg. Kalindi'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'maxlength' => 250, 'placeholder' => 'Optional description', 'wrap' => true],
    ],
]])
@endsection
