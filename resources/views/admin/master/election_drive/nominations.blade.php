@extends('admin.layouts.master')

@section('title', 'View ' . $drive->election_drive_name . ' Nominations')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid cs-master-page ed-nom-page">
    <x-breadcrum :title="'View ' . $drive->election_drive_name . ' Nominations'"
                 :items="[
                     ['label' => 'Home', 'url' => route('admin.dashboard')],
                     ['label' => 'Communications', 'url' => null],
                     ['label' => 'Club/ Society', 'url' => null],
                     ['label' => $drive->election_drive_name, 'url' => route('master.election.drive.index')],
                     ['label' => 'View Nominations', 'url' => null],
                 ]"
                 :show-back="true" />

    <x-session_message />

    @php
        // Download / Print carry whatever filter is on screen.
        $exportQuery = array_filter([
            'club_society_master_pk' => $filters['club_society_master_pk'] ?? null,
            'status'                 => $filters['status'] ?? null,
            'from'                   => $filters['from'] ?? null,
            'to'                     => $filters['to'] ?? null,
        ], fn ($v) => filled($v));
    @endphp

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <a href="{{ route('master.election.drive.nominations.export', ['id' => $driveId] + $exportQuery) }}"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
        <a href="{{ route('master.election.drive.nominations.print', ['id' => $driveId] + $exportQuery) }}"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Filters submit as a GET form: the grouped layout is rendered
                 server-side, so filtering is a page load rather than an ajax draw. --}}
            <form method="GET" action="{{ route('master.election.drive.nominations', ['id' => $driveId]) }}"
                  id="edNomFilterForm"
                  class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select name="club_society_master_pk" class="cs-select2 form-select" aria-label="Filter by club/ society">
                            <option value="">Club/ Society/ Association</option>
                            @foreach ($societyOptions as $society)
                                <option value="{{ $society->pk }}"
                                    {{ (string) ($filters['club_society_master_pk'] ?? '') === (string) $society->pk ? 'selected' : '' }}>
                                    {{ $society->club_society_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select name="status" class="cs-select2 form-select" aria-label="Filter by status">
                            <option value="">Status</option>
                            @foreach (['pending' => 'Pending', 'accepted' => 'Accepted', 'withdrawn' => 'Withdrawn'] as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-2 ed-date-range">
                        <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}"
                               aria-label="Nominated from date">
                        <span class="text-body-secondary">–</span>
                        <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}"
                               aria-label="Nominated to date">
                    </div>

                    <button type="submit" class="btn btn-primary rounded-1 px-3">Apply</button>
                    <a href="{{ route('master.election.drive.nominations', ['id' => $driveId]) }}"
                       class="btn programme-dt-btn-reset">Remove Filter</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="edNomBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#edNomColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span> <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search ed-nom-search">
                        <input type="search" class="form-control" id="edNomSearch"
                               placeholder="Search" aria-label="Search nominees">
                    </div>
                </div>
            </form>

            {{-- One block per post, as in the design. --}}
            @forelse ($groups as $postName => $rows)
                <div class="ed-post-block mb-4" data-post-block>
                    <div class="ed-post-band d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="fw-semibold">Post : {{ $postName }}</span>
                        {{-- Nominees here are Officer Trainees (student_master);
                             the design's "CLIENT TYPE" label reflects that source. --}}
                        <span class="text-body-secondary small">CLIENT TYPE : Officer Trainee</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 programme-dt-table ed-nom-table">
                            <thead>
                                <tr>
                                    <th style="width:6rem;">S No.</th>
                                    <th>Nominee</th>
                                    <th style="width:14rem;">Club/Society/Association</th>
                                    <th style="width:11rem;" class="text-center">Nominated By</th>
                                    <th style="width:10rem;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $i => $row)
                                    @php
                                        $status = strtolower((string) $row->status);
                                        $cls = $status === 'accepted'
                                            ? 'programme-status-badge--active'
                                            : ($status === 'withdrawn' ? 'programme-status-badge--inactive' : 'ed-status-pending');
                                        $initials = collect(explode(' ', trim((string) $row->nominee_name)))
                                            ->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
                                    @endphp
                                    <tr data-nominee-row>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                {{-- photoUrl() returns null when the file is not on disk, so a
                                                     missing photo renders initials rather than firing a request
                                                     that is certain to 404. onerror stays as a safety net. --}}
                                                @php $photoUrl = \App\Services\ClubSociety\OfficeBearerService::photoUrl($row->photo_path ?? null); @endphp
                                                <span class="ed-nominee-avatar ed-nominee-avatar--initials{{ $photoUrl ? ' d-none' : '' }}"
                                                      data-avatar-fallback aria-hidden="true">{{ $initials ?: '?' }}</span>
                                                @if ($photoUrl)
                                                    <img src="{{ $photoUrl }}" alt=""
                                                         class="ed-nominee-avatar" loading="lazy"
                                                         onerror="this.classList.add('d-none'); var f=this.previousElementSibling; if(f){f.classList.remove('d-none');}">
                                                @endif
                                                <span data-nominee-name>{{ $row->nominee_name ?: '-' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $row->club_society_name }}</td>
                                        <td class="text-center">{{ $row->nominated_by_count }}</td>
                                        <td class="text-center">
                                            <span class="badge rounded-1 programme-status-badge {{ $cls }}">{{ ucfirst($status) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center text-body-secondary py-5">
                    No nominations match this filter.
                </div>
            @endforelse

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="edNomColumnVisibilityModal" tabindex="-1" aria-labelledby="edNomColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="edNomColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="edNomColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@include('admin.master.partials.club_society_select2')
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        /* The page renders several grouped tables rather than one DataTable, so
           search and column visibility are applied across all of them here. */

        // Pressing Enter in the search box must not submit the filter form.
        $('#edNomSearch').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); }
        });

        $('#edNomSearch').on('input', function () {
            var term = $(this).val().toLowerCase().trim();

            $('[data-post-block]').each(function () {
                var $block = $(this);
                var visible = 0;

                $block.find('tr[data-nominee-row]').each(function () {
                    var $tr = $(this);
                    var hit = !term || $tr.text().toLowerCase().indexOf(term) !== -1;
                    $tr.toggle(hit);
                    if (hit) { visible++; }
                });

                // Renumber the visible rows so S No. stays contiguous.
                var n = 0;
                $block.find('tr[data-nominee-row]:visible').each(function () {
                    $(this).children('td').first().text(++n);
                });

                // Hide a post block that has nothing left to show.
                $block.toggle(visible > 0);
            });
        });

        /* ---- Column show / hide across every grouped table ---- */
        var COLS = ['S No.', 'Nominee', 'Club/Society/Association', 'Nominated By', 'Status'];
        var key = 'edNomGrid:hiddenColumns:v1';

        function hiddenCols() {
            try {
                var raw = localStorage.getItem(key);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }

        function applyCols() {
            var hidden = hiddenCols();
            COLS.forEach(function (_, idx) {
                var show = hidden.indexOf(idx) === -1;
                $('.ed-nom-table').each(function () {
                    $(this).find('tr').each(function () {
                        $(this).children().eq(idx).toggle(show);
                    });
                });
            });
        }

        var $grid = $('#edNomColumnToggleGrid');
        COLS.forEach(function (title, idx) {
            var inputId = 'ednomcolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId).prop('checked', hiddenCols().indexOf(idx) === -1);

            $cb.on('change', function () {
                var h = hiddenCols();
                var pos = h.indexOf(idx);
                if (this.checked) { if (pos !== -1) h.splice(pos, 1); }
                else { if (pos === -1) h.push(idx); }
                try { localStorage.setItem(key, JSON.stringify(h)); } catch (e) {}
                applyCols();
            });

            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label);
            $grid.append($cell);
        });

        applyCols();
    });
</script>
@endpush
