@php
    $listItems = $items ?? collect();
    $nameColumnLabel = $nameColumnLabel ?? 'Course Name';
    $listRouteMode = $listRouteMode ?? 'auto';
@endphp
<div class="cru-view-grid d-none">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 cru-repo-list-table" id="{{ $listTableId ?? 'cruRepoListTable' }}">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-3 ps-md-4 py-3 text-secondary small fw-semibold border-0">S. No.</th>
                        <th scope="col" class="py-3 text-secondary small fw-semibold border-0">{{ $nameColumnLabel }}</th>
                        <th scope="col" class="text-end pe-3 pe-md-4 py-3 text-secondary small fw-semibold border-0">Sub Categories</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($listItems as $item)
                        @php
                            $displayName = $item->course_repository_name;
                            $repositoryName = strtolower($displayName);
                            if ($listRouteMode === 'show') {
                                $routeUrl = route('admin.course-repository.user.show', $item->pk);
                            } elseif (strpos($repositoryName, 'foundation course') !== false) {
                                $routeUrl = route('admin.course-repository.user.foundation-course');
                            } else {
                                $routeUrl = route('admin.course-repository.user.show', $item->pk);
                            }
                            $subCount = $item->children->count() ?? 0;
                        @endphp
                        <tr>
                            <td class="ps-3 ps-md-4 text-muted">{{ $loop->iteration }}</td>
                            <td class="fw-semibold text-dark">{{ $displayName }}</td>
                            <td class="text-end pe-3 pe-md-4">
                                <a href="{{ $routeUrl }}" class="cru-subcategory-link">
                                    {{ $subCount }} {{ Str::plural('Sub-Category', $subCount) }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
