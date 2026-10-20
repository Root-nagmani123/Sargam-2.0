{{-- The admin layout. This page used to extend faculty.layouts.master, whose sidebar
     was the static admin partials, unfiltered by the RBAC menu table, so it showed
     Faculty accounts links to screens their roles are not granted (PR #317 F-024).
     That layout has since been deleted (F-026). The admin layout's sidebar is built
     from the viewer's own menu permissions. --}}
@extends('admin.layouts.master')

@section('title', 'Faculty Dashboard')

@section('setup_content')
<h1>Hi Welod</h1>

@endsection
