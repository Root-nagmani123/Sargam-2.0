@extends('admin.layouts.master')

@section('title', 'Memo Management')

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

.mnm-page .mnm-status--open {
    background: #ecfdf3;
    color: #027a48;
}

.mnm-page .mnm-status--close {
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
    <x-breadcrum title="Notice / Memo Management">
        <a href="{{ route('ot.notice.memo.view') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-3 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;">history</i>
            Memo/Notice All activity
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-3 p-md-4">

            <form method="GET" action="{{ route('memo.notice.management.user') }}" id="memoFilterForm">
                {{-- Preserve the active sort when a filter is applied. --}}
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction') }}">
                <div class="mnm-filter-bar mb-3">
                    <span class="mnm-filter-label">Filters</span>

                    <select class="form-select" id="typeFilter" name="type" aria-label="Filter by type">
                        <option value="">Type</option>
                        <option value="Notice" {{ request('type') === 'Notice' ? 'selected' : '' }}>Notice</option>
                        <option value="Memo" {{ request('type') === 'Memo' ? 'selected' : '' }}>Memo</option>
                    </select>

                    <select class="form-select" id="statusFilter" name="status" aria-label="Filter by status">
                        <option value="">Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Open</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Close</option>
                    </select>

                    <a href="{{ route('memo.notice.management.user') }}" class="mnm-reset">Reset Filters</a>

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
                                placeholder="Search participant / topic..." value="{{ request('search') }}"
                                autocomplete="off" style="padding-right:1.9rem;">
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
                    $url     = route('memo.notice.management.user') . '?' . http_build_query($params);
                    $thClass = 'mnm-sortable' . ($active ? ($curDir === 'asc' ? ' mnm-sort-asc' : ' mnm-sort-desc') : '');
                    return '<th class="' . $thClass . '"><a href="' . e($url) . '" class="mnm-sort-link">'
                        . e($label) . '</a></th>';
                };
            @endphp

            <div class="table-responsive">
                <table id="mnmTable" class="table align-middle mb-0 text-nowrap">
                    <thead>
                        <tr class="align-middle">
                            <th>S. No.</th>
                            {!! $sortHead('participant', 'Participant') !!}
                            {!! $sortHead('type', 'Type') !!}
                            {!! $sortHead('date', 'Date') !!}
                            {!! $sortHead('topic', 'Topic') !!}
                            <th>Conversation</th>
                            {!! $sortHead('status', 'Status') !!}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($memos as $index => $memo)
                        <tr>
                            <td class="text-muted">{{ $memos->firstItem() + $index }}</td>
                            <td class="fw-semibold">{{ $memo->student_name ?? 'N/A' }}</td>
                            <td><span class="mnm-type-badge">{{ $memo->type_notice_memo ?? 'N/A' }}</span></td>
                            <td>{{ $memo->date_ ?? 'N/A' }}</td>
                            <td>{{ $memo->topic_name ?? 'N/A' }}</td>
                            <td>
                                <div class="d-flex gap-2 flex-nowrap">
                                    <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->notice_id, 'type' => 'notice']) }}"
                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                        title="Notice Conversation">
                                        <i class="bi bi-chat-dots"></i> Notice
                                    </a>

                                    <a href="javascript:void(0)" class="view-conversation position-relative"
                                        data-bs-target="#chatOffcanvas"
                                        @if ($memo->notice_memo == '1') data-type="notice" data-id="{{ $memo->notice_id }}"
                                        @elseif($memo->notice_memo == '2') data-type="memo" data-id="{{ $memo->memo_id ?? $memo->notice_id }}"
                                        @else data-type="notice" data-id="{{ $memo->notice_id }}"
                                        @endif
                                        data-topic="{{ $memo->topic_name }}" data-bs-toggle="tooltip" title="Reply">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">reply</i>
                                        @if (($memo->chat_unread ?? 0) > 0)
                                        <span class="position-absolute translate-middle badge rounded-pill bg-danger chat-unread-badge"
                                            style="top: 2px; left: 100%; font-size: 9px;">
                                            {{ $memo->chat_unread > 99 ? '99+' : $memo->chat_unread }}
                                            <span class="visually-hidden">unread messages</span>
                                        </span>
                                        @endif
                                    </a>

                                    @if($memo->type_notice_memo == 'Memo')
                                    <a href="{{ route('memo.notice.management.conversation_student', ['id' => $memo->memo_id, 'type' => 'memo']) }}"
                                        class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                        title="Memo Conversation">
                                        <i class="bi bi-chat-square-text"></i> Memo
                                    </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($memo->status == 1)
                                <span class="mnm-status mnm-status--open">Open</span>
                                @else
                                <span class="mnm-status mnm-status--close">Close</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <span class="fw-medium">No records found</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination (design-system footer: numbered pills + "Showing [N] of M items") --}}
            @php
                $memoPerPage = (int) request('per_page', 10);
                if (!in_array($memoPerPage, [10, 25, 50, 100, 200], true)) $memoPerPage = 10;
            @endphp
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                <div class="programme-dt-pagination">
                    {{ $memos->links('vendor.pagination.custom') }}
                </div>
                <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div class="dataTables_length">
                        <label class="mb-0">Showing
                            <select id="memoPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                @foreach([10, 25, 50, 100, 200] as $pp)
                                <option value="{{ $pp }}" {{ $memoPerPage === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="dataTables_info">of {{ number_format($memos->total()) }} items</div>
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
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Conversation offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="chatOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="conversationTopic">Conversation</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <input type="hidden" id="userType" value="">
    <div class="offcanvas-body d-flex flex-column">
        <div class="chat-body flex-grow-1 mb-3" id="chatBody">
            <p class="text-muted text-center">Loading conversation...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function () {

    /* ── Searchable filter selects (Choices.js) ── */
    ['typeFilter', 'statusFilter'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el || typeof window.Choices === 'undefined' || el.dataset.choicesInitialized === 'true') return;
        new Choices(el, {
            shouldSort: false, searchEnabled: true, itemSelectText: '', allowHTML: false,
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
        el.dataset.choicesInitialized = 'true';
    });

    /* ── Filters submit the GET form ── */
    var $form = $('#memoFilterForm');
    $('#typeFilter, #statusFilter').on('change', function () { $form.trigger('submit'); });

    /* ── Per-page → reload page 1 with the new size + current filters ── */
    $('#memoPerPage').on('change', function () {
        var params = new URLSearchParams(new FormData($form[0]));
        params.set('per_page', this.value);
        window.location.href = "{{ route('memo.notice.management.user') }}" + '?' + params.toString();
    });

    /* ── Search: toggle, debounced submit, clear ── */
    $('#memoSearchToggle').on('click', function () {
        var $wrap = $('#memoSearchWrap');
        $wrap.toggleClass('d-none');
        if (!$wrap.hasClass('d-none')) { $('#memoSearch').trigger('focus'); }
    });
    var memoSearchTimer = null;
    $('#memoSearch').on('input', function () {
        $('#memoSearchClear').toggle(this.value.length > 0);
        clearTimeout(memoSearchTimer);
        memoSearchTimer = setTimeout(function () { $form.trigger('submit'); }, 400);
    });
    $('#memoSearch').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); clearTimeout(memoSearchTimer); $form.trigger('submit'); }
    });
    $('#memoSearchClear').on('click', function () {
        $('#memoSearch').val(''); $(this).hide();
        clearTimeout(memoSearchTimer); $form.trigger('submit');
    });

    /* ── Column visibility (built from the header cells; toggles nth-child) ── */
    var $grid = $('#memoColumnGrid');
    $('#mnmTable thead th').each(function (i) {
        var title = $(this).text().replace(/\s+/g, ' ').trim() || ('Column ' + (i + 1));
        var id = 'memocol' + i;
        var $cell = $('<div class="col-12 col-sm-6"></div>');
        var $label = $('<label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', id);
        var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', id).prop('checked', true);
        $cb.on('change', function () {
            var nth = i + 1, show = this.checked;
            $('#mnmTable tr').each(function () {
                $(this).children(':nth-child(' + nth + ')').toggle(show);
            });
        });
        $label.append($cb).append($('<span></span>').text(title));
        $cell.append($label);
        $grid.append($cell);
    });
});
</script>

<script>
/* ── Conversation offcanvas + real-time polling ── */
$(document).ready(function() {
    let pollInterval = null;
    const chatOffcanvasEl = document.getElementById('chatOffcanvas');

    function loadConversation(memoId, type, userType) {
        return fetch('/admin/memo-notice-management/get_conversation_model/'
            + encodeURIComponent(memoId) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(userType),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        ).then(r => r.text());
    }

    function applyConversationHtml(html, memoId, scrollToBottom) {
        const chatBody = document.getElementById('chatBody');
        const w = chatBody && chatBody.querySelector('.chat-wrapper');
        if (w && w.dataset.memoId !== String(memoId)) return;

        const scroll = document.querySelector('#conversationScroll');
        const wasAtBottom = !scroll || (scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 60);

        $('#chatBody').html(html); // jQuery html() executes scripts on first load

        const newScroll = document.querySelector('#conversationScroll');
        if (newScroll && (scrollToBottom || wasAtBottom)) {
            newScroll.scrollTop = newScroll.scrollHeight;
        }
    }

    function mergeNewMessages(html, memoId) {
        const chatBody = document.getElementById('chatBody');
        const w = chatBody && chatBody.querySelector('.chat-wrapper');
        if (!w || w.dataset.memoId !== String(memoId)) return;

        const scroll = document.getElementById('conversationScroll');
        if (!scroll) return;
        const wasAtBottom = scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 60;

        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const newScroll = parsed.getElementById('conversationScroll');
        if (!newScroll) return;

        scroll.innerHTML = newScroll.innerHTML;
        if (wasAtBottom) scroll.scrollTop = scroll.scrollHeight;
    }

    $(document).on('click', '.view-conversation', function() {
        let memoId = $(this).data('id');
        let topic = $(this).data('topic');
        let type = $(this).data('type');
        $('#userType').val(type);
        let user_type = 'student';

        $(this).find('.chat-unread-badge').remove();

        $('#conversationTopic').text(topic);
        $('#chatBody').html('<p class="text-muted text-center">Loading conversation...</p>');

        loadConversation(memoId, type, user_type)
            .then(html => applyConversationHtml(html, memoId, true))
            .catch(() => {
                $('#chatBody').html('<p class="text-danger text-center">Failed to load conversation.</p>');
            });

        let chatOffcanvas = new bootstrap.Offcanvas(chatOffcanvasEl);
        chatOffcanvas.show();
    });

    chatOffcanvasEl.addEventListener('shown.bs.offcanvas', function() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(function() {
            const w = document.querySelector('#chatBody .chat-wrapper');
            if (!w) return;
            const memoId   = w.dataset.memoId;
            const type     = w.dataset.type;
            const userType = w.dataset.userType;
            if (!memoId) return;
            loadConversation(memoId, type, userType)
                .then(html => mergeNewMessages(html, memoId))
                .catch(() => {});
        }, 5000);
    });

    chatOffcanvasEl.addEventListener('hide.bs.offcanvas', function() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    });
});
</script>
@endpush
