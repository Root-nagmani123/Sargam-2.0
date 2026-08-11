@extends('admin.layouts.master')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Course Repository')

@section('setup_content')
@php
$crumbItems = [
['label' => 'Home', 'url' => route('admin.dashboard')],
['label' => 'Academic', 'url' => null],
['label' => 'Course Repository', 'url' => route('admin.course-repository.user.index')],
];
if (!empty($ancestors)) {
foreach ($ancestors as $ancestor) {
$crumbItems[] = [
'label' => $ancestor->course_repository_name,
'url' => route('admin.course-repository.user.show', $ancestor->pk),
];
}
}
$crumbItems[] = $repository->course_repository_name;

$childCount = $repository->children->count();
$documentCount = $documentsCount ?? 0;
@endphp

<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 py-4" id="cru-user-main">
        <x-breadcrum :title="$repository->course_repository_name" :items="$crumbItems" />

        @include('admin.course-repository.user.partials.flash-alert')

        <div id="cruFilterResults">
            @if($childCount === 0 && $documentCount === 0)
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
                <div class="card-body">
                    <span
                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-secondary mb-3 cru-empty-icon">
                        <i class="bi bi-inbox fs-2" aria-hidden="true"></i>
                    </span>
                    <h2 class="h5 fw-semibold text-dark mb-2">No sub-categories or documents</h2>
                    <p class="text-muted small mb-0 mx-auto" style="max-width: 28rem;">
                        Nothing is available in this repository yet. Adjust filters or explore another category.
                    </p>
                </div>
            </div>
            @else
            @if($childCount > 0)
            @php
                $cruGridListTableId = 'cruRepoListTableShow';
                $cruGridColumnStorageKey = 'cru-repo-list-' . $cruGridListTableId;
                $cruGridColumns = [
                    ['key' => 'sno', 'label' => 'S. No.', 'locked' => true],
                    ['key' => 'name', 'label' => 'Category', 'default' => true],
                    ['key' => 'subcount', 'label' => 'Sub Categories', 'default' => true],
                ];
            @endphp
            <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
                @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])
            </div>

            <div class="course-cards-grid mb-4 mb-md-5" id="courseCardsGrid">
                <div class="cru-view-cards card card-body">
                    <div class="row g-3 g-md-4">
                        @foreach ($repository->children as $child)
                        @include('admin.course-repository.user.partials.repository-card', [
                        'repository' => $child,
                        'cardRoute' => route('admin.course-repository.user.show', $child->pk),
                        ])
                        @endforeach
                    </div>
                </div>
                @include('admin.course-repository.user.partials.repository-list-table', [
                'items' => $repository->children,
                'listTableId' => $cruGridListTableId,
                'listRouteMode' => 'show',
                'nameColumnLabel' => 'Category',
                'cruColumns' => $cruGridColumns,
                'cruColumnStorageKey' => $cruGridColumnStorageKey,
                ])
            </div>
            @endif

            <!-- Documents Section -->
            @if(($documentsCount ?? 0) > 0)
            @php
                // One definition of the documents table's columns, shared by the
                // Column-Visibility control (table-column-toggle) and the toggle script.
                // Keys must match the cru-col-<key> classes on the <th>/<td> cells below.
                $cruDocsTableId = 'cruUserDocsTable';
                $cruDocsColumnStorageKey = 'cru-user-show-docs-columns';
                $cruDocColumns = [
                    ['key' => 'sno', 'label' => 'S.No.', 'locked' => true],
                    ['key' => 'document_name', 'label' => 'Document Name', 'default' => true],
                    ['key' => 'file_title', 'label' => 'File Title', 'default' => true],
                    ['key' => 'course', 'label' => 'Course', 'default' => true],
                    ['key' => 'subject', 'label' => 'Subject', 'default' => true],
                    ['key' => 'topic', 'label' => 'Topic', 'default' => true],
                    ['key' => 'session_date', 'label' => 'Session Date', 'default' => true],
                    ['key' => 'author', 'label' => 'Author', 'default' => true],
                    ['key' => 'action', 'label' => 'Action', 'locked' => true],
                ];
            @endphp
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0 fw-bold">Documents ({{ $documentsCount ?? 0 }})</h5>
                    {{-- Column show/hide control (module's own, CSS-class based). Kept
                         separate from the DataTable's paging/search so the two don't fight. --}}
                    @include('admin.course-repository.user.partials.table-column-toggle', [
                        'cruTableId' => $cruDocsTableId,
                        'cruColumnStorageKey' => $cruDocsColumnStorageKey,
                        'cruColumns' => $cruDocColumns,
                    ])
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        {{-- Client-side DataTable (see @push('scripts') below): the global
                             enhancer adds the search box + length menu ("Showing …") +
                             pagination on init. Each cell carries cru-col-<key> so the
                             Column-Visibility control can hide columns independently. --}}
                        <table id="{{ $cruDocsTableId }}" class="table table-hover mb-0 align-middle w-100">
                            <thead>
                                <tr>
                                    <th data-col="sno" class="cru-col-sno text-center fw-bold">S.No.</th>
                                    <th data-col="document_name" class="cru-col-document_name fw-bold">Document Name</th>
                                    <th data-col="file_title" class="cru-col-file_title fw-bold">File Title</th>
                                    <th data-col="course" class="cru-col-course fw-bold">Course</th>
                                    <th data-col="subject" class="cru-col-subject fw-bold">Subject</th>
                                    <th data-col="topic" class="cru-col-topic fw-bold">Topic</th>
                                    <th data-col="session_date" class="cru-col-session_date fw-bold">Session Date</th>
                                    <th data-col="author" class="cru-col-author fw-bold">Author</th>
                                    <th data-col="action" class="cru-col-action text-center fw-bold">Action</th>
                                </tr>
                            </thead>
                            {{-- Rows come from the server-side DataTable (see script below).
                                 The action cell renders the shared document-actions partial
                                 server-side, same as before. --}}
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @push('scripts')
            <script>
            (function () {
                function initCruDocsTable() {
                    if (!(window.jQuery && $.fn && $.fn.dataTable)) return;
                    var el = document.getElementById(@json($cruDocsTableId));
                    if (!el || $.fn.dataTable.isDataTable(el)) return;

                    // Server-side: search, sort and paging are resolved in SQL.
                    $(el).DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.course-repository.user.show', $repository->pk) }}",
                            type: 'GET'
                        },
                        columns: [
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'cru-col-sno text-center' },
                            { data: 'document_name', name: 'document_name', className: 'cru-col-document_name' },
                            { data: 'file_title_short', name: 'file_title_short', className: 'cru-col-file_title' },
                            { data: 'course', name: 'course', orderable: false, searchable: false, className: 'cru-col-course' },
                            { data: 'subject', name: 'subject', orderable: false, searchable: false, className: 'cru-col-subject' },
                            { data: 'topic', name: 'topic', orderable: false, searchable: false, className: 'cru-col-topic' },
                            { data: 'session_date', name: 'session_date', orderable: false, searchable: false, className: 'cru-col-session_date' },
                            { data: 'author', name: 'author', orderable: false, searchable: false, className: 'cru-col-author' },
                            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'cru-col-action text-center' }
                        ],
                        paging: true,
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                        searching: true,
                        ordering: true,
                        info: true,
                        // responsive:false so the extension's own column collapsing can't
                        // fight the CSS-based Column-Visibility control.
                        responsive: false,
                        autoWidth: false,
                        order: [], // keep the server order (pk desc)
                        pagingType: 'full_numbers',
                        language: { processing: 'Loading data…' }
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initCruDocsTable);
                } else {
                    initCruDocsTable();
                }
            })();
            </script>
            @include('admin.course-repository.user.partials.column-toggle-script', [
                'cruTableId' => $cruDocsTableId,
                'cruColumnStorageKey' => $cruDocsColumnStorageKey,
                'cruColumns' => $cruDocColumns,
            ])
            @endpush
            @endif
            @endif
        </div>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection