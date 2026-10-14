@extends('admin.layouts.master')

@section('title', 'Examination Drive Configuration')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
<style>
.edc-status-pending-faculty { background:#fff3cd;color:#856404; }
/* Complete Setup action link */
.edc-setup-link { display:inline-flex;flex-direction:column;align-items:center;gap:3px;
                  text-decoration:none;color:#004384;font-size:11px;font-weight:600; }
.edc-setup-link i { font-size:20px; }
.edc-setup-link:hover{ opacity:.75; }
</style>
@endpush

@section('setup_content')
@php $isArchived = $scope === 'archived'; @endphp
<div class="container-fluid coe-page">
    <x-breadcrum title="Examination Drive Configuration" :showBack="false"
                 :items="['Setup', 'COE', 'Examination Management', 'Roll Numbers']">
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs + Download/Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <nav class="coe-tabs" aria-label="Scope">
            <a href="{{ route('admin.coe.faculty-examination-drive.index') }}"
               class="coe-tab {{ $isArchived ? '' : 'is-active' }}"
               @unless($isArchived) aria-current="page" @endunless>Active</a>
            <a href="{{ route('admin.coe.faculty-examination-drive.index', ['scope' => 'archived']) }}"
               class="coe-tab {{ $isArchived ? 'is-active' : '' }}"
               @if($isArchived) aria-current="page" @endif>Archived</a>
        </nav>
        <div class="d-flex gap-2 coe-secondary-actions">
            <button type="button" id="fedcDownloadBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <button type="button" id="fedcPrintBtn" class="btn programme-dt-btn-columns">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar coe-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select id="fedcFilterCourse" class="form-select" aria-label="Course Name">
                            <option value="">Course Name</option>
                            @foreach ($courses as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="fedcFilterTerm" class="form-select" aria-label="Term">
                            <option value="">Term</option>
                            @foreach ($terms as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="fedcFilterPhase" class="form-select" aria-label="Phase">
                            <option value="">Phase</option>
                            @foreach ($phases as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select id="fedcFilterType" class="form-select" aria-label="Examination Type">
                            <option value="">Examination Type</option>
                            @foreach ($examTypes as $et)<option value="{{ $et }}">{{ $et }}</option>@endforeach
                        </select>
                    </div>
                    <button type="button" id="fedcResetFilters" class="btn programme-dt-btn-reset">Remove Filter</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns"
                            data-bs-toggle="modal" data-bs-target="#fedcColumnVisibilityModal">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="fedcDtSearch" class="programme-dt-search d-none" data-dt-search-for="fedcTable"></div>
                    <button type="button" id="fedcSearchToggle" class="coe-search-toggle"
                            aria-controls="fedcDtSearch" aria-expanded="false" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="fedcTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th>S. NO.</th>
                                <th>DRIVE NAME</th>
                                <th>EXAMINATION TYPE</th>
                                <th>TERM</th>
                                <th>COURSE</th>
                                <th>ACADEMIC SESSION</th>
                                <th>FACULTY</th>
                                <th class="coe-col-center">SUB-COORDINATES</th>
                                <th class="coe-col-center">COMPONENTS</th>
                                <th>START DATE</th>
                                <th>END DATE</th>
                                <th class="coe-col-center">STATUS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drives as $drive)
                            <tr>
                                <td></td>
                                <td>{{ $drive['name'] }}</td>
                                <td>{{ $drive['exam_type'] === '-' ? '—' : $drive['exam_type'] }}</td>
                                <td>{{ $drive['term'] }}</td>
                                <td>{{ $drive['course'] }}</td>
                                <td>{{ $drive['session'] }}</td>
                                <td>{{ $drive['faculty'] }}</td>
                                <td class="coe-col-center">{{ $drive['sub_coords'] ?: '—' }}</td>
                                <td class="coe-col-center">{{ $drive['components'] ?: '—' }}</td>
                                <td>{{ $drive['start_date'] }}</td>
                                <td>{{ $drive['end_date'] }}</td>
                                <td class="coe-col-center">
                                    <span class="coe-state edc-status-pending-faculty">Pending at Faculty</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.coe.faculty-examination-drive.setup', $drive['id']) }}"
                                       class="edc-setup-link" title="Complete Setup">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        <span>Complete<br>Setup</span>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="fedcTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="fedcColumnVisibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0">
                <div class="row g-3" id="fedcColumnToggleGrid"></div>
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
<script>
$(function () {
    CoeGrid.init({
        table: '#fedcTable',
        title: 'Examination Drive Configuration',
        snoCol: 0,
        order: [[1, 'asc']],
        noSort: [0, 12],
        exclude: [12],
        filters: { '#fedcFilterCourse': 4, '#fedcFilterTerm': 3, '#fedcFilterType': 2 },
        reset: '#fedcResetFilters',
        download: '#fedcDownloadBtn',
        print: '#fedcPrintBtn',
        searchToggle: '#fedcSearchToggle',
        searchSlot: '#fedcDtSearch',
        colvisGrid: '#fedcColumnToggleGrid',
        colvisKey: 'coeFacultyExamDrive:hiddenColumns:v1',
        empty: { title: 'No Drives Assigned', text: 'No examination drives have been assigned to you yet.' }
    });
});
</script>
@endpush
