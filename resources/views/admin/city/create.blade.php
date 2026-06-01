@extends('admin.layouts.master')

@section('title', 'Create City')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.city.index', ['open_cty_modal' => 'add'])));
</script>
@endsection
