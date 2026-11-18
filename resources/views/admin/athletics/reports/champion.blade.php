@extends('admin.layouts.master')

@section('title', 'Overall Champion')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum title="Overall Champion" :showBack="false" />

    <x-session_message />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        @include('admin.athletics.partials.export_actions', [
            'route' => 'admin.athletics.reports.champion.export', 'params' => array_filter($filters), 'prefix' => 'amChamp',
        ])
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <form method="GET" class="d-flex flex-wrap align-items-center gap-3" id="amChampFilters">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="course" class="form-select" data-searchable="true" data-placeholder="Course" aria-label="Course">
                            <option value="">Course</option>
                            @foreach ($courses as $pk => $label)
                                <option value="{{ $pk }}" @selected($filters['course'] === (int) $pk)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="programme-dt-filter-select">
                        <select name="drive" class="form-select" data-searchable="true" data-placeholder="Drive Name" aria-label="Drive Name">
                            <option value="">Drive Name</option>
                            @foreach ($drives as $d)
                                <option value="{{ $d->id }}" @selected($filters['drive'] === $d->id)>{{ $d->form_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('admin.athletics.reports.champion.index') }}" class="btn programme-dt-btn-reset d-inline-flex align-items-center">Remove Filter</a>
                </form>
                @include('admin.athletics.partials.grid_tools', ['table' => 'amChampTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amChampTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">Rank</th>
                                <th scope="col">House</th>
                                <th scope="col" class="text-center">1st</th>
                                <th scope="col" class="text-center">2nd</th>
                                <th scope="col" class="text-center">3rd</th>
                                <th scope="col" class="text-center">Total Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row->rank }}</td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-2">
                                            {{ $row->name }}
                                            @if ($row->rank === 1)
                                                <span class="ux4g-tag-tonal-success">
                                                    <span class="material-icons material-symbols-rounded" style="font-size:16px" aria-hidden="true">emoji_events</span>Champion
                                                </span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-center">{{ (int) $row->gold }}</td>
                                    <td class="text-center">{{ (int) $row->silver }}</td>
                                    <td class="text-center">{{ (int) $row->bronze }}</td>
                                    <td class="text-center fw-semibold">{{ rtrim(rtrim(number_format((float) $row->points, 2, '.', ''), '0'), '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amChampTable"></div>
            </div>
        </div>
    </div>
</div>

@include('admin.athletics.partials.colvis_modal', ['table' => 'amChampTable'])
@endsection

@push('scripts')
<script>
$(function () {
    AthleticsGrid.init({
        table: '#amChampTable',
        key: 'amChampGrid:hidden:v1',
        dtOptions: { order: [[0, 'asc']] },
        exportKeys: ['rank', 'house', 'gold', 'silver', 'bronze', 'points'],
        links: ['amChampCsv', 'amChampExcel', 'amChampPdf', 'amChampPrint'],
        colvisGrid: '#amChampTableColvisGrid'
    });
    $('#amChampFilters select').on('change', function () { this.form.submit(); });
});
</script>
@endpush
