@extends('admin.layouts.master')
@section('title', 'FC SMS — Bulk Send')

@section('setup_content')
<div class="container-fluid py-3">

    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <h4 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>FC SMS — Bulk Send</h4>
        <span class="badge bg-secondary">Admin</span>
    </div>

    <p class="text-muted small mb-3">
        Recipients are limited to the selected template's linked course
        (same as Registration Master Course filter, Active tab).
        Choose a template and send. Lists are different:
        <strong>Form step incomplete</strong> = started submitting the form (1+ step done) but still pending;
        <strong>Registration pending</strong> = registration not completed and form not started yet.
        Each send goes as <strong>SMS + Email</strong> (same trigger). Open a list to search, pick trainees, and send to
        <strong>all</strong> or <strong>selected only</strong>.
        @if(strtolower((string) config('gupshup.driver')) === 'log')
            <span class="text-danger fw-semibold">SMS_DRIVER=log — SMS goes to laravel.log only, not to phones.</span>
        @endif
    </p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show fc-sms-flash" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show fc-sms-flash" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('fc-reg.admin.sms.index') }}" id="fcSmsTemplateFilterForm" class="row g-3 mb-3">
        @if(request()->filled('menu'))
            <input type="hidden" name="menu" value="{{ request('menu') }}">
        @endif
        <div class="col-md-6">
            <div class="border rounded-3 p-3 bg-light h-100">
                <label for="fcSmsFormFilter" class="text-muted small mb-2 d-block">Template Name</label>
                <select name="form_id" id="fcSmsFormFilter" class="form-select form-select-sm">
                    @foreach(($forms ?? []) as $form)
                        <option value="{{ (int) $form->id }}" {{ (int) ($selectedFormId ?? 0) === (int) $form->id ? 'selected' : '' }}>
                            {{ $form->form_name }} ({{ $form->form_slug }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded-3 p-3 bg-light h-100">
                <div class="text-muted small">Selected Template</div>
                <div class="fw-semibold">{{ $preview['form_name'] }}</div>
                @if(! empty($preview['form_slug']))
                    <div class="text-muted small mt-1"><code>{{ $preview['form_slug'] }}</code></div>
                @endif
                <hr class="my-2">
                <div class="text-muted small">Registration last date (B2)</div>
                <div class="fw-semibold">{{ $preview['last_date'] }}</div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form id="fcSmsSendForm" method="POST" action="{{ route('fc-reg.admin.sms.send') }}">
                @csrf
                <input type="hidden" name="form_id" id="fcSmsSelectedFormId" value="{{ (int) ($selectedFormId ?? 0) }}">
                @if(request()->filled('menu'))
                    <input type="hidden" name="menu" value="{{ request('menu') }}">
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold">SMS template</label>
                    @foreach($templates as $key => $tpl)
                        @php
                            $isOpen = ($openList ?? null) === $key;
                            $tableId = 'fcSmsRecipients' . strtoupper($key);
                        @endphp
                        <div class="border rounded-3 p-3 mb-2">
                            <div class="d-flex align-items-start gap-2 flex-wrap">
                                <div class="form-check flex-grow-1 mb-0">
                                    <input class="form-check-input ms-0 me-2 fc-sms-template-radio" type="radio" name="template"
                                           id="tpl_{{ $key }}" value="{{ $key }}"
                                           {{ old('template', 'b1') === $key ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="tpl_{{ $key }}">
                                        <span class="fw-semibold">{{ $tpl['label'] }}</span>
                                        <span class="badge bg-light text-dark border ms-1">{{ $tpl['code'] }}</span>
                                        <div class="text-muted small mt-1">{{ $tpl['help'] }}</div>
                                    </label>
                                </div>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#recipients_{{ $key }}"
                                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                        aria-controls="recipients_{{ $key }}">
                                    {{ number_format($tpl['count']) }} recipient(s) — view list
                                </button>
                            </div>

                            <div class="collapse mt-3 {{ $isOpen ? 'show' : '' }}" id="recipients_{{ $key }}"
                                 data-fc-sms-template="{{ $key }}"
                                 data-fc-sms-table="{{ $tableId }}">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 px-1">
                                    <span class="text-muted small">
                                        Selected for this template:
                                        <strong id="fcSmsSelectedCount_{{ $key }}">0</strong>
                                    </span>
                                    <button type="button" class="btn btn-link btn-sm p-0 fc-sms-clear-selection"
                                            data-template="{{ $key }}">Clear selection</button>
                                </div>
                                <div class="table-responsive border rounded">
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

                <div class="border rounded-3 p-3 mb-3 bg-light">
                    <label class="form-label fw-semibold mb-2">Send to</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="send_mode" id="send_mode_all"
                               value="all" {{ old('send_mode', 'all') === 'all' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="send_mode_all">
                            All matching recipients for the selected template
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="send_mode" id="send_mode_selected"
                               value="selected" {{ old('send_mode') === 'selected' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="send_mode_selected">
                            Selected trainees only <span class="text-muted">(tick checkboxes in the list above)</span>
                        </label>
                    </div>
                </div>

                <div id="fcSmsPkInputs"></div>

                <button type="submit" class="btn btn-primary" id="fcSmsSendBtn">
                    <i class="bi bi-send me-1"></i>Send SMS + Email
                </button>
                <div id="fcSmsSendingStatus" class="text-muted small mt-2 d-none">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Sending SMS and email. Please wait…
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
    var selectedByTemplate = { b1: new Set(), b2: new Set() };
    var selectedFormId = parseInt($('#fcSmsSelectedFormId').val() || '0', 10);

    $('#fcSmsFormFilter').on('change', function () {
        selectedFormId = parseInt(this.value || '0', 10);
        $('#fcSmsSelectedFormId').val(selectedFormId);
        selectedByTemplate.b1 = new Set();
        selectedByTemplate.b2 = new Set();
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

    function updateSelectedCount(template) {
        var tpl = template || activeTemplate();
        var count = selectedByTemplate[tpl] ? selectedByTemplate[tpl].size : 0;
        $('#fcSmsSelectedCount_' + tpl).text(count);
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
            serverSide: false,
            searching: true,
            ordering: true,
            order: [[2, 'asc']],
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
        var mode = $('input[name="send_mode"]:checked').val();
        var template = activeTemplate();
        var $pkWrap = $('#fcSmsPkInputs');
        var $btn = $('#fcSmsSendBtn');
        var $status = $('#fcSmsSendingStatus');
        $pkWrap.empty();

        if (mode === 'selected') {
            var set = selectedByTemplate[template] || new Set();
            if (set.size === 0) {
                e.preventDefault();
                alert('Please select at least one trainee from the list (use the checkboxes).');
                return false;
            }
            set.forEach(function (pk) {
                $('<input>', { type: 'hidden', name: 'registration_pks[]', value: pk }).appendTo($pkWrap);
            });
            if (!confirm('Send SMS + Email to ' + set.size + ' selected trainee(s)?')) {
                e.preventDefault();
                return false;
            }
        } else if (!confirm('Send SMS + Email to ALL matching trainees for the selected template? This can take time for large lists.')) {
            e.preventDefault();
            return false;
        }

        $btn.prop('disabled', true);
        $status.removeClass('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return true;
    });

    var $flash = $('.fc-sms-flash').first();
    if ($flash.length) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    updateSelectedCount(activeTemplate());
});
</script>
@endpush
