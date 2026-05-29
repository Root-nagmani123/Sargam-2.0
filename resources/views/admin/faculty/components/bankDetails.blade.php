<div>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <x-input name="bankname" label="Bank Name :" placeholder="Bank Name" formLabelClass="form-label"
                 value="{{ $faculty->bank_name }}" />

        </div>
        <div class="col-12 col-md-6">

            <x-input type="text" name="accountnumber" label="Account Number :" placeholder="Account Number"
                formLabelClass="form-label"  value="{{ $faculty->Account_No }}"
                formInputClass="only-numbers" />

        </div>
        <div class="col-12 col-md-6">

            <x-input name="ifsccode" label="IFSC Code :" placeholder="IFSC Code" formLabelClass="form-label"
                 value="{{ $faculty->IFSC_Code }}" />

        </div>
        <div class="col-12 col-md-6">

            <x-input type="text" name="pannumber" label="PAN Number :" placeholder="PAN Number"
                formLabelClass="form-label"  value="{{ $faculty->PAN_No }}" />
        </div>
    </div>
</div>