@extends('admin.layouts.master')

@section('title', 'Add Approved Request House - Sargam')

{{-- Design: docs/design.md — .ds-card, .ds-form-label / .ds-req, .ds-form-footer,
     .ds-btn-primary / .ds-btn-cancel; shared Estate Master styles + the dual-list
     block below. Behaviour unchanged: picking an approver reloads with ?approver=,
     and every option left in "To" is submitted. --}}
@section('setup_content')
<div class="container-fluid em-page">
    <x-breadcrum title="Add Approved Request House" />

    <x-session_message />

    <section class="card ds-card" aria-labelledby="emFormTitle">
        <div class="ds-card-header">
            <h2 class="em-card-title" id="emFormTitle">Add Approved Request House</h2>
        </div>
        <div class="ds-card-body">
            <form action="{{ route('admin.estate.store-approved-request-house') }}" method="POST" id="formApprovedRequestHouse">
                @csrf

                <div class="em-form-narrow mb-4">
                    <label for="approver_pk" class="ds-form-label">Request House Approved/Forward By<span class="ds-req" aria-hidden="true">*</span></label>
                    <select name="approver_pk" id="approver_pk" class="form-select @error('approver_pk') is-invalid @enderror" required aria-required="true">
                        <option value="">Select approver</option>
                        @foreach($approvers as $pk => $name)
                            <option value="{{ $pk }}" {{ (int) $selectedApproverPk === (int) $pk ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('approver_pk')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="source_type" id="source_employee" value="employee" checked>
                    <label class="form-check-label" for="source_employee">Employee</label>
                </div>

                <div class="arh-dual">
                    <div>
                        <label for="from_list" class="ds-form-label">From<span class="ds-req" aria-hidden="true">*</span></label>
                        <select id="from_list" class="form-select arh-list" multiple size="12">
                            @foreach($allEmployees as $emp)
                                @php $empPk = $emp->{$empPkCol ?? 'pk'}; @endphp
                                @if(!$selectedPks->contains($empPk))
                                    <option value="{{ $empPk }}">{{ trim($emp->first_name . ' ' . $emp->last_name) ?: ('ID ' . $empPk) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="arh-move" role="group" aria-label="Move employees">
                        <button type="button" class="btn em-btn-utility" id="btn_move_right" title="Move to To list" aria-label="Move selected to To list">
                            <i class="bi bi-chevron-double-right" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn em-btn-utility" id="btn_move_left" title="Move to From list" aria-label="Move selected to From list">
                            <i class="bi bi-chevron-double-left" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div>
                        <label for="to_list" class="ds-form-label">To<span class="ds-req" aria-hidden="true">*</span></label>
                        <select id="to_list" class="form-select arh-list" multiple size="12" name="employee_pks[]">
                            @foreach($allEmployees as $emp)
                                @php $empPk = $emp->{$empPkCol ?? 'pk'}; @endphp
                                @if($selectedPks->contains($empPk))
                                    <option value="{{ $empPk }}">{{ trim($emp->first_name . ' ' . $emp->last_name) ?: ('ID ' . $empPk) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ds-form-footer">
                    <a href="{{ route('admin.estate.estate-approval-setting') }}" class="btn ds-btn-cancel">Cancel</a>
                    <button type="submit" class="btn ds-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('styles')
@include('admin.estate.partials.master_styles')
<style>
    .em-page .arh-dual {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        gap: var(--ds-space-3);
        align-items: center;
    }

    .em-page .arh-list {
        min-height: 17.5rem;
        font-size: 0.875rem;
        color: var(--ds-ink);
        border-color: var(--ds-line);
        border-radius: var(--ds-radius);
        background-image: none;
    }

    .em-page .arh-list:focus {
        border-color: var(--ds-primary);
        box-shadow: var(--ds-focus-ring);
    }

    .em-page .arh-move {
        display: flex;
        flex-direction: column;
        gap: var(--ds-space-2);
    }

    .em-page #approver_pk {
        min-height: var(--ds-control-h);
        border-color: var(--ds-line);
        border-radius: var(--ds-radius);
    }

    @media (max-width: 767.98px) {
        .em-page .arh-dual { grid-template-columns: minmax(0, 1fr); }
        .em-page .arh-move { flex-direction: row; justify-content: center; }
        .em-page .arh-move .bi { display: inline-block; transform: rotate(90deg); }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var fromList = document.getElementById('from_list');
    var toList = document.getElementById('to_list');
    var btnRight = document.getElementById('btn_move_right');
    var btnLeft = document.getElementById('btn_move_left');
    var approverSelect = document.getElementById('approver_pk');

    function moveSelected(from, to) {
        var options = Array.from(from.selectedOptions);
        options.forEach(function(opt) {
            to.appendChild(opt);
        });
    }

    btnRight.addEventListener('click', function() {
        moveSelected(fromList, toList);
    });
    btnLeft.addEventListener('click', function() {
        moveSelected(toList, fromList);
    });

    // Reload page with selected approver so "To" list is loaded for that approver
    if (approverSelect) {
        approverSelect.addEventListener('change', function() {
            var v = this.value;
            if (v) {
                window.location.href = '{{ url()->current() }}?approver=' + encodeURIComponent(v);
            } else {
                window.location.href = '{{ route('admin.estate.add-approved-request-house') }}';
            }
        });
    }

    document.getElementById('formApprovedRequestHouse').addEventListener('submit', function() {
        Array.from(toList.options).forEach(function(opt) {
            opt.selected = true;
        });
    });
});
</script>
@endpush
