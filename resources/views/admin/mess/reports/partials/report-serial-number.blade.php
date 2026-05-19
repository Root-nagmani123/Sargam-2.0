{{--
    Continuous S.No. for mess reports (paginated lists continue across pages).
    @include('admin.mess.reports.partials.report-serial-number', ['paginator' => $items, 'index' => $index])
    Optional: 'start' => int for non-paginated offset (e.g. running counter passed from parent).
--}}
@php
    $paginator = $paginator ?? null;
    $index = (int) ($index ?? 0);
    $serialStart = 1;
    if (isset($start) && $start !== null && $start !== '') {
        $serialStart = (int) $start;
    } elseif ($paginator && method_exists($paginator, 'firstItem') && ! is_null($paginator->firstItem())) {
        $serialStart = (int) $paginator->firstItem();
    }
@endphp
<span class="mess-report-sno">{{ $serialStart + $index }}</span>
