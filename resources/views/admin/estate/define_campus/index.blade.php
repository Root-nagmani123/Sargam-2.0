@extends('admin.layouts.master')

@section('title', 'Define Estate/Campus - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_crud', ['cfg' => [
    'key' => 'campus',
    'title' => 'Define Estate/Campus',
    'intro' => 'Estates and campuses that houses and possessions are recorded against.',
    'singular' => 'Estate/Campus',
    'routePrefix' => 'admin.estate.define-campus',
    'tableId' => 'campusTable',
    'emptyIcon' => 'buildings',
    'items' => $items,
    'fields' => [
        ['name' => 'campus_name', 'label' => 'Estate/Campus', 'required' => true, 'maxlength' => 50, 'placeholder' => 'eg. LBSNAA Campus'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'maxlength' => 250, 'placeholder' => 'Optional description', 'wrap' => true],
    ],
]])
@endsection
