@php
    use App\Models\Athletics\AthleticsDrive;
    use App\Http\Controllers\Admin\Athletics\AthleticsController;
    use App\Http\Controllers\Admin\Athletics\ResultController;
    $courseName = AthleticsController::courseLabel($course);
@endphp
@extends('admin.layouts.master')

@section('title', $courseName.' Result Entry')

@include('admin.athletics.partials.assets')

@section('setup_content')
<div class="container-fluid am-page">
    <x-breadcrum :title="$courseName.' Result Entry'" :showBack="true" />

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                @include('admin.athletics.partials.grid_tools', ['table' => 'amCourseResTable'])
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="amCourseResTable" class="table align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Drive</th>
                                <th scope="col">Events</th>
                                <th scope="col">Result Entry Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drives as $drive)
                                @php $done = ResultController::isComplete($drive); @endphp
                                <tr data-drive="{{ $drive->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $drive->form_title }}</td>
                                    <td data-order="{{ $drive->events_count }}">
                                        <button type="button" class="btn btn-link p-0 am-count-link am-expand">{{ $drive->events_count }}</button>
                                    </td>
                                    <td>@include('admin.athletics.partials.status_tag', ['status' => $done ? 'complete' : 'pending'])</td>
                                    <td class="text-center">
                                        <button type="button" class="am-act am-expand" aria-expanded="false" aria-controls="amSheet-{{ $drive->id }}">
                                            <span class="am-act__icon"><span class="material-icons material-symbols-rounded am-expand-icon" aria-hidden="true">expand_more</span></span>
                                            <span class="am-act__label">{!! $done ? 'View' : 'Complete<br>Result Entry' !!}</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="amCourseResTable"></div>
            </div>
        </div>
    </div>

    {{-- Result sheets, moved into a DataTables child row when their drive is expanded. --}}
    <div class="d-none">
        @foreach ($drives as $drive)
            @php
                $perf = $drive->result_recorded_as !== 'rank';
                $saved = $drive->results->sortBy('position')->groupBy('event_id');
                $driveAthletes = $athletes->get($drive->id, collect());
            @endphp
            <form method="POST" action="{{ route('admin.athletics.results.save', $drive) }}" id="amSheet-{{ $drive->id }}" class="am-sheet" novalidate>
                @csrf
                @foreach ($drive->events as $event)
                    @php $eventAthletes = $driveAthletes->get($event->id, collect()); @endphp
                    <div class="am-event-band">Event: {{ $event->name }}</div>
                    <div class="table-responsive">
                        <table class="am-rows">
                            <thead>
                                <tr>
                                    <th scope="col">Position</th>
                                    <th scope="col">Athlete</th>
                                    <th scope="col">House</th>
                                    @if ($perf)
                                        <th scope="col">{{ AthleticsDrive::PERFORMANCE_LABELS[$drive->result_recorded_as] }}</th>
                                    @endif
                                    <th scope="col">Points</th>
                                    <th scope="col">Remarks</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody class="am-sheet-rows" data-template="#amTpl-{{ $drive->id }}-{{ $event->id }}">
                                @forelse ($saved->get($event->id, collect()) as $result)
                                    @include('admin.athletics.results._row', ['eventId' => $event->id, 'result' => $result, 'athletes' => $eventAthletes, 'houses' => $houses, 'perf' => $perf])
                                @empty
                                    @include('admin.athletics.results._row', ['eventId' => $event->id, 'result' => null, 'athletes' => $eventAthletes, 'houses' => $houses, 'perf' => $perf])
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <template id="amTpl-{{ $drive->id }}-{{ $event->id }}">
                        @include('admin.athletics.results._row', ['eventId' => $event->id, 'result' => null, 'athletes' => $eventAthletes, 'houses' => $houses, 'perf' => $perf])
                    </template>
                    @if ($eventAthletes->isEmpty())
                        <p class="am-fact__label px-3 pt-2 mb-0">No approved nominations for this event — record a house result instead.</p>
                    @endif
                @endforeach
                <div class="am-result-footer">
                    <button type="submit" class="ux4g-btn-primary ux4g-btn-m">Save Result</button>
                </div>
            </form>
        @endforeach
    </div>
</div>

@include('admin.athletics.partials.colvis_modal', ['table' => 'amCourseResTable'])
@endsection

@push('scripts')
<script>
$(function () {
    var dt = AthleticsGrid.init({
        table: '#amCourseResTable',
        key: 'amCourseResGrid:hidden:v1',
        serial: 0,
        noSort: [0, 4],
        colvisGrid: '#amCourseResTableColvisGrid'
    });

    function prepare($sheet) {
        if ($sheet.data('ready')) { return; }
        $sheet.data('ready', true);
        $sheet.find('.am-sheet-rows').each(function () {
            var $body = $(this);
            var rows = AthleticsGrid.rows($body, $body.data('template'), function ($row) {
                $row.find('.am-res-position').val($body.children('tr').length);
            });
            rows.sync();
            $body.children('tr').first().find('.am-res-position').val(function (i, v) { return v || 1; });
        });
        if (window.DropdownSearch) { DropdownSearch.initAll($sheet.find('select')); }
    }

    function toggle(tr, open) {
        var row = dt.row(tr);
        var id = $(tr).data('drive');
        var $sheet = $('#amSheet-' + id);
        var show = open === undefined ? !row.child.isShown() : open;
        if (show) {
            row.child($sheet, 'am-expand-row').show();
            prepare($sheet);
        } else {
            row.child.hide();
        }
        $(tr).find('button.am-act.am-expand').attr('aria-expanded', show)
            .find('.am-expand-icon').text(show ? 'expand_less' : 'expand_more');
    }

    $('#amCourseResTable').on('click', '.am-expand', function () { toggle($(this).closest('tr')[0]); });

    // An athlete brings their own house; a house-only row is a team result.
    $(document).on('change', '.am-res-athlete', function () {
        var house = $(this).find(':selected').data('house');
        if (house) { $(this).closest('tr').find('.am-res-house').val(String(house)).trigger('change.select2'); }
    });

    // Re-open the sheet that was just saved.
    var open = new URLSearchParams(window.location.search).get('open');
    if (open) {
        var tr = $('#amCourseResTable tbody tr[data-drive="' + open + '"]')[0];
        if (tr) { toggle(tr, true); }
    }
});
</script>
@endpush
