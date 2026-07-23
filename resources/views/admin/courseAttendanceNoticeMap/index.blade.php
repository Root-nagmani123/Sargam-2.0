@extends('admin.layouts.master')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />


@section('title', 'Memo Management')

@section('setup_content')
<link rel="stylesheet" href="{{ asset('css/notice-memo-discipline.css') }}?v={{ @filemtime(public_path('css/notice-memo-discipline.css')) ?: time() }}">
<div class="container-fluid mnm-page py-2 py-md-3">
    <x-breadcrum title="Send Memo / Notice">
        @unless(isOfficerTraineeUser())
        <button type="button" data-bs-toggle="modal" data-bs-target="#addNoticeModal"
            class="btn btn-primary d-inline-flex align-items-center gap-1 px-3 shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
            Create Memo/ Notice
        </button>
        @endunless
    </x-breadcrum>
    <x-session_message />

    {{-- Add Notice modal (opens from "Create Memo/ Notice") --}}
    <div class="modal fade add-notice-modal" id="addNoticeModal" tabindex="-1" aria-labelledby="addNoticeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoticeModalLabel">Add Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('memo.notice.management.store_memo_notice') }}" method="POST" id="addNoticeForm">
                    @csrf
                    <input type="hidden" name="submission_type" value="1">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Course Name <span class="text-danger">*</span></label>
                                <select class="form-select" name="course_master_pk" id="anCourse" required>
                                    <option value="">Select Course Name</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date</label>
                                <input type="date" class="form-control" name="date_memo_notice" id="anDate" max="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Session</label>
                                <select class="form-select" id="anSession" name="class_session_master_pk" disabled>
                                    <option value="">Select Session</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Venue <span class="text-danger">*</span></label>
                                <select class="form-select" id="anVenue" name="venue_id" disabled>
                                    <option value="">Select Venue</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control an-select-look" id="anSubjectName" placeholder="Auto-filled from venue" readonly>
                                <input type="hidden" name="subject_master_id" id="anSubjectId">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Topic <span class="text-danger">*</span></label>
                                <input type="text" class="form-control an-select-look" id="anTopicName" placeholder="Auto-filled from venue" readonly>
                                <input type="hidden" name="topic_id" id="anTopicId">
                            </div>

                            <input type="hidden" name="faculty_master_pk" id="anFacultyPk">
                        </div>

                        <h6 class="an-section-title">Student List (Late &amp; Absentee)</h6>

                        <div class="an-dual">
                            <div class="an-panel">
                                <div class="an-panel-title">Defaulter Students</div>
                                <div class="an-search"><i class="bi bi-search"></i><input type="text" class="an-filter" data-target="anAvailable" placeholder="Search"></div>
                                <label class="an-selectall"><input type="checkbox" class="form-check-input an-select-all" data-panel="anAvailable"> Select All</label>
                                <div class="an-list" id="anAvailable">
                                    <div class="an-empty text-muted">Select a course and date.</div>
                                </div>
                            </div>

                            <div class="an-moves">
                                <button type="button" class="an-move-btn" data-move="all-right">Move all right</button>
                                <button type="button" class="an-move-btn" data-move="right">Move right</button>
                                <button type="button" class="an-move-btn" data-move="left">Move left</button>
                                <button type="button" class="an-move-btn" data-move="all-left">Move all left</button>
                            </div>

                            <div class="an-panel">
                                <div class="an-panel-title">Selected Students</div>
                                <div class="an-search"><i class="bi bi-search"></i><input type="text" class="an-filter" data-target="anSelected" placeholder="Search"></div>
                                <label class="an-selectall"><input type="checkbox" class="form-check-input an-select-all" data-panel="anSelected"> Select All</label>
                                <div class="an-list" id="anSelected">
                                    <div class="an-empty text-muted">No students selected.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="form-label">Select Template <span class="text-danger">*</span></label>
                            <select class="form-select" name="memo_notice_template_pk" id="anTemplate" required>
                                <option value="">Select Course first</option>
                            </select>
                        </div>

                        <h6 class="an-section-title mt-4">Preview</h6>
                        <div class="an-note"><i class="bi bi-info-circle"></i> You may edit the Notice from Notice Template</div>
                        <div id="anPreviewWrap" class="an-preview" style="display:none;">
                            <h5 class="text-center fw-bold mb-2" id="anTplCourse"></h5>
                            <p class="text-center mb-0 small">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                            <hr>
                            <p class="mb-1" id="anTplType"></p>
                            <p class="mb-1"><strong>Date:</strong> <span id="anTplDate"></span></p>
                            <div id="anTplContent" class="mb-3"></div>
                            <p class="text-end mb-0"><strong id="anTplDirector"></strong><br><span id="anTplDesig"></span></p>
                        </div>
                        <div id="anPreviewNone" class="an-preview-none text-muted" style="display:none;">No active Notice template for the selected course.</div>

                        <div id="anHiddenInputs"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" id="anSendBtn">Send Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Notice modal — template is the only editable field --}}
    <div class="modal fade add-notice-modal" id="editNoticeModal" tabindex="-1" aria-labelledby="editNoticeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editNoticeModalLabel">Edit Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editNoticeForm">
                    @csrf
                    <input type="hidden" id="editNoticePk">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Template <span class="text-danger">*</span></label>
                            <select class="form-select" id="editNoticeTemplate" required>
                                <option value="">Select Template</option>
                            </select>
                        </div>

                        <h6 class="an-section-title">Preview</h6>
                        <div class="an-note"><i class="bi bi-info-circle"></i> You may edit the Notice from Notice Template</div>
                        <div id="editNoticePreviewWrap" class="an-preview" style="display:none;">
                            <h5 class="text-center fw-bold mb-2" id="editNoticeTplCourse"></h5>
                            <p class="text-center mb-0 small">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                            <hr>
                            <p class="mb-1" id="editNoticeTplType"></p>
                            <p class="mb-1"><strong>Date:</strong> <span id="editNoticeTplDate"></span></p>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr><th>Date</th><th>No. of Session(s)</th><th>Topics</th><th>Venue</th><th>Session(s)</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td id="editNoticeInfoDate"></td>
                                            <td>1</td>
                                            <td id="editNoticeInfoTopic"></td>
                                            <td id="editNoticeInfoVenue"></td>
                                            <td id="editNoticeInfoSession"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="editNoticeTplContent" class="mb-3"></div>
                            <p class="text-end mb-0"><strong id="editNoticeTplDirector"></strong><br><span id="editNoticeTplDesig"></span></p>
                        </div>
                        <div id="editNoticePreviewNone" class="an-preview-none text-muted" style="display:none;">No active Notice template for this course.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" id="editNoticeSaveBtn">Send Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade sn-colvis-modal" id="mnmColumnModal" tabindex="-1" aria-labelledby="mnmColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mnmColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sn-colvis-grid" id="mnmColumnGrid"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-close-colvis" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs + Download --}}
  <div class="py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="mnm-tabs">
                @unless(isOfficerTraineeUser())
                <a href="{{ route('send.notice.management.index') }}" class="mnm-tab js-nav-tab">Send Direct Notice</a>
                @endunless
                <a href="{{ route('memo.notice.management.index') }}" class="mnm-tab js-nav-tab active">Send Memo / Notice</a>
                @unless(isOfficerTraineeUser())
                <a href="{{ route('memo.discipline.index') }}" class="mnm-tab js-nav-tab">Send Discipline Memo</a>
                @endunless
            </div>
            @unless(isOfficerTraineeUser())
            @php
                // Built from the controller's own resolved filter values, coerced to strings
                // via ?? '' — NOT request()->query() and NOT route()'s array-param form.
                // ConvertEmptyStringsToNull turns "from_date=" into PHP null before this
                // renders; route()'s array-parameter builder then silently DROPS null-valued
                // keys entirely (while $request->has() on the receiving end still treats an
                // explicitly-present "from_date=" as present). So either shortcut produces a
                // Download link with NO from_date/to_date keys at all whenever no date filter
                // is active — exportCsv() then reads that as "brand new request, default to
                // today-only", silently downloading the wrong (empty) dataset. See the same
                // fix in memo_discipline/index.blade.php's Download link.
                $mnmExportParams = [
                    'program_name' => $programNameFilter ?? '',
                    'type' => $typeFilter ?? '',
                    'status' => $statusFilter ?? '',
                    'from_date' => $fromDateFilter ?? '',
                    'to_date' => $toDateFilter ?? '',
                    'search' => $searchFilter ?? '',
                ];
                $mnmDownloadUrl = route('memo.notice.management.export_csv') . '?' . http_build_query($mnmExportParams);
                $mnmDownloadPdfUrl = route('memo.notice.management.export_pdf') . '?' . http_build_query($mnmExportParams);
            @endphp
            <div class="dropdown">
                <button type="button" id="mnmDownloadToggle" class="mnm-download dropdown-toggle border-0" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mnmDownloadToggle">
                    <li><a href="{{ $mnmDownloadUrl }}" id="mnmDownloadLink" class="dropdown-item"><i class="bi bi-file-earmark-excel me-1"></i> Excel (.xlsx)</a></li>
                    <li><a href="{{ $mnmDownloadPdfUrl }}" id="mnmDownloadPdfLink" class="dropdown-item"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a></li>
                </ul>
            </div>
            @endunless
        </div>

    <!-- start Zero Configuration -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            @php
                $today = \Carbon\Carbon::today()->toDateString();
                $isToday = $fromDateFilter === $today && $toDateFilter === $today;
                $hasRange = ($fromDateFilter || $toDateFilter) && !$isToday;
            @endphp
            <form method="GET" action="{{ route('memo.notice.management.index') }}" id="filterForm">
                <div class="mnm-filter-bar mb-3">
                    <span class="mnm-filter-label">Filters</span>

                    <select class="form-select" id="program_name" name="program_name" aria-label="Program Name">
                        <option value="">Program Name</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->pk }}" {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
                        @endforeach
                    </select>

                    <select class="form-select" id="type" name="type" aria-label="Type">
                        <option value="">Type</option>
                        <option value="1" {{ $typeFilter == '1' ? 'selected' : '' }}>Notice</option>
                        <option value="0" {{ $typeFilter == '0' ? 'selected' : '' }}>Memo</option>
                    </select>

                    <select class="form-select" id="status" name="status" aria-label="Status">
                        <option value="">Status</option>
                        <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Open</option>
                        <option value="0" {{ $statusFilter == '0' ? 'selected' : '' }}>Close</option>
                    </select>

                    <select class="form-select" id="mnmTimePeriod" aria-label="Time Period">
                        <option value="today" {{ $isToday ? 'selected' : '' }}>Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="custom" {{ $hasRange ? 'selected' : '' }}>Custom Range</option>
                    </select>
                    <input type="date" class="form-control mnm-date {{ $hasRange ? '' : 'd-none' }}" id="from_date" name="from_date" value="{{ $fromDateFilter }}" style="max-width:160px;">
                    <input type="date" class="form-control mnm-date {{ $hasRange ? '' : 'd-none' }}" id="to_date" name="to_date" value="{{ $toDateFilter }}" style="max-width:160px;">

                    <a href="{{ route('memo.notice.management.index') }}" class="mnm-reset">Reset Filters</a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="mnm-icon-btn" data-bs-toggle="modal" data-bs-target="#mnmColumnModal">
                            <i class="bi bi-layout-three-columns"></i> Columns
                        </button>
                        <button type="button" class="mnm-icon-btn" id="mnmSearchToggle" aria-label="Search"><i class="bi bi-search"></i></button>
                        <div class="mnm-search-wrap {{ $searchFilter ? '' : 'd-none' }}" id="mnmSearchWrap" style="position:relative;">
                            <input type="text" class="mnm-search-input" id="search" name="search" placeholder="Search..."
                                value="{{ $searchFilter }}" autocomplete="off" style="padding-right:1.9rem;">
                            <button type="button" id="mnmSearchClear" aria-label="Clear search" title="Clear"
                                style="position:absolute;top:50%;right:.35rem;transform:translateY(-50%);border:0;background:transparent;color:#94a3b8;line-height:1;padding:.15rem;{{ $searchFilter ? '' : 'display:none;' }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <div id="mnmListContainer">
            <div class="table-responsive">
                <table id="mnmTable" class="table align-middle mb-0">
                    <thead>
                        <tr class="align-middle">
                            <th>S. No.</th>
                            <th>Program Name</th>
                            <th>Participant Name</th>
                            <th>Type</th>
                            <th>Session Date</th>
                            <th>Topic</th>
                            <th>Conclusion Type</th>
                            <th>Conclusion Remark</th>
                            <th>Marks Deduction</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($memos as $index => $memo)
                        @php
                            $role = session()->get('role_name');
                            $noticeKey = $memo->student_pk . '_' . $memo->course_master_pk;
                            $isNotice = $memo->type_notice_memo == 'Notice';
                            $st = $memo->status ?? null;
                            $cs = $memo->communication_status ?? null;
                            if ($isNotice) {
                                $stLabel = $st == 1 ? 'Notice Sent' : 'Notice Chat Closed';
                                $stClass = $st == 1 ? 'mnm-status--notice' : 'mnm-status--closed';
                            } elseif ($cs == 1) {
                                $stLabel = 'Memo Chat Open'; $stClass = 'mnm-status--memo-open';
                            } elseif ($cs == 2) {
                                $stLabel = 'Memo Chat Closed'; $stClass = 'mnm-status--closed';
                            } else {
                                $stLabel = 'Memo Sent'; $stClass = 'mnm-status--memo-sent';
                            }
                            $sessionDate = $memo->session_date ?? $memo->date_ ?? null;
                            $hasBell = $isNotice && isset($noticeCount[$noticeKey]) && $noticeCount[$noticeKey] >= 2;
                        @endphp
                        <tr>
                            <td>{{ $memos->firstItem() + $index }}</td>
                            <td class="fw-medium">{{ $memo->course_name ?? 'N/A' }}</td>
                            <td class="fw-medium">{{ $memo->student_name ?? 'N/A' }}</td>
                            <td>
                                @if($isNotice)
                                    <span class="badge bg-primary-subtle text-primary"><i class="bi bi-file-earmark-text me-1"></i> Notice</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-file-earmark me-1"></i> Memo</span>
                                @endif
                            </td>
                            <td>{{ $sessionDate ? date('d-m-Y', strtotime($sessionDate)) : 'N/A' }}</td>
                            <td>{{ $memo->topic_name ?? 'N/A' }}</td>
                            <td>{{ ($memo->discussion_name ?? '') !== '' ? $memo->discussion_name : 'N/A' }}</td>
                            <td>{{ ($memo->conclusion_remark ?? '') !== '' ? $memo->conclusion_remark : 'N/A' }}</td>
                            <td>{{ ($memo->mark_of_deduction ?? '') !== '' ? $memo->mark_of_deduction : 'N/A' }}</td>
                            <td>{{ !empty($memo->created_date) ? date('d-m-Y', strtotime($memo->created_date)) : 'N/A' }}</td>
                            <td><span class="mnm-status {{ $stClass }}">{{ $stLabel }}</span></td>
                            <td>
                                <div class="mnm-actions justify-content-center">
                                    {{-- Notice: view the notice conversation/document --}}
                                    @if($memo->notice_id)
                                    <a class="mnm-action" href="{{ route('memo.notice.management.conversation', ['id' => $memo->notice_id, 'type' => 'notice']) }}" title="View Notice">
                                        <i class="bi bi-file-earmark-text"></i><span>Notice</span>
                                    </a>
                                    @else
                                    <span class="mnm-action disabled" title="No notice"><i class="bi bi-file-earmark-text"></i><span>Notice</span></span>
                                    @endif

                                    {{-- Memo: view the memo conversation/document page (same as Notice above) --}}
                                    @if(!empty($memo->memo_id))
                                    <a class="mnm-action" href="{{ route('memo.notice.management.conversation', ['id' => $memo->memo_id, 'type' => 'memo']) }}" title="View Memo Document">
                                        <i class="bi bi-file-earmark"></i><span>Memo Doc</span>
                                    </a>
                                    @else
                                    <span class="mnm-action disabled" title="No memo yet"><i class="bi bi-file-earmark"></i><span>Memo Doc</span></span>
                                    @endif

                                    {{-- Edit Notice: template only, and only while still open --}}
                                    @if($isNotice && $canManageMemoNotice && $st == 1)
                                    <a href="javascript:void(0)" class="mnm-action edit-notice-btn" data-notice-id="{{ $memo->notice_id }}"
                                        data-bs-toggle="modal" data-bs-target="#editNoticeModal" title="Edit Notice">
                                        <i class="bi bi-pencil"></i><span>Edit</span>
                                    </a>
                                    @endif

                                    {{-- Chats: open the conversation offcanvas --}}
                                    @if($isNotice)
                                    <a class="mnm-action view-conversation" data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas"
                                        data-type="notice" data-id="{{ $memo->notice_id }}" data-topic="{{ $memo->topic_name }}" data-participant="{{ $memo->student_name }}" data-closed="{{ $stClass === 'mnm-status--closed' ? '1' : '0' }}" title="Open chat">
                                        <i class="bi bi-chat-dots"></i><span>Chats</span>
                                        @if($hasBell)<span class="mnm-dot"></span>@endif
                                    </a>
                                    @else
                                    <a class="mnm-action view-conversation" data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas"
                                        data-type="memo" data-id="{{ $memo->memo_id }}" data-topic="{{ $memo->topic_name }}" data-participant="{{ $memo->student_name }}" data-closed="{{ $stClass === 'mnm-status--closed' ? '1' : '0' }}" title="Open chat">
                                        <i class="bi bi-chat-dots"></i><span>Chats</span>
                                        @if($cs == 1)<span class="mnm-dot"></span>@endif
                                    </a>
                                    @endif

                                    {{-- Memo: generate (notice→status 2) or preview (memo) --}}
                                    @if($isNotice && $st == 2)
                                    <a href="javascript:void(0)" class="mnm-action generate-memo-btn" data-id="{{ $memo->memo_notice_id }}"
                                        data-bs-toggle="modal" data-bs-target="#memo_generate" title="Generate Memo">
                                        <i class="bi bi-file-earmark-plus"></i><span>Memo</span>
                                    </a>
                                    @elseif(!$isNotice)
                                    <a href="javascript:void(0)" class="mnm-action preview-memo-btn" data-notice-id="{{ $memo->notice_id }}" data-memo-id="{{ $memo->memo_id }}"
                                        data-bs-toggle="modal" data-bs-target="#memo_generate" title="View Memo">
                                        <i class="bi bi-file-earmark-check"></i><span>Memo</span>
                                        @if($cs == 1)<span class="mnm-dot"></span>@endif
                                    </a>
                                    @if($canManageMemoNotice && $cs != 2)
                                    <a href="javascript:void(0)" class="mnm-action edit-memo-btn" data-memo-id="{{ $memo->memo_id }}"
                                        data-bs-toggle="modal" data-bs-target="#memo_generate" title="Edit Memo">
                                        <i class="bi bi-pencil"></i><span>Edit</span>
                                    </a>
                                    @endif
                                    @else
                                    <span class="mnm-action disabled" title="Memo not available yet"><i class="bi bi-file-earmark"></i><span>Memo</span></span>
                                    @endif

                                    {{-- Delete: admins/faculty only, hard-deletes the notice/memo + its chat.
                                         Only allowed while the notice/memo is still open — disabled once it's
                                         closed ($stClass is 'mnm-status--closed' for Notice/Memo Chat Closed). --}}
                                    @if($canManageMemoNotice)
                                        @if($stClass === 'mnm-status--closed')
                                        <span class="mnm-action disabled" title="Cannot delete a closed {{ $isNotice ? 'notice' : 'memo' }}">
                                            <i class="bi bi-trash3"></i><span>Delete</span>
                                        </span>
                                        @else
                                        <a href="javascript:void(0)" class="mnm-action mnm-delete-record" style="color:#d92d20;"
                                            data-id="{{ $isNotice ? $memo->notice_id : $memo->memo_id }}"
                                            data-type="{{ $isNotice ? 'notice' : 'memo' }}" title="Delete">
                                            <i class="bi bi-trash3"></i><span>Delete</span>
                                        </a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="align-middle">
                            <td colspan="12" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No records found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">

                    <div class="text-muted small mb-0">
                        Showing {{ $memos->firstItem() ?? 0 }}
                        to {{ $memos->lastItem() }}
                        of {{ $memos->total() }} items
                    </div>

                    <div class="ms-auto">
                        {{ $memos->links('vendor.pagination.custom') }}
                    </div>

                </div>
            </div>
            </div><!-- /#mnmListContainer -->

        </div>
    </div>
    <!-- end Zero Configuration -->
   
    <!-- Enhanced Offcanvas with GIGW Guidelines -->
    <div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic" role="dialog">
        <div class="offcanvas-header conv-header">
            <div class="w-100">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <h4 class="conv-title mb-0">Conversation</h4>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn conv-endchat" id="endChatBtn" disabled title="Open a conversation first">End Chat</button>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close conversation panel" title="Close"></button>
                    </div>
                </div>
                <div class="conv-sub mt-2">
                    <div id="conversationTopic">Topic: —</div>
                    <div id="conversationParticipant">Participant: —</div>
                </div>
                <div class="conv-toggle mt-2" role="tablist">
                    <button type="button" class="conv-toggle-btn" data-conv-type="notice">Notice</button>
                    <button type="button" class="conv-toggle-btn" data-conv-type="memo">Memo</button>
                </div>
                <span id="type_side_menu" class="d-none"></span>
            </div>
        </div>
        <input type="hidden" id="userType" value="" aria-hidden="true">

        <div class="offcanvas-body d-flex flex-column">
            <!-- Chat Body with Enhanced Styling -->
            <div class="chat-body flex-grow-1" id="chatBody" role="log" aria-live="polite" aria-label="Conversation messages">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading conversation...</span>
                        </div>
                        <p class="text-muted">Loading conversation...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <!-- memo generation modal -->
    <div class="modal fade" id="memo_generate" tabindex="-1" aria-labelledby="memo_generateLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-semibold" id="memo_generateLabel">Generate Memo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('memo.notice.management.store_memo_status') }}" method="POST">
                        @csrf
                        <input type="hidden" name="memo_form_submit" value="1">

                        @if(old('memo_form_submit') && $errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="course_master_name" class="form-label">Course</label>

                                <input type="text" id="course_master_name" class="form-control"
                                    name="course_master_name" value="{{ old('course_master_name') }}" readonly>
                                <input type="hidden" id="course_master_pk" name="course_master_pk" value="{{ old('course_master_pk') }}">
                                <input type="hidden" id="student_notice_status_pk" name="student_notice_status_pk" value="{{ old('student_notice_status_pk') }}">
                                <input type="hidden" id="memo_count" name="memo_count" value="{{ old('memo_count') }}">
                                <input type="hidden" id="student_pk" name="student_pk" value="{{ old('student_pk') }}">
                                @error('course_master_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="date_memo_notice" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date_memo_notice" name="date_memo_notice"
                                    value="{{ old('date_memo_notice') }}" required readonly>
                                @error('date_memo_notice')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="subject_master_id" class="form-label">Subject <span
                                        class="text-danger">*</span></label>

                                <input type="text" id="subject_master_id" class="form-control" name="subject_master_id"
                                    value="{{ old('subject_master_id') }}" readonly>

                                @error('subject_master_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="topic_id" class="form-label">Topic</label>

                                <input type="text" id="topic_id" class="form-control" name="topic_id" value="{{ old('topic_id') }}" readonly>

                                @error('topic_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>



                            <div class="col-12 col-md-6 mb-3">
                                <label for="session_name" class="form-label">Session</label>
                                <input type="text" id="class_session_master_pk" name="class_session_master_pk" class="form-control" value="{{ old('class_session_master_pk') }}" readonly>
                                @error('session_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="faculty_name" class="form-label">Faculty Name</label>
                                <input type="text" id="faculty_name" name="faculty_name" class="form-control" value="{{ old('faculty_name') }}" readonly>
                                @error('faculty_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="student_name" class="form-label">Student Name</label>
                                <input type="text" id="student_name" name="student_name" class="form-control" value="{{ old('student_name') }}" readonly>
                                <select id="student_pk_select" class="form-select d-none">
                                    <option value="">Select Student</option>
                                </select>
                                <small class="text-muted d-none" id="studentReassignHint">Reassign this memo to a different student enrolled in the same course.</small>
                                @error('student_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="memoTemplate" class="form-label">Template</label>
                                <select name="memo_notice_template_pk" id="memoTemplate" class="form-select @error('memo_notice_template_pk') is-invalid @enderror">
                                    <option value="">Select Template</option>
                                </select>
                                <small class="text-muted">Memo template to use. Depends on the Memo Type/course.</small>
                                @error('memo_notice_template_pk')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="memo_type" class="form-label">Memo Type</label>
                                <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-select @error('memo_type_master_pk') is-invalid @enderror">
                                    <option value="">Select Memo Type</option>
                                    @foreach ($memo_master as $master)
                                    <option value="{{ $master->pk }}" {{ old('memo_type_master_pk') == $master->pk ? 'selected' : '' }}>{{ $master->memo_type_name }}</option>
                                    @endforeach
                                </select>
                                @error('memo_type_master_pk')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="memo_number" class="form-label">Memo Number</label>
                                <input type="text" id="memo_number" name="memo_number" class="form-control" value="{{ old('memo_number') }}" readonly>
                                @error('memo_number')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>


                            <div class="col-12 col-md-6 mb-3">
                                <label for="venue" class="form-label">Venue</label>
                                <select name="venue" id="venue" class="form-select @error('venue') is-invalid @enderror">
                                    <option value="">Select Venue</option>
                                    @foreach ($venue as $v)
                                    <option value="{{ $v->venue_id }}" {{ old('venue') == $v->venue_id ? 'selected' : '' }}>{{ $v->venue_name }}</option>
                                    @endforeach
                                </select>
                                @error('venue')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="memo_date" class="form-label">Date</label>
                                <input type="date" id="memo_date" name="memo_date" class="form-control" min="{{ date('Y-m-d') }}" value="{{ old('memo_date') }}">
                                @error('memo_date')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="meeting_time" class="form-label">Meeting Time</label>
                                <input type="time" id="meeting_time" name="meeting_time" class="form-control @error('meeting_time') is-invalid @enderror" value="{{ old('meeting_time') }}">
                                @error('meeting_time')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="textarea" class="form-label">Message (If Any)</label>
                                <textarea class="form-control" id="textarea" name="Remark" rows="3"
                                    placeholder="Enter remarks...">{{ old('Remark') }}</textarea>
                                @error('Remark')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <h6 class="an-section-title mt-2">Preview</h6>
                        <div id="memoPreviewWrap" class="an-preview" style="display:none;">
                            <h5 class="text-center fw-bold mb-2" id="memoPvCourse"></h5>
                            <p class="text-center mb-0 small">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                            <hr>
                            <p class="mb-1"><strong>Date:</strong> <span id="memoPvDate"></span></p>
                            <div id="memoPvContent" class="mb-3"></div>
                            <div id="memoPvSignature" class="text-end mb-2"></div>
                            <p class="text-end mb-0"><strong id="memoPvDirector"></strong><br><span id="memoPvDesig"></span></p>
                        </div>
                        <div id="memoPreviewNone" class="an-preview-none text-muted" style="display:none;">No matching template found — select a Memo Type and/or Template.</div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
                </form>
            </div>
        </div>

    </div>
    <!-- End Chat modal -->
    <div class="modal fade" id="end_chat_modal" tabindex="-1" aria-labelledby="endChatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="endChatModalLabel">End chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="endChatForm">
                    @csrf
                    <input type="hidden" name="id" id="endChatId">
                    <input type="hidden" name="type" id="endChatType">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="endChatConclusion" class="form-label fw-semibold">Conclusion Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="endChatConclusion" name="memo_conclusion_master_pk" required>
                                <option value="">Select Conclusion Type</option>
                                @foreach(($conclusions ?? []) as $c)
                                    <option value="{{ $c->pk }}" data-name="{{ \Illuminate\Support\Str::lower($c->discussion_name) }}">{{ $c->discussion_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="endChatMarksWrap">
                            <label for="endChatMarks" class="form-label fw-semibold">Marks to Deduct <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="endChatMarks" name="marks_deducted" min="0" step="0.5" placeholder="eg. Enter marks to deduct (e.g. 1.5, 2.5)" disabled>
                        </div>
                        <div class="mb-1">
                            <label for="endChatRemark" class="form-label fw-semibold">Conclusion Remarks</label>
                            <textarea class="form-control" id="endChatRemark" name="conclusion_remark" rows="4" placeholder="eg. Enter your remarks here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4">End Chat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Chat modal end -->

    <!-- Memo generation end -->
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    // Holds the conversation currently open in the offcanvas (used by End Chat).
    window.currentConv = { id: null, type: null };

    $(document).on('click', '.view-conversation', function() {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        let participant = $(this).data('participant') || '—';
        let isClosed = String($(this).data('closed')) === '1';
        $('#userType').val(type);
        let user_type = 'admin';

        window.currentConv = { id: memoId, type: type, closed: isClosed };

        $('#conversationTopic').text("Topic: " + (topic || '—'));
        $('#conversationParticipant').text("Participant: " + participant);
        $('#type_side_menu').text(type);
        // Reflect the current conversation type in the Notice/Memo toggle.
        $('.conv-toggle-btn').removeClass('active');
        $('.conv-toggle-btn[data-conv-type="' + type + '"]').addClass('active');

        // Keep End Chat disabled until the conversation actually loads into the
        // chat window. It's enabled only once the chat is open AND still active
        // (a closed notice/memo can't be ended again).
        $('#endChatBtn')
            .prop('disabled', true)
            .attr('title', 'Loading conversation…');
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/admin/memo-notice-management/get_conversation_model/' + memoId + '/' + type + '/' + user_type,
            type: 'GET',
            success: function(res) {
                $('#chatBody').html(res);
                // Chat is now open in the window → enable End Chat unless it's closed.
                $('#endChatBtn')
                    .prop('disabled', isClosed)
                    .attr('title', isClosed ? 'This ' + type + ' is already closed' : 'End Chat');
            },
            error: function() {
                $('#chatBody').html(
                    '<p class="text-danger text-center">Failed to load conversation.</p>'
                );
                // Load failed — no chat open, keep End Chat disabled.
                $('#endChatBtn').prop('disabled', true).attr('title', 'Open a conversation first');
            }
        });

        // Show offcanvas
        let chatOffcanvas = new bootstrap.Offcanvas(document.getElementById('chatOffcanvas'));
        chatOffcanvas.show();
    });

    // ── End Chat: open the conclusion modal for the current conversation ──
    $('#endChatBtn').on('click', function() {
        if (!window.currentConv || !window.currentConv.id) { return; }
        if (window.currentConv.closed) { return; } // closed notice/memo can't be ended
        $('#endChatId').val(window.currentConv.id);
        $('#endChatType').val(window.currentConv.type);
        $('#endChatForm')[0].reset();
        $('#endChatId').val(window.currentConv.id);
        $('#endChatType').val(window.currentConv.type);
        toggleEndChatMarks(); // reset marks field to hidden state
        new bootstrap.Modal(document.getElementById('end_chat_modal')).show();
    });

    // ── Show a "Marks to Deduct" field only when the "Marks Deduction" conclusion is chosen ──
    function toggleEndChatMarks() {
        const name = ($('#endChatConclusion').find('option:selected').data('name') || '').toString();
        const isDeduction = name.indexOf('marks deduction') !== -1;
        $('#endChatMarksWrap').toggleClass('d-none', !isDeduction);
        // Only submit + require the value when the field is visible
        $('#endChatMarks').prop('disabled', !isDeduction).prop('required', isDeduction);
        if (!isDeduction) { $('#endChatMarks').val(''); }
    }
    $('#endChatConclusion').on('change', toggleEndChatMarks);

    $('#endChatForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('memo.notice.management.endChat') }}",
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res && res.success) {
                    location.reload();
                } else {
                    alert((res && res.message) || 'Failed to end conversation.');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to end conversation.');
                $btn.prop('disabled', false);
            }
        });
    });

    // Reset listener guard when offcanvas fully hides, so next open re-registers cleanly if needed
    document.getElementById('chatOffcanvas').addEventListener('hidden.bs.offcanvas', function () {
        // Do NOT reset _memoNoticeListenersRegistered — document-level listeners persist across opens
        // Resetting it causes duplicate listener registration on each re-open

        // No chat is open now → disable End Chat until a conversation is opened again.
        window.currentConv = { id: null, type: null };
        $('#endChatBtn').prop('disabled', true).attr('title', 'Open a conversation first');
    });});
</script>
@push('scripts')
<script>
$(document).ready(function() {
    const memoChoicesIds = ['program_name', 'type', 'status', 'mnmTimePeriod', 'memo_type_master_pk', 'venue'];
    const memoChoicesMap = new Map();

    function createChoicesInstance(el) {
        if (!el || typeof window.Choices === 'undefined') return;
        if (memoChoicesMap.has(el.id)) return;

        const instance = new Choices(el, {
            shouldSort: false,
            searchEnabled: true,
            searchResultLimit: 50,
            itemSelectText: '',
            allowHTML: false,
            classNames: {
                containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
                input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
                inputCloned: ['choices__input--cloned'],
                listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                item: ['choices__item', 'dropdown-item', 'rounded-0'],
                itemSelectable: ['choices__item--selectable'],
                itemDisabled: ['choices__item--disabled', 'disabled'],
                itemChoice: ['choices__item--choice'],
                placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                highlightedState: ['is-highlighted', 'active'],
                notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2']
            }
        });

        memoChoicesMap.set(el.id, instance);
    }

    function initMemoChoices() {
        memoChoicesIds.forEach(function(id) {
            createChoicesInstance(document.getElementById(id));
        });
    }

    function syncMemoChoicesById(id) {
        const el = document.getElementById(id);
        const instance = memoChoicesMap.get(id);
        if (!el || !instance) return;
        const values = Array.from(el.selectedOptions).map(option => option.value);
        instance.removeActiveItems();
        if (values.length) {
            instance.setChoiceByValue(values);
        }
        if (el.disabled) {
            instance.disable();
        } else {
            instance.enable();
        }
    }

    initMemoChoices();

    var memoTemplateCache = []; // Memo templates for the currently-loaded course (+ memo type)

    function renderMemoTemplatePreview(tpl) {
        if (tpl && (tpl.content || tpl.director_name)) {
            $('#memoPvCourse').text($('#course_master_name').val() || '');
            $('#memoPvDate').text($('#memo_date').val() || $('#date_memo_notice').val() || '');
            $('#memoPvContent').html(tpl.content || '');
            $('#memoPvDirector').text(tpl.director_name || '');
            $('#memoPvDesig').text(tpl.director_designation || '');
            $('#memoPvSignature').html(tpl.signature_image
                ? '<img src="/storage/' + tpl.signature_image + '" alt="Signature" style="max-height:60px;">'
                : '');
            $('#memoPreviewNone').hide();
            $('#memoPreviewWrap').show();
        } else {
            $('#memoPreviewWrap').hide();
            $('#memoPreviewNone').show();
        }
    }

    // Load Memo templates for a course (+ optional memo type filter) into the picker.
    function loadMemoTemplates(courseId, selectedPk, memoTypeMasterPk) {
        var $t = $('#memoTemplate');
        memoTemplateCache = [];
        $t.html('<option value="">Select Template</option>');
        if (!courseId) { renderMemoTemplatePreview(null); return; }
        $.get("{{ route('memo.notice.management.getTemplatesByType') }}", {
            course_id: courseId,
            type: 'Memo',
            memo_type_master_pk: memoTypeMasterPk || ''
        }).done(function (res) {
            memoTemplateCache = res || [];
            if (!memoTemplateCache.length) {
                $t.html('<option value="">No template configured for this Memo Type</option>');
                renderMemoTemplatePreview(null);
                return;
            }
            memoTemplateCache.forEach(function (tpl) {
                $t.append($('<option>').val(tpl.pk).text(tpl.title));
            });
            if (selectedPk) { $t.val(String(selectedPk)); }
            else if (memoTemplateCache.length === 1) { $t.val(String(memoTemplateCache[0].pk)); }
            var chosen = memoTemplateCache.find(function (t) { return String(t.pk) === String($t.val()); });
            renderMemoTemplatePreview(chosen || null);
        });
    }

    $('#memoTemplate').on('change', function () {
        var pk = String($(this).val() || '');
        var tpl = memoTemplateCache.find(function (t) { return String(t.pk) === pk; });
        renderMemoTemplatePreview(tpl || null);
    });

    // Memo Type changed → reload the Template list filtered to that type.
    $('#memo_type_master_pk').on('change', function () {
        var courseId = $('#course_master_pk').val();
        loadMemoTemplates(courseId, null, $(this).val());
    });

    // Filter form submission on change → AJAX (no full page reload)
    $('#program_name, #type, #status, #from_date, #to_date').on('change', function() {
        if (typeof window.applyMnmFiltersAjax === 'function') {
            window.applyMnmFiltersAjax();
        } else {
            $('#filterForm').get(0).submit();
        }
    });

    // Handle Generate Memo button (editable mode)
    $(document).on('click', '.generate-memo-btn', function() {
        let memoId = $(this).data('id');
        setModalMode('generate');

        // Fresh generate: don't carry over a memo type left selected from a
        // previously-generated memo in this same page session, or its stale
        // value will filter out this course's templates and leave the preview blank.
        $('#memo_type_master_pk').val('');
        syncMemoChoicesById('memo_type_master_pk');

        $.ajax({
            url: "{{ route('memo.notice.management.get_memo_data') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                memo_notice_id: memoId
            },
            success: function(res) {
                // Populate modal fields
                $('#course_master_name').val(res.course_master_name);
                $('#date_memo_notice').val(res.date_);
                $('#student_name').val(res.student_name);
                $('#subject_master_id').val(res.student_name);
                $('#topic_id').val(res.subject_topic);
                $('#venue_id').val(res.venue_id);
                $('#student_notice_status_pk').val(res
                    .student_notice_status_pk);
                $('#course_master_pk').val(res.course_master_pk);
                $('#memo_count').val(res.memo_count + 1);

                $('#session_name').val(res.session_name);
                $('#class_session_master_pk').val(res.class_session_master_pk);
                $('#faculty_name').val(res.faculty_name);
                $('#student_pk').val(res.student_pk);
                $('#memo_number').val(res.memo_number);

                // Set memo_date to the notice date by default
                const today = new Date().toISOString().split('T')[0];
                $('#memo_date').val(today);

                // Load Memo templates for this course so the sender can pick one.
                loadMemoTemplates(res.course_master_pk, null, $('#memo_type_master_pk').val());
            },
            error: function() {
                alert('Something went wrong!');
            }
        });
    });

    // Handle Preview Memo button (read-only mode)
    $(document).on('click', '.preview-memo-btn', function() {
        let memoId = $(this).data('memo-id');
        // Set preview mode immediately to hide save button
        setModalMode('preview');
        // Also explicitly hide save button as backup
        $('#memo_generate').find('.modal-footer').find('button[type="submit"]').hide();

        if (!memoId) {
            alert('Memo ID not found!');
            return;
        }

        // Fetch all memo data (including notice-related data)
        $.ajax({
            url: "{{ route('memo.notice.management.get_generated_memo_data') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                memo_id: memoId
            },
            success: function(res) {
                // Populate all modal fields from the response
                $('#course_master_name').val(res.course_master_name || '');
                $('#date_memo_notice').val(res.date_ || '');
                $('#student_name').val(res.student_name || '');
                $('#subject_master_id').val(res.subject_master_name || res.student_name || '');
                $('#topic_id').val(res.subject_topic || '');
                $('#student_notice_status_pk').val(res.student_notice_status_pk || '');
                $('#course_master_pk').val(res.course_master_pk || '');
                $('#memo_count').val(res.memo_count || '');

                $('#session_name').val(res.session_name || '');
                $('#class_session_master_pk').val(res.class_session_master_pk || '');
                $('#faculty_name').val(res.faculty_name || '');
                $('#student_pk').val(res.student_pk || '');
                $('#memo_number').val(res.memo_number || '');

                // Populate memo-specific fields
                if (res.memo_type_master_pk) {
                    $('#memo_type_master_pk').val(res.memo_type_master_pk);
                    syncMemoChoicesById('memo_type_master_pk');
                }
                if (res.venue_master_pk) {
                    $('#venue').val(res.venue_master_pk);
                    syncMemoChoicesById('venue');
                }
                if (res.date) {
                    $('#memo_date').val(res.date);
                }
                if (res.start_time) {
                    $('#meeting_time').val(res.start_time);
                }
                if (res.message) {
                    $('#textarea').val(res.message);
                }

                // Fetch the actual template content so the detail/view mode shows
                // what the memo document says, not just the field selections.
                loadMemoTemplates(res.course_master_pk, res.memo_notice_template_pk, res.memo_type_master_pk);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching memo data:', error);
                console.error('Response:', xhr.responseText);
                alert('Failed to load memo data. Please try again.');
            }
        });
    });

    var currentEditMemoId = null;

    // Populate the student-reassignment picker for a course, preselecting the current student.
    function loadMemoStudentRoster(courseId, selectedStudentPk) {
        var $sel = $('#student_pk_select');
        $sel.html('<option value="">Select Student</option>');
        if (!courseId) { return; }
        $.get("{{ route('memo.notice.management.students_by_course') }}", { course_id: courseId })
            .done(function (res) {
                (res || []).forEach(function (s) {
                    var label = s.display_name + (s.generated_OT_code ? ' (' + s.generated_OT_code + ')' : '');
                    $sel.append($('<option>').val(s.pk).text(label));
                });
                if (selectedStudentPk) { $sel.val(String(selectedStudentPk)); }
            });
    }

    // Handle Edit Memo button (editable mode, existing memo)
    $(document).on('click', '.edit-memo-btn', function() {
        var memoId = $(this).data('memo-id');
        currentEditMemoId = memoId;
        setModalMode('edit');

        if (!memoId) {
            alert('Memo ID not found!');
            return;
        }

        $.ajax({
            url: "{{ route('memo.notice.management.get_generated_memo_data') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                memo_id: memoId
            },
            success: function(res) {
                $('#course_master_name').val(res.course_master_name || '');
                $('#date_memo_notice').val(res.date_ || '');
                $('#student_name').val(res.student_name || '');
                $('#subject_master_id').val(res.subject_master_name || res.student_name || '');
                $('#topic_id').val(res.subject_topic || '');
                $('#student_notice_status_pk').val(res.student_notice_status_pk || '');
                $('#course_master_pk').val(res.course_master_pk || '');
                $('#memo_count').val(res.memo_count || '');

                $('#session_name').val(res.session_name || '');
                $('#class_session_master_pk').val(res.class_session_master_pk || '');
                $('#faculty_name').val(res.faculty_name || '');
                $('#student_pk').val(res.student_pk || '');
                $('#memo_number').val(res.memo_number || '');

                if (res.memo_type_master_pk) {
                    $('#memo_type_master_pk').val(res.memo_type_master_pk);
                    syncMemoChoicesById('memo_type_master_pk');
                }
                if (res.venue_master_pk) {
                    $('#venue').val(res.venue_master_pk);
                    syncMemoChoicesById('venue');
                }
                if (res.date) { $('#memo_date').val(res.date); }
                if (res.start_time) { $('#meeting_time').val(res.start_time); }
                if (res.message) { $('#textarea').val(res.message); }

                loadMemoTemplates(res.course_master_pk, res.memo_notice_template_pk, res.memo_type_master_pk);
                loadMemoStudentRoster(res.course_master_pk, res.student_pk);
            },
            error: function(xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load memo data.');
            }
        });
    });

    // Function to set modal mode (generate, edit, or preview)
    var currentModalMode = 'generate';
    function setModalMode(mode) {
        currentModalMode = mode;
        const modal = $('#memo_generate');
        const form = modal.find('form');
        // Use more specific selector for save button
        const saveButton = modal.find('.modal-footer').find('button[type="submit"]');
        const modalTitle = $('#memo_generateLabel');

        // Student field: text display in generate/preview, a reassignable select in edit mode.
        $('#student_name').toggleClass('d-none', mode === 'edit');
        $('#student_pk_select').toggleClass('d-none', mode !== 'edit');
        $('#studentReassignHint').toggleClass('d-none', mode !== 'edit');

        if (mode === 'preview') {
            // Preview mode: make all fields read-only. Native date/time inputs
            // don't fully respect readonly — the picker UI can still change the
            // value in several browsers — so those two need disabled as well to
            // actually freeze them (e.g. Meeting Date on a closed memo).
            form.find('input[type="text"], input[type="date"], input[type="time"], textarea').prop('readonly', true);
            form.find('input[type="date"], input[type="time"]').prop('disabled', true);
            form.find('select').prop('disabled', true);
            saveButton.hide();
            modalTitle.text('Preview Memo');
        } else if (mode === 'edit') {
            // Edit mode: an already-generated memo — everything correctable is editable.
            form.find('input, textarea').prop('readonly', false);
            form.find('input[type="date"], input[type="time"]').prop('disabled', false); // undo preview-mode freeze
            form.find('select').prop('disabled', false);
            $('#course_master_name, #date_memo_notice, #subject_master_id, #topic_id, #class_session_master_pk, #faculty_name, #memo_number').prop('readonly', true);
            saveButton.show().text('Save Changes');
            modalTitle.text('Edit Memo');
        } else {
            // Generate mode: enable editable fields
            form.find('input, textarea').prop('readonly', false);
            form.find('input[type="date"], input[type="time"]').prop('disabled', false); // undo preview-mode freeze
            form.find('select').prop('disabled', false);
            // Keep readonly fields as readonly
            $('#course_master_name, #date_memo_notice, #subject_master_id, #topic_id, #class_session_master_pk, #faculty_name, #student_name, #memo_number').prop('readonly', true);
            // Keep non-editable selects disabled
            form.find('select').not('#memo_type_master_pk, #venue, #memoTemplate').prop('disabled', true);
            // Show save button in generate mode
            saveButton.show().text('Save');
            modalTitle.text('Generate Memo');
        }

        syncMemoChoicesById('memo_type_master_pk');
        syncMemoChoicesById('venue');
    }

    // Submit routing: edit mode goes through AJAX to updateMemoStatus; generate mode
    // uses the form's native action (store_memo_status).
    $('#memo_generate form').on('submit', function (e) {
        if (currentModalMode !== 'edit') { return; } // let the native POST proceed
        e.preventDefault();
        if (!currentEditMemoId) { return; }

        var $btn = $(this).find('.modal-footer button[type="submit"]').prop('disabled', true);
        $.ajax({
            url: "{{ rtrim(route('memo.notice.management.update_memo_status', ''), '/') }}/" + currentEditMemoId,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                student_pk: $('#student_pk_select').val(),
                memo_type_master_pk: $('#memo_type_master_pk').val(),
                memo_notice_template_pk: $('#memoTemplate').val(),
                venue: $('#venue').val(),
                date_memo_notice: $('#memo_date').val(),
                meeting_time: $('#meeting_time').val(),
                Remark: $('#textarea').val()
            },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Memo updated successfully.');
                    $('#memo_generate').modal('hide');
                    setTimeout(function () { window.location.reload(); }, 600);
                } else {
                    toastr.error(res.message || 'Update failed.');
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                var res = xhr.responseJSON;
                // A 422 validation failure carries field-specific messages in `errors`;
                // surface those instead of the generic "The given data was invalid."
                var firstFieldError = res && res.errors ? Object.values(res.errors)[0][0] : null;
                toastr.error(firstFieldError || (res && res.message) || 'Update failed.');
                $btn.prop('disabled', false);
            }
        });
    });

    // Reset modal when closed
    $('#memo_generate').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        currentEditMemoId = null;
        setModalMode('generate'); // Reset to default mode
    });

    @if(old('memo_form_submit') && $errors->any())
    setModalMode('generate');
    const memoModalEl = document.getElementById('memo_generate');
    if (memoModalEl) {
        const memoModal = new bootstrap.Modal(memoModalEl);
        memoModal.show();
    }
    syncMemoChoicesById('memo_type_master_pk');
    syncMemoChoicesById('venue');
    // Re-fetch the Template options for the previously-selected course/memo type
    // so the previously-chosen template is re-selected instead of reverting to
    // "Select Template" (its <option> only exists once templates are (re)loaded).
    loadMemoTemplates(
        @json(old('course_master_pk')),
        @json(old('memo_notice_template_pk')),
        @json(old('memo_type_master_pk'))
    );
    @endif
});
</script>
@endpush

@push('scripts')
<script>
$(function () {
    // ── DataTable sorting for #mnmTable (reusable so AJAX filtering can re-init) ──
    window.reinitMnmTable = function () {
        if ($.fn.DataTable.isDataTable('#mnmTable')) {
            $('#mnmTable').DataTable().destroy();
        }
        if ($('#mnmTable tbody tr td[colspan]').length === 0) {
            $('#mnmTable').DataTable({
                paging: false,
                searching: false,
                ordering: true,
                info: false,
                columnDefs: [
                    { orderable: false, targets: [0, 10] }
                ]
            });
        }
    };
    window.reinitMnmTable();
});
</script>
@endpush

@push('scripts')
<script>
$(function () {
    // ── AJAX filter/search: swap only the table container, no full page reload ──
    function applyMnmFiltersAjax() {
        var form = document.getElementById('filterForm');
        var listContainer = document.getElementById('mnmListContainer');
        if (!form || !listContainer) { $('#filterForm').get(0).submit(); return; }
        var params = new URLSearchParams(new FormData(form)).toString();
        var url = "{{ route('memo.notice.management.index') }}" + (params ? '?' + params : '');
        listContainer.style.opacity = '0.5';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newList = doc.querySelector('#mnmListContainer');
                if (newList) { listContainer.innerHTML = newList.innerHTML; }
                window.history.replaceState({}, '', url);

                // The Download links are static server-rendered hrefs from page load —
                // without this, they'd keep pointing at whatever filters were active
                // on that initial load (e.g. today-only) even after AJAX-applying
                // different filters, silently exporting the wrong/empty dataset.
                var downloadLink = document.getElementById('mnmDownloadLink');
                if (downloadLink) {
                    downloadLink.href = "{{ route('memo.notice.management.export_csv') }}" + (params ? '?' + params : '');
                }
                var downloadPdfLink = document.getElementById('mnmDownloadPdfLink');
                if (downloadPdfLink) {
                    downloadPdfLink.href = "{{ route('memo.notice.management.export_pdf') }}" + (params ? '?' + params : '');
                }
            })
            .catch(function () { alert('Failed to apply filters'); })
            .finally(function () {
                listContainer.style.opacity = '1';
                if (typeof window.reinitMnmTable === 'function') { window.reinitMnmTable(); }
            });
    }
    window.applyMnmFiltersAjax = applyMnmFiltersAjax;

    // Any native form submit (e.g. Enter key) → AJAX instead of full reload.
    $('#filterForm').on('submit', function (e) {
        e.preventDefault();
        applyMnmFiltersAjax();
    });

    // ── Time Period presets → from/to dates, then submit ──
    function mnmFmt(d) { return d.toISOString().split('T')[0]; }
    $('#mnmTimePeriod').on('change', function () {
        var v = $(this).val();
        var today = new Date();
        if (v === 'custom') {
            $('#from_date, #to_date').removeClass('d-none');
            return; // wait for the user to pick dates (their change submits the form)
        }
        var from = '', to = mnmFmt(today);
        if (v === 'today') {
            from = mnmFmt(today);
        } else if (v === 'week') {
            var ws = new Date(today); ws.setDate(today.getDate() - today.getDay()); from = mnmFmt(ws);
        } else if (v === 'month') {
            from = mnmFmt(new Date(today.getFullYear(), today.getMonth(), 1));
        }
        $('#from_date').val(from);
        $('#to_date').val(to);
        applyMnmFiltersAjax();
    });

    // ── Search: toggle, search-as-you-type (debounced), Enter, clear ──
    $('#mnmSearchToggle').on('click', function () {
        var $wrap = $('#mnmSearchWrap');
        $wrap.toggleClass('d-none');
        if (!$wrap.hasClass('d-none')) { $('#search').trigger('focus'); }
    });

    var mnmSearchTimer = null;
    $('#search').on('input', function () {
        $('#mnmSearchClear').toggle(this.value.length > 0);
        clearTimeout(mnmSearchTimer);
        // Debounced AJAX search: only the table container reloads, not the page.
        mnmSearchTimer = setTimeout(function () { applyMnmFiltersAjax(); }, 500);
    });
    $('#search').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); clearTimeout(mnmSearchTimer); applyMnmFiltersAjax(); }
    });
    $('#mnmSearchClear').on('click', function () {
        var $s = $('#search');
        $(this).hide();
        clearTimeout(mnmSearchTimer);
        if ($s.val() === '') { $s.trigger('focus'); return; }
        $s.val('');
        applyMnmFiltersAjax();
    });

    // After a search reload, put the cursor back in the search box (at the end)
    // so typing continues uninterrupted despite the full-page submit.
    (function restoreSearchFocus() {
        var el = document.getElementById('search');
        var wrap = document.getElementById('mnmSearchWrap');
        if (el && wrap && !wrap.classList.contains('d-none') && el.value) {
            el.focus();
            var v = el.value; el.value = ''; el.value = v; // move caret to end
        }
    })();

    // ── Column Visibility modal (static, server-paginated table) ──
    var mnmLabels = ['S. No.', 'Program Name', 'Participant Name', 'Type', 'Session Date', 'Topic', 'Conclusion Type', 'Conclusion Remark', 'Marks Deduction', 'Created Date', 'Status', 'Action'];
    var $mnmGrid = $('#mnmColumnGrid');
    mnmLabels.forEach(function (label, i) {
        var id = 'mnmCol' + i;
        $mnmGrid.append(
            '<label class="sn-colvis-chip" for="' + id + '" title="' + label + '">' +
            '<input type="checkbox" class="form-check-input mnm-col-toggle" id="' + id + '" data-col="' + i + '" checked> ' +
            '<span>' + label + '</span></label>'
        );
    });
    $mnmGrid.on('change', '.mnm-col-toggle', function () {
        var nth = parseInt($(this).data('col'), 10) + 1;
        var show = this.checked;
        $('#mnmTable tr').each(function () {
            $(this).children(':nth-child(' + nth + ')').toggle(show);
        });
    });

    // Guarantee a full page reload when switching tabs
    $(document).on('click', '.js-nav-tab', function (e) {
        if ($(this).hasClass('active')) { return; }
        var href = this.getAttribute('href');
        if (href) { e.preventDefault(); window.location.assign(href); }
    });
});
</script>
@endpush

@push('scripts')
<script>
/* ── Add Notice modal (dual-listbox + template preview) ── */
$(function () {
    var routeSessions  = "{{ route('memo.notice.management.getSessionsByCourse') }}";
    var routeVenues    = "{{ route('memo.notice.management.getVenuesBySession') }}";
    var routeTtDetails = "{{ route('memo.notice.management.getTimetableDetailsBySessionVenue') }}";
    var routeStudents  = "{{ route('memo.notice.management.getStudentAttendanceBytopic') }}";
    var routeTpl       = "{{ route('memo.notice.management.getTemplateByCourse') }}";
    var routeTpl2      = "{{ route('memo.notice.management.getTemplatesByType') }}";
    var csrf           = "{{ csrf_token() }}";
    var todayStr       = "{{ date('Y-m-d') }}";

    function makeItem(s) {
        var label = s.display_name
            + (s.generated_OT_code ? ' (' + s.generated_OT_code + ')' : '')
            + (s.attendance_label ? ' — ' + s.attendance_label : '');
        var $badge = s.attendance_label
            ? $('<span class="ms-1 badge ' + (s.attendance_label === 'Late' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') + '" style="font-size:0.7em;">').text(s.attendance_label)
            : null;
        var $text = $('<span>').text(s.display_name
            + (s.generated_OT_code ? ' (' + s.generated_OT_code + ')' : ''));
        var $item = $('<label class="an-item">')
            .attr('data-pk', s.pk)
            .attr('data-search', label.toLowerCase())
            .append($('<input type="checkbox" class="form-check-input an-check">'))
            .append($text);
        if ($badge) $item.append($badge);
        return $item;
    }

    function refreshPlaceholders() {
        $('#anAvailable, #anSelected').each(function () {
            var $l = $(this);
            $l.children('.an-empty').remove();
            if (!$l.children('.an-item').length) {
                var msg = this.id === 'anSelected'
                    ? 'No students selected.'
                    : ($('#anTopicId').val() ? 'No Late/Absent OTs found.' : 'Select course, session and venue.');
                $l.append($('<div class="an-empty text-muted">').text(msg));
            }
        });
    }

    function resetStudents() {
        $('#anAvailable, #anSelected').children('.an-item').remove();
        $('.an-select-all').prop('checked', false);
        refreshPlaceholders();
    }

    function clearSubjectTopicDetails() {
        $('#anSubjectName, #anTopicName').val('');
        $('#anSubjectId, #anTopicId, #anFacultyPk').val('');
    }

    // Course + Date → Sessions (only sessions that actually occur on the selected date)
    function loadSessions() {
        var course = $('#anCourse').val();
        var date = $('#anDate').val();
        $('#anVenue').html('<option value="">Select Venue</option>').prop('disabled', true);
        clearSubjectTopicDetails();
        resetStudents();
        if (!course || !date) {
            $('#anSession').html('<option value="">Select Session</option>').prop('disabled', true);
            return;
        }
        $.get(routeSessions, { course_id: course, date: date }).done(function (html) {
            $('#anSession').html(html).prop('disabled', false);
        }).fail(function () {
            $('#anSession').html('<option value="">Error loading sessions</option>').prop('disabled', true);
        });
    }

    // Session → Venues
    function loadVenues() {
        var course = $('#anCourse').val();
        var date = $('#anDate').val();
        var session = $('#anSession').val();
        clearSubjectTopicDetails();
        resetStudents();
        if (!session) {
            $('#anVenue').html('<option value="">Select Venue</option>').prop('disabled', true);
            return;
        }
        $.get(routeVenues, { course_id: course, date: date, session_pk: session }).done(function (html) {
            $('#anVenue').html(html).prop('disabled', false);
        }).fail(function () {
            $('#anVenue').html('<option value="">Error loading venues</option>').prop('disabled', true);
        });
    }

    // Venue → auto-fill subject/topic/faculty + load that session's Late/Absent OTs
    function loadTimetableDetailsAndStudents() {
        var course = $('#anCourse').val();
        var date = $('#anDate').val();
        var session = $('#anSession').val();
        var venue = $('#anVenue').val();
        clearSubjectTopicDetails();
        resetStudents();
        if (!venue) return;

        $.get(routeTtDetails, { course_id: course, date: date, session_pk: session, venue_id: venue }).done(function (res) {
            if (!res) {
                $('#anSubjectName, #anTopicName').val('No session found for this selection.');
                return;
            }
            $('#anSubjectName').val(res.subject_name || '');
            $('#anTopicName').val(res.subject_topic || '');
            $('#anSubjectId').val(res.subject_master_pk || '');
            $('#anTopicId').val(res.topic_id || '');
            $('#anFacultyPk').val(res.faculty_master || '');

            var topic = res.topic_id;
            if (!topic) return;
            $('#anAvailable').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>');
            $.ajax({ url: routeStudents, type: 'POST', data: { topic_id: topic, _token: csrf } })
                .done(function (res) {
                    $('#anAvailable').empty();
                    var list = (res && res.students) || [];
                    list.forEach(function (s) { $('#anAvailable').append(makeItem(s)); });
                    refreshPlaceholders();
                }).fail(function () {
                    $('#anAvailable').html('<div class="an-empty text-danger">Failed to load students.</div>');
                });
        }).fail(function () {
            $('#anSubjectName, #anTopicName').val('Failed to load session details.');
        });
    }

    var anTemplateCache = [];   // Notice templates for the selected course

    function renderTemplatePreview(tpl) {
        if (tpl && (tpl.content || tpl.director_name)) {
            $('#anTplCourse').text($('#anCourse option:selected').text());
            $('#anTplType').text('SHOW CAUSE NOTICE');
            $('#anTplDate').text((new Date()).toLocaleDateString('en-GB'));
            $('#anTplContent').html(tpl.content || '');
            $('#anTplDirector').text(tpl.director_name || '');
            $('#anTplDesig').text(tpl.director_designation || '');
            $('#anPreviewNone').hide();
            $('#anPreviewWrap').show();
        } else {
            $('#anPreviewWrap').hide();
            $('#anPreviewNone').show();
        }
    }

    // Populate the Notice template picker for a course; preview reflects the selected one.
    function loadTemplate(courseId) {
        var $sel = $('#anTemplate');
        anTemplateCache = [];
        $sel.html('<option value="">Select Course first</option>');
        if (!courseId) { $('#anPreviewWrap, #anPreviewNone').hide(); return; }
        $.get(routeTpl2, { course_id: courseId, type: 'Notice' }).done(function (list) {
            anTemplateCache = list || [];
            if (anTemplateCache.length) {
                $sel.empty();
                anTemplateCache.forEach(function (tpl) {
                    $sel.append($('<option>').val(tpl.pk).text(tpl.title));
                });
                $sel.val(String(anTemplateCache[0].pk));
                renderTemplatePreview(anTemplateCache[0]);
            } else {
                $sel.html('<option value="">No template configured</option>');
                renderTemplatePreview(null);
            }
        }).fail(function () {
            $sel.html('<option value="">Failed to load templates</option>');
            renderTemplatePreview(null);
        });
    }

    $('#anTemplate').on('change', function () {
        var pk = String($(this).val() || '');
        var tpl = anTemplateCache.find(function (t) { return String(t.pk) === pk; });
        renderTemplatePreview(tpl || null);
    });

    // Default the date when the modal opens.
    $('#addNoticeModal').on('show.bs.modal', function () {
        if (!$('#anDate').val()) $('#anDate').val(todayStr);
    });

    // Clear everything on close (Cancel, backdrop click, Esc, or after submit)
    // so the next "Add Notice" doesn't reopen with stale course/session/students/template.
    function resetAddNoticeForm() {
        var form = document.getElementById('addNoticeForm');
        if (form) form.reset();
        $('#anSession').html('<option value="">Select Session</option>').prop('disabled', true);
        $('#anVenue').html('<option value="">Select Venue</option>').prop('disabled', true);
        clearSubjectTopicDetails();
        resetStudents();
        anTemplateCache = [];
        $('#anTemplate').html('<option value="">Select Course first</option>');
        $('#anPreviewWrap, #anPreviewNone').hide();
        $('#anHiddenInputs').empty();
    }
    $('#addNoticeModal').on('hidden.bs.modal', resetAddNoticeForm);

    $('#anCourse').on('change', function () { loadSessions(); loadTemplate($(this).val()); });
    $('#anDate').on('change', loadSessions);
    $('#anSession').on('change', loadVenues);
    $('#anVenue').on('change', loadTimetableDetailsAndStudents);

    // Move buttons
    function moveItems(from, to, all) {
        var $items = $(from).children('.an-item');
        if (!all) $items = $items.filter(function () { return $(this).find('.an-check').prop('checked'); });
        $items.each(function () {
            $(this).find('.an-check').prop('checked', false);
            $(this).show();
            $(to).append(this);
        });
        $('.an-select-all').prop('checked', false);
        refreshPlaceholders();
    }
    $(document).on('click', '.an-move-btn', function () {
        var m = $(this).data('move');
        if (m === 'all-right') moveItems('#anAvailable', '#anSelected', true);
        else if (m === 'right') moveItems('#anAvailable', '#anSelected', false);
        else if (m === 'left') moveItems('#anSelected', '#anAvailable', false);
        else if (m === 'all-left') moveItems('#anSelected', '#anAvailable', true);
    });

    // Select-all (only affects currently visible items in that panel)
    $(document).on('change', '.an-select-all', function () {
        var panel = $(this).data('panel');
        var checked = this.checked;
        $('#' + panel).children('.an-item:visible').find('.an-check').prop('checked', checked);
    });

    // Per-panel search
    $(document).on('input', '.an-filter', function () {
        var q = this.value.toLowerCase();
        $('#' + $(this).data('target')).children('.an-item').each(function () {
            $(this).toggle(($(this).attr('data-search') || '').indexOf(q) > -1);
        });
    });

    // Submit → collect Selected students into hidden inputs
    $('#addNoticeForm').on('submit', function (e) {
        var pks = $('#anSelected').children('.an-item').map(function () { return $(this).data('pk'); }).get();
        if (!pks.length) {
            e.preventDefault();
            alert('Please move at least one student into "Selected Students".');
            return;
        }
        var $h = $('#anHiddenInputs').empty();
        pks.forEach(function (pk) {
            $h.append($('<input type="hidden" name="selected_student_list[]">').val(pk));
        });
    });
});
</script>
@endpush

@push('scripts')
<script>
/* ── Delete a notice/memo row (admins only) ── */
$(function () {
    $(document).on('click', '.mnm-delete-record', function () {
        var id = $(this).data('id');
        var type = ($(this).data('type') || 'notice').toString();
        if (!id) { return; }

        var label = type === 'memo' ? 'memo' : 'notice';
        Swal.fire({
            title: 'Delete this ' + label + '?',
            text: 'This will permanently remove the ' + label + ' and its conversation. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            var url = "{{ route('memo.notice.management.destroy', ['id' => '__ID__', 'type' => '__TYPE__']) }}"
                .replace('__ID__', id).replace('__TYPE__', type);

            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                success: function (res) {
                    toastr.success(res.message || 'Deleted successfully.');
                    setTimeout(function () { window.location.reload(); }, 600);
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete.');
                }
            });
        });
    });
});
</script>
@endpush

@push('scripts')
<script>
/* ── Edit Notice (template only) ── */
$(function () {
    var editNoticeTemplateCache = [];

    function renderEditNoticePreview(tpl, ctx) {
        if (tpl && (tpl.content || tpl.director_name)) {
            $('#editNoticeTplCourse').text(ctx.course_name || '');
            $('#editNoticeTplType').text('SHOW CAUSE NOTICE');
            $('#editNoticeTplDate').text(ctx.date_ ? new Date(ctx.date_).toLocaleDateString('en-GB') : '');
            $('#editNoticeInfoDate').text(ctx.date_ ? new Date(ctx.date_).toLocaleDateString('en-GB') : '');
            $('#editNoticeInfoTopic').text(ctx.topic_name || '');
            $('#editNoticeInfoVenue').text(ctx.venue_name || '');
            $('#editNoticeInfoSession').text(ctx.session_name || '');
            $('#editNoticeTplContent').html(tpl.content || '');
            $('#editNoticeTplDirector').text(tpl.director_name || '');
            $('#editNoticeTplDesig').text(tpl.director_designation || '');
            $('#editNoticePreviewNone').hide();
            $('#editNoticePreviewWrap').show();
        } else {
            $('#editNoticePreviewWrap').hide();
            $('#editNoticePreviewNone').show();
        }
    }

    var editNoticeCtx = {};

    $(document).on('click', '.edit-notice-btn', function () {
        var noticeId = $(this).data('notice-id');
        if (!noticeId) { return; }

        $('#editNoticeForm')[0].reset();
        $('#editNoticePk').val('');
        $('#editNoticeTemplate').html('<option value="">Loading…</option>').prop('disabled', true);
        $('#editNoticePreviewWrap, #editNoticePreviewNone').hide();
        $('#editNoticeSaveBtn').prop('disabled', true);

        $.get("{{ rtrim(route('memo.notice.management.editNotice', ''), '/') }}/" + noticeId)
            .done(function (data) {
                editNoticeCtx = data;
                $('#editNoticePk').val(data.pk);

                $.get("{{ route('memo.notice.management.getTemplatesByType') }}", { course_id: data.course_master_pk, type: 'Notice' })
                    .done(function (res) {
                        editNoticeTemplateCache = res || [];
                        var $sel = $('#editNoticeTemplate').prop('disabled', false).empty()
                            .append('<option value="">Select Template</option>');
                        editNoticeTemplateCache.forEach(function (tpl) {
                            $sel.append($('<option>').val(tpl.pk).text(tpl.title));
                        });
                        var preselect = data.memo_notice_template_pk || (editNoticeTemplateCache.length === 1 ? editNoticeTemplateCache[0].pk : '');
                        if (preselect) { $sel.val(String(preselect)); }
                        var chosen = editNoticeTemplateCache.find(function (t) { return String(t.pk) === String($sel.val()); });
                        renderEditNoticePreview(chosen || null, editNoticeCtx);
                        $('#editNoticeSaveBtn').prop('disabled', false);
                    });
            })
            .fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load notice data.');
            });
    });

    $('#editNoticeTemplate').on('change', function () {
        var pk = String($(this).val() || '');
        var tpl = editNoticeTemplateCache.find(function (t) { return String(t.pk) === pk; });
        renderEditNoticePreview(tpl || null, editNoticeCtx);
    });

    $('#editNoticeForm').on('submit', function (e) {
        e.preventDefault();
        var id = $('#editNoticePk').val();
        var templatePk = $('#editNoticeTemplate').val();
        if (!templatePk) { toastr.error('Please select a template.'); return; }

        var $btn = $('#editNoticeSaveBtn').prop('disabled', true);
        $.ajax({
            url: "{{ rtrim(route('memo.notice.management.update_notice_template', ''), '/') }}/" + id,
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                memo_notice_template_pk: templatePk
            },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || 'Notice updated successfully.');
                    $('#editNoticeModal').modal('hide');
                    setTimeout(function () { window.location.reload(); }, 600);
                } else {
                    toastr.error(res.message || 'Update failed.');
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Update failed.');
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush

@endsection
