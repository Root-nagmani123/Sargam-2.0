{{--
    Define House — full-page Add / Edit wizard.

    ONE view serves both, so the two look alike (new-design-index-page.md §3c's
    rule, applied to the full-page form of §3d): same rail, same field cards,
    same footer pair. Only $house (null = Add) and the submit target differ.

    It posts to the SAME storeDefineHouse / updateDefineHouse endpoints the modal
    used, in the same array shape (house_no[], meter_one[], …), so no validation
    rule had to move.
--}}
@extends('admin.layouts.master')

@php
    $isEdit = ! empty($house);
    $pageTitle = $isEdit ? 'Edit Define House' : 'Add Define House';

    // Edit resolves the unit type through the sub-type when that mapping exists,
    // which is the same fallback the grid and the old modal used.
    $selCampus = old('estate_campus_master_pk', $house->estate_campus_master_pk ?? '');
    $selUnitType = old('estate_unit_type_master_pk', $house->resolved_unit_type_pk ?? ($house->estate_unit_master_pk ?? ''));
    $selBlock = old('estate_block_master_pk', $house->estate_block_master_pk ?? '');
    $selUnitSub = old('estate_unit_sub_type_master_pk', $house->estate_unit_sub_type_master_pk ?? '');

    // Status is one control over two columns: Under Renovation is a property of
    // the house (vacant_renovation_status = 0) and outranks occupancy.
    // Add opens on the placeholder, as the design shows; Edit opens on the
    // record's own state.
    $selStatus = '';
    if ($isEdit) {
        $renovation = (int) ($house->vacant_renovation_status ?? 1);
        $selStatus = $renovation === 0
            ? '0'
            : (($renovation === 2 || (int) ($house->used_home_status ?? 0) === 1) ? '2' : '1');
    }
@endphp

@section('title', $pageTitle)

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4 dhf-page">
    <x-breadcrum :title="$pageTitle" :showBack="true" :items="[
        ['label' => 'Setup', 'url' => route('admin.dashboard')],
        ['label' => 'Security Management', 'url' => null],
        ['label' => 'Estate Management', 'url' => null],
        ['label' => 'Define House', 'url' => route('admin.estate.define-house')],
        ['label' => $pageTitle, 'url' => null],
    ]" />

    <div id="dhfAlerts"></div>

    <form id="dhfForm" method="post"
        action="{{ $isEdit ? route('admin.estate.define-house.update', ['id' => $house->pk]) : route('admin.estate.define-house.store') }}">
        @csrf
        @if($isEdit)
        @method('PUT')
        @endif

        {{-- The design has no Remarks field, but updateDefineHouse writes
             `remarks` unconditionally — omitting it would blank the stored value
             on every edit. Carry it through instead of dropping it. --}}
        <input type="hidden" name="remarks" value="{{ old('remarks', $house->remarks ?? '') }}">

        <div class="ds-card">
            <div class="ds-wizard">
            {{-- Left rail: the two steps, with the finished one ticked. --}}
            <nav class="ds-wizard-rail" aria-label="Form steps">
                <ol class="ds-stepper">
                    <li class="ds-step is-active" data-step-marker="1">
                        <span class="ds-step-index" aria-hidden="true"></span>
                        <span class="ds-step-label">Basic<br>Details</span>
                    </li>
                    <li class="ds-step" data-step-marker="2">
                        <span class="ds-step-index" aria-hidden="true"></span>
                        <span class="ds-step-label">House<br>Details</span>
                    </li>
                </ol>
            </nav>

            <div class="ds-wizard-body">
                {{-- ── Step 1 — Basic Details ── --}}
                <section class="dhf-step-panel" data-step="1">
                    <h2 class="ds-form-section-title">Basic Details</h2>

                    <div class="ds-form-grid">
                        <div>
                            <label class="ds-form-label" for="estate_campus_master_pk">Estate Name<span
                                    class="ds-req">*</span></label>
                            <select class="form-select" id="estate_campus_master_pk"
                                name="estate_campus_master_pk" required>
                                <option value="">Select Estate</option>
                                @foreach($campuses ?? [] as $c)
                                <option value="{{ $c->pk }}" @selected((string) $selCampus === (string) $c->pk)>{{ $c->campus_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="ds-form-label" for="estate_unit_type_master_pk">Unit Name<span
                                    class="ds-req">*</span></label>
                            <select class="form-select" id="estate_unit_type_master_pk"
                                name="estate_unit_type_master_pk" required>
                                <option value="">Select Unit</option>
                                @foreach($unitTypes ?? [] as $ut)
                                <option value="{{ $ut->pk }}" @selected((string) $selUnitType === (string) $ut->pk)>{{ $ut->unit_type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="ds-form-label" for="estate_block_master_pk">Building Name<span
                                    class="ds-req">*</span></label>
                            <select class="form-select" id="estate_block_master_pk"
                                name="estate_block_master_pk" required>
                                <option value="">Select Building</option>
                                @foreach($blocks ?? [] as $b)
                                <option value="{{ $b->pk }}" @selected((string) $selBlock === (string) $b->pk)>{{ $b->block_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="ds-form-label" for="estate_unit_sub_type_master_pk">Unit Sub-type<span
                                    class="ds-req">*</span></label>
                            <select class="form-select" id="estate_unit_sub_type_master_pk"
                                name="estate_unit_sub_type_master_pk" required>
                                <option value="">Select Sub-type</option>
                                @foreach($unitSubTypes ?? [] as $ust)
                                <option value="{{ $ust->pk }}" @selected((string) $selUnitSub === (string) $ust->pk)>{{ $ust->unit_sub_type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="ds-form-label" for="water_charge">Water Charges<span
                                    class="ds-req">*</span></label>
                            <input type="number" class="form-control" id="water_charge" name="water_charge"
                                value="{{ old('water_charge', $isEdit ? number_format((float) ($house->water_charge ?? 0), 2, '.', '') : '0.00') }}"
                                step="0.01" min="0" required>
                        </div>

                        <div>
                            <label class="ds-form-label" for="electric_charge">Fixed Electricity Charges<span
                                    class="ds-req">*</span></label>
                            <input type="number" class="form-control" id="electric_charge"
                                name="electric_charge"
                                value="{{ old('electric_charge', $isEdit ? number_format((float) ($house->electric_charge ?? 0), 2, '.', '') : '0.00') }}"
                                step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="ds-form-footer">
                        <a href="{{ route('admin.estate.define-house') }}" class="btn ds-btn-cancel">Cancel</a>
                        <button type="button" class="btn ds-btn-primary" id="dhfNextBtn">Next</button>
                    </div>
                </section>

                {{-- ── Step 2 — House Details ── --}}
                <section class="dhf-step-panel d-none" data-step="2">
                    <div id="dhfHouseCards">
                        {{-- Card 0 is the template every clone is made from; its
                             values are blanked and the +/− visibility is derived
                             from the DOM afterwards (§3c, Repeatable field cards). --}}
                        <div class="dhf-house-card" data-house-index="0">
                            <h2 class="ds-form-section-title">House Details</h2>

                            <div class="ds-form-grid">
                                <div>
                                    <label class="ds-form-label">House Number<span class="ds-req">*</span></label>
                                    <input type="text" class="form-control dhf-house-no"
                                        name="house_no[0]" placeholder="House Number" maxlength="20"
                                        value="{{ old('house_no.0', $house->house_no ?? '') }}" required>
                                </div>

                                <div>
                                    <label class="ds-form-label">Meter Number 01<span class="ds-req">*</span></label>
                                    <input type="text" class="form-control dhf-meter-one"
                                        name="meter_one[0]" placeholder="Meter Number 01" maxlength="30"
                                        inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                                        value="{{ old('meter_one.0', $house->meter_one ?? '') }}" required>
                                </div>

                                <div>
                                    <label class="ds-form-label">Meter Number 02<span class="ds-req">*</span></label>
                                    <input type="text" class="form-control dhf-meter-two"
                                        name="meter_two[0]" placeholder="Meter Number 02" maxlength="30"
                                        inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                                        value="{{ old('meter_two.0', $house->meter_two ?? '') }}">
                                </div>

                                <div>
                                    <label class="ds-form-label">Licence Fee<span class="ds-req">*</span></label>
                                    <input type="number" class="form-control dhf-licence-fee"
                                        name="licence_fee[0]" step="0.01" min="0"
                                        value="{{ old('licence_fee.0', $isEdit ? number_format((float) ($house->licence_fee ?? 0), 2, '.', '') : '0.00') }}"
                                        required>
                                </div>

                                <div>
                                    <label class="ds-form-label">Status<span class="ds-req">*</span></label>
                                    <select class="form-select dhf-status" name="vacant_renovation_status[0]"
                                        required>
                                        <option value="">Select Status</option>
                                        <option value="1" @selected(old('vacant_renovation_status.0', $selStatus) === '1')>Vacant</option>
                                        <option value="2" @selected(old('vacant_renovation_status.0', $selStatus) === '2')>Occupied</option>
                                        <option value="0" @selected(old('vacant_renovation_status.0', $selStatus) === '0')>Under Renovation</option>
                                    </select>
                                </div>

                                {{-- The row controls sit opposite Status, as the design shows. --}}
                                <div class="dhf-row-actions">
                                    <div class="dhf-card-actions">
                                        <button type="button" class="dhf-icon-btn dhf-icon-btn--remove dhf-remove-house"
                                            title="Remove this house" aria-label="Remove this house">
                                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="dhf-icon-btn dhf-icon-btn--add dhf-add-house"
                                            title="Add another house" aria-label="Add another house">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ds-form-footer">
                        <button type="button" class="btn ds-btn-back" id="dhfPrevBtn">Previous</button>
                        <a href="{{ route('admin.estate.define-house') }}" class="btn ds-btn-cancel">Cancel</a>
                        <button type="submit" class="btn ds-btn-primary" id="dhfSaveBtn">Save</button>
                    </div>
                </section>
            </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    /* Define House add/edit wizard — page-scoped remainder only.
       The card, wizard shell, step rail, field grid, labels and footer are all
       design-system components (design.md Layer C, defined in sargam-app.css);
       what is left here is specific to this form and uses --ds-* tokens only
       (design.md usage rule 2). */
    .dhf-page .form-control,
    .dhf-page .form-select {
        min-height: var(--ds-control-h);
        border-color: var(--ds-line);
        border-radius: var(--ds-radius-1);
        color: var(--ds-ink);
        font-size: 0.875rem;
    }

    .dhf-page .form-control:focus,
    .dhf-page .form-select:focus {
        border-color: var(--ds-primary);
        box-shadow: var(--ds-focus-ring);
    }

    .dhf-page .form-control::placeholder {
        color: var(--ds-ink-muted);
    }

    /* Each repeated house is its own titled block within the step panel. */
    .dhf-page .dhf-house-card + .dhf-house-card {
        margin-top: var(--ds-space-4);
    }

    /* Row controls sit opposite Status, at the panel's right edge. */
    .dhf-page .dhf-row-actions {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
    }

    .dhf-page .dhf-card-actions {
        display: flex;
        gap: var(--ds-space-2);
        padding-bottom: 0.125rem;
    }

    .dhf-page .dhf-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.875rem;
        height: 1.875rem;
        padding: 0;
        border: 0;
        border-radius: var(--ds-radius-1);
        color: var(--ds-surface);
        font-size: 0.875rem;
        line-height: 1;
    }

    /* The row controls are the design's brighter red/blue, not the brand
       navy/maroon — they read as controls rather than as page chrome. */
    .dhf-page .dhf-icon-btn--remove {
        background: var(--ds-danger);
    }

    .dhf-page .dhf-icon-btn--add {
        background: var(--ds-accent);
    }
</style>
@endpush

@push('scripts')
{{-- Select2 JS globally footer (admin.layouts.footer) se load hoti hai. --}}
<script>
    $(function () {
        var isEdit = @json($isEdit);
        var indexUrl = @json(route('admin.estate.define-house'));

        // ── Steps ────────────────────────────────────────────────────────────
        function showStep(step) {
            $('.dhf-step-panel').addClass('d-none').filter('[data-step="' + step + '"]').removeClass('d-none');
            $('.dhf-page .ds-step').each(function () {
                var marker = parseInt($(this).attr('data-step-marker'), 10);
                $(this).toggleClass('is-active', marker === step)
                    .toggleClass('is-done', marker < step);
            });
            $('html, body').animate({ scrollTop: 0 }, 150);
        }

        // Next gates on step 1's own fields only — reportValidity() on the whole
        // form would fire on the still-hidden step 2, and the browser refuses to
        // focus a hidden invalid control (silent dead end).
        $('#dhfNextBtn').on('click', function () {
            var ok = true;
            $('.dhf-step-panel[data-step="1"]').find('input, select').each(function () {
                if (!this.checkValidity()) {
                    this.reportValidity();
                    ok = false;
                    return false;
                }
            });
            if (ok) showStep(2);
        });

        $('#dhfPrevBtn').on('click', function () {
            showStep(1);
        });

        // ── Repeatable house cards ───────────────────────────────────────────
        // Every card's state is derived from the DOM after each change, never by
        // nudging the neighbouring card (§3c).
        function syncHouseCards() {
            var $cards = $('#dhfHouseCards .dhf-house-card');
            var last = $cards.length - 1;

            $cards.each(function (index) {
                var $card = $(this);
                $card.attr('data-house-index', index);
                $card.find('.dhf-house-no').attr('name', 'house_no[' + index + ']');
                $card.find('.dhf-meter-one').attr('name', 'meter_one[' + index + ']');
                $card.find('.dhf-meter-two').attr('name', 'meter_two[' + index + ']');
                $card.find('.dhf-licence-fee').attr('name', 'licence_fee[' + index + ']');
                $card.find('.dhf-status').attr('name', 'vacant_renovation_status[' + index + ']');
                // Edit updates exactly one house, so it never shows either control.
                $card.find('.dhf-remove-house').toggle(!isEdit && $cards.length > 1);
                $card.find('.dhf-add-house').toggle(!isEdit && index === last);
            });
        }

        $(document).on('click', '.dhf-add-house', function () {
            var $cards = $('#dhfHouseCards .dhf-house-card');
            var $clone = $cards.first().clone();
            $clone.find('input').val('');
            $clone.find('.dhf-licence-fee').val('0.00');
            $clone.find('.dhf-status').val('');
            $('#dhfHouseCards').append($clone);
            syncHouseCards();
            $clone.find('.dhf-house-no').trigger('focus');
        });

        $(document).on('click', '.dhf-remove-house', function () {
            if ($('#dhfHouseCards .dhf-house-card').length <= 1) return;
            $(this).closest('.dhf-house-card').remove();
            syncHouseCards();
        });

        // Meter inputs: digits only (type / paste / drag-drop).
        $(document).on('input', '.dhf-meter-one, .dhf-meter-two', function () {
            var val = String($(this).val() || '');
            var cleaned = val.replace(/\D/g, '');
            if (val !== cleaned) $(this).val(cleaned);
        });

        syncHouseCards();

        // ── Submit ───────────────────────────────────────────────────────────
        function showFormAlert(message) {
            $('#dhfAlerts').html(
                $('<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">')
                    .append('<i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" aria-hidden="true"></i>')
                    .append($('<span class="flex-grow-1">').text(message))
                    .append('<button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>')
            );
            $('html, body').animate({ scrollTop: 0 }, 150);
        }

        $('#dhfForm').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#dhfSaveBtn');

            // Duplicates are rejected server-side too, but catching them here
            // saves a round trip and points at the offending field.
            var seen = {};
            var duplicate = null;
            $('#dhfHouseCards .dhf-house-no').each(function () {
                var key = String($(this).val() || '').trim().replace(/-+/g, '-').toUpperCase();
                if (!key) return;
                if (seen[key]) { duplicate = $(this).val(); return false; }
                seen[key] = true;
            });
            if (duplicate) {
                showFormAlert('Duplicate House Number in the form: ' + duplicate);
                return;
            }

            $btn.prop('disabled', true);
            $('#dhfAlerts').empty();

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).done(function (res) {
                if (!res || !res.success) {
                    showFormAlert((res && res.message) ? res.message : 'Failed to save.');
                    $btn.prop('disabled', false);
                    return;
                }
                var message = res.message || (isEdit ? 'Estate house updated.' : 'Estate house(s) added successfully.');
                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: message }).then(function () {
                        window.location.href = indexUrl;
                    });
                } else {
                    window.location.href = indexUrl;
                }
            }).fail(function (xhr) {
                var msg = 'Failed to save.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstKey && xhr.responseJSON.errors[firstKey] && xhr.responseJSON.errors[firstKey][0]) {
                            msg = xhr.responseJSON.errors[firstKey][0];
                        }
                    }
                }
                showFormAlert(msg);
                $btn.prop('disabled', false);
            });
        });

        // Long option lists get a searchable widget; the short ones don't need one.
        if (typeof $.fn.select2 !== 'undefined') {
            $('#estate_campus_master_pk, #estate_unit_type_master_pk, #estate_block_master_pk, #estate_unit_sub_type_master_pk')
                .select2({ width: '100%', minimumResultsForSearch: 10 });
        }
    });
</script>
@endpush
