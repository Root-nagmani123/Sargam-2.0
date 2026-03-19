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
    $startWeekDay = $firstOfMonth->dayOfWeekIso; // 1=Mon, 7=Sun
    $prevMonth = $firstOfMonth->copy()->subMonth();
    $nextMonth = $firstOfMonth->copy()->addMonth();
    $selected = $selected ? \Carbon\Carbon::parse($selected) : null;
@endphp

<style>
    .calendar-table thead th {
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        padding: 10px 5px !important;
        font-weight: 600 !important;
        color: #666 !important;
        border-bottom: 2px solid #e0e0e0 !important;
    }
    
    .calendar-cell {
        position: relative;
        padding: 8px 4px;
        vertical-align: top;
        height: 80px;
    }
    
    .calendar-cell .day-number {
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }
    
    .calendar-cell.has-event {
        background-color: #f8f9fa;
    }
    
    .calendar-cell.is-selected {
        background-color: #e7f3ff;
        border: 2px solid #0d6efd;
    }

    /* Blink current date */
    @keyframes calendar-today-blink {
        0%,
        45% {
            opacity: 1;
        }
        55%,
        100% {
            opacity: 0.25;
        }
    }

    .calendar-cell.is-today {
        background-color: rgba(255, 193, 7, 0.14);
        border: 2px solid rgba(255, 193, 7, 0.55);
    }

    .calendar-cell.is-today .day-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.75rem;
        height: 1.75rem;
        border-radius: 999px;
        background: rgba(255, 193, 7, 0.35);
        animation: calendar-today-blink 1s linear infinite;
    }
    
    .holiday-badge {
        font-size: 0.65rem;
        padding: 2px 4px;
        border-radius: 3px;
        display: block;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }
    
    .holiday-gazetted {
        background-color: #dc3545;
        color: #fff;
    }
    
    .holiday-restricted {
        background-color: #ffc107;
        color: #000;
    }
    
    .holiday-optional {
        background-color: #17a2b8;
        color: #fff;
    }
    
    .calendar-legend {
        display: flex;
        gap: 15px;
        margin-top: 10px;
        font-size: 0.85rem;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 2px;
    }
</style>

<div class="calendar-component" data-year="{{ $year }}" data-month="{{ $month }}">
<div class="d-flex align-items-center mb-3">
<select class="form-select form-select-sm calendar-year" aria-label="Select Year">
@foreach(range(now()->year - 5, now()->year + 5) as $y)
<option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
@endforeach
</select>

<select class="form-select form-select-sm calendar-month ms-2" aria-label="Select Month">
@foreach(range(1,12) as $m)
<option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('M') }}</option>
@endforeach
</select>


<div class="btn-group btn-group-sm ms-auto" role="group" aria-label="View type">
<button type="button" class="btn btn-outline-secondary view-btn" data-view="month">Month</button>
<button type="button" class="btn btn-secondary view-btn active" data-view="year">Year</button>
</div>
</div>


<div class="calendar-wrap">
<table class="table calendar-table mb-0" role="grid" aria-label="Calendar {{ $firstOfMonth->format('F Y') }}">
<thead>
<tr>
@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $w)
<th scope="col">{{ $w }}</th>
@endforeach
</tr>
</thead>
<tbody>
@php
$day = 1;
$printed = 0;
$prevDays = $prevMonth->daysInMonth - ($startWeekDay - 2); // number of prev-mth days to show (if startWeekDay>1)
// We will render weeks until we've printed all days
$cells = [];
@endphp


@for($r = 0; $r < 6; $r++)
<tr>
@for($c = 1; $c <= 7; $c++)
@php
$cellIndex = $r*7 + $c;
// compute corresponding calendar day
$globalDayIndex = $cellIndex - ($startWeekDay - 1);
@endphp


@if($globalDayIndex < 1)
{{-- previous month day --}}
@php $d = $prevMonth->copy()->day($prevMonth->daysInMonth + $globalDayIndex); @endphp
<td class="text-muted" aria-hidden="true">{{ $d->day }}</td>
@elseif($globalDayIndex > $daysInMonth)
{{-- next month day --}}
@php $d = $nextMonth->copy()->day($globalDayIndex - $daysInMonth); @endphp
<td class="text-muted" aria-hidden="true">{{ $d->day }}</td>
@else
@php $d = \Carbon\Carbon::create($year, $month, $globalDayIndex); @endphp
@php $iso = $d->toDateString(); @endphp


@php
$isSelected = $selected && $selected->toDateString() === $iso;
$isToday = $d->isToday();
$hasEvent = array_key_exists($iso, $events);
@endphp


<td tabindex="0" role="button" class="calendar-cell {{ $isSelected ? 'is-selected' : '' }} {{ $isToday ? 'is-today' : '' }} {{ $hasEvent ? 'has-event' : '' }}" data-date="{{ $iso }}" aria-pressed="{{ $isSelected ? 'true' : 'false' }}" aria-current="{{ $isToday ? 'date' : 'false' }}">
<span class="day-number">{{ $d->day }}</span>
@if($hasEvent)
    @foreach($events[$iso] as $event)
        @if(isset($event['type']) && $event['type'] === 'holiday')
            <span class="holiday-badge holiday-{{ $event['holiday_type'] }}" 
                  title="{{ $event['title'] }} - {{ $event['description'] ?? '' }}">
                {{ Str::limit($event['title'], 15) }}
            </span>
        @endif
    @endforeach
    <span class="visually-hidden">, has events</span>
@endif
</td>
@endif
@endfor
</tr>
@endfor
</tbody>
</table>
</div>

<!-- Legend -->
<div class="calendar-legend">
    <div class="legend-item">
        <span class="legend-color" style="background-color: #D47176 
;"></span>
        <span>Gazetted Holiday</span>
    </div>
    <div class="legend-item">
        <span class="legend-color" style="background-color: #FBC272;"></span>
        <span>Restricted Holiday</span>
    </div>
    <div class="legend-item">
        <span class="legend-color" style="background-color: #17a2b8;"></span>
        <span>Optional Holiday</span>
    </div>
</div>
</div>