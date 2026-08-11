@extends('admin.layouts.master')
@section('title', 'Request For Duplicate ID Card - Sargam')
@section('content')
<div class="container-fluid">
    <x-breadcrum title="Request For Duplicate ID Card"></x-breadcrum>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="text-muted small">Show</label>
                        <select name="per_page" class="form-select form-select-sm" style="width:90px" onchange="this.form.submit()">
                            @foreach([10,25,50,100] as $n)
                                <option value="{{ $n }}" {{ (int)request('per_page',10)===$n ? 'selected':'' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted small">entries</span>
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                    </form>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="per_page" value="{{ request('per_page',10) }}">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control " placeholder="Search with in table:" style="width:220px">
                        <button class="btn btn-sm btn-primary">Search</button>
                    </form>
                    <a href="{{ route('admin.duplicate_idcard.create') }}" class="btn btn-sm btn-success">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;"></i>
                        Add New Duplicate id card
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap align-middle', 'id' => 'duplicateIdcardTable']) !!}
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
@endpush

