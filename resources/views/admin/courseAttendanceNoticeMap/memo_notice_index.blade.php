{{-- resources/views/admin/courseAttendanceNoticeMap/memo_notice_index.blade.php --}}

@extends('admin.layouts.master')

@section('title', 'Memo/Notice Templates')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<link rel="stylesheet"
    href="{{ asset('css/notice-memo-discipline.css') }}?v={{ @filemtime(public_path('css/notice-memo-discipline.css')) ?: time() }}">
<style>
.mnm-page .mnm-type-badge {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.35rem 0.75rem;
    border-radius: 5px;
    background: #eff8ff;
    color: #175cd3;
}

.mnm-page .mnm-status--active {
    background: #ecfdf3;
    color: #027a48;
}

.mnm-page .mnm-status--inactive {
    background: #fef3f2;
    color: #b42318;
}

.mnm-page table#mnmTable thead th a.mnm-sort-link {
    color: inherit;
    text-decoration: none;
    white-space: nowrap;
}

.mnm-page table#mnmTable thead th a.mnm-sort-link:hover {
    color: #004a93;
}

/* Stacked ▲▼ sort indicator (matches the DataTables look used app-wide). */
.mnm-page table#mnmTable thead th.mnm-sortable {
    position: relative;
    padding-right: 20px;
}

.mnm-page table#mnmTable thead th.mnm-sortable::before,
.mnm-page table#mnmTable thead th.mnm-sortable::after {
    position: absolute;
    display: block;
    right: 6px;
    font-size: 0.8em;
    line-height: 9px;
    color: #667085;
    opacity: 0.45;
}

.mnm-page table#mnmTable thead th.mnm-sortable::before {
    content: "\25B2";
    bottom: 50%;
}

.mnm-page table#mnmTable thead th.mnm-sortable::after {
    content: "\25BC";
    top: 50%;
}

.mnm-page table#mnmTable thead th.mnm-sort-asc::before,
.mnm-page table#mnmTable thead th.mnm-sort-desc::after {
    opacity: 1;
    color: #004a93;
}
</style>
@endpush

@section('setup_content')
<div class="container-fluid mnm-page">
    <x-breadcrum title="Memo/Notice Templates" :showBack="false" buttonText="Create New Template"
        :buttonUrl="route('admin.memo-notice.create')" buttonIcon="add"
        buttonClass="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm" />

    <x-session_message />
    <div id="status-msg" class="mb-3"></div>

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-3 p-md-4">

            <form method="GET" action="{{ route('admin.memo-notice.index') }}" id="memoFilterForm">
                {{-- Preserve the active sort when a filter is applied. --}}
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction') }}">
                <div class="mnm-filter-bar mb-3">
                    <span class="mnm-filter-label">Filters</span>

                    <select class="form-select" id="courseFilter" name="course_master_pk" aria-label="Filter by course">
                        <option value="">Program Name</option>
                        @foreach ($courses as $course)
                        <option value="{{ $course->pk }}"
                            {{ (string) request('course_master_pk') === (string) $course->pk ? 'selected' : '' }}>
                            {{ $course->course_name }}
                        </option>
                        @endforeach
                    </select>

                    <select class="form-select" id="statusFilter" name="active_inactive" aria-label="Filter by status">
                        <option value="">Status</option>
                        <option value="1" {{ request('active_inactive') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('active_inactive') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <a href="{{ route('admin.memo-notice.index') }}" class="mnm-reset">Reset Filters</a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="mnm-icon-btn" data-bs-toggle="modal"
                            data-bs-target="#memoColumnModal">
                            <i class="bi bi-layout-three-columns"></i> Columns
                        </button>
                        <button type="button" class="mnm-icon-btn" id="memoSearchToggle" aria-label="Search"><i
                                class="bi bi-search"></i></button>
                        <div class="mnm-search-wrap {{ request('search') ? '' : 'd-none' }}" id="memoSearchWrap"
                            style="position:relative;">
                            <input type="text" class="mnm-search-input" id="memoSearch" name="search"
                                placeholder="Search..." value="{{ request('search') }}" autocomplete="off"
                                style="padding-right:1.9rem;">
                            <button type="button" id="memoSearchClear" aria-label="Clear search" title="Clear"
                                style="position:absolute;top:50%;right:.35rem;transform:translateY(-50%);border:0;background:transparent;color:#94a3b8;line-height:1;padding:.15rem;{{ request('search') ? '' : 'display:none;' }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            @php
                $curSort = (string) request('sort', '');
                $curDir  = strtolower((string) request('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
                $sortHead = function ($key, $label) use ($curSort, $curDir) {
                    $active  = $curSort === $key;
                    $nextDir = ($active && $curDir === 'asc') ? 'desc' : 'asc';
                    $params  = array_merge(request()->except('page'), ['sort' => $key, 'direction' => $nextDir]);
                    $url     = route('admin.memo-notice.index') . '?' . http_build_query($params);
                    $thClass = 'mnm-sortable' . ($active ? ($curDir === 'asc' ? ' mnm-sort-asc' : ' mnm-sort-desc') : '');
                    return '<th class="' . $thClass . '"><a href="' . e($url) . '" class="mnm-sort-link">'
                        . e($label) . '</a></th>';
                };
            @endphp
            <div class="table-responsive">
                <table id="mnmTable" class="table align-middle mb-0 text-nowrap">
                    <thead>
                        <tr class="align-middle">
                            <th>#</th>
                            {!! $sortHead('program', 'Program Name') !!}
                            {!! $sortHead('title', 'Title') !!}
                            {!! $sortHead('type', 'Type') !!}
                            {!! $sortHead('status', 'Status') !!}
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $index => $template)
                        <tr>
                            <td class="text-muted">{{ $templates->firstItem() + $index }}</td>
                            <td class="fw-semibold">{{ $template->course->course_name ?? 'General' }}</td>
                            <td>{{ $template->title }}</td>
                            <td><span class="mnm-type-badge">{{ $template->memo_notice_type ?: 'N/A' }}</span></td>
                            <td>
                                @if($template->active_inactive == 1)
                                <span class="mnm-status mnm-status--active js-status-badge">Active</span>
                                @else
                                <span class="mnm-status mnm-status--inactive js-status-badge">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="mnm-actions justify-content-center align-items-center">

                                    <button type="button" class="mnm-action view-template-btn"
                                        data-id="{{ $template->pk }}" title="View template">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <a href="{{ route('admin.memo-notice.edit', $template->pk) }}" class="mnm-action"
                                        title="Edit template">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <div class="form-check form-switch mb-0 d-inline-flex align-items-center"
                                        title="Activate / Deactivate">
                                        <input class="form-check-input status-toggle-data" type="checkbox" role="switch"
                                            data-id="{{ $template->pk }}"
                                            data-course="{{ $template->course_master_pk }}"
                                            data-type="{{ $template->memo_notice_type }}"
                                            aria-label="Toggle template status"
                                            {{ $template->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                    @if($template->active_inactive == 0)
                                    <form action="{{ route('admin.memo-notice.destroy', $template->pk) }}" method="POST"
                                        class="d-inline programme-delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="mnm-action" style="color:#f04438;"
                                            title="Delete template">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                    @else
                                    <span class="mnm-action disabled" title="Deactivate first to delete">
                                        <i class="bi bi-trash3"></i>
                                    </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <span class="fw-medium">No templates found. Create your first template!</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                <div class="text-muted small mb-0">
                    Showing {{ $templates->firstItem() ?? 0 }}
                    to {{ $templates->lastItem() ?? 0 }}
                    of {{ $templates->total() }} items
                </div>
                <div class="ms-auto">
                    {{ $templates->links('vendor.pagination.custom') }}
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Column Visibility Modal --}}
<div class="modal fade" id="memoColumnModal" tabindex="-1" aria-labelledby="memoColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="memoColumnModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-2" id="memoColumnGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4"
                    data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- View Template Modal --}}
<div class="modal fade" id="memoViewModal" tabindex="-1" aria-labelledby="memoViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="memoViewModalLabel">Template Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div id="memoViewBody"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {

    /* ── Searchable filter selects (Choices.js) ── */
    var memoChoicesIds = ['courseFilter', 'statusFilter'];
    if (typeof window.Choices !== 'undefined') {
        memoChoicesIds.forEach(function(id) {
            var el = document.getElementById(id);
            if (!el || el.dataset.choicesInitialized === 'true') return;
            new Choices(el, {
                shouldSort: false,
                searchEnabled: true,
                searchResultLimit: 50,
                itemSelectText: '',
                allowHTML: false,
                classNames: {
                    containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
                    input: ['choices__input', 'form-control', 'form-control-sm', 'border-0',
                        'shadow-none', 'my-1'
                    ],
                    inputCloned: ['choices__input--cloned'],
                    listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0',
                        'shadow-sm', 'w-100'
                    ],
                    item: ['choices__item', 'dropdown-item', 'rounded-0'],
                    itemSelectable: ['choices__item--selectable'],
                    itemDisabled: ['choices__item--disabled', 'disabled'],
                    itemChoice: ['choices__item--choice'],
                    placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                    highlightedState: ['is-highlighted', 'active'],
                    notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small',
                        'py-2'
                    ]
                }
            });
            el.dataset.choicesInitialized = 'true';
        });
    }

    /* ── Filters submit the GET form (full-page nav keeps filters across pages) ── */
    var $form = $('#memoFilterForm');
    $('#courseFilter, #statusFilter').on('change', function() {
        $form.trigger('submit');
    });

    /* ── Search: toggle, debounced live filtering, clear ── */
    $('#memoSearchToggle').on('click', function() {
        var $wrap = $('#memoSearchWrap');
        $wrap.toggleClass('d-none');
        if (!$wrap.hasClass('d-none')) {
            $('#memoSearch').trigger('focus');
        }
    });
    var memoSearchTimer = null;
    $('#memoSearch').on('input', function() {
        $('#memoSearchClear').toggle(this.value.length > 0);
        clearTimeout(memoSearchTimer);
        memoSearchTimer = setTimeout(function() {
            $form.trigger('submit');
        }, 400);
    });
    $('#memoSearch').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(memoSearchTimer);
            $form.trigger('submit');
        }
    });
    $('#memoSearchClear').on('click', function() {
        var $s = $('#memoSearch');
        $s.val('');
        $(this).hide();
        clearTimeout(memoSearchTimer);
        $form.trigger('submit');
    });

    /* ── Column visibility (built from the header cells; toggles nth-child) ── */
    var $grid = $('#memoColumnGrid');
    $('#mnmTable thead th').each(function(i) {
        var title = $(this).text().replace(/\s+/g, ' ').trim() || ('Column ' + (i + 1));
        var id = 'memocol' + i;
        var $cell = $('<div class="col-12 col-sm-6"></div>');
        var $label = $(
            '<label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>'
            ).attr('for', id);
        var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', id).prop(
            'checked', true);
        $cb.on('change', function() {
            var nth = i + 1,
                show = this.checked;
            $('#mnmTable tr').each(function() {
                $(this).children(':nth-child(' + nth + ')').toggle(show);
            });
        });
        $label.append($cb).append($('<span></span>').text(title));
        $cell.append($label);
        $grid.append($cell);
    });

    /* ── View template (read-only modal, loaded via AJAX) ── */
    function memoEsc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }
    var memoViewModal = null;
    $(document).on('click', '.view-template-btn', function() {
        var id = $(this).data('id');
        if (!memoViewModal) { memoViewModal = new bootstrap.Modal(document.getElementById('memoViewModal')); }
        $('#memoViewBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        memoViewModal.show();

        $.get("/admin/memo-notice/" + id + "/view").done(function(res) {
            if (!res || !res.success) {
                $('#memoViewBody').html('<div class="alert alert-danger mb-0">Failed to load template.</div>');
                return;
            }
            var t = res.template;
            var statusMod = t.status === 'Active' ? 'active' : 'inactive';
            var sig = t.signature_url
                ? '<img src="' + t.signature_url + '" alt="Signature" style="max-height:60px;display:block;margin-left:auto;margin-bottom:4px;">'
                : '';
            var dir = (t.director_name || t.director_designation)
                ? '<div class="text-end mt-3">' + sig +
                    '<div class="fw-semibold">' + memoEsc(t.director_name) + '</div>' +
                    '<div class="text-muted small">' + memoEsc(t.director_designation) + '</div></div>'
                : '';
            var html = '' +
                '<div class="row g-3 mb-3">' +
                    '<div class="col-md-6"><div class="text-muted small">Program</div><div class="fw-semibold">' + memoEsc(t.course_name) + '</div></div>' +
                    '<div class="col-md-3"><div class="text-muted small">Type</div><div><span class="mnm-type-badge">' + memoEsc(t.type || 'N/A') + '</span></div></div>' +
                    '<div class="col-md-3"><div class="text-muted small">Status</div><div><span class="mnm-status mnm-status--' + statusMod + '">' + memoEsc(t.status) + '</span></div></div>' +
                    '<div class="col-12"><div class="text-muted small">Title</div><div class="fw-semibold">' + memoEsc(t.title) + '</div></div>' +
                '</div>' +
                '<div class="text-muted small mb-1">Content</div>' +
                '<div class="border rounded-3 p-3">' + (t.content || '<span class="text-muted">No content.</span>') + '</div>' +
                dir;
            $('#memoViewBody').html(html);
        }).fail(function() {
            $('#memoViewBody').html('<div class="alert alert-danger mb-0">Failed to load template.</div>');
        });
    });
});
</script>

<script>
/* ── Reflect a template's active/inactive state in its Status badge ── */
function memoUpdateBadge($checkbox, active) {
    var $badge = $checkbox.closest('tr').find('.js-status-badge');
    if (!$badge.length) return;
    $badge.removeClass('mnm-status--active mnm-status--inactive')
        .addClass(active ? 'mnm-status--active' : 'mnm-status--inactive')
        .text(active ? 'Active' : 'Inactive');
}

/* ── Status toggle: activate/deactivate a template (deactivates SAME course+type peers) ── */
$(document).on('change', '.status-toggle-data', function() {

    let checkbox = $(this);
    let id = checkbox.data('id');
    let newStatus = checkbox.is(':checked') ? 1 : 0;

    let courseId = checkbox.data('course');
    let type = checkbox.data('type'); // Memo / Notice

    let oldStatus = newStatus === 1 ? 0 : 1;

    Swal.fire({
        title: 'Are you sure?',
        text: newStatus == 1 ?
            "Do you want to activate this template?" : "Do you want to deactivate this template?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Continue',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (!result.isConfirmed) {
            checkbox.prop('checked', oldStatus == 1);
            return;
        }

        checkbox.prop('disabled', true);

        $.ajax({
            url: "/admin/memo-notice/" + id + "/status/" + newStatus,
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {

                if (res.status === "success") {

                    // Keep this row's Status badge in sync with the new state.
                    memoUpdateBadge(checkbox, newStatus === 1);

                    if (newStatus == 1) {
                        // Deactivate only SAME COURSE & SAME TYPE in UI
                        $('.status-toggle-data').each(function() {
                            let other = $(this);

                            if (
                                other.data('id') != id &&
                                other.data('course') == courseId &&
                                other.data('type') == type
                            ) {
                                other.prop('checked', false);
                                memoUpdateBadge(other, false);
                            }
                        });
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Status updated successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }

                checkbox.prop('disabled', false);
            },
            error: function() {

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                });

                checkbox.prop('disabled', false);
                checkbox.prop('checked', oldStatus == 1);
            }
        });

    });

});

/* ── Delete confirmation (inactive templates only) ── */
$(document).on('submit', '.programme-delete-form', function(e) {
    e.preventDefault();
    var form = this;
    Swal.fire({
        title: 'Delete template?',
        text: 'Are you sure you want to delete this template?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            HTMLFormElement.prototype.submit.call(form);
        }
    });
});
</script>
@endpush