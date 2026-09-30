{{--
    COE → Master Data — one screen for every flat "fields + status" master
    (Examination Type, Examination Term, Building, …). Each master's controller
    passes a $master array built by App\Http\Controllers\Admin\COE\Concerns\RendersSimpleMaster:

      title        page heading + export title       "Building Master"
      entity       the thing, singular               "Building"
      prefix       id prefix, unique per page        "ebm" → #ebmTable, #ebmColumnToggleGrid …
      colvisKey    localStorage key for hidden columns
      labels       createButton, createTitle, editTitle, createSubmit, editSubmit
      sno          {show, label}  — the running-number column ("S. No.", "Floor No.")
      filters      [{key, label, wide, options}] — key is a field key or "status"; wide = 180px for a long label
      formOrder    field keys in dialog order (default: grid order)
      fields       [{key, name, label, column, type text|number|select, placeholder,
                     required, requiredMsg, max, min, unique, uniqueWith, half, align,
                     options (select) | dependsOn + optionsBy (dependent select)}]
      rows         [{pk, active, values: {key: value}}]

    Behaviour is CoeGrid.simpleMaster() in public/js/coe-grid.js.
--}}
@extends('admin.layouts.master')

@section('title', $master['title'])

@push('styles')
{{-- Select2 JS is global; its CSS is per page (Status selects are searchable). --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
{{-- COE module layer (coe-*). --}}
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

    {{-- No status pills on a master grid → the export pair sits alone on the right (§1). --}}
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

            {{-- Toolbar (§2): an optional Status filter left · Columns + search right. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-{{ $master['filters'] ? 'between' : 'end' }} gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                @if ($master['filters'])
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filter</span>
                        @foreach ($master['filters'] as $flt)
                            <div class="programme-dt-filter-select {{ $flt['key'] === 'status' ? 'coe-filter-narrow' : (! empty($flt['wide']) ? 'coe-filter-wide' : '') }}">
                                <select id="{{ $p }}Filter_{{ $flt['key'] }}" class="form-select"
                                        data-placeholder="{{ $flt['label'] }}" aria-label="{{ $flt['label'] }}">
                                    <option value="">{{ $flt['label'] }}</option>
                                    @foreach ($flt['options'] as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
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
                    <table id="{{ $p }}Table" class="table table-hover align-middle mb-0 w-100 programme-dt-table {{ $master['sno']['show'] ? '' : 'coe-table--no-sno' }}">
                        <thead>
                            <tr>
                                @if ($master['sno']['show'])
                                    <th scope="col" class="coe-col-sno">{{ $master['sno']['label'] }}</th>
                                @endif
                                @foreach ($master['fields'] as $f)
                                    <th scope="col" @if (($f['align'] ?? '') === 'center') class="coe-col-center" @endif>{{ $f['column'] }}</th>
                                @endforeach
                                <th scope="col" class="coe-col-center">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- @foreach, not @forelse: an @empty colspan row breaks DataTables'
                             column count. DataTables renders its own empty state.
                             Status + Action cells are painted by the script from
                             data-coe-active, so both follow one source after a toggle. --}}
                        <tbody>
                            @foreach ($master['rows'] as $row)
                                <tr data-coe-id="{{ $row['pk'] }}" data-coe-active="{{ $row['active'] ? '1' : '0' }}">
                                    @if ($master['sno']['show'])<td></td>@endif
                                    @foreach ($master['fields'] as $f)
                                        @php $v = $row['values'][$f['key']] ?? ''; @endphp
                                        <td class="coe-cell-{{ $f['key'] }} {{ ($f['align'] ?? '') === 'center' ? 'coe-col-center' : '' }}"
                                            @if (($f['type'] ?? 'text') === 'number') data-order="{{ (int) $v }}" @endif>{{ $v }}</td>
                                    @endforeach
                                    {{-- data-search: with only data-order set, DataTables filters on
                                         the 0/1 and the Status filter would match nothing. --}}
                                    <td class="coe-col-center coe-cell-status" data-order="{{ $row['active'] ? 1 : 0 }}"
                                        data-search="{{ $row['active'] ? 'Active' : 'Inactive' }}">
                                        <span class="coe-state coe-state--{{ $row['active'] ? 'active' : 'inactive' }}">
                                            {{ $row['active'] ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="coe-cell-actions"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills it (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="{{ $p }}Table"></div>
            </div>

        </div>
    </div>
</div>

{{-- Create / Edit — one dialog; the title and submit caption change (§3c). --}}
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
                        @foreach (collect($master['fields'])->sortBy(fn ($f) => array_search($f['key'], $master['formOrder'], true)) as $f)
                            @php
                                $type = $f['type'] ?? 'text';
                                $isNum = $type === 'number';
                            @endphp
                            <div class="{{ ! empty($f['half']) ? 'col-sm-6' : 'col-12' }}">
                                <label class="coe-form-label" for="{{ $p }}Field_{{ $f['key'] }}">
                                    {{ $f['label'] }}@if (! empty($f['required']))<span class="coe-req">*</span>@endif
                                </label>
                                @if ($type === 'select')
                                    {{-- Options are filled by the script (a dependent list is rebuilt
                                         whenever its parent changes). --}}
                                    <select id="{{ $p }}Field_{{ $f['key'] }}" name="{{ $f['name'] }}" data-coe-field="{{ $f['key'] }}"
                                            class="form-select coe-control" @if (! empty($f['required'])) required @endif></select>
                                @else
                                <input type="{{ $isNum ? 'number' : 'text' }}" id="{{ $p }}Field_{{ $f['key'] }}"
                                       name="{{ $f['name'] }}" data-coe-field="{{ $f['key'] }}"
                                       class="form-control coe-control"
                                       placeholder="{{ $f['placeholder'] ?? '' }}" autocomplete="off"
                                       @if ($isNum) min="{{ $f['min'] ?? 0 }}" max="{{ $f['max'] ?? '' }}" step="1" inputmode="numeric"
                                       @else maxlength="{{ $f['max'] ?? '' }}" @endif
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

{{-- Column Visibility --}}
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
{{-- Built first: the json directive splits its argument on commas, so an inline
     array literal breaks it. (Don't write the at-php directive name in a Blade
     comment either — php blocks are extracted before comments are stripped.) --}}
@php $masterJs = \Illuminate\Support\Arr::except($master, ['rows']); @endphp
<script>
$(function () {
    CoeGrid.simpleMaster(@json($masterJs));
});
</script>
@endpush
