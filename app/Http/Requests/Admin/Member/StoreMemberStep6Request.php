<?php

namespace App\Http\Requests\Admin\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;

class StoreMemberStep6Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // PR #319 review round 2 (F-003). employee_category_master is created by a
        // migration this same PR ships. An exists: rule against a table that does not
        // exist yet raises SQLSTATE 42S02 from inside the validator, which is a 500 on
        // the whole member save rather than a field-level error — on an environment
        // where code is deployed ahead of `php artisan migrate`. When the table is
        // absent the field is dropped from the payload by the controller anyway
        // (MemberController::step6SchemaIsReady()), so validating it buys nothing.
        //
        // Memoised per request. rules() is called once per step-validation AJAX call and
        // again when combinedMemberRules() merges all six step requests on save, so an
        // unmemoised Schema::hasTable() here is several information_schema round trips
        // per wizard run — the request-path schema introspection AUTO-07 exists to flag,
        // and it fired on this exact line before the static was added.
        static $employeeCategoryTableExists = null;

        if ($employeeCategoryTableExists === null) {
            $employeeCategoryTableExists = Schema::hasTable('employee_category_master');
        }

        $employeeCategoryRule = $employeeCategoryTableExists
            ? ['nullable', 'exists:employee_category_master,pk']
            : ['nullable'];

        return [
            'gradepay'         => ['nullable', 'exists:salary_grade_master,pk'],
            'employeecategory' => $employeeCategoryRule,
            // basic_pay is decimal(15,2), matching every other money column on
            // payroll_salary_master, so `numeric` rather than `integer`: the column can
            // hold paise and the rule must not reject what the column accepts. min:0
            // rejects a negative; 1,00,00,000 is far above any monthly basic pay here, so
            // a mistyped extra digit is caught at the form instead of stored.
            //
            // Whether basic pay is ever fractional IN PRACTICE is a domain question and
            // is still open — the Product owner's confirmation is owed. Decimal holds
            // every integer, so neither answer produces a wrong stored value; if pay is
            // integral-only the residual is just that the form accepts paise.
            //
            // (An earlier version of this block carried two contradictory comments, one
            // saying the precision question was NOT settled here and one describing it as
            // settled. The independent review raised that as F-040; the stale half is
            // removed rather than reconciled, because only this reading is true.)
            'basicpay'         => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'bankname'         => ['nullable', 'string', 'max:100'],
            'accountno'        => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'gradepay.exists'         => 'The selected grade pay is invalid.',
            'employeecategory.exists' => 'The selected employee category is invalid.',
            'basicpay.numeric'        => 'Basic pay must be a valid amount.',
            'basicpay.min'            => 'Basic pay cannot be negative.',
            'basicpay.max'            => 'Basic pay must not exceed 1,00,00,000.',
            'bankname.max'            => 'Bank name must not exceed 100 characters.',
            'accountno.max'           => 'Account number must not exceed 50 characters.',
        ];
    }
}
