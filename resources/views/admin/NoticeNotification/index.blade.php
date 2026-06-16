@extends('admin.layouts.master')

@section('title', 'Notice notification List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
.notice-title-cell {
    max-width: 260px;
}

.notice-title-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: default;
}

.custom-notice-tooltip {
    position: fixed;
    z-index: 1080;
    max-width: min(520px, calc(100vw - 24px));
    padding: 8px 10px;
    border-radius: 6px;
    background-color: rgba(28, 28, 28, 0.96);
    color: #fff;
    font-size: 0.8rem;
    line-height: 1.4;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
    pointer-events: none;
    opacity: 0;
    transform: translate3d(0, 0, 0);
    transition: opacity 0.12s ease-in-out;
}

.custom-notice-tooltip.is-visible {
    opacity: 1;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Notice notification List">
        <a href="{{ route('admin.notice.create') }}"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add Notice Notification</span>
        </a>
    </x-breadcrum>
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 js-auto-hide-alert" role="alert">
        <strong>There were some problems with your request.</strong>
        <ul class="mb-0 mt-2 ps-3">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3 js-auto-hide-alert" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div id="status-msg" class="mb-3"></div>
    <div class="card">

        <div class="card-body">
            <div class="bg-light rounded-3 p-3 mb-4">
                <form method="GET" action="{{ route('admin.notice.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-md">
                            <label class="form-label fw-semibold mb-1">Notice Type</label>
                            <select name="notice_type" class="form-select form-select-sm js-choice"
                                onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($types as $type)
                                <option value="{{ $type }}" {{ request('notice_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md">
                            <label class="form-label fw-semibold mb-1">Course</label>
                            <select name="course_id" class="form-select form-select-sm js-choice"
                                onchange="this.form.submit()">
                                <option value="">All</option>
                                @foreach($courses as $c)
                                <option value="{{ $c->id }}" {{ request('course_id') == $c->pk ? 'selected' : '' }}>
                                    {{ $c->course_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md">
                            <label class="form-label fw-semibold mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm js-choice"
                                onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="1" {{ request('status') == "1" ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') == "0" ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md">
                            <label class="form-label fw-semibold mb-1">Search</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control"
                                    value="{{ request('search') }}" placeholder="Title, type, course, creator...">
                                <button type="submit" class="btn btn-primary" aria-label="Search">
                                    <i class="material-icons material-symbols-rounded fs-6 lh-1"
                                        aria-hidden="true">search</i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12 col-md-auto d-flex align-items-end gap-2">
                            <a href="{{ route('admin.notice.index') }}"
                                class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                                Reset
                            </a>

                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                                    type="button" id="columnToggleBtn" data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside" aria-expanded="false">
                                    <span class="material-icons material-symbols-rounded fs-6 lh-1"
                                        aria-hidden="true">view_column</span>
                                    <span>Columns</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="columnToggleBtn">
                                    @php
                                    $toggleColumns = [
                                    'title' => 'Notice Title',
                                    'type' => 'Notice Type',
                                    'course' => 'Course Name',
                                    'created_by' => 'Created By',
                                    'created_date' => 'Created Date',
                                    'display_date' => 'Display Date',
                                    'expiry_date' => 'Expiry Date',
                                    'status' => 'Status',
                                    ];
                                    @endphp
                                    @foreach($toggleColumns as $key => $label)
                                    <li>
                                        <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
                                            <input type="checkbox" class="form-check-input m-0 js-col-toggle"
                                                data-col="{{ $key }}" checked>
                                            <span>{{ $label }}</span>
                                        </label>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 60px;">S.N.</th>
                            <th scope="col" class="col-title">Notice Title</th>
                            <th scope="col" class="col-type">Notice Type</th>
                            <th scope="col" class="col-course">Course Name</th>
                            <th scope="col" class="col-created_by">Created By</th>
                            <th scope="col" class="col-created_date">Created Date</th>
                            <th scope="col" class="col-display_date">Display Date</th>
                            <th scope="col" class="col-expiry_date">Expiry Date</th>
                            <th scope="col" class="text-center col-status">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($notices as $index => $n)
                        @php $encId = Crypt::encrypt($n->pk); @endphp

                        <tr>
                            <td class="fw-semibold">{{ $index + $notices->firstItem() }}</td>
                            <td class="fw-semibold col-title notice-title-cell">
                                <span class="notice-title-text js-custom-title-tooltip"
                                    data-full-title="{{ $n->notice_title }}">
                                    {{ $n->notice_title }}
                                </span>
                            </td>
                            <td class="col-type">
                                <span class="badge rounded-pill bg-info-subtle text-info text-capitalize">
                                    {{ $n->notice_type }}
                                </span>
                            </td>
                            <td class="col-course">{{ $n->course->course_name ?? 'N/A' }}</td>
                            <td class="col-created_by">{{ $n->user->first_name }} {{ $n->user->last_name }}</td>
                            <td class="col-created_date">{{ \Carbon\Carbon::parse($n->created_date)->format('d-m-Y') }}
                            </td>
                            <td class="col-display_date">{{ \Carbon\Carbon::parse($n->display_date)->format('d-m-Y') }}
                            </td>
                            <td class="col-expiry_date">{{ \Carbon\Carbon::parse($n->expiry_date)->format('d-m-Y') }}
                            </td>

                            <td class="text-center col-status">
                                <span
                                    class="badge rounded-1 js-notice-status-badge bg-{{ $n->active_inactive == 1 ? 'success-subtle text-success' : 'danger-subtle text-danger' }}"
                                    data-id="{{ $n->pk }}">
                                    {{ $n->active_inactive == 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center d-flex justify-content-center">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <a href="{{ route('admin.notice.edit', $encId) }}"
                                        class="btn btn-sm btn-outline-primary btn-transparent border-0 p-0" title="Edit"
                                        aria-label="Edit Notice">
                                        <span class="material-symbols-rounded fs-5">edit</span>
                                    </a>
                                    <div
                                        class="form-check form-switch d-inline-flex align-items-center justify-content-center">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="notices_notification" data-column="active_inactive"
                                            data-id="{{ $n->pk }}" {{ $n->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                    <div class="js-notice-delete-actions" data-id="{{ $n->pk }}">
                                        <form id="deleteForm{{ $encId }}"
                                            action="{{ route('admin.notice.destroy', $encId) }}" method="POST"
                                            class="d-inline js-notice-delete-enabled {{ $n->active_inactive == 0 ? '' : 'd-none' }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-transparent border-0 p-0"
                                                title="Delete" aria-label="Delete Notice"
                                                onclick="deleteConfirm('{{ $encId }}')">
                                                <span class="material-symbols-rounded fs-5">delete</span>
                                            </button>
                                        </form>

                                        <button
                                            class="btn btn-sm btn-outline-secondary btn-transparent border-0 p-0 js-notice-delete-disabled {{ $n->active_inactive == 1 ? '' : 'd-none' }}"
                                            disabled title="Delete Disabled">
                                            <span class="material-symbols-rounded fs-5">block</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $notices->firstItem() ?? 0 }}
                    to {{ $notices->lastItem() }}
                    of {{ $notices->total() }} items
                </div>

                <div>
                    {{ $notices->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
function autoHideAlert(alertEl) {
    if (!alertEl || alertEl.dataset.autoHideBound === 'true') {
        return;
    }

    alertEl.dataset.autoHideBound = 'true';

    setTimeout(function() {
        if (!document.body.contains(alertEl)) {
            return;
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            bootstrap.Alert.getOrCreateInstance(alertEl).close();
            return;
        }

        alertEl.classList.remove('show');
        alertEl.remove();
    }, 1000);
}

function initColumnToggle() {
    var toggles = document.querySelectorAll('.js-col-toggle');
    if (!toggles.length) {
        return;
    }

    var storageKey = 'noticeNotificationHiddenCols';
    var hidden = [];

    try {
        hidden = JSON.parse(localStorage.getItem(storageKey)) || [];
    } catch (e) {
        hidden = [];
    }

    function applyColumn(col, visible) {
        document.querySelectorAll('.col-' + col).forEach(function(cell) {
            cell.classList.toggle('d-none', !visible);
        });
    }

    function persist() {
        var hiddenCols = [];
        toggles.forEach(function(cb) {
            if (!cb.checked) {
                hiddenCols.push(cb.dataset.col);
            }
        });
        try {
            localStorage.setItem(storageKey, JSON.stringify(hiddenCols));
        } catch (e) {}
    }

    toggles.forEach(function(cb) {
        var col = cb.dataset.col;

        if (hidden.indexOf(col) !== -1) {
            cb.checked = false;
            applyColumn(col, false);
        }

        cb.addEventListener('change', function() {
            applyColumn(col, cb.checked);
            persist();
        });
    });
}

function initNoticeIndexPage() {
    document.querySelectorAll('.js-auto-hide-alert').forEach(autoHideAlert);

    initColumnToggle();
    initCustomNoticeTitleTooltip();

    var statusMsgBox = document.getElementById('status-msg');
    if (statusMsgBox && statusMsgBox.dataset.observingAutoHide !== 'true') {
        statusMsgBox.dataset.observingAutoHide = 'true';

        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (!(node instanceof Element)) {
                        return;
                    }

                    if (node.classList.contains('alert')) {
                        autoHideAlert(node);
                    }

                    node.querySelectorAll('.alert').forEach(autoHideAlert);
                });
            });
        });

        observer.observe(statusMsgBox, {
            childList: true,
            subtree: true
        });

        statusMsgBox.querySelectorAll('.alert').forEach(autoHideAlert);
    }

    if (window.jQuery && window.noticeStatusUiSyncBound !== true) {
        window.noticeStatusUiSyncBound = true;

        $(document).on('ajaxSuccess.noticeStatusUi', function(event, xhr, settings) {
            if (!settings || !settings.url || settings.url.indexOf('toggle-status') === -1) {
                return;
            }

            var tableName = '';
            var itemId = '';
            var statusValue = '';

            if (typeof settings.data === 'string') {
                var params = new URLSearchParams(settings.data);
                tableName = params.get('table') || '';
                itemId = params.get('id') || '';
                statusValue = params.get('status') || '';
            } else if (settings.data && typeof settings.data === 'object') {
                tableName = settings.data.table || '';
                itemId = settings.data.id || '';
                statusValue = settings.data.status;
            }

            if (tableName !== 'notices_notification' || !itemId) {
                return;
            }

            var isActive = String(statusValue) === '1';
            var $badge = $('.js-notice-status-badge[data-id="' + itemId + '"]');

            if (!$badge.length) {
                return;
            }

            $badge
                .removeClass('bg-success-subtle text-success bg-danger-subtle text-danger')
                .addClass(isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger')
                .text(isActive ? 'Active' : 'Inactive');

            var $actions = $('.js-notice-delete-actions[data-id="' + itemId + '"]');
            if ($actions.length) {
                $actions.find('.js-notice-delete-enabled').toggleClass('d-none', isActive);
                $actions.find('.js-notice-delete-disabled').toggleClass('d-none', !isActive);
            }
        });
    }

    if (typeof Choices === 'undefined') {
        return;
    }

    document.querySelectorAll('.js-choice').forEach(function(el) {
        if (el.dataset.choicesInitialized === 'true') {
            return;
        }

        new Choices(el, {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: '',
        });

        el.dataset.choicesInitialized = 'true';
    });
}

function initCustomNoticeTitleTooltip() {
    if (document.body.dataset.noticeTooltipBound === 'true') {
        return;
    }

    var tooltip = document.createElement('div');
    tooltip.className = 'custom-notice-tooltip';
    document.body.appendChild(tooltip);

    function moveTooltip(event) {
        var offsetX = 14;
        var offsetY = 16;
        var left = event.clientX + offsetX;
        var top = event.clientY + offsetY;
        var maxLeft = window.innerWidth - tooltip.offsetWidth - 10;
        var maxTop = window.innerHeight - tooltip.offsetHeight - 10;

        tooltip.style.left = Math.max(10, Math.min(left, maxLeft)) + 'px';
        tooltip.style.top = Math.max(10, Math.min(top, maxTop)) + 'px';
    }

    document.addEventListener('mouseover', function(event) {
        var target = event.target.closest('.js-custom-title-tooltip');
        if (!target) {
            return;
        }

        var fullTitle = target.getAttribute('data-full-title');
        if (!fullTitle) {
            return;
        }

        tooltip.textContent = fullTitle;
        tooltip.classList.add('is-visible');
        moveTooltip(event);
    });

    document.addEventListener('mousemove', function(event) {
        if (!tooltip.classList.contains('is-visible')) {
            return;
        }
        moveTooltip(event);
    });

    document.addEventListener('mouseout', function(event) {
        var related = event.relatedTarget;
        var leavingTarget = event.target.closest('.js-custom-title-tooltip');

        if (!leavingTarget) {
            return;
        }

        if (related && related.closest('.js-custom-title-tooltip')) {
            return;
        }

        tooltip.classList.remove('is-visible');
    });

    document.body.dataset.noticeTooltipBound = 'true';
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNoticeIndexPage);
} else {
    initNoticeIndexPage();
}
</script>
@endpush

@endsection