{{--
    Sortable column header for mess reports.
    @include('admin.mess.reports.partials.report-sort-th', ['sortKey' => 'item_name', 'label' => 'Item Name', 'defaultDir' => 'asc', 'class' => 'text-start'])
--}}
@php
    $sortKey = $sortKey ?? 'item_name';
    $label = $label ?? $sortKey;
    $defaultDir = $defaultDir ?? 'asc';
    $defaultSort = $defaultSort ?? null;
    $thClass = trim(($class ?? '') . ' mess-report-sort-th');
    $messFilterField = $messFilterField ?? null;
    $activeSort = request()->filled('sort') ? (string) request('sort') : ($defaultSort ?? '');
    $isActive = $activeSort === $sortKey || ($activeSort === '' && $defaultSort === $sortKey);
    $currentDir = $isActive
        ? (request()->filled('sort_dir') ? (strtolower((string) request('sort_dir')) === 'desc' ? 'desc' : 'asc') : $defaultDir)
        : $defaultDir;
    $nextDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';
    $params = request()->query();
    unset($params['sort'], $params['sort_dir'], $params['page']);
    foreach (array_keys($params) as $paramKey) {
        if (str_starts_with((string) $paramKey, 'psq_page_')) {
            unset($params[$paramKey]);
        }
    }
    $params['sort'] = $sortKey;
    $params['sort_dir'] = $nextDir;
    $sortUrl = request()->url() . '?' . http_build_query($params);
@endphp
<th class="{{ $thClass }}" scope="col"@if(!empty($rowspan)) rowspan="{{ (int) $rowspan }}"@endif@if($messFilterField) data-mess-filter="{{ $messFilterField }}"@endif>
    <a href="{{ $sortUrl }}" class="mess-report-sort-link text-decoration-none text-reset d-inline-flex align-items-center gap-1">
        <span>{{ $label }}</span>
        @if($isActive)
            <span class="mess-report-sort-icon material-symbols-rounded" aria-hidden="true">{{ $currentDir === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
        @else
            <span class="mess-report-sort-icon mess-report-sort-icon--muted material-symbols-rounded" aria-hidden="true">unfold_more</span>
        @endif
    </a>
</th>
