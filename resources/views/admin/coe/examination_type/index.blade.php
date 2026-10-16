{{--
    COE → Master Data → Examination Type Master, server-side (Yajra) version
    of resources/views/admin/coe/simple_master/index.blade.php. Same markup/CSS
    (coe-admin.css) and modals; only the grid's data source differs — rows come
    from $master['endpoints']['list'] via CoeGrid.simpleMasterServerSide(), not
    a server-rendered <tbody>. Behaviour is in public/js/coe-grid.js.
--}}
@extends('admin.layouts.master')

@section('title', $master['title'])

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $p = $master['prefix'];
    $l = $master['labels'];
@endphp
<div class="container-fluid coe-page">
    <x-breadcrum :title="$master['title']" :showBack="false"
                 :items="['Setup', 'COE', 'Master Data', $master['title']]">
        <button type="button" class="btn coe-btn-header" id="{{ $p }}CreateBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i><span>{{ $l['createButton'] }}</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions">
        <button type="button" id="{{ $p }}DownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="{{ $p }}PrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#{{ $p }}ColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <div id="{{ $p }}DtSearch" class="programme-dt-search d-none" data-dt-search-for="{{ $p }}Table"></div>
                    <button type="button" id="{{ $p }}SearchToggle" class="coe-search-toggle"
                            aria-controls="{{ $p }}DtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="{{ $p }}Table" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-sno">{{ $master['sno']['label'] }}</th>
                                @foreach ($master['fields'] as $f)
                                    <th scope="col">{{ $f['column'] }}</th>
                                @endforeach
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- Rows are drawn by DataTables from $master['endpoints']['list'] (serverSide). --}}
                        <tbody></tbody>
                    </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="{{ $p }}Table"></div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="{{ $p }}FormModal" tabindex="-1" aria-labelledby="{{ $p }}FormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content coe-modal border-0 shadow">
            <form id="{{ $p }}Form" novalidate>
                <div class="modal-header coe-modal-header coe-modal-header--rule">
                    <h5 class="modal-title" id="{{ $p }}FormModalLabel">{{ $l['createTitle'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body coe-modal-body">
                    <div class="row g-3">
                        @foreach ($master['fields'] as $f)
                            <div class="col-12">
                                <label class="coe-form-label" for="{{ $p }}Field_{{ $f['key'] }}">
                                    {{ $f['label'] }}@if (! empty($f['required']))<span class="coe-req">*</span>@endif
                                </label>
                                <input type="text" id="{{ $p }}Field_{{ $f['key'] }}"
                                       name="{{ $f['name'] }}" data-coe-field="{{ $f['key'] }}"
                                       class="form-control coe-control"
                                       placeholder="{{ $f['placeholder'] ?? '' }}" autocomplete="off"
                                       maxlength="{{ $f['max'] ?? '' }}"
                                       @if (! empty($f['required'])) required @endif>
                            </div>
                        @endforeach
                        <div class="col-12">
                            <label class="coe-form-label" for="{{ $p }}Status">Status<span class="coe-req">*</span></label>
                            <select id="{{ $p }}Status" name="active_inactive" class="form-select coe-control" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer coe-modal-footer">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn coe-btn coe-btn-primary coe-btn--narrow" id="{{ $p }}Submit">{{ $l['createSubmit'] }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.coe.partials.confirm_delete_modal', ['id' => $p.'DeleteModal', 'confirmId' => $p.'DeleteConfirm'])
@include('admin.coe.partials.confirm_status_modal')

<div class="modal fade" id="{{ $p }}ColumnVisibilityModal" tabindex="-1" aria-labelledby="{{ $p }}ColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="{{ $p }}ColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="{{ $p }}ColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
@php $masterJs = $master + ['csrfToken' => csrf_token()]; @endphp
<script>
$(function () {
    CoeGrid.simpleMasterServerSide(@json($masterJs));
});
</script>
@endpush
