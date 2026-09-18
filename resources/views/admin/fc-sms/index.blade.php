@extends('admin.layouts.master')
@section('title', 'FC SMS — Bulk Send')

@push('styles')
<style>
/* =====================================================================
   FC SMS — Bulk Send — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Only what Bootstrap utilities + .ds-* can't express lives here.
   ===================================================================== */

/* --- Two-up filter grid ----------------------------------------- */
.fc-sms-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--ds-space-3);
}
@media (max-width: 767.98px) {
    .fc-sms-filter-grid { grid-template-columns: 1fr; }
}

.fc-sms-field-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ds-ink-muted);
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin-bottom: var(--ds-space-2);
    display: block;
}

/* --- Selected-template summary (accent card) -------------------- */
.fc-sms-summary { display: flex; flex-direction: column; gap: var(--ds-space-2); }
.fc-sms-summary .fc-sms-summary-name {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--ds-ink);
    line-height: 1.2;
}
.fc-sms-summary .fc-sms-summary-slug {
    display: inline-block;
    font-family: var(--bs-font-monospace, monospace);
    font-size: 0.75rem;
    color: var(--ds-primary);
    background: rgba(var(--bs-primary-rgb, 0 74 147), 0.08);
    padding: 0.1rem var(--ds-space-2);
    border-radius: var(--ds-radius-1);
}
.fc-sms-summary-date {
    display: flex;
    align-items: center;
    gap: var(--ds-space-2);
    padding-top: var(--ds-space-2);
    border-top: 1px dashed var(--ds-line);
}
.fc-sms-summary-date .label { font-size: 0.75rem; color: var(--ds-ink-muted); }
.fc-sms-summary-date .value { font-weight: 600; color: var(--ds-ink); }

/* --- Template option cards (radio-selectable) ------------------- */
.fc-sms-option {
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    padding: var(--ds-space-3);
    margin-bottom: var(--ds-space-3);
    background: var(--ds-surface);
    transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
}
.fc-sms-option:hover { border-color: var(--ds-primary); box-shadow: var(--ds-shadow-sm); }
.fc-sms-option:has(.fc-sms-template-radio:checked) {
    border-color: var(--ds-primary);
    box-shadow: var(--ds-focus-ring);
    background: rgba(var(--bs-primary-rgb, 0 74 147), 0.03);
}
.fc-sms-option .form-check-label { cursor: pointer; }
.fc-sms-option-title { font-weight: 600; color: var(--ds-ink); }
.fc-sms-option-help { font-size: 0.8125rem; color: var(--ds-ink-muted); margin-top: 2px; }
.fc-sms-code-badge {
    font-family: var(--bs-font-monospace, monospace);
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--ds-ink-muted);
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    padding: 0.05rem var(--ds-space-2);
    vertical-align: middle;
}

/* View-list toggle button footprint matches the design controls */
.fc-sms-view-btn {
    white-space: nowrap;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1);
}
.fc-sms-view-btn[aria-expanded="true"] .fc-sms-view-caret { transform: rotate(180deg); }
.fc-sms-view-caret { transition: transform .15s ease; }

/* Selection strip above each recipient table */
.fc-sms-select-strip {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--ds-space-2);
    padding: var(--ds-space-2) var(--ds-space-1);
    margin-bottom: var(--ds-space-2);
    background: var(--ds-surface-2);
    border-radius: var(--ds-radius-1);
}
.fc-sms-select-strip .count-pill {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1);
    font-size: 0.8125rem;
    color: var(--ds-ink-muted);
}
.fc-sms-select-strip .count-pill strong { color: var(--ds-primary); }

/* Recipient table wrap uses the token line + radius */
.fc-sms-table-wrap {
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    overflow: hidden;
}

/* --- Send-to choices -------------------------------------------- */
.fc-sms-sendmode {
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    padding: var(--ds-space-3);
    background: var(--ds-surface-2);
}
.fc-sms-sendmode .form-check + .form-check { margin-top: var(--ds-space-2); }

/* --- Sticky submit footer --------------------------------------- */
.fc-sms-footer {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--ds-space-3);
    margin-top: var(--ds-space-4);
    padding-top: var(--ds-space-3);
    border-top: 1px solid var(--ds-line);
}
</style>
@endpush

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="FC SMS — Bulk Send" :showBack="false"></x-breadcrum>

    {{-- Step 1 — pick the registration template ------------------------------ --}}
    <div class="ds-card mb-4">
        <div class="ds-card-header">
            <i class="bi bi-ui-checks-grid" aria-hidden="true"></i>
            <span>Registration template</span>
        </div>
        <div class="ds-card-body">
            <form method="GET" action="{{ route('fc-reg.admin.sms.index') }}" id="fcSmsTemplateFilterForm">
                @if(request()->filled('menu'))
                    <input type="hidden" name="menu" value="{{ request('menu') }}">
                @endif
                <div class="fc-sms-filter-grid">
                    <div>
                        <label for="fcSmsFormFilter" class="fc-sms-field-label">Template Name</label>
                        <select name="form_id" id="fcSmsFormFilter" class="form-select">
                            @foreach(($forms ?? []) as $form)
                                <option value="{{ (int) $form->id }}" {{ (int) ($selectedFormId ?? 0) === (int) $form->id ? 'selected' : '' }}>
                                    {{ $form->form_name }} ({{ $form->form_slug }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ds-card ds-card--accent" style="box-shadow:none;">
                        <div class="ds-card-body fc-sms-summary" style="background:var(--ds-surface);">
                            <div>
                                <div class="fc-sms-field-label mb-1">Selected Template</div>
                                <div class="fc-sms-summary-name">{{ $preview['form_name'] }}</div>
                                @if(! empty($preview['form_slug']))
                                    <span class="fc-sms-summary-slug mt-1">{{ $preview['form_slug'] }}</span>
                                @endif
                            </div>
                            <div class="fc-sms-summary-date">
                                <i class="bi bi-calendar-event text-muted" aria-hidden="true"></i>
                                <span class="label">Registration last date (B2)</span>
                                <span class="value ms-auto">{{ $preview['last_date'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Step 2 — compose + choose recipients --------------------------------- --}}
    <div class="ds-card">
        <div class="ds-card-header">
            <i class="bi bi-send" aria-hidden="true"></i>
            <span>Compose &amp; send</span>
        </div>
        <div class="ds-card-body">
            <form id="fcSmsSendForm" method="POST" action="{{ route('fc-reg.admin.sms.send') }}">
                @csrf
                <input type="hidden" name="form_id" id="fcSmsSelectedFormId" value="{{ (int) ($selectedFormId ?? 0) }}">
                @if(request()->filled('menu'))
                    <input type="hidden" name="menu" value="{{ request('menu') }}">
                @endif

                <div class="mb-4">
                    <label class="fc-sms-field-label">SMS template</label>
                    @foreach($templates as $key => $tpl)
                        @php
                            $isOpen = ($openList ?? null) === $key;
                            $tableId = 'fcSmsRecipients' . strtoupper($key);
                        @endphp
                        <div class="fc-sms-option">
                            <div class="d-flex align-items-start gap-3 flex-wrap">
                                <div class="form-check flex-grow-1 mb-0">
                                    <input class="form-check-input ms-0 me-2 fc-sms-template-radio" type="radio" name="template"
                                           id="tpl_{{ $key }}" value="{{ $key }}"
                                           {{ old('template', 'b1') === $key ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="tpl_{{ $key }}">
                                        <span class="fc-sms-option-title">{{ $tpl['label'] }}</span>
                                        <span class="fc-sms-code-badge ms-1">{{ $tpl['code'] }}</span>
                                        <div class="fc-sms-option-help">{{ $tpl['help'] }}</div>
                                    </label>
                                </div>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary fc-sms-view-btn"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#recipients_{{ $key }}"
                                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                        aria-controls="recipients_{{ $key }}">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    <span>{{ number_format($tpl['count']) }} recipient(s)</span>
                                    <i class="bi bi-chevron-down fc-sms-view-caret" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="collapse mt-3 {{ $isOpen ? 'show' : '' }}" id="recipients_{{ $key }}"
                                 data-fc-sms-template="{{ $key }}"
                                 data-fc-sms-table="{{ $tableId }}">
                                <div class="fc-sms-select-strip">
                                    <span class="count-pill">
                                        <i class="bi bi-check2-square" aria-hidden="true"></i>
                                        Selected for this template:
                                        <strong id="fcSmsSelectedCount_{{ $key }}">0</strong>
                                    </span>
                                    <button type="button" class="btn btn-link btn-sm p-0 fc-sms-clear-selection"
                                            data-template="{{ $key }}">Clear selection</button>
                                </div>
                                <div class="fc-sms-table-wrap">
                                    <table id="{{ $tableId }}"
                                           class="table table-sm table-hover mb-0 align-middle w-100"
                                           data-fc-sms-recipients="1">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:36px;">
                                                    <input type="checkbox" class="form-check-input fc-sms-page-select-all"
                                                           data-template="{{ $key }}" title="Select all on this page"
                                                           aria-label="Select all on this page">
                                                </th>
                                                <th style="width:50px;">#</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Mobile</th>
                                                @if($key === 'b1')
                                                    <th>Pending step</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mb-3">
                    <label class="fc-sms-field-label">Send to</label>
                    <div class="fc-sms-sendmode">
                        {{-- send_mode is resolved automatically from the list selection:
                             any ticked trainees => 'selected', otherwise => 'all'. --}}
                        <input type="hidden" name="send_mode" id="fcSmsSendMode" value="all">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle text-primary" style="font-size:1.05rem;line-height:1.4;" aria-hidden="true"></i>
                            <div>
                                <div id="fcSmsSendSummary" class="fw-semibold text-dark"></div>
                                <div class="text-muted small mt-1">
                                    Tick trainees in the list above to message only them. With nothing ticked,
                                    every matching recipient for the selected template is messaged.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="fcSmsPkInputs"></div>

                <div class="fc-sms-footer">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2" id="fcSmsSendBtn">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        <span id="fcSmsSendBtnLabel">Send SMS + Email</span>
                    </button>
                    <div id="fcSmsSendingStatus" class="text-muted small d-none d-inline-flex align-items-center">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        <span id="fcSmsSendingStatusText">Sending SMS and email. Please wait…</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    var recipientsUrl = @json(route('fc-reg.admin.sms.recipients'));
    var initialized = {};
    var selectedByTemplate = { b1: new Set(), b2: new Set(), b3: new Set() };
    var selectedFormId = parseInt($('#fcSmsSelectedFormId').val() || '0', 10);

    $('#fcSmsFormFilter').on('change', function () {
        selectedFormId = parseInt(this.value || '0', 10);
        $('#fcSmsSelectedFormId').val(selectedFormId);
        selectedByTemplate.b1 = new Set();
        selectedByTemplate.b2 = new Set();
        selectedByTemplate.b3 = new Set();
        initialized = {};
        $('#fcSmsTemplateFilterForm').trigger('submit');
    });

    var dtLanguage = {
        processing: 'Loading…',
        search: '',
        searchPlaceholder: 'Search name, username, mobile…',
        lengthMenu: 'Show _MENU_',
        info: 'Showing _START_–_END_ of _TOTAL_ recipients',
        infoEmpty: 'Showing 0 of 0 recipients',
        infoFiltered: '(filtered from _MAX_ total)',
        emptyTable: 'No recipients found.',
        zeroRecords: 'No matching recipients.',
        paginate: { previous: '‹', next: '›' }
    };

    function activeTemplate() {
        return $('input[name="template"]:checked').val() || 'b1';
    }

    // B3 (Travel pending) is email-only — no DLT-approved SMS template yet.
    function isEmailOnlyTemplate(template) {
        return template === 'b3';
    }

    function channelActionLabel(template) {
        return isEmailOnlyTemplate(template) ? 'Send Email' : 'Send SMS + Email';
    }

    function channelSendingLabel(template) {
        return isEmailOnlyTemplate(template)
            ? 'Sending email. Please wait…'
            : 'Sending SMS and email. Please wait…';
    }

    // The template actually driving the send: prefer whichever template has
    // ticked trainees (this is what the user visibly selected), falling back
    // to the checked radio only when nothing is ticked anywhere. This avoids
    // silently falling back to "send to all" when the radio and the ticked
    // checkboxes belong to different templates.
    function resolveSendTemplate() {
        var checkedRadio = activeTemplate();
        if ((selectedByTemplate[checkedRadio] || new Set()).size > 0) {
            return checkedRadio;
        }
        var templates = Object.keys(selectedByTemplate);
        for (var i = 0; i < templates.length; i++) {
            if (selectedByTemplate[templates[i]].size > 0) {
                return templates[i];
            }
        }
        return checkedRadio;
    }

    function updateSelectedCount(template) {
        var tpl = template || activeTemplate();
        var count = selectedByTemplate[tpl] ? selectedByTemplate[tpl].size : 0;
        $('#fcSmsSelectedCount_' + tpl).text(count);
        updateSendSummary();
    }

    // Resolve send_mode from the active template's selection:
    //   any ticked trainees -> 'selected' (message only those),
    //   nothing ticked       -> 'all' (message every matching recipient).
    function updateSendSummary() {
        var tpl = resolveSendTemplate();
        var set = selectedByTemplate[tpl] || new Set();
        var mode = set.size > 0 ? 'selected' : 'all';
        var channel = isEmailOnlyTemplate(tpl) ? 'email' : 'SMS + email';
        $('#fcSmsSendMode').val(mode);
        if (mode === 'selected') {
            $('#fcSmsSendSummary').html(
                'Sending ' + channel + ' to <strong>' + set.size + '</strong> selected trainee(s) for this template.'
            );
        } else {
            $('#fcSmsSendSummary').html(
                'No trainees ticked — sending ' + channel + ' to <strong>all</strong> matching recipients for this template.'
            );
        }
        $('#fcSmsSendBtnLabel').text(channelActionLabel(tpl));
        $('#fcSmsSendingStatusText').text(channelSendingLabel(tpl));
    }

    function syncPageCheckboxes(tableId, template) {
        var $table = $('#' + tableId);
        var set = selectedByTemplate[template] || new Set();
        $table.find('.fc-sms-recipient-pick').each(function () {
            this.checked = set.has(parseInt(this.value, 10));
        });
        var $picks = $table.find('.fc-sms-recipient-pick');
        var allChecked = $picks.length > 0 && $picks.filter(':checked').length === $picks.length;
        $table.find('.fc-sms-page-select-all').prop('checked', allChecked);
    }

    /**
     * Tick a template's radio and keep the dependent UI in step. Fires `change`
     * so the existing radio handler recalculates the selected-recipient count;
     * no-ops when the template is already the active one.
     */
    function selectTemplate(template) {
        if (!template) {
            return;
        }
        var $radio = $('#tpl_' + template);
        if (!$radio.length || $radio.prop('checked')) {
            return;
        }
        $radio.prop('checked', true).trigger('change');
    }

    function initRecipientsTable($collapse) {
        var template = $collapse.data('fc-sms-template');
        var tableId = $collapse.data('fc-sms-table');
        if (!template || !tableId || initialized[tableId]) {
            return;
        }

        var $table = $('#' + tableId);
        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            initialized[tableId] = true;
            return;
        }

        var columns = [
            { data: 'select', name: 'select', orderable: false, searchable: false, className: 'text-center', width: '36px' },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '50px' },
            { data: 'name', name: 'name' },
            { data: 'user_id', name: 'user_id', orderable: false },
            { data: 'mobile', name: 'mobile' }
        ];

        if (template === 'b1') {
            columns.push({ data: 'step_name', name: 'step_name', orderable: false });
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            ordering: false,
            autoWidth: false,
            responsive: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: "<'row align-items-center mb-2'<'col-sm-6'l><'col-sm-6'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row align-items-center mt-2'<'col-sm-6'i><'col-sm-6'p>>",
            language: dtLanguage,
            ajax: {
                url: recipientsUrl,
                type: 'GET',
                data: function (d) {
                    d.template = template;
                    d.form_id = selectedFormId;
                }
            },
            columns: columns,
            drawCallback: function () {
                syncPageCheckboxes(tableId, template);
                if (typeof adjustAllDataTables === 'function') {
                    setTimeout(adjustAllDataTables, 50);
                }
            }
        });

        initialized[tableId] = true;
    }

    $('[data-fc-sms-template]').each(function () {
        var $collapse = $(this);
        if ($collapse.hasClass('show')) {
            initRecipientsTable($collapse);
        }
        $collapse.on('shown.bs.collapse', function () {
            // Expanding a template's recipient list also selects that template.
            // Picking the radio and opening the list were two separate clicks
            // before, so it was easy to expand one template and send another.
            selectTemplate($collapse.data('fc-sms-template'));
            initRecipientsTable($collapse);
        });
    });

    $(document).on('change', '.fc-sms-recipient-pick', function () {
        var template = $(this).data('template');
        var pk = parseInt(this.value, 10);
        if (!selectedByTemplate[template]) {
            selectedByTemplate[template] = new Set();
        }
        if (this.checked) {
            selectedByTemplate[template].add(pk);
        } else {
            selectedByTemplate[template].delete(pk);
        }
        updateSelectedCount(template);
    });

    $(document).on('change', '.fc-sms-page-select-all', function () {
        var template = $(this).data('template');
        var tableId = 'fcSmsRecipients' + template.toUpperCase();
        var checked = this.checked;
        if (!selectedByTemplate[template]) {
            selectedByTemplate[template] = new Set();
        }
        $('#' + tableId + ' .fc-sms-recipient-pick').each(function () {
            this.checked = checked;
            var pk = parseInt(this.value, 10);
            if (checked) {
                selectedByTemplate[template].add(pk);
            } else {
                selectedByTemplate[template].delete(pk);
            }
        });
        updateSelectedCount(template);
    });

    $('.fc-sms-clear-selection').on('click', function () {
        var template = $(this).data('template');
        selectedByTemplate[template] = new Set();
        var tableId = 'fcSmsRecipients' + template.toUpperCase();
        $('#' + tableId + ' .fc-sms-recipient-pick').prop('checked', false);
        $('#' + tableId + ' .fc-sms-page-select-all').prop('checked', false);
        updateSelectedCount(template);
    });

    $('input[name="template"]').on('change', function () {
        updateSelectedCount(this.value);
    });

    $('#fcSmsSendForm').on('submit', function (e) {
        // Resolve which template is actually being sent, based on where the
        // ticked trainees live — not just whichever radio happens to be
        // checked — so a ticked selection can never silently degrade into
        // "send to all" because the radio drifted to a different template.
        var template = resolveSendTemplate();
        var set = selectedByTemplate[template] || new Set();
        var mode = set.size > 0 ? 'selected' : 'all';
        var $pkWrap = $('#fcSmsPkInputs');
        var $btn = $('#fcSmsSendBtn');
        var $status = $('#fcSmsSendingStatus');
        $('#fcSmsSendMode').val(mode);
        $pkWrap.empty();

        if (template !== activeTemplate()) {
            selectTemplate(template);
        }

        var actionLabel = channelActionLabel(template);
        if (mode === 'selected') {
            set.forEach(function (pk) {
                $('<input>', { type: 'hidden', name: 'registration_pks[]', value: pk }).appendTo($pkWrap);
            });
            if (!confirm(actionLabel + ' to ' + set.size + ' selected trainee(s)?')) {
                e.preventDefault();
                return false;
            }
        } else if (!confirm(actionLabel + ' to ALL matching trainees for the selected template? This can take time for large lists.')) {
            e.preventDefault();
            return false;
        }

        $btn.prop('disabled', true);
        $status.removeClass('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return true;
    });

    updateSelectedCount(activeTemplate());
});
</script>

{{-- Flash messages use the app-standard SweetAlert design
     (icon:'success' renders as the global top-right toast card). --}}
@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: @json(session('success')),
            timer: 3000,
            showConfirmButton: false,
        });
    });
</script>
@endif
@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') return;
        Swal.fire('Error', @json(session('error')), 'error');
    });
</script>
@endif
@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            icon: 'error',
            title: 'Please fix the following',
            html: '<ul class="text-start mb-0 ps-3">' + @json(
                    collect($errors->all())->map(fn ($e) => '<li>' . e($e) . '</li>')->implode('')
                ) + '</ul>',
        });
    });
</script>
@endif
@endpush
