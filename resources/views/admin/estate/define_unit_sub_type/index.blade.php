@extends('admin.layouts.master')

@section('title', 'Define Unit Sub Type - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_crud', ['cfg' => [
    'key' => 'unitSubType',
    'title' => 'Define Unit Sub Type',
    'intro' => 'Sub-classifications of a house, used by houses and eligibility criteria.',
    'singular' => 'Unit Sub Type',
    'routePrefix' => 'admin.estate.define-unit-sub-type',
    'tableId' => 'unitSubTypeTable',
    'emptyIcon' => 'diagram-3',
    'items' => $items,
    'fields' => [
        ['name' => 'unit_sub_type', 'label' => 'Unit Sub Type', 'required' => true, 'maxlength' => 17, 'placeholder' => 'eg. Type-(12)'],
    ],
]])
@endsection
