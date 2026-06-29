@php
    $listItems = $items ?? collect();
    $nameColumnLabel = $nameColumnLabel ?? 'Course Name';
    $listRouteMode = $listRouteMode ?? 'auto';
    $cruListTableId = $listTableId ?? 'cruRepoListTable';
    // Optional column show/hide support (driven by the shared filter toolbar's Columns control).
    $cruListColumns = $cruColumns ?? null;
    $cruListColumnStorageKey = $cruColumnStorageKey ?? ('cru-repo-list-' . $cruListTableId);
@endphp
<div class="cru-view-grid d-none">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 cru-repo-list-table" id="{{ $cruListTableId }}">
                <thead class="table-light">
                    <tr>
                        <th scope="col" data-col="sno" class="cru-col-sno ps-3 ps-md-4 py-3 text-secondary small fw-semibold border-0">S. No.</th>
                        <th scope="col" data-col="name" class="cru-col-name py-3 text-secondary small fw-semibold border-0">{{ $nameColumnLabel }}</th>
                        <th scope="col" data-col="documents" class="cru-col-documents text-end pe-3 pe-md-4 py-3 text-secondary small fw-semibold border-0">Attachments</th>
                        <th scope="col" data-col="subcount" class="cru-col-subcount text-end pe-3 pe-md-4 py-3 text-secondary small fw-semibold border-0">Sub Categories</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($listItems as $item)
                        @php
                            $displayName = $item->course_repository_name;
                            $repositoryName = strtolower($displayName);
                            if ($listRouteMode === 'show') {
                                $routeUrl = route('admin.course-repository.user.show', encrypt($item->pk));
                            } elseif (strpos($repositoryName, 'foundation course') !== false) {
                                $routeUrl = route('admin.course-repository.user.foundation-course');
                            } else {
                                $routeUrl = route('admin.course-repository.user.show', encrypt($item->pk));
                            }
                            $subCount = $item->children->count() ?? 0;
                            $attachmentCount = $item->getTotalDocumentCount();
                        @endphp
                        <tr>
                            <td data-col="sno" class="cru-col-sno ps-3 ps-md-4 text-muted">{{ $loop->iteration }}</td>
                            <td data-col="name" class="cru-col-name fw-semibold text-dark">{{ $displayName }}</td>
                            <td data-col="documents" class="cru-col-documents text-end pe-3 pe-md-4">
                                <a href="{{ $routeUrl }}" class="cru-document-link">
                                    {{ $attachmentCount }} {{ Str::plural('Attachment', $attachmentCount) }}
                                </a>
                            </td>
                            <td data-col="subcount" class="cru-col-subcount text-end pe-3 pe-md-4">
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

@if(!empty($cruListColumns))
@push('scripts')
@include('admin.course-repository.user.partials.column-toggle-script', [
    'cruTableId' => $cruListTableId,
    'cruColumnStorageKey' => $cruListColumnStorageKey,
    'cruColumns' => $cruListColumns,
])
@endpush
@endif
