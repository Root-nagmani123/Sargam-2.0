{{-- Shared global search bar for Memo / Notice / Discipline / Direct Notice pages --}}
<div class="card mb-3" id="memoGlobalSearchCard">
    <div class="card-body py-2">
        <div class="position-relative">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       id="memoGlobalSearchInput"
                       class="form-control border-start-0 ps-0"
                       placeholder="Search across Memo, Notice, Discipline &amp; Direct Notice…"
                       autocomplete="off">
                <button class="btn btn-outline-secondary" type="button" id="memoGlobalSearchClear" style="display:none;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Dropdown results panel -->
            <div id="memoGlobalSearchResults"
                 class="position-absolute w-100 bg-white border rounded shadow-sm mt-1"
                 style="z-index:1055; display:none; max-height:420px; overflow-y:auto;">
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #memoGlobalSearchResults .gs-item {
        padding: 10px 14px;
        border-bottom: 1px solid #f0f0f0;
        cursor: default;
        transition: background .15s;
    }
    #memoGlobalSearchResults .gs-item:last-child { border-bottom: none; }
    #memoGlobalSearchResults .gs-item:hover { background: #f8f9fa; }
    #memoGlobalSearchResults .gs-badge {
        font-size: 0.7rem;
        padding: 2px 7px;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: .03em;
        white-space: nowrap;
    }
    .gs-badge-memo           { background:#dbeafe; color:#1d4ed8; }
    .gs-badge-notice         { background:#dcfce7; color:#15803d; }
    .gs-badge-direct_notice  { background:#fef9c3; color:#854d0e; }
    .gs-badge-discipline     { background:#fce7f3; color:#9d174d; }
    #memoGlobalSearchResults .gs-empty,
    #memoGlobalSearchResults .gs-loading {
        padding: 16px 14px;
        color: #6c757d;
        font-size: 0.9rem;
        text-align: center;
    }
    .gs-highlight { background: #fef08a; border-radius: 2px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var searchUrl = "{{ route('memo.notice.management.global_search') }}";
    var $input    = $('#memoGlobalSearchInput');
    var $results  = $('#memoGlobalSearchResults');
    var $clear    = $('#memoGlobalSearchClear');
    var timer     = null;

    function badgeClass(typeKey) {
        return 'gs-badge-' + typeKey;
    }

    function highlight(text, q) {
        if (!q || !text) return text || '';
        var escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return String(text).replace(new RegExp('(' + escaped + ')', 'gi'),
            '<mark class="gs-highlight">$1</mark>');
    }

    function statusLabel(status) {
        var map = {0: 'Pending', 1: 'Submitted', 2: 'Approved', 3: 'Final'};
        return map[status] !== undefined ? map[status] : status;
    }

    function renderResults(data, q) {
        if (!data.results || data.results.length === 0) {
            $results.html('<div class="gs-empty">No results found for "<strong>' +
                $('<span>').text(q).html() + '</strong>"</div>').show();
            return;
        }
        var html = '';
        data.results.forEach(function (r) {
            html += '<div class="gs-item">' +
                '<div class="d-flex align-items-center gap-2 mb-1">' +
                    '<span class="gs-badge ' + badgeClass(r.type_key) + '">' + r.type + '</span>' +
                    '<strong class="text-dark">' + highlight(r.student_name, q) + '</strong>' +
                    (r.ot_code ? '<span class="text-muted small">' + highlight(r.ot_code, q) + '</span>' : '') +
                '</div>' +
                '<div class="text-muted small">' +
                    highlight(r.course_name, q) +
                    (r.detail && r.detail !== '—' ? ' &mdash; ' + highlight(r.detail, q) : '') +
                '</div>' +
                '<div class="d-flex gap-3 mt-1 small text-secondary">' +
                    (r.date ? '<span><i class="bi bi-calendar3 me-1"></i>' + r.date + '</span>' : '') +
                    '<span><i class="bi bi-info-circle me-1"></i>' + statusLabel(r.status) + '</span>' +
                '</div>' +
            '</div>';
        });
        $results.html(html).show();
    }

    function doSearch(q) {
        $results.html('<div class="gs-loading"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Searching…</div>').show();
        $.getJSON(searchUrl, { q: q }, function (data) {
            renderResults(data, q);
        }).fail(function () {
            $results.html('<div class="gs-empty text-danger">Search failed. Please try again.</div>').show();
        });
    }

    $input.on('input', function () {
        var q = $.trim($(this).val());
        $clear.toggle(q.length > 0);
        clearTimeout(timer);
        if (q.length < 2) { $results.hide(); return; }
        timer = setTimeout(function () { doSearch(q); }, 320);
    });

    $clear.on('click', function () {
        $input.val('').trigger('input').focus();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#memoGlobalSearchCard').length) {
            $results.hide();
        }
    });

    $input.on('focus', function () {
        if ($.trim($(this).val()).length >= 2 && $results.children().length) {
            $results.show();
        }
    });
})();
</script>
@endpush
