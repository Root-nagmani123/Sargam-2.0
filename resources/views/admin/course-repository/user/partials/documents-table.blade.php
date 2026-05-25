@php
    $documents = $documents ?? collect();
    $totalCount = $documents->count();
@endphp
<div class="card cru-table-card border rounded-3 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle cru-table">
            <thead>
                <tr>
                    <th class="text-center text-nowrap">S. No.</th>
                    <th>Document Name</th>
                    <th>File Title</th>
                    <th>Course</th>
                    <th>Subject</th>
                    <th>Topic</th>
                    <th class="text-nowrap">Session Date</th>
                    <th>Author</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="text-truncate" style="max-width: 12rem;">
                        <span class="fw-semibold text-dark">{{ Str::limit($doc->upload_document ?? 'N/A', 40) }}</span>
                    </td>
                    <td class="text-truncate" style="max-width: 10rem;">{{ Str::limit($doc->file_title ?? 'N/A', 35) }}</td>
                    <td>
                        @if($doc->detail && $doc->detail->course)
                            {{ $doc->detail->course->course_name }}
                        @else
                            NA
                        @endif
                    </td>
                    <td>
                        @if($doc->detail && $doc->detail->subject)
                            {{ Str::limit($doc->detail->subject->subject_name, 25) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td>
                        @if($doc->detail && $doc->detail->topic)
                            {{ Str::limit($doc->detail->topic->subject_topic, 20) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($doc->detail && $doc->detail->session_date)
                            {{ $doc->detail->session_date->format('d/m/Y') }}
                        @else
                            NA
                        @endif
                    </td>
                    <td>
                        @if($doc->detail && $doc->detail->author)
                            {{ Str::limit($doc->detail->author->full_name, 20) }}
                        @elseif($doc->detail && $doc->detail->author_name)
                            {{ Str::limit($doc->detail->author_name, 20) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-2 cru-table-actions">
                            @if($doc->course_repository_details_pk)
                                <a href="{{ route('admin.course-repository.user.document-view', $doc->course_repository_details_pk) }}"
                                   class="btn btn-link btn-sm text-primary p-0 cru-btn-view"
                                   title="View"
                                   aria-label="View document">
                                    <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                                </a>
                            @elseif($doc->public_file_url ?? null)
                                <a href="{{ $doc->public_file_url }}"
                                   class="btn btn-link btn-sm text-primary p-0 cru-btn-view"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="View"
                                   aria-label="View document in new tab">
                                    <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                                </a>
                            @endif
                            <a href="{{ route('course-repository.document.download', $doc->pk) }}?file={{ urlencode($doc->upload_document) }}"
                               class="btn btn-link btn-sm text-primary p-0 cru-btn-download"
                               title="Download"
                               aria-label="Download document">
                                <i class="bi bi-download fs-5" aria-hidden="true"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">No documents found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($totalCount > 0)
    <div class="cru-table-footer d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3 border-top bg-white">
        <p class="small text-muted mb-0">
            Showing <span class="fw-semibold text-dark">{{ $totalCount }}</span> of
            <span class="fw-semibold text-dark">{{ $totalCount }}</span> items
        </p>
    </div>
    @endif
</div>
