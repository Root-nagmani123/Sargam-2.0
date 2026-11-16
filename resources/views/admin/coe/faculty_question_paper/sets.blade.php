{{-- COE → Question Paper Management → Create multiple paper sets (faculty).
     Two modes on one page: "Same questions, different order" (one master paper,
     its order shuffled per set) and "Different questions in each set" (each set
     built on its own to the same blueprint). [data-only] blocks show for their
     mode only; the rest is drawn by the script below.
     Params: $paper (summary facts), $mode ('shuffled' | 'independent'),
     $editorUrl (Add Question Paper - the master), $sample (SAMPLE blueprint and
     questions: design-first, nothing on this page is saved yet). --}}
@extends('admin.layouts.master')

@section('title', 'Create multiple paper sets')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/coe-admin.css') }}?v={{ @filemtime(public_path('css/coe-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid coe-page coe-qp-add coe-sets" id="coeSets" data-mode="{{ $mode }}">
    <x-breadcrum title="Create multiple paper sets" :showBack="true"
                 :items="['Setup', 'COE', 'Question Paper Management', 'Create multiple sets']">
        <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-sets-draft>Save draft</button>
    </x-breadcrum>
    <p class="coe-sets__lede">One examination. Multiple sets. Choose how the questions vary.</p>

    <x-session_message />

    {{-- The examination every set belongs to --}}
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

    {{-- 1. Configure your sets --}}
    <section class="card rounded-3 mb-3" aria-labelledby="setsConfigTitle">
        <div class="card-body p-3 p-md-4">
            <h2 class="coe-sets__title" id="setsConfigTitle">1. Configure your sets</h2>
            <p class="coe-sets__label mt-3" id="setsModeLabel">Set creation mode</p>

            <div class="row g-3 mb-4" role="radiogroup" aria-labelledby="setsModeLabel">
                <div class="col-md-6">
                    <label class="coe-sets-mode" for="setsModeShuffled">
                        <input type="radio" class="form-check-input" name="setsMode" id="setsModeShuffled"
                               value="shuffled" @checked($mode === 'shuffled')>
                        <span>
                            <span class="coe-sets-mode__title">Same questions, different order</span>
                            <span class="coe-sets-mode__text">Use one master paper and shuffle questions for each set.</span>
                        </span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="coe-sets-mode" for="setsModeIndependent">
                        <input type="radio" class="form-check-input" name="setsMode" id="setsModeIndependent"
                               value="independent" @checked($mode === 'independent')>
                        <span>
                            <span class="coe-sets-mode__title">Different questions in each set</span>
                            <span class="coe-sets-mode__text">Build and edit <span data-sets-letters>A, B, C and D</span> independently.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="coe-sets-count">
                <div class="coe-sets-count__group">
                    <span class="coe-sets__label mb-0" id="setsCountLabel">Number of sets</span>
                    <div class="coe-sets-stepper" role="group" aria-labelledby="setsCountLabel">
                        <button type="button" data-sets-step="-1" aria-label="Remove a set">&minus;</button>
                        <output id="setsCount" aria-live="polite">4</output>
                        <button type="button" data-sets-step="1" aria-label="Add a set">+</button>
                    </div>
                </div>
                <div class="coe-sets-count__group">
                    <span class="coe-sets__label mb-0">Set labels</span>
                    <div class="coe-sets-chips" id="setsChips"></div>
                </div>
            </div>
            <p class="coe-sets__hint">2–8 sets per paper</p>

            <div class="row g-3 mt-1" data-only="shuffled">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="setsShuffle" checked>
                        <label class="form-check-label" for="setsShuffle">Shuffle questions within each section</label>
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- Kept as a setting: the sample paper has no linked questions yet. --}}
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="setsKeepLinked" checked>
                        <label class="form-check-label" for="setsKeepLinked">Keep linked questions and subparts together</label>
                    </div>
                </div>
            </div>

            <p class="coe-sets-notice" data-only="independent">
                All sets share the same header, instructions and <span data-sets-total></span>-mark section blueprint.
                Questions are edited separately.
            </p>
        </div>
    </section>

    {{-- 2. Review generated sets / Build each paper set --}}
    <section class="card rounded-3 mb-3" aria-labelledby="setsWorkTitle">
        <div class="card-body p-3 p-md-4">
            <div class="coe-sets__head">
                <div>
                    <h2 class="coe-sets__title" id="setsWorkTitle">
                        <span data-only="shuffled">2. Review generated sets</span>
                        <span data-only="independent">2. Build each paper set</span>
                    </h2>
                    <p class="coe-sets__text" data-only="shuffled">Edit the master once. Every set keeps the same questions and marks.</p>
                    <p class="coe-sets__text" data-only="independent">Select a set to add its questions. Changes apply only to that set.</p>
                </div>
                <div class="coe-sets__actions">
                    <a href="{{ $editorUrl }}" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-only="shuffled">Edit master paper</a>
                    <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-only="shuffled" data-sets-regenerate>Regenerate order</button>
                    <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-only="independent" data-sets-preview>
                        <span>Preview Set <span data-sets-selected></span></span>
                    </button>
                </div>
            </div>

            <div class="row g-3" id="setsCards"></div>
            <p class="coe-sets-notice" id="setsNotice" role="status"></p>

            <div class="row g-3 mt-0">
                <div class="col-xl-8"><div class="coe-sets-panel" id="setsMain"></div></div>
                <div class="col-xl-4"><div class="coe-sets-panel" id="setsSide"></div></div>
            </div>
        </div>
    </section>

    <div class="card rounded-3">
        <div class="card-body coe-sets-footer">
            <div>
                <p class="coe-sets-footer__count" id="setsReady"></p>
                <p class="coe-sets-footer__note" id="setsReadyNote"></p>
            </div>
            <div class="coe-sets__actions">
                <a href="{{ $editorUrl }}" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-only="shuffled">Back</a>
                <button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-only="independent" data-sets-draft>Save draft</button>
                <button type="button" class="btn coe-btn coe-btn-primary" id="setsReview">Review all sets</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var S = @json($sample);
    var PAPER = { name: @json($paper['name']), term: @json($paper['term']) };
    var LETTERS = 'ABCDEFGH';
    var MIN = 2, MAX = 8;
    var TOTAL = S.sections.reduce(function (t, s) { return t + s.count * s.marks; }, 0);
    var NEEDED = S.sections.reduce(function (t, s) { return t + s.count; }, 0);
    var STATUS = { ready: ['Ready', 'is-ready'], draft: ['Draft', 'is-draft'], none: ['Not started', 'is-none'] };
    var NOT_WIRED = 'Paper sets are not saved yet: this page is a preview of the workflow with sample questions.';

    var $root = $('#coeSets');
    var state = {
        mode: $root.attr('data-mode'),
        count: 4,
        selected: 1,
        seed: 1,
        shuffle: true,
        preventReuse: true,
        // Independent mode: each set's own questions ({s: section index, text}).
        sets: [0, 1, 2, 3].map(function (k) {
            return (S.independent[k] || []).map(function (q) { return { s: q.s, text: q.text }; });
        })
    };
    var dirty = false;

    /* ── Helpers ── */
    function esc(v) {
        return String(v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function letter(k) { return LETTERS.charAt(k); }
    // ['B','C','D'] → "B, C and D"
    function andList(items) {
        return items.length < 2 ? items.join('') : items.slice(0, -1).join(', ') + ' and ' + items[items.length - 1];
    }
    function setsPhrase(letters) { return (letters.length === 1 ? 'Set ' : 'Sets ') + andList(letters); }
    function sectionName(sec) { return sec.title.split(' · ')[0]; }
    function plural(n, word) { return n + ' ' + word + (n === 1 ? '' : 's'); }

    // Seeded shuffle (mulberry32 + Fisher-Yates): the same seed gives the same sets.
    function rng(seed) {
        return function () {
            seed = (seed + 0x6D2B79F5) | 0;
            var t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }
    function shuffled(list, rand) {
        var a = list.slice();
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(rand() * (i + 1));
            var t = a[i]; a[i] = a[j]; a[j] = t;
        }
        return a;
    }

    /* Shuffled mode: each set's order as master indexes. Set A is the master;
       the rest shuffle inside their sections, re-drawn until no two sets match. */
    function orders() {
        var master = S.master.map(function (q, i) { return i; });
        var seen = {}, rand = rng(state.seed), out = [master];
        seen[master.join()] = true;
        for (var k = 1; k < state.count; k++) {
            var order = master;
            for (var tries = 0; state.shuffle && tries < 50 && seen[order.join()]; tries++) {
                order = [];
                S.sections.forEach(function (sec, si) {
                    order = order.concat(shuffled(master.filter(function (i) { return S.master[i].s === si; }), rand));
                });
            }
            seen[order.join()] = true;
            out.push(order);
        }
        return out;
    }

    /* Independent mode: how far a set is against the blueprint. */
    function info(k) {
        var qs = state.sets[k] || [];
        var marks = qs.reduce(function (t, q) { return t + S.sections[q.s].marks; }, 0);
        var have = S.sections.map(function (sec, si) { return qs.filter(function (q) { return q.s === si; }).length; });
        return {
            qs: qs,
            marks: marks,
            have: have,
            status: qs.length === 0 ? 'none' : (qs.length === NEEDED ? 'ready' : 'draft'),
            open: have.findIndex(function (n, si) { return n < S.sections[si].count; })   // first section with room, or -1
        };
    }

    /* The paper itself, read-only: section bands and numbered questions. */
    function paperHtml(qs) {
        var n = 0;
        return S.sections.map(function (sec, si) {
            var rows = qs.filter(function (q) { return q.s === si; }).map(function (q) {
                n++;
                return '<li><span class="coe-sets-qs__num">' + pad(n) + '</span>' +
                    '<span class="coe-sets-qs__text">' + esc(q.text) + '</span>' +
                    '<span class="coe-sets-qs__marks">(' + sec.marks + ')</span></li>';
            }).join('');
            return '<div class="coe-sets-section"><span>' + esc(sec.title) + '</span>' +
                '<span class="coe-sets-section__marks">' + sec.count + ' × ' + sec.marks + ' = ' + (sec.count * sec.marks) + ' marks</span></div>' +
                '<ol class="coe-sets-qs">' + rows + '</ol>';
        }).join('');
    }

    /* ── Render ── */
    function renderConfig() {
        var letters = [];
        for (var k = 0; k < state.count; k++) { letters.push(letter(k)); }
        $('#setsCount').text(state.count);
        $('[data-sets-step="-1"]').prop('disabled', state.count <= MIN);
        $('[data-sets-step="1"]').prop('disabled', state.count >= MAX);
        $('#setsChips').html(letters.map(function (l) { return '<span class="coe-sets-chip">' + l + '</span>'; }).join(''));
        $('[data-sets-letters]').text(andList(letters));
        $('[data-sets-total]').text(TOTAL);
        $('[data-sets-selected]').text(letter(state.selected));
        $('.coe-sets-mode').each(function () {
            $(this).toggleClass('is-selected', $(this).find('input').prop('checked'));
        });
    }

    function card(k, status, line, note) {
        var sel = k === state.selected;
        return '<div class="col-sm-6 col-xl-3">' +
            '<button type="button" class="coe-sets-card' + (sel ? ' is-selected' : '') + '" data-set="' + k + '" aria-pressed="' + sel + '">' +
            '<span class="coe-sets-card__head"><span class="coe-sets-card__name">Set ' + letter(k) + '</span>' +
            '<span class="coe-sets-badge ' + STATUS[status][1] + '">' + STATUS[status][0] + '</span></span>' +
            '<span class="coe-sets-card__line">' + line + '</span>' +
            '<span class="coe-sets-card__note">' + note + '</span></button></div>';
    }

    function renderShuffled() {
        var all = orders(), k = state.selected, L = letter(k), html = '';
        for (var i = 0; i < state.count; i++) {
            html += card(i, 'ready', NEEDED + ' questions • ' + TOTAL + ' marks',
                i === k ? 'Selected preview' : (i === 0 || !state.shuffle ? 'Master order' : 'Unique question order'));
        }
        $('#setsCards').html(html);

        $('#setsNotice').toggleClass('is-warn', !state.shuffle).text(state.shuffle
            ? 'All ' + state.count + ' sets generated. Sections, marks, instructions and linked subparts are preserved; only question order changes.'
            : 'Shuffling is off, so every set uses the master order. Tick "Shuffle questions within each section" to vary it.');

        var qs = all[k].map(function (m) { return S.master[m]; });
        $('#setsMain').html(
            '<div class="coe-sets__head mb-0"><div>' +
                '<p class="coe-sets-eyebrow">Set ' + L + '</p>' +
                '<h3 class="coe-sets-panel__title">Question paper preview</h3>' +
                '<p class="coe-sets__text mt-1">' + esc(PAPER.name) + ' • ' + esc(PAPER.term) + '</p>' +
            '</div><p class="coe-sets-panel__marks">' + TOTAL + ' marks</p></div>' +
            '<p class="coe-sets-instructions">Instructions: Answer all questions. Each question carries the marks shown.</p>' +
            paperHtml(qs) +
            '<p class="coe-sets-foot">Preview reflects the generated order. Edit question content in the master paper.</p>'
        );

        var others = [];
        for (var o = 1; o < state.count; o++) { others.push(letter(o)); }
        $('#setsSide').html(
            '<h3 class="coe-sets-panel__subtitle">Question order</h3>' +
            '<p class="coe-sets__text mt-1">Track each question back to the master.</p>' +
            '<table class="coe-sets-map"><thead><tr><th scope="col">Set ' + L + '</th><th scope="col">Master paper</th></tr></thead><tbody>' +
            all[k].map(function (m, i) { return '<tr><td>Question ' + (i + 1) + '</td><td>Question ' + (m + 1) + '</td></tr>'; }).join('') +
            '</tbody></table>' +
            '<div class="coe-sets-guide"><strong>What stays the same</strong><ul>' +
                '<li>All ' + NEEDED + ' questions and their marks</li>' +
                '<li>' + esc(andList(S.sections.map(sectionName))) + ' boundaries</li>' +
                '<li>Linked questions and subparts</li>' +
                '<li>Paper header and instructions</li>' +
            '</ul></div>' +
            '<p class="coe-sets-foot">Regenerating changes the order in ' + andList(others) + '. Set A keeps the master order.</p>'
        );

        $('#setsReady').text(state.count + ' of ' + state.count + ' sets ready');
        $('#setsReadyNote').text(TOTAL + ' marks verified in every set');
        $('#setsReview').prop('disabled', false).text('Review all ' + state.count + ' sets');
    }

    function renderIndependent() {
        var k = state.selected, L = letter(k), html = '', all = [];
        for (var i = 0; i < state.count; i++) {
            var it = info(i);
            all.push(it);
            html += card(i, it.status, plural(it.qs.length, 'question') + ' • ' + it.marks + ' / ' + TOTAL + ' marks',
                i === k ? 'Selected for editing' : { ready: 'Complete', draft: 'In progress', none: 'Start building' }[it.status]);
        }
        $('#setsCards').html(html);

        // "3 sets need attention. Add 32 marks to Set B, then complete Sets C and D …"
        var drafts = [], fresh = [], ready = [], open = [];
        all.forEach(function (it, i) {
            if (it.status === 'draft') { drafts.push('add ' + (TOTAL - it.marks) + ' marks to Set ' + letter(i)); }
            if (it.status === 'none') { fresh.push(letter(i)); }
            if (it.status === 'ready') { ready.push(letter(i)); } else { open.push(letter(i)); }
        });
        var todo = drafts.concat(fresh.length ? ['complete ' + setsPhrase(fresh)] : []).join(', then ');
        $('#setsNotice').toggleClass('is-warn', open.length > 0).text(open.length
            ? plural(open.length, 'set') + (open.length === 1 ? ' needs' : ' need') + ' attention. ' +
              todo.charAt(0).toUpperCase() + todo.slice(1) + ' before sending all sets for review.'
            : 'All ' + state.count + ' sets match the ' + TOTAL + '-mark blueprint and are ready for review.');

        var cur = all[k], status = STATUS[cur.status][0];
        var sections = S.sections.map(function (sec, si) {
            // Numbering runs on from the sections before this one.
            var rows = '', n = cur.have.slice(0, si).reduce(function (t, c) { return t + c; }, 0);
            cur.qs.forEach(function (q, qi) {
                if (q.s !== si) { return; }
                n++;
                rows += '<li><span class="coe-sets-qs__num">' + pad(n) + '</span>' +
                    '<span class="coe-sets-qs__text">' + esc(q.text) + '</span>' +
                    '<span class="coe-sets-qs__marks">' + sec.marks + ' marks</span>' +
                    '<button type="button" class="coe-sets-qs__edit" data-sets-edit="' + qi + '">Edit</button></li>';
            });
            return '<div class="coe-sets-section coe-sets-section--edit"><span>' + esc(sec.title) + '</span>' +
                '<span class="coe-sets-section__marks">' + (cur.have[si] * sec.marks) + ' / ' + (sec.count * sec.marks) + ' marks</span></div>' +
                '<ol class="coe-sets-qs">' + rows + '</ol>';
        }).join('');

        var gap = '';
        if (cur.open >= 0) {
            var sec = S.sections[cur.open], missing = sec.count - cur.have[cur.open];
            gap = '<div class="coe-sets-remaining"><strong>' + plural(missing, 'more ' + sec.kind + ' question') + ' needed</strong>' +
                '<span>Add ' + plural(missing, 'question') + ' × ' + sec.marks + ' marks to complete this section.</span></div>' +
                '<button type="button" class="coe-sets-link" data-sets-add="' + cur.open + '">+ Add ' + sec.kind + ' question</button>';
        }

        $('#setsMain').html(
            '<div class="coe-sets__head"><div>' +
                '<p class="coe-sets-eyebrow">Set ' + L + ' · ' + status + '</p>' +
                '<h3 class="coe-sets-panel__title">Questions for Set ' + L + '</h3>' +
                '<p class="coe-sets__text mt-1">' + cur.qs.length + ' of ' + NEEDED + ' questions • ' + cur.marks + ' of ' + TOTAL + ' marks</p>' +
            '</div><div class="coe-sets__actions">' +
                '<button type="button" class="btn coe-btn coe-btn-cancel coe-btn-cancel--brand" data-sets-bank>Question bank</button>' +
                '<button type="button" class="btn coe-btn coe-btn-primary" data-sets-add>+ Add question</button>' +
            '</div></div>' +
            sections + gap +
            '<p class="coe-sets-foot">Questions are unique to Set ' + L + '; the shared blueprint stays unchanged.</p>'
        );

        var pct = Math.round(cur.marks * 100 / TOTAL);
        var summary = [];
        if (ready.length) { summary.push(setsPhrase(ready) + (ready.length === 1 ? ' is' : ' are') + ' ready.'); }
        if (fresh.length) { summary.push(setsPhrase(fresh) + (fresh.length === 1 ? ' has' : ' have') + ' not been started.'); }
        $('#setsSide').html(
            '<h3 class="coe-sets-panel__subtitle">Set ' + L + ' checklist</h3>' +
            '<p class="coe-sets__text mt-1">Keep every set aligned to the same paper.</p>' +
            '<div class="progress coe-sets-progress" role="progressbar" aria-label="Marks added to Set ' + L + '"' +
                ' aria-valuenow="' + cur.marks + '" aria-valuemin="0" aria-valuemax="' + TOTAL + '">' +
                '<div class="progress-bar" style="width: ' + pct + '%"></div></div>' +
            '<p class="coe-sets-big">' + cur.marks + ' / ' + TOTAL + ' marks</p>' +
            '<p class="coe-sets__text">' + (TOTAL - cur.marks ? (TOTAL - cur.marks) + ' marks remaining' : 'All marks added') + '</p>' +
            '<ul class="coe-sets-check">' +
                '<li><strong>Paper header &amp; instructions</strong><span class="is-done">Shared across sets</span></li>' +
                S.sections.map(function (sec, si) {
                    var done = cur.have[si] === sec.count;
                    return '<li><strong>' + esc(sectionName(sec)) + ' · ' + sec.count + ' ' + sec.kind.replace('-', ' ') + 's</strong>' +
                        (done
                            ? '<span class="is-done">Complete · ' + (sec.count * sec.marks) + ' marks</span>'
                            : '<span>' + cur.have[si] + ' of ' + sec.count + ' added · ' + (cur.have[si] * sec.marks) + ' / ' + (sec.count * sec.marks) + ' marks</span>') +
                        '</li>';
                }).join('') +
            '</ul>' +
            '<div class="form-check mt-4">' +
                '<input class="form-check-input" type="checkbox" id="setsPreventReuse"' + (state.preventReuse ? ' checked' : '') + '>' +
                '<label class="form-check-label" for="setsPreventReuse">Prevent question reuse across sets</label></div>' +
            '<p class="coe-sets__text mt-2">A question used in one set cannot be added to another set.</p>' +
            '<div class="coe-sets-guide"><strong>Independent questions, shared structure</strong>' +
                '<p class="mb-0">Write different questions for every set. The section count, question count and marks must match the ' + TOTAL + '-mark blueprint.</p></div>' +
            '<p class="coe-sets__text mt-4">' + summary.join(' ') + '</p>'
        );

        $('#setsReady').text(ready.length + ' of ' + state.count + ' sets ready');
        $('#setsReadyNote').text(open.length ? 'Complete ' + andList(open) + ' to enable review' : 'Every set matches the ' + TOTAL + '-mark blueprint');
        $('#setsReview').prop('disabled', open.length > 0).text('Review all ' + state.count + ' sets');
    }

    function render(focusSet) {
        while (state.sets.length < state.count) { state.sets.push([]); }
        $root.attr('data-mode', state.mode);
        renderConfig();
        if (state.mode === 'shuffled') { renderShuffled(); } else { renderIndependent(); }
        if (focusSet !== undefined) { $('#setsCards [data-set="' + focusSet + '"]').trigger('focus'); }
    }

    /* ── Questions (independent mode) ── */
    function norm(t) { return String(t).replace(/\s+/g, ' ').trim().toLowerCase(); }

    // Why this text cannot go into the selected set, or '' when it can.
    function refuse(text, skip) {
        var t = norm(text);
        if (t === '') { return 'Write the question.'; }
        if (t.length > 1000) { return 'Keep the question under 1000 characters.'; }
        for (var k = 0; k < state.count; k++) {
            if (k !== state.selected && !state.preventReuse) { continue; }
            var hit = state.sets[k].some(function (q, qi) { return !(k === state.selected && qi === skip) && norm(q.text) === t; });
            if (hit) { return k === state.selected ? 'This question is already in this set.' : 'This question is already used in Set ' + letter(k) + '.'; }
        }
        return '';
    }

    function ask(title, value, skip) {
        return Swal.fire({
            title: title,
            input: 'textarea',
            inputLabel: 'Question',
            inputValue: value || '',
            showCancelButton: true,
            confirmButtonText: 'Save',
            inputValidator: function (v) { return refuse(v, skip) || undefined; }
        });
    }

    function addTo(si) {
        var cur = info(state.selected);
        if (si === undefined) { si = cur.open; }
        if (si < 0 || cur.have[si] >= S.sections[si].count) {
            Swal.fire({ icon: 'info', title: 'Set ' + letter(state.selected) + ' is complete', text: 'It already has every question the blueprint needs.' });
            return;
        }
        var sec = S.sections[si];
        ask('Add a ' + sec.kind + ' question to Set ' + letter(state.selected) + ' (' + sec.marks + ' marks)').then(function (r) {
            if (!r.isConfirmed) { return; }
            state.sets[state.selected].push({ s: si, text: r.value.replace(/\s+/g, ' ').trim() });
            dirty = true;
            render();
        });
    }

    function notWired(title) { Swal.fire({ icon: 'info', title: title, text: NOT_WIRED }); }

    /* ── Events ── */
    $('input[name="setsMode"]').on('change', function () {
        state.mode = this.value;
        var url = new URL(window.location.href);
        url.searchParams.set('mode', state.mode);
        window.history.replaceState(null, '', url);
        render();
    });

    $('[data-sets-step]').on('click', function () {
        var n = state.count + Number($(this).data('sets-step'));
        if (n < MIN || n > MAX) { return; }
        var apply = function () {
            if (n < state.count && state.mode === 'independent') { state.sets.splice(n); }
            state.count = n;
            state.selected = Math.min(state.selected, n - 1);
            render();
        };
        var dropped = state.sets[n] || [];
        if (n < state.count && state.mode === 'independent' && dropped.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Remove Set ' + letter(n) + '?',
                text: 'Its ' + plural(dropped.length, 'question') + ' will be removed.',
                showCancelButton: true,
                confirmButtonText: 'Remove',
                cancelButtonText: 'Keep it'
            }).then(function (r) { if (r.isConfirmed) { dirty = true; apply(); } });
            return;
        }
        apply();
    });

    $('#setsShuffle').on('change', function () { state.shuffle = this.checked; render(); });

    $('[data-sets-regenerate]').on('click', function () { state.seed++; render(); });

    $root.on('click', '[data-set]', function () {
        state.selected = Number($(this).data('set'));
        render(state.selected);
    });

    $root.on('click', '[data-sets-add]', function () {
        var si = $(this).attr('data-sets-add');
        addTo(si === '' ? undefined : Number(si));
    });

    $root.on('click', '[data-sets-edit]', function () {
        var qi = Number($(this).data('sets-edit'));
        var q = state.sets[state.selected][qi];
        ask('Edit a Set ' + letter(state.selected) + ' question', q.text, qi).then(function (r) {
            if (!r.isConfirmed) { return; }
            q.text = r.value.replace(/\s+/g, ' ').trim();
            dirty = true;
            render();
        });
    });

    $root.on('change', '#setsPreventReuse', function () { state.preventReuse = this.checked; });

    $('[data-sets-preview]').on('click', function () {
        var it = info(state.selected);
        Swal.fire({
            title: 'Set ' + letter(state.selected) + ' preview',
            width: 860,
            html: '<div class="coe-page coe-sets text-start">' +
                (it.qs.length ? paperHtml(it.qs) : '<p class="coe-sets__text">No questions added yet.</p>') + '</div>'
        });
    });

    $root.on('click', '[data-sets-bank]', function () {
        Swal.fire({ icon: 'info', title: 'Question bank', text: 'The question bank is not available yet.' });
    });
    $('[data-sets-draft]').on('click', function () { notWired('Save draft'); });
    $('#setsReview').on('click', function () { notWired('Review all sets'); });

    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

    render();
});
</script>
@endpush
