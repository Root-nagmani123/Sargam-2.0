@extends('admin.layouts.master')

@section('title', 'Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('admin_assets/css/dual-listbox.css') }}">
<link rel="stylesheet" href="{{ asset('css/memo-notice-management-admin.css') }}?v={{ @filemtime(public_path('css/memo-notice-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $mnmFromDate = $fromDateFilter ?: \Carbon\Carbon::today()->toDateString();
    $mnmToDate = $toDateFilter ?: \Carbon\Carbon::today()->toDateString();
@endphp

<div class="container-fluid mnm-master-page">
    <x-breadcrum title="Notice / Memo Management">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm"
            data-bs-toggle="modal"
            data-bs-target="#mnmAddNoticeModal"
            id="mnmOpenAddNoticeBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Notice</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card mnm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('memo.notice.management.index') }}" id="filterForm">
                <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar mnm-dt-toolbar w-100">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label flex-shrink-0">Filters</span>

                        <div class="programme-dt-filter-select flex-shrink-0">
                            <label for="program_name" class="visually-hidden">Course Name</label>
                            <select class="form-select" id="program_name" name="program_name" aria-label="Program Name">
                                <option value="">Program Name</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->pk }}" {{ (string)$programNameFilter == (string)$course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select flex-shrink-0">
                            <label for="type" class="visually-hidden">Type</label>
                            <select class="form-select" id="type" name="type" aria-label="Type">
                                <option value="">Type</option>
                                <option value="1" {{ $typeFilter == '1' ? 'selected' : '' }}>Notice</option>
                                <option value="0" {{ $typeFilter == '0' ? 'selected' : '' }}>Memo</option>
                            </select>
                        </div>

                        <div class="programme-dt-filter-select flex-shrink-0">
                            <label for="status" class="visually-hidden">Status</label>
                            <select class="form-select" id="status" name="status" aria-label="Status">
                                <option value="">Status</option>
                                <option value="1" {{ $statusFilter == '1' ? 'selected' : '' }}>Open</option>
                                <option value="0" {{ $statusFilter == '0' ? 'selected' : '' }}>Close</option>
                            </select>
                        </div>

                        <input type="hidden" id="from_date" name="from_date" value="{{ $mnmFromDate }}">
                        <input type="hidden" id="to_date" name="to_date" value="{{ $mnmToDate }}">

                        <div class="programme-dt-filter-select mnm-time-period-filter mnm-time-period-range d-none d-lg-block position-relative flex-shrink-0">
                            <label for="mnm_time_period_picker" class="visually-hidden">Time Period</label>
                            <input type="text"
                                id="mnm_time_period_picker"
                                class="form-control mnm-time-period-input"
                                placeholder="Time Period"
                                value=""
                                readonly
                                autocomplete="off"
                                aria-label="Time period">
                        </div>

                        <div class="mnm-time-period-mobile d-flex d-lg-none flex-wrap gap-2 w-100">
                            <div class="programme-dt-filter-select mnm-date-mobile-wrap flex-fill">
                                <label for="mnm_from_date_mobile" class="visually-hidden">From Date</label>
                                <input type="date"
                                    id="mnm_from_date_mobile"
                                    class="form-control mnm-date-mobile"
                                    value="{{ $mnmFromDate }}"
                                    aria-label="From date">
                            </div>
                            <div class="programme-dt-filter-select mnm-date-mobile-wrap flex-fill">
                                <label for="mnm_to_date_mobile" class="visually-hidden">To Date</label>
                                <input type="date"
                                    id="mnm_to_date_mobile"
                                    class="form-control mnm-date-mobile"
                                    value="{{ $mnmToDate }}"
                                    aria-label="To date">
                            </div>
                        </div>

                        <a href="{{ route('memo.notice.management.index') }}" class="btn programme-dt-btn-reset flex-shrink-0">
                            Reset Filters
                        </a>
                    </div>

                    <div class="mnm-table-search-slot ms-xl-auto flex-shrink-0">
                        <div class="dropdown">
                            <button type="button"
                                class="btn mnm-search-trigger"
                                id="mnmSearchTrigger"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Search records">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-3 mnm-search-menu">
                                <label for="search" class="form-label small text-secondary mb-2">Search</label>
                                <div class="input-group">
                                    <input type="search"
                                        class="form-control mnm-search-input shadow-none"
                                        id="search"
                                        name="search"
                                        placeholder="Search..."
                                        value="{{ $searchFilter }}"
                                        autocomplete="off"
                                        aria-label="Search notices and memos">
                                    <button type="submit" class="btn btn-primary" id="mnmSearchSubmit" aria-label="Apply search">
                                        <i class="bi bi-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="programme-dt-panel mnm-dt-panel">
                <div class="table-responsive mnm-dt-scroll">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table mnm-dt-table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-nowrap">S. No.</th>
                            <th scope="col" class="mnm-col-program">Program Name</th>
                            <th scope="col">Participant Name</th>
                            <th scope="col" class="text-nowrap">Type</th>
                            <th scope="col" class="text-nowrap">Session Date</th>
                            <th scope="col" class="mnm-col-topic">Topic</th>
                            <th scope="col" class="mnm-col-conversation">Conversation</th>
                            <th scope="col">Response</th>
                            <th scope="col">Conclusion Type</th>
                            <th scope="col">Discussion Name</th>
                            <th scope="col">Conclusion Remark</th>
                            <th scope="col" class="text-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($memos->isEmpty())
                        <tr class="align-middle">
                            <td colspan="12" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No records found
                            </td>
                        </tr>
                        @else
                        @foreach ($memos as $index => $memo)
                        <tr>
                            <!-- Serial -->
                            <td class="sno">{{ $memos->firstItem() + $index }}</td>

                            <!-- Program Name -->
                            <td class="fw-medium mnm-col-program">{{ $memo->course_name ?? 'N/A' }}</td>

                            <!-- Student -->
                            <td class="s_name fw-medium">{{ $memo->student_name }}</td>

                            <!-- Type -->
                            <td class="type">
                                @if ($memo->notice_memo == '1')
                                    Notice
                                @elseif ($memo->notice_memo == '2')
                                    Memo
                                @else
                                    Other
                                @endif
                            </td>

                            <!-- Session Date -->
                            <td class="1">
                                @if(isset($memo->session_date) && $memo->session_date)
                                    {{ date('d-m-Y', strtotime($memo->session_date)) }}
                                @else
                                    {{ date('d-m-Y', strtotime($memo->date_)) }}
                                @endif
                            </td>

                            <!-- Topic -->
                            <td class="mnm-col-topic">{{ $memo->topic_name }}</td>
@php
$noticeKey = $memo->student_pk . '_' . $memo->course_master_pk;
@endphp
                            <!-- Conversations -->
                            <td class="conversation mnm-col-conversation">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @if($memo->type_notice_memo == 'Notice' || $memo->type_notice_memo == 'Memo')
                                    @if($memo->notice_id)
                                    <a href="{{ route('memo.notice.management.conversation', ['id' => $memo->notice_id, 'type' => 'notice']) }}"
                                        class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                        <i class="bi bi-chat-dots me-1"></i> Notice
                                    </a>
                                    @else
                                    <span class="text-muted small d-flex align-items-center">
                                        <i class="bi bi-chat-slash me-1"></i> No Conversation
                                    </span>
                                    @endif
                                    @endif
                                    @if(isset($noticeCount[$noticeKey]) && ($noticeCount[$noticeKey] >= 2) && $memo->type_notice_memo != 'Memo')
                                            <span class="position-relative d-inline-block ms-2">
                                                <!-- Bell Icon -->
                                                <i class="bi bi-bell-fill text-warning blink" 
                                                title="{{ $noticeCount[$noticeKey] }} notices sent, please send memo"></i>

                                                <!-- Count Badge -->
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                    {{ $noticeCount[$noticeKey] }}
                                                </span>
                                            </span>
                                        @endif

                                    @php 
                                    $role = session()->get('role_name');
                                    @endphp

                                    <!-- Admin Offcanvas -->
                                     @if($memo->type_notice_memo == 'Notice')
                                    <a
                                        class="text-primary d-inline-flex align-items-center gap-1 text-decoration-none view-conversation"
                                        data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas" data-type="notice" 
                                        data-id="{{ $memo->notice_id }}" data-topic="{{ $memo->topic_name }}">
                                        <i class="material-icons material-symbols-rounded">chat</i> {{ $role }}
                                    </a>
                                    @elseif($memo->type_notice_memo == 'Memo')
                                    <a
                                        class="text-primary d-inline-flex align-items-center gap-1 text-decoration-none view-conversation"
                                        data-bs-toggle="offcanvas" data-bs-target="#chatOffcanvas" data-type="memo"
                                        data-id="{{ $memo->memo_id }}" data-topic="{{ $memo->topic_name }}">
                                        <i class="material-icons material-symbols-rounded">chat</i> {{ $role }}
                                    </a>
                                    @else
                                    <span class="text-muted small d-flex align-items-center">
                                        <i class="bi bi-chat-slash me-1"></i> No Conversation
                                    </span>
                                    @endif

                                    @if($memo->type_notice_memo == 'Notice')
                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                        Memo
                                    </button>
                                    @elseif($memo->type_notice_memo == 'Memo' &&
                                    in_array($memo->communication_status,[1,2]))
                                    <a href="{{ route('memo.notice.management.conversation', ['id' => $memo->memo_id, 'type' => 'memo']) }}"
                                        class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                        <i class="bi bi-chat-square-text me-1"></i> Memo
                                    </a>
                                    @endif
                                </div>
                            </td>


                            <!-- Response (Generate Memo) -->
                            <td class="response">
                                @if($memo->type_notice_memo == 'Notice')
                                @if($memo->status == 1)
                                <button type="button" class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="Memo generation not available yet">
                                    <i class="bi bi-file-earmark-lock me-1"></i> Generate Memo
                                </button>
                                @elseif($memo->status == 2)
                                <a href="javascript:void(0)" class="btn btn-sm btn-success generate-memo-btn"
                                    data-id="{{ $memo->memo_notice_id }}" data-bs-toggle="modal"
                                    data-bs-target="#memo_generate">
                                    <i class="bi bi-file-earmark-plus me-1"></i> Generate Memo
                                </a>
                                @endif
                                @endif
                            </td>

                            <!-- Conclusion -->
                            <td class="conclusion_type">
                                @if($memo->type_notice_memo == 'Memo')
                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-secondary preview-memo-btn"
                                    data-notice-id="{{ $memo->notice_id }}" data-memo-id="{{ $memo->memo_id }}" data-bs-toggle="modal"
                                    data-bs-target="#memo_generate">
                                    Memo Generated
                                </a>
                                @endif
                            </td>

                            <!-- Discussion Name -->
                            <td class="discussion_name">
                                @if($memo->type_notice_memo == 'Memo' && $memo->communication_status == 2)
                                {{ $memo->discussion_name }}
                                @endif
                            </td>

                            <!-- Conclusion Remark -->
                            <td>
                                @if($memo->type_notice_memo == 'Memo' && $memo->communication_status == 2)
                                {{ $memo->conclusion_remark }}
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="status sticky-status">
                                @if ($memo->status == 1)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i> Open
                                </span>
                                @else
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="bi bi-x-circle me-1"></i> Close
                                </span>
                                @endif
                            </td>

                        </tr>
                        @endforeach
                        @endif
                    </tbody>

                </table>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="mnm-pagination-nav">
                        {{ $memos->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count mnm-dt-count mb-0">
                        Showing {{ $memos->firstItem() ?? 0 }}
                        to {{ $memos->lastItem() ?? 0 }}
                        of {{ $memos->total() }} items
                    </div>
                </div>
            </div>
        </div>
    </div>
   
    <!-- Enhanced Offcanvas with GIGW Guidelines -->
    <div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="chatOffcanvas" aria-labelledby="conversationTopic" role="dialog">
        <div class="offcanvas-header">
            <div class="d-flex flex-column w-100">
                <h4 class="offcanvas-title mb-2" id="conversationTopic">
                    <i class="material-symbols-rounded me-2" style="vertical-align: middle; font-size: 24px;">forum</i>
                    Conversation
                </h4>
                <h5 id="type_side_menu">Loading...</h5>
            </div>
            <button type="button" 
                    class="btn-close" 
                    data-bs-dismiss="offcanvas" 
                    aria-label="Close conversation panel"
                    title="Close">
            </button>
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
            <div class="modal-content border-0 shadow-sm rounded-3">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-semibold text-dark" id="memo_generateLabel">Generate Memo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <form action="{{ route('memo.notice.management.store_memo_status') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-6 mb-3">
                                <label for="course_master_name" class="form-label">Course</label>

                                <input type="text" id="course_master_name" class="form-control"
                                    name="course_master_name" readonly>
                                <input type="hidden" id="course_master_pk" name="course_master_pk">
                                <input type="hidden" id="student_notice_status_pk" name="student_notice_status_pk">
                                <input type="hidden" id="memo_count" name="memo_count">
                                <input type="hidden" id="student_pk" name="student_pk">
                                @error('course_master_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="date_memo_notice" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date_memo_notice" name="date_memo_notice"
                                    required readonly>
                                @error('date_memo_notice')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="subject_master_id" class="form-label">Subject <span
                                        class="text-danger">*</span></label>

                                <input type="text" id="subject_master_id" class="form-control" name="subject_master_id"
                                    readonly>

                                @error('subject_master_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="topic_id" class="form-label">Topic</label>

                                <input type="text" id="topic_id" class="form-control" name="topic_id" readonly>

                                @error('topic_id')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>



                            <div class="col-12 col-md-6 mb-3">
                                <label for="session_name" class="form-label">Session</label>
                                <input type="text" id="class_session_master_pk" class="form-control" readonly>
                                @error('session_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label for="faculty_name" class="form-label">Faculty Name</label>
                                <input type="text" id="faculty_name" class="form-control" readonly>
                                @error('faculty_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="student_name" class="form-label">Student Name</label>
                                <input type="text" id="student_name" class="form-control" readonly>
                                @error('student_name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="memo_type" class="form-label">Memo Type</label>
                                <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-select">
                                    <option value="">Select Memo Type</option>
                                    @foreach ($memo_master as $master)
                                    <option value="{{ $master->pk }}">{{ $master->memo_type_name }}</option>
                                    @endforeach
                                </select>
                                @error('memo_type_master_pk')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label for="memo_number" class="form-label">Memo Number</label>
                                <input type="text" id="memo_number" name="memo_number" class="form-control" readonly>
                                @error('memo_number')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>


                            <div class="col-12 col-md-6 mb-3">
                                <label for="venue" class="form-label">Venue</label>
                                <select name="venue" id="venue" class="form-select">
                                    <option value="">Select Venue</option>
                                    @foreach ($venue as $v)
                                    <option value="{{ $v->venue_id }}">{{ $v->venue_name }}</option>
                                    @endforeach
                                </select>
                                @error('venue')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="memo_date" class="form-label">Date</label>
                                <input type="date" id="memo_date" class="form-control">
                                @error('memo_date')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3 mb-3">
                                <label for="meeting_time" class="form-label">Meeting Time</label>
                                <input type="time" id="meeting_time" name="meeting_time" class="form-control">
                                @error('meeting_time')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label for="textarea" class="form-label">Message (If Any)</label>
                                <textarea class="form-control" id="textarea" name="Remark" rows="3"
                                    placeholder="Enter remarks..."></textarea>
                                @error('Remark')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                </div>
                <div class="modal-footer border-0 bg-transparent px-4 pb-4 pt-2 gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-2 px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary rounded-2 px-4 fw-semibold">Save</button>
                </div>
                </form>
            </div>
        </div>

    </div>
    <!-- Memo generation end -->

    @include('admin.courseAttendanceNoticeMap.partials.add_notice_modal')
</div>
@push('scripts')
@include('admin.courseAttendanceNoticeMap.partials.add_notice_scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    var $ = jQuery;

$(function() {
    function formatYmd(date) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function initMnmDesktopTimePeriod() {
        if (window.mnmTimePeriodPicker || typeof flatpickr === 'undefined') {
            return;
        }
        if (!document.getElementById('mnm_time_period_picker')) {
            return;
        }
        var mnmFromVal = $('#from_date').val();
        var mnmToVal = $('#to_date').val();
        window.mnmTimePeriodPicker = flatpickr('#mnm_time_period_picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd.m.Y',
            defaultDate: [mnmFromVal, mnmToVal],
            showMonths: window.innerWidth >= 1200 ? 2 : 1,
            locale: { rangeSeparator: ' to ' },
            onChange: function (selectedDates) {
                if (selectedDates[0]) {
                    $('#from_date').val(formatYmd(selectedDates[0]));
                }
                if (selectedDates.length > 1 && selectedDates[1]) {
                    $('#to_date').val(formatYmd(selectedDates[1])).trigger('change');
                } else if (selectedDates[0]) {
                    $('#to_date').val(formatYmd(selectedDates[0]));
                }
            }
        });
    }

    function destroyMnmDesktopTimePeriod() {
        if (window.mnmTimePeriodPicker) {
            window.mnmTimePeriodPicker.destroy();
            window.mnmTimePeriodPicker = null;
        }
    }

    function syncMnmMobileDatesFromHidden() {
        $('#mnm_from_date_mobile').val($('#from_date').val());
        $('#mnm_to_date_mobile').val($('#to_date').val());
    }

    function applyMnmTimePeriodMode() {
        if (window.matchMedia('(min-width: 992px)').matches) {
            initMnmDesktopTimePeriod();
            if (window.mnmTimePeriodPicker) {
                window.mnmTimePeriodPicker.setDate([$('#from_date').val(), $('#to_date').val()], false);
            }
        } else {
            destroyMnmDesktopTimePeriod();
            syncMnmMobileDatesFromHidden();
        }
    }

    $('#mnm_from_date_mobile, #mnm_to_date_mobile').on('change', function () {
        $('#from_date').val($('#mnm_from_date_mobile').val());
        $('#to_date').val($('#mnm_to_date_mobile').val());
        $('#from_date').trigger('change');
    });

    applyMnmTimePeriodMode();
    var mnmDesktopMq = window.matchMedia('(min-width: 992px)');
    if (typeof mnmDesktopMq.addEventListener === 'function') {
        mnmDesktopMq.addEventListener('change', applyMnmTimePeriodMode);
    } else if (typeof mnmDesktopMq.addListener === 'function') {
        mnmDesktopMq.addListener(applyMnmTimePeriodMode);
    }

    $('#search').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#filterForm').submit();
        }
    });

    const filterChoicesIds = ['program_name', 'type', 'status'];
    const modalChoicesIds = ['memo_type_master_pk', 'venue'];
    const memoChoicesMap = new Map();
    let modalChoicesReady = false;

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

    function initFilterChoices() {
        filterChoicesIds.forEach(function(id) {
            createChoicesInstance(document.getElementById(id));
        });
    }

    function initModalChoices() {
        if (modalChoicesReady) {
            return;
        }
        modalChoicesIds.forEach(function(id) {
            createChoicesInstance(document.getElementById(id));
        });
        modalChoicesReady = true;
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

    initFilterChoices();

    $('#memo_generate').on('show.bs.modal', function() {
        initModalChoices();
    });

    $('.view-conversation').on('click', function() {
        var memoId = $(this).data('id');
        var topic = $(this).data('topic');
        var type = $(this).data('type');
        $('#userType').val(type);
        var user_type = 'admin';

        $('#conversationTopic').text('Topic: ' + topic);
        $('#type_side_menu').text(type);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        $.ajax({
            url: '/admin/memo-notice-management/get_conversation_model/' + memoId + '/' + type + '/' + user_type,
            type: 'GET',
            success: function(res) {
                $('#chatBody').html(res);
            },
            error: function() {
                $('#chatBody').html('<p class="text-danger text-center">Failed to load conversation.</p>');
            }
        });

        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('chatOffcanvas')).show();
    });

    // Filter form submission on change
    $('#program_name, #type, #status, #from_date, #to_date').on('change', function() {
        $('#filterForm').submit();
    });
    
    // Handle Generate Memo button (editable mode)
    $('.generate-memo-btn').on('click', function() {
        let memoId = $(this).data('id');
        setModalMode('generate');

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

                // Add more if needed
            },
            error: function() {
                alert('Something went wrong!');
            }
        });
    });

    // Handle Preview Memo button (read-only mode)
    $('.preview-memo-btn').on('click', function() {
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
            },
            error: function(xhr, status, error) {
                console.error('Error fetching memo data:', error);
                console.error('Response:', xhr.responseText);
                alert('Failed to load memo data. Please try again.');
            }
        });
    });

    // Function to set modal mode (generate or preview)
    function setModalMode(mode) {
        const modal = $('#memo_generate');
        const form = modal.find('form');
        // Use more specific selector for save button
        const saveButton = modal.find('.modal-footer').find('button[type="submit"]');
        const modalTitle = $('#memo_generateLabel');

        if (mode === 'preview') {
            // Preview mode: make all fields read-only
            form.find('input[type="text"], input[type="date"], input[type="time"], textarea').prop('readonly', true);
            form.find('select').prop('disabled', true);
            // Hide save button in preview mode
            saveButton.hide();
            modalTitle.text('Preview Memo');
        } else {
            // Generate mode: enable editable fields
            form.find('input, textarea').prop('readonly', false);
            form.find('select').prop('disabled', false);
            // Keep readonly fields as readonly
            $('#course_master_name, #date_memo_notice, #subject_master_id, #topic_id, #class_session_master_pk, #faculty_name, #student_name, #memo_number').prop('readonly', true);
            // Keep non-editable selects disabled
            form.find('select').not('#memo_type_master_pk, #venue').prop('disabled', true);
            // Show save button in generate mode
            saveButton.show();
            modalTitle.text('Generate Memo');
        }

        syncMemoChoicesById('memo_type_master_pk');
        syncMemoChoicesById('venue');
    }

    // Reset modal when closed
    $('#memo_generate').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        setModalMode('generate');
    });
});
});
</script>
@endpush

@endsection