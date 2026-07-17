@props(['events' => []])

@foreach($events as $event)
    <div class="tt-ev {{ $loop->first ? 'tt-ev-first' : '' }}">
        @if(!empty($event['title']))
            <div class="tt-ev-title">{{ $event['title'] }}</div>
        @endif

        {{-- Faculty is the emphasised line in the reference layout. --}}
        @if(!empty($event['faculty']))
            <div class="tt-ev-fac">({{ $event['faculty'] }})</div>
        @endif

        @if(!empty($event['venue']))
            <div class="tt-ev-ven">{{ $event['venue'] }}</div>
        @endif

        @if(!empty($event['remarks']))
            <div class="tt-ev-rem">{{ $event['remarks'] }}</div>
        @endif
    </div>
@endforeach
