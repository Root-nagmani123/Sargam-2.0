<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="gradepay" label="Grade Pay :" formLabelClass="form-label" formSelectClass="form-select" :options="$gradePayOptions ?? []" :value="old('gradepay', $payrollSalary->salary_grade_pk ?? '')" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-select name="employeecategory" label="Employee Category :" formLabelClass="form-label" formSelectClass="form-select" :options="$employeeCategoryOptions ?? []" :value="old('employeecategory', $payrollSalary->employee_category_master_pk ?? '')" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input type="number" name="basicpay" formLabelClass="form-label" formInputClass="form-control" label="Basic Pay :" id="basicpay" value="{{ old('basicpay', $payrollSalary->basic_pay ?? '') }}" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="bankname" formLabelClass="form-label" formInputClass="form-control" label="Bank Name :" id="bankname" value="{{ old('bankname', $payrollSalary->bank_name ?? '') }}" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="accountno" formLabelClass="form-label" formInputClass="form-control" label="Account No :" id="accountno" value="{{ old('accountno', $payrollSalary->account_no ?? '') }}" />
        </div>
    </div>
</div>
