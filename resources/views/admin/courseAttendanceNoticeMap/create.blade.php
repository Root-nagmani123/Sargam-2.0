@extends('admin.layouts.master')

@section('title', 'Create Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid py-5">
    <p class="text-secondary mb-0">Opening Add Notice…</p>
</div>
<script>
    window.location.replace(@json(route('memo.notice.management.index', ['open_add_notice' => 1])));
</script>
@endsection
