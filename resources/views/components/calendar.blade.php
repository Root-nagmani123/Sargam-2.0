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
$hasEvent = array_key_exists($iso, $events);
@endphp


<td tabindex="0" role="button" class="calendar-cell {{ $isSelected ? 'is-selected' : '' }} {{ $hasEvent ? 'has-event' : '' }}" data-date="{{ $iso }}" aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
<span class="day-number">{{ $d->day }}</span>
@if($hasEvent)
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
</div>