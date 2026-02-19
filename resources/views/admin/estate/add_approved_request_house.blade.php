@extends('admin.layouts.master')

@section('title', 'Add Approved Request House - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Add Approved Request House" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-dark mb-1">Add Approved Request House</h1>
            <p class="text-muted small mb-4">Please add the Approved Request House.</p>

            <form action="{{ route('admin.estate.store-approved-request-house') }}" method="POST" id="formApprovedRequestHouse">
                @csrf

                <div class="row align-items-center gap-2 mb-4">
                    <label for="approver_pk" class="col-auto col-form-label">Request House Approved/Forward By</label>
                    <div class="col-auto d-flex align-items-center gap-2">
                        <select name="approver_pk" id="approver_pk" class="form-select form-select-md" style="min-width: 220px;" required>
                            <option value="">— Select —</option>
                            @foreach($approvers as $pk => $name)
                                <option value="{{ $pk }}" {{ (int) $selectedApproverPk === (int) $pk ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="source_type" id="source_employee" value="employee" checked>
                    <label class="form-check-label" for="source_employee">Employee</label>
                </div>

                <div class="row g-3 align-items-start">
                    <div class="col-12 col-md-5">
                        <label for="from_list" class="form-label">From <span class="text-danger">*</span></label>
                        <select id="from_list" class="form-select form-select-lg" multiple size="12" style="min-height: 280px;">
                            @foreach($allEmployees as $emp)
                                @if(!$selectedPks->contains($emp->pk))
                                    <option value="{{ $emp->pk }}">{{ trim($emp->first_name . ' ' . $emp->last_name) ?: ('ID ' . $emp->pk) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex flex-column justify-content-center align-items-center gap-2 py-4">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btn_move_right" title="Move to To list">
                            <i class="bi bi-chevron-double-right"></i> &gt;&gt;
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100" id="btn_move_left" title="Move to From list">
                            <i class="bi bi-chevron-double-left"></i> &lt;&lt;
                        </button>
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="to_list" class="form-label">To <span class="text-danger">*</span></label>
                        <select id="to_list" class="form-select form-select-lg" multiple size="12" name="employee_pks[]" style="min-height: 280px;">
                            @foreach($allEmployees as $emp)
                                @if($selectedPks->contains($emp->pk))
                                    <option value="{{ $emp->pk }}">{{ trim($emp->first_name . ' ' . $emp->last_name) ?: ('ID ' . $emp->pk) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.estate-approval-setting') }}" class="btn btn-outline-danger">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

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
@endsection
