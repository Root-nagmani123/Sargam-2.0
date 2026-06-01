@extends('admin.layouts.master')

@section('title', 'Edit City')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.city.index', [
        'open_cty_modal' => 'edit',
        'cty_id' => request()->route('id'),
    ])));
</script>
@endsection
