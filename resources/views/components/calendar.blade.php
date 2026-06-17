@props([
    'year' => now()->year,
    'month' => now()->month,
    'selected' => null,
    'events' => [],
    'theme' => 'gov-red'
])

@php
    $firstOfMonth = \Carbon\Carbon::create($year, $month, 1);
    $daysInMonth = $firstOfMonth->daysInMonth;
    $startWeekDay = $firstOfMonth->dayOfWeekIso;
    $prevMonth = $firstOfMonth->copy()->subMonth();
    $nextMonth = $firstOfMonth->copy()->addMonth();
    $selected = $selected ? \Carbon\Carbon::parse($selected) : null;

    $monthHolidays = [];
    $holidayCounts = ['gazetted' => 0, 'restricted' => 0, 'optional' => 0];
    $holidayDatesByType = ['gazetted' => [], 'restricted' => [], 'optional' => []];

    foreach ($events as $dateKey => $dayEvents) {
        $eventDate = \Carbon\Carbon::parse($dateKey);
        if ((int) $eventDate->year !== (int) $year || (int) $eventDate->month !== (int) $month) {
            continue;
        }
        foreach ($dayEvents as $event) {
            if (($event['type'] ?? '') !== 'holiday') {
                continue;
            }
            $type = $event['holiday_type'] ?? 'gazetted';
            if (!isset($holidayCounts[$type])) {
                $holidayCounts[$type] = 0;
                $holidayDatesByType[$type] = [];
            }
            $monthHolidays[] = [
                'date' => $eventDate,
                'title' => $event['title'] ?? 'Holiday',
                'holiday_type' => $type,
                'description' => $event['description'] ?? '',
            ];
            if (!in_array($dateKey, $holidayDatesByType[$type], true)) {
                $holidayDatesByType[$type][] = $dateKey;
                $holidayCounts[$type]++;
            }
        }
    }

    usort($monthHolidays, fn ($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

    $holidayPriority = ['gazetted' => 3, 'restricted' => 2, 'optional' => 1];

    $calendarRows = 6;
@endphp

<div class="calendar-component" data-theme="{{ $theme }}" data-year="{{ $year }}" data-month="{{ $month }}">
    <div class="calendar-nav-bar" role="navigation" aria-label="Calendar navigation">
        <div class="calendar-nav-bar__side calendar-nav-bar__side--start">
            <button type="button" class="calendar-nav-btn calendar-nav-year-prev" aria-label="Previous year">
                <span class="calendar-nav-btn__icon" aria-hidden="true">&lt;&lt;</span>
            </button>
            <button type="button" class="calendar-nav-btn calendar-nav-month-prev" aria-label="Previous month">
                <span class="calendar-nav-btn__icon" aria-hidden="true">&lt;</span>
            </button>
        </div>

        <div class="calendar-month-year-label" aria-live="polite">
            {{ $firstOfMonth->format('M Y') }}
        </div>

        <div class="calendar-nav-bar__side calendar-nav-bar__side--end">
            <button type="button" class="calendar-nav-btn calendar-nav-month-next" aria-label="Next month">
                <span class="calendar-nav-btn__icon" aria-hidden="true">&gt;</span>
            </button>
            <button type="button" class="calendar-nav-btn calendar-nav-year-next" aria-label="Next year">
                <span class="calendar-nav-btn__icon" aria-hidden="true">&gt;&gt;</span>
            </button>
        </div>

        <select class="calendar-year visually-hidden" aria-hidden="true" tabindex="-1">
            @foreach(range(now()->year - 5, now()->year + 5) as $y)
            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <select class="calendar-month visually-hidden" aria-hidden="true" tabindex="-1">
            @foreach(range(1, 12) as $m)
            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
        </select>
    </div>

    <div class="calendar-wrap">
        <div class="calendar-grid" role="grid" aria-label="Calendar {{ $firstOfMonth->format('F Y') }}">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $w)
            <div class="calendar-grid__weekday" role="columnheader">{{ $w }}</div>
            @endforeach

            @for($r = 0; $r < $calendarRows; $r++)
                @for($c = 1; $c <= 7; $c++)
                @php
                $cellIndex = $r * 7 + $c;
                $globalDayIndex = $cellIndex - ($startWeekDay - 1);
                @endphp

                @if($globalDayIndex < 1)
                @php $d = $prevMonth->copy()->day($prevMonth->daysInMonth + $globalDayIndex); @endphp
                <div class="calendar-grid__slot" role="gridcell">
                    <div class="calendar-cell calendar-day-other" aria-hidden="true">
                        <span class="day-number">{{ $d->day }}</span>
                    </div>
                </div>
                @elseif($globalDayIndex > $daysInMonth)
                @php $d = $nextMonth->copy()->day($globalDayIndex - $daysInMonth); @endphp
                <div class="calendar-grid__slot" role="gridcell">
                    <div class="calendar-cell calendar-day-other" aria-hidden="true">
                        <span class="day-number">{{ $d->day }}</span>
                    </div>
                </div>
                @else
                @php
                $d = \Carbon\Carbon::create($year, $month, $globalDayIndex);
                $iso = $d->toDateString();
                $isSelected = $selected && $selected->toDateString() === $iso;
                $isToday = $d->isToday();
                $hasEvent = array_key_exists($iso, $events);
                $isBirthday = $hasEvent && collect($events[$iso])->contains(fn ($e) => ($e['type'] ?? '') === 'birthday');
                $dayHolidayType = null;
                if ($hasEvent) {
                    $best = 0;
                    foreach ($events[$iso] as $event) {
                        if (($event['type'] ?? '') !== 'holiday') {
                            continue;
                        }
                        $type = $event['holiday_type'] ?? 'gazetted';
                        $score = $holidayPriority[$type] ?? 0;
                        if ($score > $best) {
                            $best = $score;
                            $dayHolidayType = $type;
                        }
                    }
                }
                $cellTitle = '';
                if ($hasEvent) {
                    $cellTitle = collect($events[$iso])->map(function ($event) {
                        return ($event['title'] ?? '') . (($event['description'] ?? '') !== '' ? ' — ' . $event['description'] : '');
                    })->implode('; ');
                }
                @endphp
                <div class="calendar-grid__slot" role="gridcell">
                    <div tabindex="0" role="button"
                        class="calendar-cell{{ $isSelected ? ' is-selected' : '' }}{{ $isToday ? ' is-today' : '' }}{{ $hasEvent ? ' has-event' : '' }}{{ $isBirthday ? ' is-birthday' : '' }}{{ $dayHolidayType ? ' is-holiday-' . $dayHolidayType : '' }}"
                        data-date="{{ $iso }}"
                        @if($cellTitle !== '') title="{{ $cellTitle }}" @endif
                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                        aria-current="{{ $isToday ? 'date' : 'false' }}">
                        <span class="day-number">{{ $d->day }}</span>
                        <span class="calendar-cell-events visually-hidden">
                            @if($hasEvent)
                                @foreach($events[$iso] as $event)
                                    @if(($event['type'] ?? '') === 'birthday')
                                    Birthday: {{ $event['title'] ?? '' }}.
                                    @elseif(($event['type'] ?? '') === 'holiday')
                                    {{ $event['title'] ?? 'Holiday' }}.
                                    @endif
                                @endforeach
                            @endif
                        </span>
                    </div>
                </div>
                @endif
                @endfor
            @endfor
        </div>
    </div>

    <div class="calendar-legend-row">
        <ul class="calendar-legend list-unstyled mb-0">
            <li class="calendar-legend-item">
                <span class="calendar-legend-dot calendar-legend-dot--gazetted" aria-hidden="true"></span>
                <span>Gazetted Holiday: {{ str_pad((string) $holidayCounts['gazetted'], 2, '0', STR_PAD_LEFT) }}</span>
            </li>
            <li class="calendar-legend-item">
                <span class="calendar-legend-dot calendar-legend-dot--restricted" aria-hidden="true"></span>
                <span>Restricted Holiday: {{ str_pad((string) $holidayCounts['restricted'], 2, '0', STR_PAD_LEFT) }}</span>
            </li>
            <li class="calendar-legend-item">
                <span class="calendar-legend-dot calendar-legend-dot--optional" aria-hidden="true"></span>
                <span>Optional Holiday: {{ str_pad((string) $holidayCounts['optional'], 2, '0', STR_PAD_LEFT) }}</span>
            </li>
        </ul>
        <button type="button" class="calendar-holidays-toggle" aria-expanded="false"
            aria-controls="calendar-holidays-panel">
            Show holidays this month
        </button>
    </div>

    <div class="calendar-holidays-panel" id="calendar-holidays-panel" hidden>
        <h6 class="calendar-holidays-panel__title">
            Holidays in {{ $firstOfMonth->format('F Y') }}
        </h6>

        <div class="calendar-holiday-filters" role="tablist" aria-label="Filter holidays">
            <button type="button" class="calendar-holiday-filter active" data-filter="gazetted"
                role="tab" aria-selected="true">Gazetted Holiday</button>
            <button type="button" class="calendar-holiday-filter" data-filter="restricted"
                role="tab" aria-selected="false">Restricted Holiday</button>
            <button type="button" class="calendar-holiday-filter" data-filter="optional"
                role="tab" aria-selected="false">Optional Holiday</button>
        </div>

        @if(empty($monthHolidays))
        <p class="text-body-secondary small mb-0">No holidays scheduled for this month.</p>
        @else
        <ul class="calendar-holiday-list list-unstyled mb-0">
            @foreach($monthHolidays as $holiday)
            <li class="calendar-holiday-list__item" data-holiday-type="{{ $holiday['holiday_type'] }}">
                <span class="calendar-holiday-date calendar-holiday-date--{{ $holiday['holiday_type'] }}">
                    {{ $holiday['date']->format('d M') }}
                </span>
                <span class="calendar-holiday-name">{{ $holiday['title'] }}</span>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>
