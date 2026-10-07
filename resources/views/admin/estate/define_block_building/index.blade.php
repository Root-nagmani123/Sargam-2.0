@extends('admin.layouts.master')

@section('title', 'Define Block/Building - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_crud', ['cfg' => [
    'key' => 'blockBuilding',
    'title' => 'Define Block/Building',
    'intro' => 'Blocks and buildings that houses are located in.',
    'singular' => 'Block/Building',
    'routePrefix' => 'admin.estate.define-block-building',
    'tableId' => 'blockBuildingTable',
    'emptyIcon' => 'building',
    'items' => $items,
    'fields' => [
        ['name' => 'block_name', 'label' => 'Building/Block', 'required' => true, 'maxlength' => 40, 'placeholder' => 'eg. Kalindi Block'],
    ],
]])
@endsection
