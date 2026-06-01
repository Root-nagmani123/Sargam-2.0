@extends('admin.layouts.master')

@section('title', 'Country')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.country.index', ['open_cty_modal' => 'add'])));
</script>
@endsection
