@extends('admin.layouts.master')

@section('title', 'Add Question Paper')

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
@endphp

@section('setup_content')
<div class="container-fluid coe-page coe-qp-add">
    <x-breadcrum title="Add Question Paper" :showBack="true"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Add Question Paper']" />

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

    {{-- Template: tick one to start from it; untick to write from scratch. --}}
    <section class="card rounded-3 mb-3" aria-labelledby="qpaperTplTitle">
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

    <form id="qpaperForm" class="card rounded-3" novalidate>
        <div class="card-body p-3 p-md-4">
            <div class="coe-qp-editor">
                {{-- Summernote's starting document: the paper being edited (already
                     cleaned by PaperHtmlSanitizer); a new paper gets its template from the script. --}}
                <div id="qpaperEditor">{!! $editorHtml ?? '' !!}</div>
                <p class="coe-dropzone__error d-none" id="qpaperEditorError" role="alert"></p>
            </div>

            <div class="coe-qp-actions">
                <a href="{{ route('admin.coe.faculty-question-paper.index') }}" id="qpaperCancel"
                   class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand">Cancel</a>
                <button type="submit" id="qpaperSubmit" class="btn coe-btn coe-btn-primary" disabled>Upload Paper</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.js"></script>
<script>
$(function () {
    'use strict';

    var LIST_URL = @json(route('admin.coe.faculty-question-paper.index'));
    var UPLOAD_URL = @json(route('admin.coe.faculty-question-paper.upload', $paper['id']));
    var BLANK = '<p><br></p>';

    var $form = $('#qpaperForm');
    var $submit = $('#qpaperSubmit');
    var $editor = $('#qpaperEditor');
    var $checks = $('.coe-tpl-card__check');
    var current = $checks.filter(':checked').val() || null;   // the ticked template
    var baseline = null;        // the editor's content as last loaded
    var fromTemplate = false;   // baseline is a template or a blank page, not the saved paper
    var submitting = false;

    function templateHtml(key) { return $('#qpaperTplHtml_' + key).html(); }

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
            onChange: function () { $('#qpaperEditorError').addClass('d-none'); refresh(); },
            onKeyup: syncStyleLabel,
            onMouseup: syncStyleLabel
        }
    });

    function code() { return $editor.summernote('code'); }

    function editorText() {
        return $('<div>').html(code()).text().replace(/\s+/g, ' ').trim();
    }

    // The faculty has changed the editor since a template / the saved paper was loaded.
    function isEdited() { return code() !== baseline; }

    // Switching templates would lose work: their own edits, or the saved paper.
    function wouldLoseWork() { return editorText() !== '' && (isEdited() || !fromTemplate); }

    function load(html, isTemplate) {
        $editor.summernote('code', html);
        baseline = code();                      // as Summernote normalised it
        fromTemplate = isTemplate;
        refresh();
    }

    /* Upload Paper is live once the paper has some text. */
    function refresh() {
        $submit.prop('disabled', editorText() === '');
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
        var apply = function () {
            current = key;
            syncCards();
            load(key ? templateHtml(key) : BLANK, true);
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

    // Start: the ticked template for a new paper; the saved paper for an edit.
    if (current) { load(templateHtml(current), true); } else { load(code(), false); }

    /* ── Leaving with unsaved work ── */
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && isEdited()) { e.preventDefault(); e.returnValue = ''; }
    });

    $('#qpaperCancel').on('click', function (e) {
        if (!isEdited()) { return; }
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
       POST paper_html; the server cleans it. Back to the list only once the
       paper is stored. */
    function showError(text) {
        $('#qpaperEditorError').text(text).removeClass('d-none');
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        if (submitting) { return; }

        if (editorText() === '') {
            showError('Write the question paper before uploading it.');
            return;
        }
        if (fromTemplate && !isEdited()) {
            showError('Type your questions into the template before uploading the paper.');
            return;
        }

        submitting = true;
        $submit.prop('disabled', true);

        $.ajax({
            url: UPLOAD_URL,
            method: 'POST',
            data: { paper_html: code() },
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
    });
});
</script>
@endpush
