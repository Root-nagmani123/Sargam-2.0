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
$documentCount = $documents->count();
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
            @if($documents->count() > 0)
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
                    <h5 class="mb-0 fw-bold">Documents ({{ $documents->count() }})</h5>
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
                            <tbody>
                                @foreach ($documents as $index => $doc)
                                <tr>
                                    <td class="cru-col-sno text-center">{{ $loop->iteration }}</td>
                                    <td class="cru-col-document_name">
                                        <span
                                            class="material-icons material-symbols-rounded text-danger">picture_as_pdf</span>{{ Str::limit($doc->upload_document ?? 'N/A', 30) }}
                                    </td>
                                    <td class="cru-col-file_title">{{ Str::limit($doc->file_title ?? 'N/A', 25) }}</td>
                                    <td class="cru-col-course">
                                        <small>
                                            @if($doc->fallback_course)
                                            {{ $doc->fallback_course }}
                                            @else
                                            N/A
                                            @endif
                                        </small>
                                    </td>
                                    <td class="cru-col-subject">
                                        <small>
                                            @if($doc->fallback_subject)
                                            {{ Str::limit($doc->fallback_subject, 20) }}
                                            @else
                                            N/A
                                            @endif
                                        </small>
                                    </td>
                                    <td class="cru-col-topic">
                                        <small>
                                            @if($doc->fallback_topic)
                                            {{ Str::limit($doc->fallback_topic, 15) }}
                                            @else
                                            N/A
                                            @endif
                                        </small>
                                    </td>
                                    <td class="cru-col-session_date">
                                        <small>
                                            @if($doc->detail && $doc->detail->session_date)
                                            {{ $doc->detail->session_date->format('d-m-Y') }}
                                            @else
                                            N/A
                                            @endif
                                        </small>
                                    </td>
                                    <td class="cru-col-author">
                                        <small>
                                            @if($doc->fallback_author)
                                            {{ Str::limit($doc->fallback_author, 15) }}
                                            @else
                                            N/A
                                            @endif
                                        </small>
                                    </td>
                                    {{-- Shared action partial (same as documents-table / week-detail).
                                         Gates on the document's pk/detail, not physical file presence,
                                         so the buttons always show. --}}
                                    <td class="cru-col-action text-center">
                                        @include('admin.course-repository.user.partials.document-actions', [
                                            'detailPk' => $doc->detail?->pk ?? $doc->course_repository_details_pk,
                                            'detail' => $doc->detail,
                                            'fileDoc' => $doc,
                                        ])
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
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
                    if (el.querySelectorAll('tbody tr').length === 0) return; // nothing to page

                    var dt = $(el).DataTable({
                        paging: true,
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                        searching: true,
                        ordering: true,
                        info: true,
                        // responsive:false so the extension's own column collapsing can't
                        // fight the CSS-based Column-Visibility control.
                        responsive: false,
                        autoWidth: false,
                        order: [], // keep the server order (pk desc)
                        columnDefs: [
                            // S.No is a display counter and Action is just icons — neither sorts.
                            { orderable: false, targets: [0, -1] }
                        ],
                        pagingType: 'full_numbers'
                    });

                    // Renumber S.No in the CURRENT display order after every draw, so it stays
                    // sequential (1..N, continuous across pages) through sort/search/paging —
                    // otherwise each row keeps the number it was printed with server-side.
                    function renumber() {
                        var start = dt.page.info().start;
                        dt.rows({ page: 'current', order: 'applied', search: 'applied' })
                          .every(function (rowIdx, tableLoop, rowLoop) {
                              var cell = this.node().querySelector('.cru-col-sno');
                              if (cell) cell.textContent = start + rowLoop + 1;
                          });
                    }
                    dt.on('draw', renumber);
                    renumber();
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
@include('admin.course-repository.partials.single-click-links')
@endsection