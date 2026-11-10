{{--
    COE → Master Data → Examination Term Master, server-side (Yajra) version
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

    @php $exportUrl = fn (string $format) => str_replace('__FORMAT__', $format, $master['endpoints']['export'] ?? ''); @endphp
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 coe-secondary-actions">
        @if (! empty($master['endpoints']['export']))
            {{-- Sargam's master export (docs/new-design-index-page.md §1): CSV / Excel / PDF and a
                 server-rendered branded Print, all from ExportsCoeMaster. ?q=, ?status_filter= and
                 ?cols= are stamped on by CoeGrid so each format is the grid as currently shown. --}}
            <div class="dropdown">
                <button type="button" id="{{ $p }}DownloadToggle"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{{ $p }}DownloadToggle">
                    <li><a class="dropdown-item coe-export-link" href="{{ $exportUrl('csv') }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                    <li><a class="dropdown-item coe-export-link" href="{{ $exportUrl('excel') }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                    <li><a class="dropdown-item coe-export-link" href="{{ $exportUrl('pdf') }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
                </ul>
            </div>
            <a href="{{ $exportUrl('print') }}" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary coe-export-link" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        @else
        <button type="button" id="{{ $p }}DownloadBtn" class="btn programme-dt-btn-columns" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </button>
        <button type="button" id="{{ $p }}PrintBtn" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
        @endif
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                {{-- Active / Inactive filter (the grid reads status_filter); only masters that set statusFilter --}}
                @if (! empty($master['statusFilter']))
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="{{ $p }}StatusFilter" class="form-select" aria-label="Status">
                            <option value="">Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <button type="button" id="{{ $p }}ResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                @endif
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
                    <table id="{{ $p }}Table" class="table table-hover align-middle mb-0 w-100 programme-dt-table {{ ! empty($master['pinActions']) ? 'coe-master-table' : '' }}">
                        <thead>
                            <tr>
                                <th scope="col" class="coe-col-sno">{{ $master['sno']['label'] }}</th>
                                @foreach ($master['fields'] as $f)
                                    <th scope="col" @if (($f['align'] ?? '') === 'center') class="coe-col-center" @endif>{{ $f['column'] }}</th>
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
                        {{-- A computed field (eg. a count from another table) is a grid column only. --}}
                        @foreach (collect($master['fields'])->reject(fn ($f) => ! empty($f['computed'])) as $f)
                            <div class="col-12">
                                <label class="coe-form-label" for="{{ $p }}Field_{{ $f['key'] }}">
                                    {{ $f['label'] }}@if (! empty($f['required']))<span class="coe-req">*</span>@endif
                                </label>
                                @if (($f['type'] ?? 'text') === 'select')
                                    <select id="{{ $p }}Field_{{ $f['key'] }}"
                                            name="{{ $f['name'] }}" data-coe-field="{{ $f['key'] }}"
                                            class="form-select coe-control"
                                            @if (! empty($f['required'])) required @endif>
                                        <option value="">{{ $f['placeholder'] ?? 'Select' }}</option>
                                        @foreach ($f['options'] as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
                                    </select>
                                @elseif (($f['type'] ?? 'text') === 'number')
                                    <input type="number" id="{{ $p }}Field_{{ $f['key'] }}"
                                           name="{{ $f['name'] }}" data-coe-field="{{ $f['key'] }}"
                                           class="form-control coe-control"
                                           placeholder="{{ $f['placeholder'] ?? '' }}" autocomplete="off"
                                           min="{{ $f['minValue'] ?? 0 }}" max="{{ $f['maxValue'] ?? '' }}" step="{{ $f['step'] ?? 'any' }}"
                                           @if (! empty($f['required'])) required @endif>
                                @else
                                <input type="text" id="{{ $p }}Field_{{ $f['key'] }}"
                                       name="{{ $f['name'] }}" data-coe-field="{{ $f['key'] }}"
                                       class="form-control coe-control"
                                       placeholder="{{ $f['placeholder'] ?? '' }}" autocomplete="off"
                                       maxlength="{{ $f['max'] ?? '' }}"
                                       @if (! empty($f['required'])) required @endif>
                                @endif
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
