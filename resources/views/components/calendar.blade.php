@props([
    'year' => now()->year,
    'month' => now()->month,
    'selected' => null,
    'events' => [],
    'theme' => 'gov-red',
])

@php
    $firstOfMonth = \Carbon\Carbon::create($year, $month, 1);
    $daysInMonth = $firstOfMonth->daysInMonth;
    $startWeekDay = $firstOfMonth->dayOfWeekIso;
    $prevMonth = $firstOfMonth->copy()->subMonth();
    $nextMonth = $firstOfMonth->copy()->addMonth();
    $selected = $selected ? \Carbon\Carbon::parse($selected) : null;

    $holidayTypeOrder = ['gazetted' => 1, 'restricted' => 2, 'optional' => 3];
    $holidaysByType = ['gazetted' => [], 'restricted' => [], 'optional' => []];
    $holidayCounts = ['gazetted' => 0, 'restricted' => 0, 'optional' => 0];

    foreach ($events as $iso => $dayEvents) {
        $cd = \Carbon\Carbon::parse($iso);
        if ($cd->year != $year || $cd->month != $month) {
            continue;
        }
        foreach ($dayEvents as $ev) {
            if (($ev['type'] ?? '') !== 'holiday') {
                continue;
            }
            $ht = $ev['holiday_type'] ?? '';
            if (! isset($holidaysByType[$ht])) {
                continue;
            }
            $holidayCounts[$ht]++;
            $holidaysByType[$ht][] = [
                'date' => $cd->copy(),
                'title' => $ev['title'] ?? '',
                'description' => $ev['description'] ?? '',
            ];
        }
    }

    foreach ($holidaysByType as $ht => $list) {
        usort($holidaysByType[$ht], function ($a, $b) {
            return $a['date']->timestamp <=> $b['date']->timestamp;
        });
    }

    $primaryHolidayForDate = function (string $iso) use ($events, $holidayTypeOrder) {
        if (! isset($events[$iso])) {
            return null;
        }
        $best = null;
        $bestRank = 99;
        foreach ($events[$iso] as $ev) {
            if (($ev['type'] ?? '') !== 'holiday') {
                continue;
            }
            $ht = $ev['holiday_type'] ?? '';
            $rank = $holidayTypeOrder[$ht] ?? 99;
            if ($rank < $bestRank) {
                $bestRank = $rank;
                $best = $ht;
            }
        }

        return $best;
    };
@endphp

<style>
    .calendar-component .calendar-toolbar-label {
        font-size: 1rem;
        letter-spacing: 0.01em;
    }

    .calendar-component .calendar-nav-group .btn {
        min-width: 2.25rem;
        font-weight: 700;
        line-height: 1;
        color: var(--bs-primary);
        border-color: var(--bs-border-color-translucent);
    }

    .calendar-component .calendar-table {
        --cal-cell-size: min(2.75rem, 12vw);
        table-layout: fixed;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0.35rem 0.35rem;
    }

    .calendar-component .calendar-table thead th {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0.35rem 0.15rem !important;
        font-weight: 600 !important;
        font-size: 0.8rem;
        color: var(--bs-secondary-color) !important;
        text-align: center;
    }

    .calendar-component .calendar-table tbody td {
        border: none !important;
        padding: 0 !important;
        vertical-align: middle;
        text-align: center;
        width: 14.28%;
    }

    .calendar-component .calendar-cell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        aspect-ratio: 1;
        max-width: var(--cal-cell-size);
        max-height: var(--cal-cell-size);
        margin: 0 auto;
        padding: 0;
        border-radius: 0.375rem;
        border: 1px solid var(--bs-border-color-translucent);
        background-color: var(--bs-body-bg);
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--bs-body-color);
        cursor: default;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .calendar-component .calendar-cell:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }

    .calendar-component .calendar-filler {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        aspect-ratio: 1;
        max-width: var(--cal-cell-size);
        max-height: var(--cal-cell-size);
        margin: 0 auto;
        padding: 0;
        border-radius: 0.375rem;
        border: 1px solid var(--bs-border-color-translucent);
        color: var(--bs-secondary-color);
        background-color: var(--bs-tertiary-bg);
        font-weight: 500;
        font-size: 0.9rem;
    }

    .calendar-component .calendar-cell.is-selected {
        background-color: #0d1b4d;
        border-color: #0d1b4d;
        color: #fff;
    }

    .calendar-component .calendar-cell--gazetted:not(.is-selected) {
        background-color: #fce4ec;
        border-color: #f8bbd9;
        color: #ad1457;
    }

    .calendar-component .calendar-cell--restricted:not(.is-selected) {
        background-color: #fff9c4;
        border-color: #fff59d;
        color: #f57f17;
    }

    .calendar-component .calendar-cell--optional:not(.is-selected) {
        background-color: #e0f7fa;
        border-color: #b2ebf2;
        color: #00838f;
    }

    .calendar-component .calendar-cell--birthday:not(.is-selected)::after {
        content: '';
        position: absolute;
        bottom: 4px;
        left: 50%;
        transform: translateX(-50%);
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e91e63, #ff6090);
    }

    .calendar-component .calendar-cell .day-number {
        line-height: 1;
    }

    .calendar-component .calendar-cell .calendar-cell-sr {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .calendar-component .calendar-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .calendar-component .calendar-holiday-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 0.35rem;
        white-space: nowrap;
    }

    .calendar-component .calendar-holiday-badge--gazetted {
        background-color: #fce4ec;
        color: #ad1457;
    }

    .calendar-component .calendar-holiday-badge--restricted {
        background-color: #fff9c4;
        color: #f57f17;
    }

    .calendar-component .calendar-holiday-badge--optional {
        background-color: #e0f7fa;
        color: #00838f;
    }

    .calendar-component .nav-pills .nav-link {
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--bs-secondary-color);
        padding: 0.45rem 0.85rem;
    }

    .calendar-component .nav-pills .nav-link.active {
        background-color: #0d1b4d;
        color: #fff;
    }

    .calendar-component .calendar-holiday-row + .calendar-holiday-row {
        border-top: 1px solid var(--bs-border-color-translucent);
    }
</style>

<div class="calendar-component" data-year="{{ $year }}" data-month="{{ $month }}">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-nowrap overflow-x-auto">
        <div class="btn-group calendar-nav-group flex-shrink-0" role="group" aria-label="Previous year and month">
            <button type="button" class="btn btn-light btn-sm rounded-2 calendar-nav-first" aria-label="Previous year">
                &laquo;</button>
            <button type="button" class="btn btn-light btn-sm rounded-2 calendar-nav-prev" aria-label="Previous month">
                &lsaquo;</button>
        </div>
        <div class="calendar-toolbar-label fw-bold text-dark text-center flex-grow-1 px-2 text-nowrap">
            {{ $firstOfMonth->format('M Y') }}
        </div>
        <div class="btn-group calendar-nav-group flex-shrink-0" role="group" aria-label="Next month and year">
            <button type="button" class="btn btn-light btn-sm rounded-2 calendar-nav-next" aria-label="Next month">
                &rsaquo;</button>
            <button type="button" class="btn btn-light btn-sm rounded-2 calendar-nav-last" aria-label="Next year">
                &raquo;</button>
        </div>
        <div class="visually-hidden">
            <select class="form-select form-select-sm calendar-year" aria-label="Select Year" tabindex="-1">
                @foreach (range(now()->year - 5, now()->year + 5) as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm calendar-month" aria-label="Select Month" tabindex="-1">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->format('M') }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="calendar-wrap">
        <table class="table calendar-table mb-0" role="grid"
            aria-label="Calendar {{ $firstOfMonth->format('F Y') }}">
            <thead>
                <tr>
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $w)
                        <th scope="col">{{ $w }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for ($r = 0; $r < 6; $r++)
                    <tr>
                        @for ($c = 1; $c <= 7; $c++)
                            @php
                                $cellIndex = $r * 7 + $c;
                                $globalDayIndex = $cellIndex - ($startWeekDay - 1);
                            @endphp

                            @if ($globalDayIndex < 1)
                                @php $d = $prevMonth->copy()->day($prevMonth->daysInMonth + $globalDayIndex); @endphp
                                <td>
                                    <div class="calendar-filler" aria-hidden="true">
                                        <span class="day-number">{{ $d->day }}</span>
                                    </div>
                                </td>
                            @elseif ($globalDayIndex > $daysInMonth)
                                @php $d = $nextMonth->copy()->day($globalDayIndex - $daysInMonth); @endphp
                                <td>
                                    <div class="calendar-filler" aria-hidden="true">
                                        <span class="day-number">{{ $d->day }}</span>
                                    </div>
                                </td>
                            @else
                                @php
                                    $d = \Carbon\Carbon::create($year, $month, $globalDayIndex);
                                    $iso = $d->toDateString();
                                    $isSelected = $selected && $selected->toDateString() === $iso;
                                    $isToday = $d->isToday();
                                    $hasEvent = array_key_exists($iso, $events);
                                    $isBirthday =
                                        $hasEvent && collect($events[$iso])->contains('type', 'birthday');
                                    $holidayKind = $primaryHolidayForDate($iso);
                                    $holidayClass = $holidayKind ? 'calendar-cell--' . $holidayKind : '';
                                @endphp
                                <td>
                                    <div tabindex="0" role="gridcell"
                                        class="calendar-cell {{ $isSelected ? 'is-selected' : '' }} {{ $holidayClass }} {{ $isBirthday ? 'calendar-cell--birthday' : '' }}"
                                        data-date="{{ $iso }}"
                                        aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                                        aria-current="{{ $isToday ? 'date' : 'false' }}">
                                        <span class="day-number">{{ $d->day }}</span>
                                        @if ($hasEvent)
                                            @php $srParts = []; @endphp
                                            @foreach ($events[$iso] as $event)
                                                @if (($event['type'] ?? '') === 'birthday')
                                                    @php $srParts[] = ($event['title'] ?? 'Birthday') . ' ' . ($event['description'] ?? ''); @endphp
                                                @elseif(($event['type'] ?? '') === 'holiday')
                                                    @php
                                                        $srParts[] =
                                                            ($event['title'] ?? 'Holiday') .
                                                            ' (' .
                                                            ucfirst($event['holiday_type'] ?? 'holiday') .
                                                            ')';
                                                    @endphp
                                                @endif
                                            @endforeach
                                            @if (!empty($srParts))
                                                <span class="calendar-cell-sr">. {{ implode('. ', $srParts) }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            @endif
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-end justify-content-between gap-3 mt-2">
        <ul class="list-unstyled mb-0 small">
            <li class="d-flex align-items-center gap-1 mb-1">
                <span class="calendar-legend-dot" style="background-color: #e91e63;"></span>
                <span class="text-body-secondary">Gazetted Holiday:</span>
                <span class="fw-semibold text-dark">{{ str_pad((string) $holidayCounts['gazetted'], 2, '0', STR_PAD_LEFT) }}</span>
            </li>
            <li class="d-flex align-items-center gap-1 mb-1">
                <span class="calendar-legend-dot" style="background-color: #fb8c00;"></span>
                <span class="text-body-secondary">Restricted Holiday:</span>
                <span class="fw-semibold text-dark">{{ str_pad((string) $holidayCounts['restricted'], 2, '0', STR_PAD_LEFT) }}</span>
            </li>
            <li class="d-flex align-items-center gap-1">
                <span class="calendar-legend-dot" style="background-color: #26a69a;"></span>
                <span class="text-body-secondary">Optional Holiday:</span>
                <span class="fw-semibold text-dark">{{ str_pad((string) $holidayCounts['optional'], 2, '0', STR_PAD_LEFT) }}</span>
            </li>
        </ul>
        <a href="#dashboardCalendarHolidayCollapse"
            class="link-primary small text-decoration-underline fw-semibold ms-md-auto" data-bs-toggle="collapse"
            role="button" aria-expanded="false" aria-controls="dashboardCalendarHolidayCollapse"
            data-calendar-holidays-toggle>
            Show holidays this month
        </a>
    </div>

    <div class="collapse mt-3" id="dashboardCalendarHolidayCollapse">
        <div class="card border rounded-3 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <h6 class="fw-bold text-dark mb-3">Holidays in {{ $firstOfMonth->format('F Y') }}</h6>
                <ul class="nav nav-pills flex-nowrap gap-1 mb-3 p-1 rounded-3 bg-body-secondary bg-opacity-50"
                    style="overflow-x: auto;" id="dashboardHolidayTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-nowrap" id="tab-holiday-gazetted" data-bs-toggle="tab"
                            data-bs-target="#pane-holiday-gazetted" type="button" role="tab"
                            aria-controls="pane-holiday-gazetted" aria-selected="true">Gazetted Holiday</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="tab-holiday-restricted" data-bs-toggle="tab"
                            data-bs-target="#pane-holiday-restricted" type="button" role="tab"
                            aria-controls="pane-holiday-restricted" aria-selected="false">Restricted Holiday</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="tab-holiday-optional" data-bs-toggle="tab"
                            data-bs-target="#pane-holiday-optional" type="button" role="tab"
                            aria-controls="pane-holiday-optional" aria-selected="false">Optional Holiday</button>
                    </li>
                </ul>
                <div class="tab-content" id="dashboardHolidayTabContent">
                    <div class="tab-pane fade show active" id="pane-holiday-gazetted" role="tabpanel"
                        aria-labelledby="tab-holiday-gazetted" tabindex="0">
                        @forelse ($holidaysByType['gazetted'] as $row)
                            <div
                                class="calendar-holiday-row d-flex align-items-center gap-3 flex-wrap flex-md-nowrap py-2">
                                <span
                                    class="calendar-holiday-badge calendar-holiday-badge--gazetted">{{ $row['date']->format('d M') }}</span>
                                <span class="text-dark">{{ $row['title'] }}</span>
                            </div>
                        @empty
                            <p class="text-body-secondary small mb-0">No gazetted holidays this month.</p>
                        @endforelse
                    </div>
                    <div class="tab-pane fade" id="pane-holiday-restricted" role="tabpanel"
                        aria-labelledby="tab-holiday-restricted" tabindex="0">
                        @forelse ($holidaysByType['restricted'] as $row)
                            <div
                                class="calendar-holiday-row d-flex align-items-center gap-3 flex-wrap flex-md-nowrap py-2">
                                <span
                                    class="calendar-holiday-badge calendar-holiday-badge--restricted">{{ $row['date']->format('d M') }}</span>
                                <span class="text-dark">{{ $row['title'] }}</span>
                            </div>
                        @empty
                            <p class="text-body-secondary small mb-0">No restricted holidays this month.</p>
                        @endforelse
                    </div>
                    <div class="tab-pane fade" id="pane-holiday-optional" role="tabpanel"
                        aria-labelledby="tab-holiday-optional" tabindex="0">
                        @forelse ($holidaysByType['optional'] as $row)
                            <div
                                class="calendar-holiday-row d-flex align-items-center gap-3 flex-wrap flex-md-nowrap py-2">
                                <span
                                    class="calendar-holiday-badge calendar-holiday-badge--optional">{{ $row['date']->format('d M') }}</span>
                                <span class="text-dark">{{ $row['title'] }}</span>
                            </div>
                        @empty
                            <p class="text-body-secondary small mb-0">No optional holidays this month.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
