@extends('admin.layouts.master')

@section('title', 'User Registration and Exemption Summary - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid exemption-datalist-page py-3">
    <x-breadcrum title="User Registration and Exemption Summary" />

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- One form drives both Filter and Export; the Export button posts to a
                 different action via formaction, so the filters always travel with it. --}}
            <form method="GET" action="{{ route('admin.exemption.export') }}"
                  class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                @csrf

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select name="exemption_category" id="exemption_category" class="form-select" aria-label="Exemption Category">
                            <option value="">Exemption Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->Exemption_name }}"
                                    {{ request('exemption_category') == $category->Exemption_name ? 'selected' : '' }}>
                                    {{ $category->Exemption_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select name="application_type" id="application_type" class="form-select" aria-label="Application Type">
                            <option value="">Application Type</option>
                            <option value="1" {{ request('application_type') == '1' ? 'selected' : '' }}>Registration</option>
                            <option value="2" {{ request('application_type') == '2' ? 'selected' : '' }}>Exemption</option>
                        </select>
                    </div>

                    <button type="submit" formaction="{{ route('exemptions.datalist') }}"
                            class="btn btn-primary rounded-1 px-3">Filter</button>

                    <a href="{{ route('exemptions.datalist') }}" class="btn programme-dt-btn-reset">Reset Filters</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div class="programme-dt-filter-select">
                        <select name="format" id="format" class="form-select" aria-label="Export Format">
                            <option value="">Export Format</option>
                            <option value="pdf" {{ request('format') == 'pdf' ? 'selected' : '' }}>PDF</option>
                            <option value="xlsx" {{ request('format') == 'xlsx' ? 'selected' : '' }}>Excel</option>
                            <option value="csv" {{ request('format') == 'csv' ? 'selected' : '' }}>CSV</option>
                        </select>
                    </div>
                    <button type="submit" class="btn programme-dt-btn-columns border-0 text-primary">
                        <i class="bi bi-download" aria-hidden="true"></i> <span>Export</span>
                    </button>
                    <div id="exdlDtSearch" class="programme-dt-search" data-dt-search-for="exemptionDatalistTable"></div>
                </div>
            </form>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Not paginated server-side: the global `.datatable` auto-init
                         (admin/layouts/footer.blade.php) pages and searches it in the
                         browser, and the enhancer moves the chrome into the slots. --}}
                    <table class="table table-hover text-nowrap align-middle mb-0 w-100 programme-dt-table datatable"
                           id="exemptionDatalistTable" data-export="false" data-page-length="10">
                        <thead>
                            <tr>
                                <th style="width:70px;" class="text-center">S.No</th>
                                <th>User Name</th>
                                <th>Mobile No</th>
                                <th>Web Code</th>
                                <th>Exemption Category</th>
                                <th class="text-center">Medical Document</th>
                                <th>Type</th>
                                <th class="text-center">Exemption Count</th>
                                <th>Submitted On</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $index => $data)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        {{ $data->username ?? trim(($data->first_name ?? '') . ' ' . ($data->middle_name ?? '') . ' ' . ($data->last_name ?? '')) ?: 'N/A' }}
                                    </td>
                                    <td>{{ $data->contact_no }}</td>
                                    <td>{{ $data->web_auth }}</td>
                                    <td>
                                        @if ($data->application_type == 2)
                                            {{ $data->Exemption_name ?? 'N/A' }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($data->application_type == 2)
                                            @if ($data->medical_exemption_doc)
                                                <a href="{{ asset('storage/' . $data->medical_exemption_doc) }}" target="_blank"
                                                   class="programme-action-btn" title="View document" aria-label="View medical document">
                                                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">No Document</span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($data->application_type == 1)
                                            Registration
                                        @elseif ($data->application_type == 2)
                                            Exemption
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($data->exemption_count)
                                            {{ $data->exemption_count }}
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    {{-- data-order keeps d-m-Y sorting chronological, not alphabetical --}}
                                    <td data-order="{{ $data->created_date ? \Carbon\Carbon::parse($data->created_date)->format('Ymd') : '' }}">
                                        {{ $data->created_date ? \Carbon\Carbon::parse($data->created_date)->format('d-m-Y') : '--' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="exdlDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="exemptionDatalistTable"></div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    // S.No is rendered server-side, so it must be renumbered whenever the
    // client-side table pages, sorts or filters.
    var sel = '#exemptionDatalistTable';
    if (!$(sel).length || !$.fn.DataTable.isDataTable(sel)) { return; }
    var dt = $(sel).DataTable();
    dt.on('draw', function () {
        var start = dt.page.info().start;
        dt.column(0, { search: 'applied', order: 'applied', page: 'current' })
          .nodes()
          .each(function (cell, i) { cell.innerHTML = start + i + 1; });
    });
    dt.draw(false);
});
</script>
@endpush
