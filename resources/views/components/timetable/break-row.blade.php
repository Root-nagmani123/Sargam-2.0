@props(['label' => '', 'from' => '', 'to' => '', 'colspan' => 1])

@php
    // The printed sheet writes break times without separators: "1140 to 1210 hrs".
    $compact = fn (string $t) => str_replace(':', '', $t);
@endphp

{{--
    A break renders as one band merged across every column. It is reached from the
    same row loop as any other event: the builder marks a row as a band when a
    single break covers it, so no separate break pass exists.
--}}
<td class="tt-band" colspan="{{ $colspan }}">
    {{ $label }}
    @if($from !== '' && $to !== '')
        {{ $compact($from) }} to {{ $compact($to) }} hrs
    @endif
</td>
