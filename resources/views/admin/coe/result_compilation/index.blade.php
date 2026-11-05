@extends('admin.layouts.master')

@section('title', 'Result Compilation')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Result Compilation" :showBack="false"
                 :items="['Setup', 'COE', 'Result Management', 'Result Compilation']">
    </x-breadcrum>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Course scope">
            <a href="{{ route('admin.coe.result-compilation.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}" @unless ($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.result-compilation.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}" @if ($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex flex-wrap gap-2 coe-secondary-actions">
            @include('admin.coe.partials.download_menu', ['prefix' => 'rcp'])
            <button type="button" id="rcpPrintBtn" class="btn programme-dt-btn-columns" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="rcpFilterCourse" class="form-select" data-placeholder="Course" aria-label="Course">
                            <option value="">Course</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="rcpResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#rcpColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="rcpDtSearch" class="programme-dt-search d-none" data-dt-search-for="rcpTable"></div>
                    <button type="button" id="rcpSearchToggle" class="coe-search-toggle"
                            aria-controls="rcpDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="rcpTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table coe-table--no-sno">
                        <thead>
                            <tr>
                                <th scope="col">Course</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Approved</th>
                                <th scope="col">Pending</th>
                                <th scope="col">Passing Average</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php $q = ['course' => $row['course_pk'], 'subject' => $row['subject_pk'], 'scope' => $scope]; @endphp
                                <tr>
                                    <td>{{ $row['course'] }}</td>
                                    <td class="coe-col-wrap">
                                        {{ $row['subject'] }}
                                        <div class="coe-muted small">{{ $row['code'] !== '' ? $row['code'].' · ' : '' }}Maximum {{ \App\Services\COE\ResultMarksService::number($row['max']) }}</div>
                                    </td>
                                    <td data-order="{{ $row['approved'] }}">
                                        <a href="{{ route('admin.coe.result-compilation.participants', $q + ['status' => 'evaluated']) }}">{{ $row['approved'] }} / {{ $row['total'] }}</a>
                                    </td>
                                    <td data-order="{{ $row['pending'] }}">
                                        <a href="{{ route('admin.coe.result-compilation.participants', $q + ['status' => 'pending']) }}">{{ $row['pending'] }}</a>
                                    </td>
                                    <td>{{ $row['average'] === null ? '—' : $row['average'].'%' }}</td>
                                    <td>
                                        @if ($row['pending'] > 0 && ! $isArchived && $row['blocker'])
                                            <span class="coe-muted small">{{ $row['blocker'] }}</span>
                                        @elseif ($row['pending'] > 0 && ! $isArchived)
                                            <button type="button" class="coe-act coe-act--edit coe-act--wide rcp-notify"
                                                    data-course="{{ $row['course_pk'] }}" data-subject="{{ $row['subject_pk'] }}">
                                                <span class="coe-act__icon"><i class="bi bi-chat-square-dots" aria-hidden="true"></i></span>
                                                <span class="coe-act__label">Notify for Pending</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="rcpTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.coe.partials.colvis_modal', ['id' => 'rcpColumnVisibilityModal', 'grid' => 'rcpColumnToggleGrid'])
@endsection

@push('scripts')
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    CoeGrid.init({
        table: '#rcpTable',
        title: @json($isArchived ? 'Result Compilation - Archived' : 'Result Compilation'),
        snoCol: false,
        order: [],
        noSort: [5],
        exclude: [5],
        filters: { '#rcpFilterCourse': 0 },
        reset: '#rcpResetFilters',
        download: '#rcpDownloadExcel',
        pdf: '#rcpDownloadPdf',
        print: '#rcpPrintBtn',
        searchToggle: '#rcpSearchToggle',
        searchSlot: '#rcpDtSearch',
        colvisGrid: '#rcpColumnToggleGrid',
        colvisKey: 'coeResultCompilation:hiddenColumns:v1',
        empty: { title: 'No Results', zeroTitle: 'No Results Found', text: 'Results appear here once faculty approve marks in Result Entry.' }
    });

    // Bell notification to the faculty who still owe marks for this course + subject.
    $('#rcpTable').on('click', '.rcp-notify', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: @json(route('admin.coe.result-compilation.notify')),
            method: 'POST',
            data: { course: $btn.data('course'), subject: $btn.data('subject') },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).done(function (res) {
            Swal.fire({ icon: 'success', title: 'Notify for Pending', text: res.message });
        }).fail(function (xhr) {
            Swal.fire({ icon: 'error', title: 'Notify for Pending',
                text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not send the reminder.' });
        }).always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endpush
