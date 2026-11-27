@extends('admin.layouts.master')

@section('title', 'Upcoming Events')

@section('content')
{{-- The Upcoming Events card's page: the academy holidays still ahead, the same rows the card
     counts (Holiday::active()->upcoming(), see the route). Was a static template sample. --}}
@php
    $typeBadge = [
        'gazetted' => ['Gazetted', 'bg-danger-subtle text-danger-emphasis'],
        'restricted' => ['Restricted', 'bg-warning-subtle text-warning-emphasis'],
        'optional' => ['Optional', 'bg-success-subtle text-success-emphasis'],
    ];
@endphp
<div class="container-fluid">
    <x-breadcrum title="Upcoming Events" :showBack="true"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-3">
                <h1 class="h4 mb-0">Upcoming Events</h1>
                <p class="text-body-secondary small mb-0">
                    {{ $holidays->count() }} academy {{ \Illuminate\Support\Str::plural('holiday', $holidays->count()) }} ahead
                </p>
            </div>

            @if($holidays->isEmpty())
            <div class="ds-empty-state">
                <i class="material-icons material-symbols-rounded fs-1 d-block mb-2 opacity-50" aria-hidden="true">event_available</i>
                <p class="mb-0">No upcoming holidays on the academy calendar.</p>
            </div>
            @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Holiday</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="text-end">In</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($holidays as $holiday)
                        @php
                            $type = $typeBadge[strtolower((string) $holiday->holiday_type)] ?? [ucfirst((string) $holiday->holiday_type) ?: '—', 'bg-secondary-subtle text-secondary-emphasis'];
                            $days = (int) now()->startOfDay()->diffInDays($holiday->holiday_date->copy()->startOfDay());
                            $description = trim((string) $holiday->description);
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                <span class="fw-semibold">{{ $holiday->holiday_date->format('d M Y') }}</span>
                                <span class="d-block small text-body-secondary">{{ $holiday->holiday_date->format('l') }}</span>
                            </td>
                            <td>
                                {{ $holiday->holiday_name }}
                                @if($description !== '' && $description !== trim((string) $holiday->holiday_name))
                                <span class="d-block small text-body-secondary">{{ $description }}</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $type[1] }}">{{ $type[0] }}</span></td>
                            <td class="text-end text-nowrap">{{ $days === 1 ? 'Tomorrow' : $days.' days' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
