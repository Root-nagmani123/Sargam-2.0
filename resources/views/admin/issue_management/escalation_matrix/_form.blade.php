@php
    $isEdit = $isEdit ?? false;
@endphp

@if($isEdit)
    <input type="hidden" name="issue_category_master_pk" id="edit_category_pk" value="">
    <div class="mb-3">
        <label class="form-label">Complaint Category</label>
        <input type="text" class="form-control" id="edit_category_name" readonly style="background:#e9ecef;">
    </div>
@else
    <div class="mb-3">
        <label class="form-label">Complaint Category <span class="text-danger">*</span></label>
        <select name="issue_category_master_pk" class="form-select" required>
            <option value="">- Select Category -</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->pk }}">{{ $cat->issue_category }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-12"><h6 class="border-bottom pb-2">Level 1</h6></div>
    <div class="col-md-6">
        <label class="form-label">Employee Name (Level 1) <span class="text-danger">*</span></label>
        <select name="level1_employee_pk" id="{{ $isEdit ? 'edit_level1_employee' : 'level1_employee' }}" class="form-select" required>
            <option value="">- Select -</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->employee_pk }}">{{ $emp->employee_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">No of Days (Level 1) <span class="text-danger">*</span></label>
        <input type="number" name="level1_days" id="{{ $isEdit ? 'edit_level1_days' : 'level1_days' }}" class="form-control" min="0" value="0" required>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-12"><h6 class="border-bottom pb-2">Level 2</h6></div>
    <div class="col-md-6">
        <label class="form-label">Employee Name (Level 2) <span class="text-danger">*</span></label>
        <select name="level2_employee_pk" id="{{ $isEdit ? 'edit_level2_employee' : 'level2_employee' }}" class="form-select" required>
            <option value="">- Select -</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->employee_pk }}">{{ $emp->employee_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">No of Days (Level 2) <span class="text-danger">*</span></label>
        <input type="number" name="level2_days" id="{{ $isEdit ? 'edit_level2_days' : 'level2_days' }}" class="form-control" min="0" value="0" required>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-12"><h6 class="border-bottom pb-2">Level 3</h6></div>
    <div class="col-md-6">
        <label class="form-label">Employee Name (Level 3) <span class="text-danger">*</span></label>
        <select name="level3_employee_pk" id="{{ $isEdit ? 'edit_level3_employee' : 'level3_employee' }}" class="form-select" required>
            <option value="">- Select -</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->employee_pk }}">{{ $emp->employee_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">No of Days (Level 3) <span class="text-danger">*</span></label>
        <input type="number" name="level3_days" id="{{ $isEdit ? 'edit_level3_days' : 'level3_days' }}" class="form-control" min="0" value="0" required>
    </div>
</div>
