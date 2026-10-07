@extends('admin.layouts.master')

@section('title', 'Define Unit Type - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_crud', ['cfg' => [
    'key' => 'unitType',
    'title' => 'Define Unit Type',
    'intro' => 'House types (Type-I, Type-II …) used by houses, eligibility criteria and electric slabs.',
    'singular' => 'Unit Type',
    'routePrefix' => 'admin.estate.define-unit-type',
    'tableId' => 'unitTypeTable',
    'emptyIcon' => 'house',
    'items' => $items,
    'fields' => [
        ['name' => 'unit_type', 'label' => 'Unit Type', 'required' => true, 'maxlength' => 50, 'placeholder' => 'eg. Type-II'],
    ],
]])
@endsection
