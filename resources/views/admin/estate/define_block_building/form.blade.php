@extends('admin.layouts.master')

@section('title', ($item ? 'Edit' : 'Add') . ' Block/Building - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_form_page', ['item' => $item, 'cfg' => [
    'title' => 'Define Block/Building',
    'singular' => 'Block/Building',
    'routePrefix' => 'admin.estate.define-block-building',
    'fields' => [
        ['name' => 'block_name', 'label' => 'Building/Block', 'required' => true, 'maxlength' => 40, 'placeholder' => 'eg. Kalindi Block'],
    ],
]])
@endsection
