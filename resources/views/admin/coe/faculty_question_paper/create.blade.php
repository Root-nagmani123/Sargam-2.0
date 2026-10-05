@extends('admin.layouts.master')

@section('title', 'Add Question Paper')

@push('styles')
{{-- Summernote (lite: no Bootstrap dependency) — same build as the Notice screens. --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-lite.min.css">
{{-- COE layer (coe-*); after Summernote so the paper styles win ties. --}}
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

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

    <form id="qpaperForm" class="card rounded-3" novalidate>
        <input type="hidden" name="mode" id="qpaperMode" value="upload">

        <div class="card-body p-3 p-md-4">
            {{-- Upload a .docx, or write the paper here --}}
            <div class="coe-seg coe-qp-seg" role="tablist" aria-label="How to add the question paper">
                <button type="button" class="coe-seg__btn active" role="tab" id="qpaperTabUpload"
                        aria-selected="true" aria-controls="qpaperPaneUpload" data-qpaper-mode="upload">Upload</button>
                <button type="button" class="coe-seg__btn" role="tab" id="qpaperTabCreate"
                        aria-selected="false" aria-controls="qpaperPaneCreate" data-qpaper-mode="create"
                        tabindex="-1">Create</button>
            </div>

            <div id="qpaperPaneUpload" role="tabpanel" aria-labelledby="qpaperTabUpload">
                {{-- The file input is #qpaperUploadInput (dropzone id + "Input"). --}}
                @include('admin.coe.partials.dropzone', [
                    'id' => 'qpaperUpload',
                    'field' => 'question_paper',
                    'accept' => '.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'hint' => 'File type: .docx',
                    'icon' => 'bi-file-earmark-word',
                ])
            </div>

            <div id="qpaperPaneCreate" class="coe-qp-editor" role="tabpanel" aria-labelledby="qpaperTabCreate" hidden>
                {{-- Summernote takes this markup as its starting document. --}}
                <div id="qpaperEditor">
                    @include('admin.coe.faculty_question_paper.partials.paper_template', ['paper' => $paper])
                </div>
                <input type="hidden" name="paper_html" id="qpaperHtml">
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
<script src="{{ asset('js/coe-grid.js') }}?v={{ @filemtime(public_path('js/coe-grid.js')) ?: time() }}"></script>
<script>
$(function () {
    'use strict';

    var LIST_URL = @json(route('admin.coe.faculty-question-paper.index'));

    var $form = $('#qpaperForm');
    var $submit = $('#qpaperSubmit');
    var $editor = $('#qpaperEditor');
    var $tabs = $('.coe-qp-seg [role="tab"]');
    var mode = 'upload';
    var chosenFile = null;
    var editorReady = false;
    var initialHtml = null;
    var submitting = false;

    /* ── Upload pane ── */
    CoeGrid.dropzone({
        root: '#qpaperUpload',
        accept: /\.docx$/i,
        acceptText: 'Only .docx files are allowed.',
        onChange: function (file) { chosenFile = file; refresh(); }
    });

    /* ── Create pane — Summernote starts on first use, while visible ── */

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

    function startEditor() {
        if (editorReady) { return; }
        $editor.summernote({
            minHeight: 520,
            disableResizeEditor: true,
            styleTags: ['p', 'h2', 'h3', 'h4'],
            buttons: {
                qpAlignLeft: alignButton('justifyLeft', 'alignLeft', 'Align left'),
                qpAlignCenter: alignButton('justifyCenter', 'alignCenter', 'Align center'),
                qpAlignRight: alignButton('justifyRight', 'alignRight', 'Align right'),
                qpAlignJustify: alignButton('justifyFull', 'alignJustify', 'Justify')
            },
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                ['para', ['qpAlignLeft', 'qpAlignCenter', 'qpAlignRight', 'qpAlignJustify']],
                ['list', ['ul', 'ol']],
                ['insert', ['link', 'table']],
                ['history', ['undo', 'redo']]
            ],
            callbacks: {
                onChange: function () { $('#qpaperEditorError').addClass('d-none'); refresh(); }
            }
        });
        editorReady = true;
        initialHtml = $editor.summernote('code');   // as Summernote normalised it
    }

    function editorText() {
        if (!editorReady) { return ''; }
        return $('<div>').html($editor.summernote('code')).text().replace(/\s+/g, ' ').trim();
    }

    function isDirty() {
        return !!chosenFile || (editorReady && $editor.summernote('code') !== initialHtml);
    }

    /* Upload Paper is live once the active pane has something to send. */
    function refresh() {
        $submit.prop('disabled', mode === 'upload' ? !chosenFile : editorText() === '');
    }

    /* ── Upload / Create switch (tabs: click, ←/→, Home/End) ── */
    function activate(next, focus) {
        mode = next;
        $('#qpaperMode').val(next);
        $tabs.each(function () {
            var on = $(this).data('qpaper-mode') === next;
            $(this).toggleClass('active', on).attr({ 'aria-selected': on, tabindex: on ? 0 : -1 });
            $('#' + $(this).attr('aria-controls')).prop('hidden', !on);
            if (on && focus) { this.focus(); }
        });
        if (next === 'create') { startEditor(); }
        refresh();
    }

    $tabs.on('click', function () { activate($(this).data('qpaper-mode'), false); });
    $tabs.on('keydown', function (e) {
        var i = $tabs.index(this);
        var to = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: $tabs.length - 1 }[e.key];
        if (to === undefined) { return; }
        e.preventDefault();
        activate($tabs.eq((to + $tabs.length) % $tabs.length).data('qpaper-mode'), true);
    });

    /* ── Leaving with unsaved work ── */
    window.addEventListener('beforeunload', function (e) {
        if (!submitting && isDirty()) { e.preventDefault(); e.returnValue = ''; }
    });

    $('#qpaperCancel').on('click', function (e) {
        if (!isDirty()) { return; }
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Discard this question paper?',
            text: 'The file you chose and any changes in the editor will be lost.',
            showCancelButton: true,
            confirmButtonText: 'Discard',
            cancelButtonText: 'Keep editing'
        }).then(function (r) {
            if (r.isConfirmed) { submitting = true; window.location.href = LIST_URL; }
        });
    });

    /* ── Submit ──────────────────────────────────────────────────────────────
       DESIGN PREVIEW: no backend yet, so nothing is stored. When the endpoint
       exists, POST this form (multipart: mode + question_paper | paper_html),
       sanitise paper_html on the server, and redirect from the response. */
    $form.on('submit', function (e) {
        e.preventDefault();

        if (mode === 'upload') {
            if (!chosenFile) { return; }
        } else {
            if (editorText() === '') {
                $('#qpaperEditorError').text('Write the question paper before uploading it.').removeClass('d-none');
                return;
            }
            $('#qpaperHtml').val($editor.summernote('code'));
        }

        submitting = true;
        $submit.prop('disabled', true);
        Swal.fire({ icon: 'success', title: 'Success', text: 'Question paper uploaded.' })
            .then(function () { window.location.href = LIST_URL; });
    });

    refresh();
});
</script>
@endpush
