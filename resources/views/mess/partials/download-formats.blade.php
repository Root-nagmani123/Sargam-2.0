{{--
    The Download menu for a Mess grid — CSV / Excel / PDF off one button.

    Structure and wording follow the app's existing Download control on
    admin/member/index.blade.php: a single button carrying `dropdown-toggle`, and
    a menu of "Download <format>" rows, each with its own file-type icon in the
    colour that format is conventionally shown in (green for the spreadsheet
    pair, red for PDF). The Mess pages keep their own `*-master-export-btn`
    class, which already paints the 40px blue-on-white pill that control uses.

    The export route has always accepted `csv` and a non-inline `pdf`; nothing on
    screen offered them, so they were unreachable features rather than missing
    ones. Print keeps its own button — it is a destination, not a file format.

    The menu carries no URL of its own. Each Mess page builds its export URL from
    the live grid (search term, status pill, filter form, Column-Visibility
    selection) and registers that builder under its Download button's id in
    `window.MessExport`; the shared handler below calls it. That keeps the
    guarantee the export layer rests on — the file holds exactly the rows and
    columns the screen was showing.

    @param string $toggleId  id of the page's Download button, which is also the
                             dropdown toggle and the key into window.MessExport.
--}}
@php
    $toggleId = $toggleId ?? '';
@endphp
<ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2 mess-export-menu"
    aria-labelledby="{{ $toggleId }}">
    <li>
        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2"
           href="#" data-mess-export="csv">
            <i class="bi bi-filetype-csv text-success" aria-hidden="true"></i>
            <span>Download CSV</span>
        </a>
    </li>
    <li>
        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2"
           href="#" data-mess-export="excel">
            <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i>
            <span>Download Excel</span>
        </a>
    </li>
    <li>
        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2"
           href="#" data-mess-export="pdf">
            <i class="bi bi-filetype-pdf text-danger" aria-hidden="true"></i>
            <span>Download PDF</span>
        </a>
    </li>
    <li><hr class="dropdown-divider"></li>
    <li>
        {{-- Every column of the report, ignoring whatever Column Visibility
             has hidden on screen. Same rows, same filters — the difference
             is width, which is why it sits apart from the three above. --}}
        <a class="dropdown-item d-flex align-items-center gap-2 mx-2 rounded-1 py-2"
           href="#" data-mess-export="excel" data-mess-all-columns="1">
            <i class="bi bi-database-down text-secondary" aria-hidden="true"></i>
            <span>Full Details (Excel)</span>
        </a>
    </li>
</ul>

@once
@push('styles')
<style>
    .mess-export-menu { min-width: 13rem; }

    /* The icons are the only colour in the row; the label stays body text so the
       menu does not read as three coloured links. */
    .mess-export-menu .dropdown-item i { font-size: 1.05rem; line-height: 1; }
    .mess-export-menu .dropdown-item:active i { color: inherit; }

    /* Bootstrap's caret is a bare triangle butted against the label. */
    .mess-export-toggle::after { margin-left: .1rem; vertical-align: .15em; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Each grid registers its own URL builder under its Download button's id —
    // see `window.MessExport[...] = buildUrl` in the page's export script.
    // Without a registered builder the menu stays inert rather than guessing a
    // URL that would ignore the grid's filters.
    window.MessExport = window.MessExport || {};

    document.addEventListener('click', function (e) {
        var item = e.target.closest('[data-mess-export]');
        if (!item) return;

        e.preventDefault();

        var group = item.closest('.dropdown, .btn-group');
        var toggle = group ? group.querySelector('[data-bs-toggle="dropdown"]') : null;
        var builder = toggle ? window.MessExport[toggle.id] : null;

        if (typeof builder !== 'function') return;

        // inline=false: every row here is a download. Print keeps its own button
        // and is the only caller that opens the PDF inline.
        var url = builder(item.getAttribute('data-mess-export'), false);

        // "Full Details" is the same report without the Column-Visibility
        // narrowing. The builders all append `columns=…`; stripping it here
        // keeps that knowledge in one place instead of in ten page scripts.
        if (item.hasAttribute('data-mess-all-columns')) {
            url = url.replace(/([?&])columns=[^&]*&?/, '$1').replace(/[?&]$/, '');
        }

        window.location.href = url;
    });
})();
</script>
@endpush
@endonce
