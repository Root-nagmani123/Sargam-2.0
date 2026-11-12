{{-- COE question paper editor: the faculty's Add Question Paper and Rajbhasha's
     Translate Question Paper. Pick a template or write from scratch; Upload Paper
     posts paper_html to $saveUrl.
     Params: $pageTitle, $paper (summary + template header), $editorHtml (the
     document to start from, or null for a template), $templates, $listUrl,
     $saveUrl, $hiddenUrl (where each cover of the paper is reported, for the
     logs), $startsFromCopy
     (optional: $editorHtml is someone else's paper, to be changed before upload —
     Rajbhasha's first translation), $paperSets (optional: show "1. Configure
     your sets" - the faculty's Add Question Paper). Guarded like the viewer:
     CoePaperGuard. --}}
@extends('admin.layouts.master')

@section('title', $pageTitle)

@push('styles')
{{-- Summernote (lite: no Bootstrap dependency) — same build as the Notice screens. --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css">
{{-- COE layer (coe-*); after Summernote so the paper styles win ties. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@php
    // A new paper starts from the Standard template, ticked; an edit starts from
    // the saved paper, with no template ticked.
    $startTemplate = empty($editorHtml) ? array_key_first($templates) : null;
    $startsFromCopy = ($startsFromCopy ?? false) && ! empty($editorHtml);
    $paperSets = $paperSets ?? false;
@endphp

@section('setup_content')
<div class="container-fluid coe-page coe-qp-add">
    <x-breadcrum :title="$pageTitle" :showBack="true"
                 :items="['Setup', 'COE', 'Question Paper Management', $pageTitle]" />

    <x-session_message />

    {{-- The paper being added --}}
    <div class="card rounded-3 mb-3">
        <dl class="card-body coe-qp-summary mb-0">
            <div>
                <dt>Course Name</dt>
                <dd>{{ $paper['course'] }}</dd>
            </div>
            <div>
                <dt>Examination Name</dt>
                <dd>{{ $paper['name'] }}</dd>
            </div>
            <div>
                <dt>Term Name</dt>
                <dd>{{ $paper['term'] }}</dd>
            </div>
            <div>
                <dt>Total Marks</dt>
                <dd>{{ $paper['max_marks'] }}</dd>
            </div>
        </dl>
    </div>

    <div class="coe-qp-setup {{ $paperSets ? 'has-sets' : '' }}">
        {{-- Template: tick one to start from it; untick to write from scratch. --}}
        <section class="card rounded-3 coe-qp-tpl" aria-labelledby="qpaperTplTitle">
            <div class="card-body p-3 p-md-4">
                <h2 class="coe-qp-block-title" id="qpaperTplTitle">Template</h2>
                <p class="coe-qp-block-text">Choose a pre-defined template to speed up your question paper creation.</p>

                <div class="coe-tpl-grid" role="group" aria-labelledby="qpaperTplTitle">
                    @foreach ($templates as $key => $template)
                        <div class="coe-tpl-card {{ $startTemplate === $key ? 'is-selected' : '' }}">
                            <div class="coe-tpl-card__head">
                                <div>
                                    <h3 class="coe-tpl-card__title" id="qpaperTplName_{{ $key }}">{{ $template['title'] }}</h3>
                                    <p class="coe-tpl-card__desc">{{ $template['description'] }}</p>
                                </div>
                                <input type="checkbox" class="form-check-input coe-tpl-card__check"
                                       value="{{ $key }}" data-title="{{ $template['title'] }}"
                                       aria-labelledby="qpaperTplName_{{ $key }}"
                                       @checked($startTemplate === $key)>
                            </div>
                            {{-- A miniature of the template, filled from its <template> below. --}}
                            <div class="coe-tpl-card__preview" aria-hidden="true">
                                <div class="coe-tpl-sheet" data-qpaper-preview="{{ $key }}"></div>
                            </div>
                            <p class="coe-tpl-card__meta">{{ $template['meta'] }}</p>
                        </div>
                    @endforeach
                </div>

                @foreach ($templates as $key => $template)
                    <template id="qpaperTplHtml_{{ $key }}">
                        @include('admin.coe.faculty_question_paper.templates.'.$key, ['paper' => $paper])
                    </template>
                @endforeach
            </div>
        </section>

        @if ($paperSets)
            {{-- 1. Configure your sets: Set A is the master paper in the editor. --}}
            <section class="card coe-sets-config" aria-labelledby="setsConfigTitle">
                <h2 class="coe-sets-config__title" id="setsConfigTitle">1. Configure your sets</h2>

                <div class="coe-sets-modes" role="radiogroup" aria-labelledby="setsConfigTitle">
                    <label class="coe-sets-mode is-selected" for="setsModeShuffled">
                        <input type="radio" class="form-check-input" name="setsMode" id="setsModeShuffled" value="shuffled" checked>
                        <span>
                            <span class="coe-sets-mode__title">Same questions, different order</span>
                            <span class="coe-sets-mode__text">Use one master paper and shuffle questions for each set.</span>
                        </span>
                    </label>
                    <label class="coe-sets-mode" for="setsModeIndependent">
                        <input type="radio" class="form-check-input" name="setsMode" id="setsModeIndependent" value="independent">
                        <span>
                            <span class="coe-sets-mode__title">Different questions in each set</span>
                            <span class="coe-sets-mode__text">Build and edit <span data-sets-letters>A, B, C and D</span> independently.</span>
                        </span>
                    </label>
                </div>

                <div class="coe-sets-count">
                    <div class="coe-sets-count__number">
                        <div class="coe-sets-count__row">
                            <span class="coe-sets-label" id="setsCountLabel">Number of sets</span>
                            <div class="coe-sets-stepper" role="group" aria-labelledby="setsCountLabel">
                                <button type="button" data-sets-step="-1" aria-label="Remove a set">&minus;</button>
                                <output id="setsCount" aria-live="polite">4</output>
                                <button type="button" data-sets-step="1" aria-label="Add a set">+</button>
                            </div>
                        </div>
                        <p class="coe-sets-hint">2–8 sets per paper</p>
                    </div>
                    <div class="coe-sets-count__labels">
                        <span class="coe-sets-label">Set labels</span>
                        <div class="coe-sets-chips" id="setsChips"></div>
                    </div>
                </div>

                <div class="coe-sets-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="setsShuffle" checked>
                        <label class="form-check-label" for="setsShuffle">Shuffle questions within each section</label>
                    </div>
                    {{-- shortcut: a setting only - no template marks linked questions yet; honour it in the shuffle once one does. --}}
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="setsKeepLinked" checked>
                        <label class="form-check-label" for="setsKeepLinked">Keep linked questions and subparts together</label>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <form id="qpaperForm" class="card rounded-3 coe-qp-form {{ $paperSets ? 'has-sets' : '' }}" data-mode="shuffled" novalidate>
        {{-- Covers the paper while the window is not in use (screen capture tools, Alt+Tab). --}}
        <div class="coe-qp-shield" aria-hidden="true">
            <i class="bi bi-eye-slash" aria-hidden="true"></i>
            <p>The paper is hidden while this window is not in use.<br>Click here to continue.</p>
        </div>
        <div class="card-body coe-qp-form__body">
            @if ($paperSets)
                <div class="coe-sets-cards" id="setsCards" role="group" aria-label="Paper sets"></div>
            @endif

            {{-- Summernote's toolbar is moved here (toolbarContainer): right-aligned above the paper and the side panel. --}}
            <div class="coe-qp-toolbar" id="qpaperToolbar"></div>

            <div class="coe-qp-work">
                <div class="coe-qp-editor">
                    {{-- Summernote's starting document: the paper being edited (already
                         cleaned by PaperHtmlSanitizer); a new paper gets its template from the script. --}}
                    <div id="qpaperEditor">{!! $editorHtml ?? '' !!}</div>
                    <p class="coe-dropzone__error d-none" id="qpaperEditorError" role="alert"></p>
                </div>
                @if ($paperSets)
                    <aside class="coe-sets-order" id="setsOrder" aria-labelledby="setsOrderTitle"></aside>
                @endif
            </div>

            <div class="coe-qp-actions">
                <a href="{{ $listUrl }}" id="qpaperCancel"
                   class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand">Cancel</a>
                <button type="submit" id="qpaperSubmit" class="btn coe-btn coe-btn-primary" disabled>Upload Paper</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.js"></script>
<script src="{{ asset('js/coe-paper-viewer.js') }}?v={{ @filemtime(public_path('js/coe-paper-viewer.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var LIST_URL = @json($listUrl);
    var UPLOAD_URL = @json($saveUrl);
    var HIDDEN_URL = @json($hiddenUrl ?? null);
    var STARTS_FROM_COPY = @json($startsFromCopy);
    var TOTAL_MARKS = @json($paper['max_marks']);
    var BLANK = '<p><br></p>';

    // Upload Paper with the starting document untouched: why that is refused.
    var UNTOUCHED = {
        template: 'Type your questions into the template before uploading the paper.',
        copy: 'Translate the paper before uploading it.'
    };

    var $form = $('#qpaperForm');
    var $submit = $('#qpaperSubmit');
    var $editor = $('#qpaperEditor');
    var $checks = $('.coe-tpl-card__check');
    var current = $checks.filter(':checked').val() || null;   // the ticked template
    var submitting = false;

    /* Papers by set: docs[0] is Set A, the master - the paper that is uploaded.
       Each: html (when not in the editor), base (as last loaded - null while a
       set has never been opened), kind ('template' - or a blank page - 'copy' or
       'saved'), tpl (the template it started from). Without sets: docs[0] only. */
    var docs = [{ html: null, base: null, kind: 'saved', tpl: current }];
    var editing = 0;            // the set whose paper is in the editor
    var previewing = false;     // the editor shows a shuffled set, read-only

    function templateHtml(key) { return $('#qpaperTplHtml_' + key).html(); }

    // An inert document: images in the paper are not fetched.
    function parse(html) { return new DOMParser().parseFromString(html || '', 'text/html').body; }

    /* ── Template previews: each card shows its template in miniature ── */
    $('[data-qpaper-preview]').each(function () {
        $(this).html(templateHtml($(this).data('qpaper-preview')));
    });

    /* ── Editor toolbar, as designed: Normal ▾ · B I U S · align · lists · link · … ── */

    // Summernote 0.8.18 only offers alignment inside its 'paragraph' dropdown
    // (its justify* names are not toolbar buttons); the design shows four icons.
    function alignButton(command, icon, tooltip) {
        return function (context) {
            var ui = $.summernote.ui;
            return ui.button({
                contents: ui.icon(context.options.icons[icon]),
                tooltip: tooltip,
                container: context.options.container,   // the lite tooltip positions against it
                click: context.createInvokeHandler('editor.' + command)
            }).render();
        };
    }

    // The built-in style button shows a wand icon; the design shows the style's name.
    var STYLES = [
        { value: 'p', title: 'Normal' },
        { value: 'h2', title: 'Title' },
        { value: 'h3', title: 'Heading' },
        { value: 'h4', title: 'Subheading' }
    ];

    function styleButton(context) {
        var ui = $.summernote.ui;
        return ui.buttonGroup([
            ui.button({
                className: 'dropdown-toggle coe-qp-style',
                contents: '<span class="coe-qp-style__label">Normal</span> <span class="note-icon-caret"></span>',
                tooltip: 'Text style',
                container: context.options.container,
                data: { toggle: 'dropdown' }
            }),
            ui.dropdown({
                className: 'dropdown-style',
                items: STYLES,
                template: function (item) { return '<' + item.value + '>' + item.title + '</' + item.value + '>'; },
                click: context.createInvokeHandler('editor.formatBlock')
            })
        ]).render();
    }

    // "…" shows the less-used tools (table, line, undo, redo) — the 'more' group.
    function moreButton(context) {
        var ui = $.summernote.ui;
        return ui.button({
            className: 'coe-qp-more',
            contents: '<i class="bi bi-three-dots" aria-hidden="true"></i>',
            tooltip: 'More',
            container: context.options.container,
            click: function () {
                var open = !context.layoutInfo.toolbar.hasClass('is-more-open');
                context.layoutInfo.toolbar.toggleClass('is-more-open', open);
                $(this).attr('aria-expanded', open ? 'true' : 'false');
            }
        }).render();
    }

    // Keeps "Normal ▾" naming the block the caret is in.
    function syncStyleLabel() {
        var sel = window.getSelection();
        var $editable = $editor.next('.note-editor').find('.note-editable');
        if (!sel || !sel.anchorNode || !$.contains($editable[0], sel.anchorNode)) { return; }
        var tag = ($(sel.anchorNode).closest('p, h2, h3, h4', $editable[0]).prop('tagName') || 'P').toLowerCase();
        var style = STYLES.filter(function (s) { return s.value === tag; })[0] || STYLES[0];
        $('.coe-qp-style__label').text(style.title);
    }

    $editor.summernote({
        minHeight: 520,
        disableResizeEditor: true,
        toolbarContainer: '#qpaperToolbar',
        buttons: {
            qpStyle: styleButton,
            qpAlignLeft: alignButton('justifyLeft', 'alignLeft', 'Align left'),
            qpAlignCenter: alignButton('justifyCenter', 'alignCenter', 'Align center'),
            qpAlignRight: alignButton('justifyRight', 'alignRight', 'Align right'),
            qpAlignJustify: alignButton('justifyFull', 'alignJustify', 'Justify'),
            qpMore: moreButton
        },
        toolbar: [
            ['style', ['qpStyle']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough']],
            ['para', ['qpAlignLeft', 'qpAlignCenter', 'qpAlignRight', 'qpAlignJustify']],
            ['list', ['ol', 'ul']],
            ['insert', ['link']],
            ['more', ['table', 'hr', 'undo', 'redo']],
            ['toggle', ['qpMore']]
        ],
        callbacks: {
            onChange: function () {
                if (previewing) { return; }
                $('#qpaperEditorError').addClass('d-none');
                refresh();
            },
            onKeyup: syncStyleLabel,
            onMouseup: syncStyleLabel
        }
    });

    /* ── Guarded like the viewer: the LBSNAA watermark over the sheet, covered while the
       window is not in use, no save / print / source keys, copy stays in the paper ── */
    var $editing = $editor.next('.note-editor').find('.note-editing-area');
    $('<div class="coe-qp-mark" aria-hidden="true"></div>').appendTo($editing);   // coe-admin.css draws it
    document.body.classList.add(CoePaperGuard.NO_PRINT);

    // Each cover is reported once (blur and visibilitychange both fire on Alt+Tab).
    var shielded = false;
    function shield(on, reason) {
        if (on && !shielded && reason && HIDDEN_URL) { CoePaperGuard.report(HIDDEN_URL, reason); }
        shielded = on;
        $form.toggleClass('is-shielded', on);
    }
    CoePaperGuard.guard({ active: function () { return true; }, shield: shield, copyKeys: true });
    $form.find('.coe-qp-shield').on('click', function () { shield(false); });
    CoePaperGuard.keepClipboard($editing.find('.note-editable')[0], function (html) {
        $editor.summernote('pasteHTML', html);
    });

    function code() { return $editor.summernote('code'); }

    // A set's paper: live from the editor while it is being edited.
    function docHtml(k) { return k === editing && !previewing ? code() : docs[k].html; }

    function textOf(html) { return (parse(html).textContent || '').replace(/\s+/g, ' ').trim(); }

    function editorText() { return textOf(code()); }

    // A set's paper has changed since a template / the saved paper was loaded.
    function isEdited(k) {
        k = k === undefined ? editing : k;
        return docs[k].base !== null && docHtml(k) !== docs[k].base;
    }

    // Why a set's paper is not ready to upload, or '' when it is.
    function notReady(k) {
        if (textOf(docHtml(k)) === '') { return 'Write the question paper before uploading it.'; }
        return UNTOUCHED[docs[k].kind] && !isEdited(k) ? UNTOUCHED[docs[k].kind] : '';
    }

    // Switching templates would lose work: the user's own edits, or a paper.
    function wouldLoseWork() { return editorText() !== '' && (isEdited() || docs[editing].kind !== 'template'); }

    function load(html, kind) {
        $editor.summernote('code', html);
        docs[editing].base = code();            // as Summernote normalised it
        docs[editing].kind = kind;
        docs[editing].tpl = current;
        refresh();
    }

    /* Upload Paper is live once the master paper has some text. */
    function refresh() {
        $submit.prop('disabled', textOf(docHtml(0)) === '');
        renderSets();
    }

    /* ── Template cards: one ticked at most; none = write from scratch ── */
    function syncCards() {
        $checks.prop('checked', false);
        if (current) { $checks.filter('[value="' + current + '"]').prop('checked', true); }
        $checks.each(function () { $(this).closest('.coe-tpl-card').toggleClass('is-selected', this.checked); });
    }

    function confirmLoss(title, text, confirmText) {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: text,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Keep my paper'
        }).then(function (r) { return r.isConfirmed; });
    }

    function pick(key, title) {
        if (previewing) { select(0); }          // a template goes into the master
        var apply = function () {
            current = key;
            syncCards();
            load(key ? templateHtml(key) : BLANK, 'template');
        };
        syncCards();                            // until confirmed, the old choice stays ticked
        if (!wouldLoseWork()) { apply(); return; }
        (key
            ? confirmLoss('Use the ' + title + ' template?', 'Your paper in the editor will be replaced by this template.', 'Use Template')
            : confirmLoss('Start from a blank page?', 'Your paper in the editor will be cleared.', 'Clear')
        ).then(function (ok) { if (ok) { apply(); } });
    }

    $checks.on('change', function () {
        pick(this.checked ? this.value : null, $(this).data('title'));
    });

    // The whole card toggles its checkbox.
    $('.coe-tpl-card').on('click', function (e) {
        if (!$(e.target).is('.coe-tpl-card__check')) { $(this).find('.coe-tpl-card__check').trigger('click'); }
    });

    /* ── Paper sets ──────────────────────────────────────────────────────────
       Shuffled: Set A is the master; B… show it read-only with the questions
       shuffled inside each section (seeded, so a set keeps its order).
       Independent: every set is its own paper in the editor.
       shortcut: only Set A is uploaded - the paper record holds one paper; store
       the other sets once the backend has a place for them. */
    var $sets = $('#setsCards');
    var SETS = $sets.length > 0;
    var LETTERS = 'ABCDEFGH';
    var MIN_SETS = 2, MAX_SETS = 8;
    var sets = { mode: 'shuffled', count: 4, selected: 0, shuffle: true };

    function esc(v) {
        return String(v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function letter(k) { return LETTERS.charAt(k); }
    // ['B','C','D'] → "B, C and D"
    function andList(items) {
        return items.length < 2 ? items.join('') : items.slice(0, -1).join(', ') + ' and ' + items[items.length - 1];
    }
    function letters(from) {
        var out = [];
        for (var k = from; k < sets.count; k++) { out.push(letter(k)); }
        return out;
    }

    // Seeded shuffle (mulberry32 + Fisher-Yates): the same seed gives the same sets.
    function rng(seed) {
        return function () {
            seed = (seed + 0x6D2B79F5) | 0;
            var t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }
    function range(n) { var a = []; for (var i = 0; i < n; i++) { a.push(i); } return a; }
    function shuffled(list, rand) {
        var a = list.slice();
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(rand() * (i + 1));
            var t = a[i]; a[i] = a[j]; a[j] = t;
        }
        return a;
    }

    // Question rows of each section (the templates' .coe-paper-questions tables).
    function sectionRows(body) {
        return $(body).find('table.coe-paper-questions > tbody').map(function () {
            return [$(this).children('tr').get()];
        }).get();
    }

    // Every set's order, as row indexes per section. Set A keeps the master order;
    // the rest are re-drawn until no two sets match.
    function orders(sizes) {
        var rand = rng(1), seen = {}, out = [];
        for (var k = 0; k < sets.count; k++) {
            var o = sizes.map(range);
            for (var t = 0; k > 0 && sets.shuffle && t < 50 && seen[JSON.stringify(o)]; t++) {
                o = sizes.map(function (n) { return shuffled(range(n), rand); });
            }
            seen[JSON.stringify(o)] = true;
            out.push(o);
        }
        return out;
    }

    function questionNo(row) {
        return parseInt($(row).children('.coe-paper-questions__no').text(), 10) || 0;
    }

    /* Set k of the master: {html, map: [master question number of each question]}.
       Rows move; the numbers stay in place, so every set reads 1, 2, 3… */
    function shuffledSet(k) {
        var body = parse(docHtml(0)), secs = sectionRows(body), map = [];
        var order = orders(secs.map(function (rows) { return rows.length; }))[k];
        secs.forEach(function (rows, si) {
            var nums = rows.map(function (r) { return $(r).children('.coe-paper-questions__no').html(); });
            var tbody = rows.length ? rows[0].parentNode : null;
            order[si].forEach(function (from, i) {
                var row = rows[from];
                map.push(questionNo(row));
                $(row).children('.coe-paper-questions__no').html(nums[i]);
                tbody.appendChild(row);
            });
        });
        return { html: body.innerHTML, map: map };
    }

    // Park the paper in the editor, then show set k.
    function select(k) {
        if (!previewing) { docs[editing].html = code(); }
        sets.selected = k;
        previewing = sets.mode === 'shuffled' && k > 0;
        if (previewing) {
            $editor.summernote('code', shuffledSet(k).html);
            $editor.summernote('disable');
        } else {
            var target = sets.mode === 'shuffled' ? 0 : k;
            $editor.summernote('enable');
            editing = target;
            if (!docs[target]) { docs[target] = { html: current ? templateHtml(current) : BLANK, base: null, kind: 'template', tpl: current }; }
            $editor.summernote('code', docs[target].html);
            if (docs[target].base === null) { docs[target].base = code(); }
            current = docs[target].tpl;
            syncCards();
        }
        $form.toggleClass('is-previewing', previewing);
        refresh();
    }

    // A paper's questions and the marks printed against them ("(2)"); the
    // paper record's total when the paper prints none.
    function tally(html) {
        var rows = $(parse(html)).find('table.coe-paper-questions > tbody > tr');
        var marks = 0;
        rows.each(function () { marks += parseFloat($(this).children('.coe-paper-marks').text().replace(/[^\d.]/g, '')) || 0; });
        return { n: rows.length, marks: marks || (isNaN(parseFloat(TOTAL_MARKS)) ? null : TOTAL_MARKS) };
    }

    // t: the set's tally. Shuffled sets are the master's; an independent set
    // never opened is Pending until it is.
    function card(k, t) {
        var master = sets.mode === 'shuffled', src = master ? 0 : k;
        var ready = !!docs[src] && !notReady(src);
        var note = '';
        if (k === sets.selected) { note = '<span class="coe-sets-card__note is-selected">Selected preview</span>'; }
        else if (master) { note = '<span class="coe-sets-card__note">' + (k === 0 ? 'Master Paper' : sets.shuffle ? 'Unique question order' : 'Same order as the master') + '</span>'; }
        return '<button type="button" class="coe-sets-card' + (k === sets.selected ? ' is-selected' : '') + '" data-set="' + k + '" aria-pressed="' + (k === sets.selected) + '">' +
            '<span class="coe-sets-card__head"><span class="coe-sets-card__name">Set ' + letter(k) + '</span>' +
            '<span class="coe-sets-badge ' + (ready ? 'is-ready">Ready' : 'is-pending">Pending') + '</span></span>' +
            '<span class="coe-sets-card__line">' + t.n + ' question' + (t.n === 1 ? '' : 's') +
                (t.marks === null ? '' : '  •  ' + esc(t.marks) + ' marks') + '</span>' +
            note + '</button>';
    }

    function renderOrder() {
        var k = sets.selected, set = shuffledSet(k), n = set.map.length;
        var sections = $(parse(docHtml(0))).find('.coe-paper-section__en h3').map(function () {
            var m = $(this).text().match(/Section\s+\S+/);
            return m ? m[0] : null;
        }).get();
        var rows = n
            ? set.map.map(function (m, i) { return '<tr><td>Question ' + (i + 1) + '</td><td>Question ' + m + '</td></tr>'; }).join('')
            : '<tr><td colspan="2">The master paper has no numbered questions to shuffle.</td></tr>';
        $('#setsOrder').html(
            '<h3 class="coe-sets-order__title" id="setsOrderTitle">Question order</h3>' +
            '<p class="coe-sets-order__text">Track each question back to the master.</p>' +
            '<table class="coe-sets-order__map"><thead><tr><th scope="col">Set ' + letter(k) + '</th><th scope="col">Master paper</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table>' +
            '<div class="coe-sets-order__same"><p>What stays the same</p><ul>' +
                '<li>All ' + n + ' questions and their marks</li>' +
                (sections.length ? '<li>' + esc(andList(sections)) + ' boundaries</li>' : '') +
                '<li>Linked questions and subparts</li>' +
                '<li>Paper header and instructions</li>' +
            '</ul></div>' +
            '<p class="coe-sets-order__foot">Regenerating changes the order in ' + andList(letters(1)) + '. Set A keeps the master order.</p>'
        );
    }

    function renderSets() {
        if (!SETS) { return; }
        var all = letters(0);
        $form.attr('data-mode', sets.mode);
        $('#setsCount').text(sets.count);
        $('[data-sets-step="-1"]').prop('disabled', sets.count <= MIN_SETS);
        $('[data-sets-step="1"]').prop('disabled', sets.count >= MAX_SETS);
        $('#setsChips').html(all.map(function (l, k) {
            return '<span class="coe-sets-chip">' + l + (k === 0 ? ' (Master Paper)' : '') + '</span>';
        }).join(''));
        $('[data-sets-letters]').text(andList(all));
        $('.coe-sets-mode').each(function () { $(this).toggleClass('is-selected', $(this).find('input').prop('checked')); });

        var master = tally(docHtml(0));
        $sets.css('--sets', Math.min(sets.count, 4)).html(all.map(function (l, k) {
            var fresh = current ? templateHtml(current) : BLANK;   // what an unopened set starts from
            return card(k, sets.mode === 'shuffled' || k === 0 ? master : tally(docs[k] ? docHtml(k) : fresh));
        }).join(''));
        if (sets.mode === 'shuffled') { renderOrder(); }
    }

    if (SETS) {
        $('input[name="setsMode"]').on('change', function () {
            sets.mode = this.value;
            select(sets.selected);
        });

        $('[data-sets-step]').on('click', function () {
            var n = sets.count + Number($(this).data('sets-step'));
            if (n < MIN_SETS || n > MAX_SETS) { return; }
            var apply = function () {
                if (sets.selected >= n) { select(n - 1); }   // leave the set before it goes
                if (n < sets.count) { docs.splice(n); }
                sets.count = n;
                refresh();
            };
            if (n < sets.count && sets.mode === 'independent' && docs[n] && isEdited(n)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Remove Set ' + letter(n) + '?',
                    text: 'Its paper will be removed.',
                    showCancelButton: true,
                    confirmButtonText: 'Remove',
                    cancelButtonText: 'Keep it'
                }).then(function (r) { if (r.isConfirmed) { apply(); } });
                return;
            }
            apply();
        });

        $('#setsShuffle').on('change', function () {
            sets.shuffle = this.checked;
            if (previewing) { select(sets.selected); } else { refresh(); }
        });

        $sets.on('click', '[data-set]', function () {
            select(Number($(this).data('set')));
            $sets.find('[data-set="' + sets.selected + '"]').trigger('focus');
        });
    }

    // Start: the ticked template for a new paper; the saved paper for an edit
    // (or, for a first translation, the faculty's paper to translate).
    if (current) { load(templateHtml(current), 'template'); } else { load(code(), STARTS_FROM_COPY ? 'copy' : 'saved'); }

    // Any set with work that would be lost.
    function anyEdited() {
        return docs.some(function (d, k) { return d && isEdited(k); });
    }

    /* ── Leaving with unsaved work ── */
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && anyEdited()) { e.preventDefault(); e.returnValue = ''; }
    });

    $('#qpaperCancel').on('click', function (e) {
        if (!anyEdited()) { return; }
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Discard this question paper?',
            text: 'Your changes in the editor will be lost.',
            showCancelButton: true,
            confirmButtonText: 'Discard',
            cancelButtonText: 'Keep editing'
        }).then(function (r) {
            if (r.isConfirmed) { submitting = true; window.location.href = LIST_URL; }
        });
    });

    /* ── Submit ──────────────────────────────────────────────────────────────
       POST paper_html (Set A, the master); the server cleans it. Back to the
       list only once the paper is stored. */
    function showError(text) {
        $('#qpaperEditorError').text(text).removeClass('d-none');
    }

    function upload() {
        submitting = true;
        $submit.prop('disabled', true);

        $.ajax({
            url: UPLOAD_URL,
            method: 'POST',
            data: { paper_html: docHtml(0) },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).done(function (res) {
            Swal.fire({ icon: 'success', title: 'Success', text: (res && res.message) || 'Question paper uploaded.' })
                .then(function () { window.location.href = LIST_URL; });
        }).fail(function (xhr) {
            submitting = false;
            refresh();
            var r = xhr.responseJSON || {};
            var firstError = r.errors && Object.keys(r.errors).length ? r.errors[Object.keys(r.errors)[0]][0] : null;
            Swal.fire({ icon: 'error', title: 'Error', text: firstError || r.message || 'Could not upload the question paper.' });
        });
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        if (submitting) { return; }

        var why = notReady(0);
        if (why) {
            if (editing !== 0 || previewing) { select(0); }
            showError(why);
            return;
        }

        // Independent sets B… have nowhere to go yet: say so rather than drop them silently.
        var unsaved = [];
        docs.forEach(function (d, k) { if (k > 0 && d && sets.mode === 'independent' && isEdited(k)) { unsaved.push(letter(k)); } });
        if (!unsaved.length) { upload(); return; }
        Swal.fire({
            icon: 'warning',
            title: 'Upload Set A only?',
            text: 'Set A (the master paper) is uploaded. ' + (unsaved.length === 1 ? 'Set ' : 'Sets ') + andList(unsaved) +
                ' cannot be saved yet and will be lost.',
            showCancelButton: true,
            confirmButtonText: 'Upload Set A',
            cancelButtonText: 'Keep editing'
        }).then(function (r) { if (r.isConfirmed) { upload(); } });
    });
});
</script>
@endpush
