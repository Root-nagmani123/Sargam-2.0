@extends('admin.layouts.master')

@section('title', 'Edit Country')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.country.index', ['open_cty_modal' => 'edit', 'cty_id' => $country->pk ?? request()->route('id')])));
</script>
@endsection
