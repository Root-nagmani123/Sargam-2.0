@extends('admin.layouts.master')

@section('title', 'Define Block/Building - Sargam')

@section('setup_content')
@include('admin.estate.partials.master_crud', ['cfg' => [
    'key' => 'blockBuilding',
    'title' => 'Define Block/Building',
    'singular' => 'Block/ Building',
    'routePrefix' => 'admin.estate.define-block-building',
    'tableId' => 'blockBuildingTable',
    'items' => $items,
    'fields' => [
        ['name' => 'block_name', 'label' => 'Block/ Building Name', 'column' => 'Building/ Block', 'required' => true, 'maxlength' => 40, 'placeholder' => 'eg. Kalindi Block'],
    ],
]])
@endsection
