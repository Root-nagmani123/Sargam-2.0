<?php

namespace App\Http\Requests\Admin\Member;

use App\Models\EmployeeMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreMemberStep2Request extends FormRequest
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
        // The employee_master pk of the row being edited; absent on a create.
        $empID = (string) (request()->emp_id ?? '');

        return [
            'type' => 'required|exists:employee_type_master,pk',
            'id' => array_merge(['required', 'string', 'max:50'], $this->employeeIdUniqueness($empID)),
            'group' => 'required', // |exists:employee_groups,id
            'designation' => 'required', // |exists:designations,id
            'userid' => array_merge(
                ['required', 'string', 'max:50'],
                [$this->userNameUniqueness($empID)]
            ),
            'section' => 'required|exists:department_master,pk',
        ];
    }

    /**
     * Uniqueness for the Employee ID - applied only when the value is being
     * INTRODUCED or CHANGED, never to a value that is already stored.
     *
     * employee_master.emp_id is indexed but not unique, so the database accepts
     * a duplicate silently and the check has to live here. But the table already
     * holds 216 groups of shared emp_id covering 585 rows, and an unconditional
     * rule made every one of those members unsaveable: opening such a member to
     * correct a mobile number submitted the stored Employee ID unchanged, the
     * rule matched the OTHER rows in the group, and the wizard could not advance
     * past step 2. The only way past it was to alter the identity field in order
     * to save an unrelated edit, which is worse than the duplicate.
     *
     * Comparing against the stored value keeps the guard where it belongs. A
     * create, or an edit that types a different Employee ID, is checked in full;
     * an edit that leaves it alone is not checked at all, because nothing new is
     * being introduced. Existing duplicates stay editable and no new ones can be
     * created - and the create path additionally re-checks under a row lock
     * inside the insert transaction.
     *
     * @return array<int, Unique>
     */
    private function employeeIdUniqueness(string $empID): array
    {
        $submitted = (string) (request()->id ?? '');

        if ($empID !== '') {
            $stored = EmployeeMaster::query()->where('pk', $empID)->value('emp_id');

            // Unchanged: not a new collision, so not this rule's business.
            if ($stored !== null && (string) $stored === $submitted) {
                return [];
            }
        }

        $rule = Rule::unique('employee_master', 'emp_id');

        // On update the row being edited is itself a match and must not count.
        // On create there is no such row, and `ignore('')` would compare an
        // integer key against an empty string, so the clause is added only when
        // there is a key to ignore.
        if ($empID !== '') {
            $rule->ignore($empID, 'pk');
        }

        return [$rule];
    }

    /**
     * Uniqueness for the login user name.
     *
     * Same `ignore('')` hazard as above, which this rule used to walk into: on a
     * create $empID is '', so the clause became `user_id != ''` against an
     * integer column, which MySQL evaluates by casting '' to 0. The ignore is
     * therefore added only when there is a key to ignore.
     */
    private function userNameUniqueness(string $empID): Unique
    {
        $rule = Rule::unique('user_credentials', 'user_name');

        if ($empID !== '') {
            $rule->ignore($empID, 'user_id');
        }

        return $rule;
    }

    public function messages()
    {
        return [
            'type.required' => 'Please select employee type',
            'type.exists' => 'Selected employee type is invalid',

            'id.required' => 'Employee ID is required',
            'id.max' => 'Employee ID must not exceed 50 characters',
            'id.unique' => 'This employee ID already exists',

            'group.required' => 'Please select employee group',
            'group.exists' => 'Selected employee group is invalid',

            'designation.required' => 'Please select designation',
            'designation.exists' => 'Selected designation is invalid',

            'userid.required' => 'User ID is required',
            'userid.max' => 'User ID must not exceed 50 characters',
            'userid.unique' => 'This user ID already exists',

            'section.required' => 'Please select department',
            'section.exists' => 'Selected department is invalid',
        ];
    }
}
