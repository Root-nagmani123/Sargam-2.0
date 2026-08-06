@php
    $isEdit = $isEdit ?? false;
@endphp

@if($isEdit)
    <input type="hidden" name="issue_category_master_pk" id="edit_category_pk" value="">
    <div class="mb-3">
        <label for="edit_category_name" class="ic-form-label">Complaint Category<span class="ic-req">*</span></label>
        <input type="text" class="form-control ic-control" id="edit_category_name" readonly>
    </div>
@else
    <div class="mb-3">
        <label for="add_category_pk" class="ic-form-label">Complaint Category<span class="ic-req">*</span></label>
        <select name="issue_category_master_pk" id="add_category_pk" class="form-select ic-control" required>
            <option value="">Select Category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->pk }}">{{ $cat->issue_category }}</option>
            @endforeach
        </select>
    </div>
@endif

{{-- Three identical level blocks: heading + rule, then employee / days side by side.
     The ids are what the page's level-exclusion JS binds to — keep them. --}}
@foreach([1, 2, 3] as $n)
    @php
        $empId = ($isEdit ? 'edit_' : '') . 'level' . $n . '_employee';
        $daysId = ($isEdit ? 'edit_' : '') . 'level' . $n . '_days';
    @endphp
    <h6 class="ic-level-heading">Level {{ $n }}</h6>
    <div class="row g-3 {{ $n === 3 ? 'mb-0' : 'mb-3' }}">
        <div class="col-md-6">
            <label for="{{ $empId }}" class="ic-form-label">Employee Name (Level {{ $n }})<span class="ic-req">*</span></label>
            {{-- Options are filled from the single `escalationEmployees` array on
                 modal open. Rendering all ~1.8k employees into six selects put
                 11k <option> elements (≈1.3MB) on every page load. --}}
            <select name="level{{ $n }}_employee_pk" id="{{ $empId }}"
                    class="form-select ic-control ic-employee-select" required>
                <option value="">Select Employee</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="{{ $daysId }}" class="ic-form-label">Number of days (Level {{ $n }})<span class="ic-req">*</span></label>
            <input type="number" name="level{{ $n }}_days" id="{{ $daysId }}"
                   class="form-control ic-control" min="0" placeholder="e.g. {{ $n }}" required>
        </div>
    </div>
@endforeach
