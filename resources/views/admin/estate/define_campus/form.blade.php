@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Estate/Campus - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_form_page', ['item' => $item, 'cfg' => [
    'title' => 'Define Estate/Campus',
    'singular' => 'Estate/Campus',
    'routePrefix' => 'admin.estate.define-campus',
    'fields' => [
        ['name' => 'campus_name', 'label' => 'Estate/Campus', 'required' => true, 'maxlength' => 50, 'placeholder' => 'eg. LBSNAA Campus'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false, 'maxlength' => 250, 'placeholder' => 'Optional description'],
    ],
]])
@endsection
