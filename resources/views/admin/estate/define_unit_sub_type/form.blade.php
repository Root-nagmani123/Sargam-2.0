@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Unit Sub Type - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_form_page', ['item' => $item, 'cfg' => [
    'title' => 'Define Unit Sub Type',
    'singular' => 'Unit Sub Type',
    'routePrefix' => 'admin.estate.define-unit-sub-type',
    'fields' => [
        ['name' => 'unit_sub_type', 'label' => 'Unit Sub Type', 'required' => true, 'maxlength' => 17, 'placeholder' => 'eg. Type-(12)'],
    ],
]])
@endsection
