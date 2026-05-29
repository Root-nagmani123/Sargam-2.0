@extends('admin.layouts.master')

@section('title', 'Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid py-5">
    <p class="text-secondary mb-0">Opening Add Template…</p>
</div>
<script>
    window.location.replace(@json(route('admin.memo-notice.index', ['open_add_template' => 1])));
</script>
@endsection
