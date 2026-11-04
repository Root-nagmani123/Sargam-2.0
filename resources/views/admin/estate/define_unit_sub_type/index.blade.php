@extends('admin.layouts.master')

@section('title', 'Define Unit Sub Type - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_crud', ['cfg' => [
    'key' => 'unitSubType',
    'title' => 'Define Unit Sub Type',
    'singular' => 'Unit Sub-Type',
    'routePrefix' => 'admin.estate.define-unit-sub-type',
    'tableId' => 'unitSubTypeTable',
    'items' => $items,
    'fields' => [
        ['name' => 'unit_sub_type', 'label' => 'Unit Sub-Type Name', 'column' => 'Sub-Unit Type', 'required' => true, 'maxlength' => 17, 'placeholder' => 'eg. Type-(12)'],
    ],
]])
@endsection
