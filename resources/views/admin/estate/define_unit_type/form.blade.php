@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Unit Type - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_form_page', ['item' => $item, 'cfg' => [
    'title' => 'Define Unit Type',
    'singular' => 'Unit Type',
    'routePrefix' => 'admin.estate.define-unit-type',
    'fields' => [
        ['name' => 'unit_type', 'label' => 'Unit Type', 'required' => true, 'maxlength' => 50, 'placeholder' => 'eg. Type-II'],
    ],
]])
@endsection
