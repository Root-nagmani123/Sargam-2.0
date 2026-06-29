<?php

namespace App\Support\FC;

/**
 * Registry of fillable joining-document form templates (schema-driven).
 *
 * Each template is a schema describing the form: header sections (scalar
 * fields), optional repeatable tables, an optional declaration/undertaking,
 * optional place/date footer, and signature captions for the PDF.
 *
 * Two generic blade views render ANY template:
 *   - fc.registration.document-forms.generic        (on-screen fillable form)
 *   - fc.registration.document-forms.pdf.generic    (printable PDF)
 *
 * Validation rules and the normalised stored shape are derived from the schema
 * automatically — so adding a new fillable form is just adding a schema entry
 * here and setting the document field's "Fillable Form Template" in Form Builder.
 *
 * Field shape:  ['name'=>'officer_name','label'=>'Name / नाम','type'=>'text',
 *                'required'=>true,'width'=>'col-md-6','options'=>[...]]
 *   type: text | textarea | date | number | email | select
 * Table shape:  ['key'=>'nominees','heading'=>'...','intro'=>'...','columns'=>[ <field>, ... ]]
 *
 * Optional presentational keys (rendered by both blades, no data impact):
 *   template['intro']   — preamble paragraph under the title
 *   template['notes']   — array of footnote strings rendered at the bottom
 *   section['intro']    — text under a section heading
 *   table['intro']      — text under a table heading
 */
class DocumentFormTemplates
{
    /**
     * @return array<string,array>
     */
    public static function all(): array
    {
        return [
            'family_details'      => self::familyDetails(),
            'group_insurance'     => self::groupInsurance(),
            'nps_subscription'    => self::npsSubscription(),
            'employee_info_sheet' => self::employeeInfoSheet(),
            'debts_liabilities'   => self::debtsLiabilities(),
            'immovable_property'  => self::immovableProperty(),
            'movable_property'    => self::movableProperty(),
            'close_relation'      => self::closeRelation(),
            'dowry_declaration'   => self::dowryDeclaration(),
            'home_town'           => self::homeTown(),
            'marital_status'      => self::maritalStatus(),
            'oath_affirmation'    => self::oathAffirmation(),
            'surety_bond_ias'     => self::suretyBondIas(),
            'surety_bond_others'  => self::suretyBondOthers(),
            'assumption_charge'   => self::assumptionCharge(),
            'police_verification' => self::policeVerification(),
        ];
    }

    public static function get(?string $key): ?array
    {
        if (! $key) {
            return null;
        }

        return self::all()[$key] ?? null;
    }

    public static function exists(?string $key): bool
    {
        return self::get($key) !== null;
    }

    /** @return array<string,string> key => title ('' => none) for the admin dropdown */
    public static function options(): array
    {
        $out = ['' => '— None (normal file upload) —'];
        foreach (self::all() as $key => $tpl) {
            $out[$key] = $tpl['title'];
        }

        return $out;
    }

    /** The on-screen blade view (allows per-template override; defaults to generic). */
    public static function formView(array $tpl): string
    {
        return $tpl['form_view'] ?? 'fc.registration.document-forms.generic';
    }

    /** The PDF blade view (allows per-template override; defaults to generic). */
    public static function pdfView(array $tpl): string
    {
        return $tpl['pdf_view'] ?? 'fc.registration.document-forms.pdf.generic';
    }

    // ── Validation + normalisation (derived from schema) ─────────────────────

    /** @return array<string,mixed> */
    public static function rules(string $key): array
    {
        $tpl = self::get($key);
        if (! $tpl) {
            return [];
        }

        $rules = [];
        foreach (self::scalarFields($tpl) as $f) {
            $required = ! empty($f['required']) ? 'required' : 'nullable';
            $rules[$f['name']] = $required.'|'.self::typeRule($f['type'] ?? 'text');
        }
        foreach ($tpl['tables'] ?? [] as $tbl) {
            foreach ($tbl['columns'] as $c) {
                $rules[$tbl['key'].'.'.$c['name']]      = 'nullable|array';
                $rules[$tbl['key'].'.'.$c['name'].'.*'] = 'nullable|'.self::typeRule($c['type'] ?? 'text');
            }
        }

        return $rules;
    }

    /**
     * Normalise validated input into the JSON-stored / PDF-passed structure.
     * Scalars become top-level keys; table rows go under `_tables[<key>]`.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public static function normalize(string $key, array $data): array
    {
        $tpl = self::get($key);
        if (! $tpl) {
            return $data;
        }

        $out = [];
        foreach (self::scalarFields($tpl) as $f) {
            $out[$f['name']] = $data[$f['name']] ?? null;
        }

        $tables = [];
        foreach ($tpl['tables'] ?? [] as $tbl) {
            $raw  = $data[$tbl['key']] ?? [];
            $cols = array_column($tbl['columns'], 'name');
            $count = 0;
            foreach ($cols as $cn) {
                $count = max($count, is_array($raw[$cn] ?? null) ? count($raw[$cn]) : 0);
            }
            $rows = [];
            for ($i = 0; $i < $count; $i++) {
                $row = [];
                $empty = true;
                foreach ($cols as $cn) {
                    $v = trim((string) ($raw[$cn][$i] ?? ''));
                    $row[$cn] = $v;
                    if ($v !== '') {
                        $empty = false;
                    }
                }
                if (! $empty) {
                    $rows[] = $row;
                }
            }
            $tables[$tbl['key']] = $rows;
        }
        $out['_tables'] = $tables;

        return $out;
    }

    /** Flatten every scalar field across header + footer sections. @return array<int,array> */
    public static function scalarFields(array $tpl): array
    {
        $fields = [];
        foreach (array_merge($tpl['sections'] ?? [], $tpl['sections_footer'] ?? []) as $section) {
            foreach ($section['fields'] ?? [] as $f) {
                $fields[] = $f;
            }
        }

        return $fields;
    }

    private static function typeRule(string $type): string
    {
        return match ($type) {
            'date'     => 'date',
            'number'   => 'numeric',
            'email'    => 'email|max:255',
            'textarea' => 'string|max:5000',
            'select'   => 'string|max:255',
            default    => 'string|max:1000',
        };
    }

    // ── Reusable field fragments ─────────────────────────────────────────────

    private static function placeDateSection(): array
    {
        return [
            'heading' => 'Place &amp; Date / स्थान और तिथि',
            'fields'  => [
                ['name' => 'place', 'label' => 'Place / स्थान', 'type' => 'text', 'width' => 'col-md-6'],
                ['name' => 'declaration_date', 'label' => 'Date / तदनांक', 'type' => 'date', 'width' => 'col-md-6'],
            ],
        ];
    }

    /** Date-only footer (several official forms have a "Dated" line but no "Place"). */
    private static function dateOnlySection(): array
    {
        return [
            'heading' => 'Date / दिनांक',
            'fields'  => [
                ['name' => 'declaration_date', 'label' => 'Date / दिनांक', 'type' => 'date', 'width' => 'col-md-4'],
            ],
        ];
    }

    private static function officerSection(): array
    {
        return [
            'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
            'fields'  => [
                ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
            ],
        ];
    }

    // ── Template schemas ─────────────────────────────────────────────────────

    private static function familyDetails(): array
    {
        return [
            'key'      => 'family_details',
            'title'    => 'Form No. 3: Details of Family',
            'subtitle' => '[See Rule 54 (12) of CCS (Pension) Rules, 1972]',
            'title_hi' => 'फ़ॉर्म सं. 3 : परिवार का विवरण',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of the Government Servant / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth / जन्म तिथि', 'type' => 'date', 'width' => 'col-md-6'],
                        ['name' => 'details_as_on', 'label' => 'Details as on Date / तदनांक के विवरण', 'type' => 'date', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'members',
                    'heading' => 'Family Members / परिवार के सदस्य',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name / नाम', 'type' => 'text'],
                        ['name' => 'dob', 'label' => 'Date of Birth / जन्म तिथि', 'type' => 'date'],
                        ['name' => 'relationship', 'label' => 'Relationship / संबंध', 'type' => 'text'],
                        ['name' => 'marital_status', 'label' => 'Marital Status / वैवाहिक स्थिति', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks / टिप्पणी', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby undertake to keep the above particulars up-to-date by notifying to the Head of the office any addition or alteration. / मैं एतद्द्वारा किसी भी परिवर्तन या परिवर्धन के बारे में कार्यालय प्रमुख को सूचित करके उपर्युक्त विवरणों को अद्यतन रखने का वचन देता/देती हूँ।',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर', 'Dated Signature of Head of Office / कार्यालय प्रमुख के दिनांकित हस्ताक्षर'],
        ];
    }

    private static function groupInsurance(): array
    {
        return [
            'key'      => 'group_insurance',
            'title'    => 'Nomination — Central Government Employees Group Insurance Scheme, 1980',
            'subtitle' => 'Form 7 (member without family) / Form 8 (member with family) — See Para 19.7',
            'intro'    => 'Use <strong>Form 7</strong> if you have <strong>no family</strong> (nomination may be in favour of any person). Use <strong>Form 8</strong> if you <strong>have a family</strong> (nomination must be in favour of one or more members of the family). Indicate which applies below.',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'form_variant', 'label' => 'Applicable Form', 'type' => 'select', 'options' => ['Form 7 — Member without family', 'Form 8 — Member with family'], 'required' => true, 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'nominees',
                    'heading' => 'Nomination / नामांकन',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name(s) and address(es) of nominee / nominees', 'type' => 'text'],
                        ['name' => 'relationship', 'label' => 'Relationship with Government servant', 'type' => 'text'],
                        ['name' => 'age', 'label' => 'Age', 'type' => 'text'],
                        ['name' => 'share', 'label' => 'Share of amount to be paid to each', 'type' => 'text'],
                        ['name' => 'contingency', 'label' => 'Contingencies on the happening of which the nomination shall become invalid', 'type' => 'text'],
                        ['name' => 'successor', 'label' => 'Name, address &amp; relationship of the person to whom the right shall pass if the nominee predeceases the Government servant', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby nominate the person(s) mentioned above to receive the amount of the Savings Fund / Insurance under the Central Government Employees Group Insurance Scheme, 1980 in the event of my death, and confer on him/her the right to receive the said amount.',
            'notes' => [
                'Draw a line across the blank space below the last entry to prevent the insertion of any name after it.',
                'Where the amount of share payable to each nominee is not specified, the amount shall be distributed among the nominees in equal shares.',
                'A member without a family shall make nomination in Form 7; a member with a family shall make nomination in Form 8 in favour of one or more members of the family only.',
            ],
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant', 'Signature of Witness 1', 'Signature of Witness 2'],
        ];
    }

    private static function npsSubscription(): array
    {
        return [
            'key'      => 'nps_subscription',
            'title'    => 'NPS Subscriber Registration Form (CSRF-1)',
            'subtitle' => 'National Pension System',
            'sections' => [
                [
                    'heading' => 'Subscriber Category / अंशदाता श्रेणी',
                    'fields'  => [
                        ['name' => 'category', 'label' => 'Subscriber Category', 'type' => 'select', 'options' => ['Central Government', 'State Government', 'All Citizen Model', 'Corporate'], 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'first_name', 'label' => 'First Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-4'],
                        ['name' => 'middle_name', 'label' => 'Middle Name', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'last_name', 'label' => 'Last Name / उपनाम', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'father_name', 'label' => "Father's Name", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'mother_name', 'label' => "Mother's Name", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth / जन्म तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'place_of_birth', 'label' => 'Place of Birth (City &amp; Country)', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'gender', 'label' => 'Gender / लिंग', 'type' => 'select', 'options' => ['Male', 'Female', 'Transgender'], 'width' => 'col-md-4'],
                        ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'marital_status', 'label' => 'Marital Status', 'type' => 'select', 'options' => ['Single', 'Married'], 'width' => 'col-md-4'],
                        ['name' => 'spouse_name', 'label' => "Spouse's Name (if married)", 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Identity &amp; Contact / पहचान एवं संपर्क',
                    'fields'  => [
                        ['name' => 'pan', 'label' => 'PAN', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'aadhaar', 'label' => 'Aadhaar No.', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'mobile', 'label' => 'Mobile No.', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Correspondence Address / पत्राचार का पता',
                    'fields'  => [
                        ['name' => 'corr_address', 'label' => 'Address', 'type' => 'textarea', 'width' => 'col-md-12'],
                        ['name' => 'corr_city', 'label' => 'City', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'corr_state', 'label' => 'State', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'corr_pincode', 'label' => 'Pincode', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Permanent Address / स्थायी पता',
                    'fields'  => [
                        ['name' => 'perm_address', 'label' => 'Address', 'type' => 'textarea', 'width' => 'col-md-12'],
                        ['name' => 'perm_city', 'label' => 'City', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'perm_state', 'label' => 'State', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'perm_pincode', 'label' => 'Pincode', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Bank Details / बैंक विवरण',
                    'fields'  => [
                        ['name' => 'account_type', 'label' => 'Account Type', 'type' => 'select', 'options' => ['Savings', 'Current'], 'width' => 'col-md-3'],
                        ['name' => 'account_number', 'label' => 'Account Number', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'branch_name', 'label' => 'Branch', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'branch_address', 'label' => 'Branch Address', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'micr', 'label' => 'MICR Code', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'ifsc', 'label' => 'IFSC Code', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'Scheme Preference / योजना वरीयता',
                    'fields'  => [
                        ['name' => 'pension_fund', 'label' => 'Pension Fund Manager (PFM)', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'investment_option', 'label' => 'Investment Option', 'type' => 'select', 'options' => ['Active Choice', 'Auto Choice'], 'width' => 'col-md-3'],
                        ['name' => 'tier_ii', 'label' => 'Tier II Account?', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-3'],
                        ['name' => 'tax_resident_outside', 'label' => 'Tax resident of any country other than India? (FATCA)', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'nominees',
                    'heading' => 'Nomination Details / नामांकन विवरण',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Nominee Name', 'type' => 'text'],
                        ['name' => 'relationship', 'label' => 'Relationship', 'type' => 'text'],
                        ['name' => 'age', 'label' => 'Age', 'type' => 'text'],
                        ['name' => 'percentage', 'label' => 'Share %', 'type' => 'text'],
                        ['name' => 'guardian', 'label' => 'Guardian (if nominee is a minor)', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the information furnished above is true and correct to the best of my knowledge and belief. I undertake to intimate any change in the above information to the CRA / Nodal Office. I have read and understood the terms and conditions of the NPS and agree to be bound by them.',
            'notes' => [
                'Up to three nominees may be registered here; for more, use the prescribed annexure.',
                'If the total share does not add up to 100%, it will be treated as invalid.',
            ],
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature / Thumb Impression of the Subscriber'],
        ];
    }

    private static function employeeInfoSheet(): array
    {
        return [
            'key'      => 'employee_info_sheet',
            'title'    => 'Employee Information Sheet (EIS)',
            'subtitle' => 'PFMS — Form EIS/B/1',
            'sections' => [
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'options' => ['Male', 'Female', 'Transgender'], 'width' => 'col-md-3'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'width' => 'col-md-3'],
                        ['name' => 'employee_type', 'label' => 'Type', 'type' => 'select', 'options' => ['Pensionable', 'NPS'], 'width' => 'col-md-3'],
                        ['name' => 'pan', 'label' => 'PAN', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'aadhaar', 'label' => 'Aadhaar No.', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'employee_code', 'label' => 'Employee Code', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'date_of_entry_govt', 'label' => 'Date of Entry in Govt. Service', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'date_of_superannuation', 'label' => 'Date of Superannuation', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'physically_disabled', 'label' => 'Physically Disabled?', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Posting Details / तैनाती विवरण',
                    'fields'  => [
                        ['name' => 'current_office', 'label' => 'Current Office', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'group', 'label' => 'Group', 'type' => 'select', 'options' => ['A', 'B', 'C'], 'width' => 'col-md-3'],
                        ['name' => 'date_of_joining_office', 'label' => 'Date of Joining Office', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'date_in_current_post', 'label' => 'Date in Current Post', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'city_class', 'label' => 'City Class (X/Y/Z)', 'type' => 'select', 'options' => ['X', 'Y', 'Z'], 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Pay Details / वेतन विवरण',
                    'fields'  => [
                        ['name' => 'pay_commission', 'label' => 'Pay Commission', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pay_level', 'label' => 'Pay Level', 'type' => 'text', 'width' => 'col-md-2'],
                        ['name' => 'basic_pay', 'label' => 'Basic Pay (₹)', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pay_wef', 'label' => 'Pay w.e.f. Date', 'type' => 'date', 'width' => 'col-md-2'],
                        ['name' => 'next_increment_date', 'label' => 'Next Increment Date', 'type' => 'date', 'width' => 'col-md-2'],
                    ],
                ],
                [
                    'heading' => 'PF / NPS Details / पीएफ-एनपीएस विवरण',
                    'fields'  => [
                        ['name' => 'pf_type', 'label' => 'PF Type', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pf_agency', 'label' => 'PF Agency', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pf_series', 'label' => 'PF Series', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pran_no', 'label' => 'PF / PRAN No.', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'CGEGIS / CGHS / Category',
                    'fields'  => [
                        ['name' => 'cgegis', 'label' => 'CGEGIS Applicable?', 'type' => 'select', 'options' => ['Yes', 'No'], 'width' => 'col-md-2'],
                        ['name' => 'cghs', 'label' => 'CGHS Applicable?', 'type' => 'select', 'options' => ['Yes', 'No'], 'width' => 'col-md-2'],
                        ['name' => 'cghs_card_no', 'label' => 'CGHS Card No.', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => ['General', 'OBC', 'SC', 'ST'], 'width' => 'col-md-2'],
                        ['name' => 'ex_serviceman', 'label' => 'Ex-Serviceman?', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'Bank &amp; Contact / बैंक एवं संपर्क',
                    'fields'  => [
                        ['name' => 'ifsc', 'label' => 'IFSC Code', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'branch', 'label' => 'Branch', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'account_number', 'label' => 'Savings A/c No.', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'mobile', 'label' => 'Mobile No.', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Officer / Official'],
        ];
    }

    private static function debtsLiabilities(): array
    {
        return [
            'key'      => 'debts_liabilities',
            'title'    => 'Statement of Debts and Other Liabilities on First Appointment',
            'subtitle' => 'Form No. 6-C',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of the Officer (in full) and Service to which he/she belongs', 'type' => 'text', 'required' => true, 'width' => 'col-md-8'],
                        ['name' => 'present_post', 'label' => 'Present Post held', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'as_on_date', 'label' => 'Statement as on Date', 'type' => 'date', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'liabilities',
                    'heading' => 'Debts &amp; Other Liabilities / ऋण एवं अन्य देयताएँ',
                    'columns' => [
                        ['name' => 'amount', 'label' => 'Amount', 'type' => 'text'],
                        ['name' => 'creditor', 'label' => 'Name and address of Creditor', 'type' => 'text'],
                        ['name' => 'date_incurred', 'label' => 'Date of incurring Liability', 'type' => 'date'],
                        ['name' => 'details', 'label' => 'Details of Transaction', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text'],
                    ],
                ],
            ],
            'notes' => [
                'Indicate the source from which the loan was raised and the purpose for which it was utilised.',
                'In the Remarks column, indicate the sanction/permission obtained from, or the report made to, the competent authority, if any.',
            ],
            'declaration' => 'I hereby declare that the above statement of my debts and other liabilities is true and complete to the best of my knowledge and belief.',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function immovableProperty(): array
    {
        return [
            'key'      => 'immovable_property',
            'title'    => 'Statement of Immovable Property on First Appointment',
            'subtitle' => 'Form No. 6-A (Form 1 — see Rule 16 AIS (Conduct) Rules / Rule 18 CCS (Conduct) Rules)',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of the Officer (in full) and Service to which he/she belongs', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'present_post', 'label' => 'Present Post held', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'cadre', 'label' => 'Cadre of the State on which borne', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'properties',
                    'heading' => 'Immovable Property / अचल संपत्ति',
                    'columns' => [
                        ['name' => 'location', 'label' => 'Name of District, Sub-Division, Taluk and Village in which property is situated', 'type' => 'text'],
                        ['name' => 'property_details', 'label' => 'Name and details of Property (Housing/other building and Land)', 'type' => 'text'],
                        ['name' => 'present_value', 'label' => 'Present Value', 'type' => 'text'],
                        ['name' => 'in_whose_name', 'label' => 'If not in own name, in whose name held and his/her relationship to the officer', 'type' => 'text'],
                        ['name' => 'how_acquired', 'label' => 'How acquired (purchase/lease/mortgage/inheritance/gift, etc.) with date of acquisition and name &amp; details of persons from whom acquired', 'type' => 'text'],
                        ['name' => 'annual_income', 'label' => 'Annual Income from the Property', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the above statement of immovable property is true and complete to the best of my knowledge and belief.',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function movableProperty(): array
    {
        return [
            'key'      => 'movable_property',
            'title'    => 'Statement of Movable Property on First Appointment',
            'subtitle' => 'Form No. 6-B',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of the Officer (in full) and Service to which he/she belongs', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'present_post', 'label' => 'Present Post held', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'present_pay', 'label' => 'Present Pay (₹)', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'year', 'label' => 'Year', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'movables',
                    'heading' => 'Movable Property / चल संपत्ति',
                    'columns' => [
                        ['name' => 'description', 'label' => 'Name and details of Movable Property', 'type' => 'text'],
                        ['name' => 'present_value', 'label' => 'Present Value', 'type' => 'text'],
                        ['name' => 'in_whose_name', 'label' => 'If not in own name, in whose name held and his/her relationship to the Government Servant', 'type' => 'text'],
                        ['name' => 'how_acquired', 'label' => 'How acquired (purchase/inheritance/gift, etc.) with date of acquisition and name &amp; details of persons from whom acquired', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the above statement of movable property is true and complete to the best of my knowledge and belief.',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function closeRelation(): array
    {
        $columns = [
            ['name' => 'relation', 'label' => 'Relation', 'type' => 'text'],
            ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'text'],
            ['name' => 'present_address', 'label' => 'Present Address', 'type' => 'text'],
            ['name' => 'place_of_birth', 'label' => 'Place of Birth', 'type' => 'text'],
            ['name' => 'occupation', 'label' => 'Occupation', 'type' => 'text'],
        ];

        return [
            'key'      => 'close_relation',
            'title'    => 'Declaration of Close Relations who are Foreign Nationals / Domiciled Abroad',
            'subtitle' => 'Form to be filled by Government employees on first appointment (MHA O.M. No. F.3/12(S)/64-Ests.(B), dated 12-10-1965)',
            'intro'    => 'Give particulars of close relations (father, mother, wife/husband, sons, daughters, brothers, sisters) who are (A) nationals of, or domiciled in, other countries, or (B) of non-Indian origin residing in India. If there are no such relations, write &ldquo;NIL&rdquo;.',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'foreign_nationals',
                    'heading' => 'A. Close relations who are nationals of, or are domiciled in, other countries',
                    'columns' => $columns,
                ],
                [
                    'key'     => 'non_indian_origin',
                    'heading' => 'B. Close relations residing in India who are of non-Indian origin',
                    'columns' => $columns,
                ],
            ],
            'declaration' => 'I certify that the foregoing information is correct and complete to the best of my knowledge and belief.',
            'notes' => [
                'Suppression of any factual information which the Government servant is required to convey would be regarded as a serious matter and would expose the official concerned to serious consequences.',
                'Any subsequent change in the above particulars should be reported to the Head of Office.',
            ],
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function dowryDeclaration(): array
    {
        return [
            'key'      => 'dowry_declaration',
            'title'    => 'Dowry Declaration',
            'subtitle' => 'Under the Dowry Prohibition Act, 1961 (Rule 13-A, CCS (Conduct) Rules, 1964)',
            'sections' => [
                [
                    'heading' => 'Declarant Details / घोषणाकर्ता विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of Officer (in Block Letters) / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Name of Service / सेवा का नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'marital_choice', 'label' => 'Marital Status', 'type' => 'select', 'options' => ['Unmarried', 'Married'], 'width' => 'col-md-4'],
                        ['name' => 'parent_guardian_name', 'label' => 'Name of Parent / Guardian', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'parent_guardian_address', 'label' => 'Address of Parent / Guardian', 'type' => 'textarea', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'declaration' => 'I hereby solemnly declare that I have neither demanded nor taken/given, nor shall I demand or take/give, any dowry, directly or indirectly, in connection with my marriage, in accordance with the provisions of the Dowry Prohibition Act, 1961.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature (Name of Officer in Block Letters)'],
        ];
    }

    private static function homeTown(): array
    {
        return [
            'key'      => 'home_town',
            'title'    => 'Home Town Declaration',
            'subtitle' => 'For the purpose of Leave Travel Concession (MHA Memo No. 43/715/57-Ests.(A), dated 24-06-1958)',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation (name of service followed by "Probationer")', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'town_village', 'label' => 'Name of Town / Village', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'district', 'label' => 'District', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'state', 'label' => 'State', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'reason', 'label' => 'Reason (mention a, b, c or d whichever is applicable)', 'type' => 'text', 'width' => 'col-md-12'],
                    ],
                ],
            ],
            'notes' => [
                '(a) The place is the one which requires your physical presence at intervals for discharging various domestic and social obligations, and you visit it frequently.',
                '(b) You own residential property in that place, or you are a member of a joint family having such property there.',
                '(c) Your near relations are permanently residing in that place.',
                '(d) Prior to your entry into Government service, you had been living there for some years.',
            ],
            'declaration' => "I declare that my 'Home Town' for the purpose of Leave Travel Concession is as indicated above and the particulars given are correct.",
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function maritalStatus(): array
    {
        return [
            'key'      => 'marital_status',
            'title'    => 'Declaration Regarding Marital Status',
            'subtitle' => 'Under Rule 21, CCS (Conduct) Rules, 1964',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'status_clause', 'label' => 'Applicable Declaration (select the clause that applies)', 'type' => 'select', 'required' => true, 'width' => 'col-md-12', 'options' => [
                            'I am unmarried / a widower / a widow',
                            'I am married and have only one spouse living',
                            'I am married and have more than one spouse living (exemption applied for)',
                            'I am about to marry a person who has a spouse living (exemption applied for)',
                        ]],
                        ['name' => 'exemption_reasons', 'label' => 'Reasons for seeking exemption (if applicable)', 'type' => 'textarea', 'width' => 'col-md-12'],
                    ],
                ],
            ],
            'declaration' => 'I solemnly affirm that the above declaration is true, and I understand that if the declaration is found to be false, I shall be liable to be dismissed from service.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Full Signature of the Government Servant'],
        ];
    }

    private static function oathAffirmation(): array
    {
        return [
            'key'      => 'oath_affirmation',
            'title'    => 'Form of Oath / Affirmation of Allegiance',
            'intro'    => 'To be made at the Lal Bahadur Shastri National Academy of Administration, Mussoorie.',
            'sections' => [
                [
                    'heading' => 'Details / विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name (in capital letters) / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Service / सेवा', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'mode', 'label' => 'Mode', 'type' => 'select', 'options' => ['Solemnly affirm', 'Swear'], 'width' => 'col-md-3'],
                    ],
                ],
            ],
            'declaration' => 'I do solemnly affirm / swear that I will bear true faith and allegiance to the Constitution of India as by law established, that I will uphold the sovereignty and integrity of India, and that I will faithfully discharge the duties of my office. (SO HELP ME GOD)',
            'notes' => [
                'A person who, by reason of conscientious objection, declines to swear may instead make a solemn affirmation.',
            ],
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Officer'],
        ];
    }

    private static function suretyBondIas(): array
    {
        return [
            'key'      => 'surety_bond_ias',
            'title'    => 'Surety Bond (IAS / IPS / IFoS)',
            'subtitle' => 'To be executed on Non-Judicial Stamp Paper of ₹100',
            'intro'    => 'Bond executed by the Probationer together with one Surety, binding the Probationer to serve the Government for the prescribed period and to refund the cost of training / pay and allowances in the event of default, in accordance with the applicable Service Rules.',
            'sections' => [
                [
                    'heading' => 'Probationer / परिवीक्षाधीन अधिकारी',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Full Name and Address of the Probationer', 'type' => 'textarea', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Service', 'type' => 'select', 'options' => ['IAS', 'IPS', 'IFoS'], 'width' => 'col-md-3'],
                        ['name' => 'exam_year', 'label' => 'Year of Examination', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'service_rule', 'label' => 'Applicable Service Rule', 'type' => 'text', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Surety / प्रतिभू',
                    'fields'  => [
                        ['name' => 'surety_name', 'label' => 'Full Name of Surety', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'surety_address', 'label' => 'Address of Surety', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'surety_occupation', 'label' => 'Occupation of Surety', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'surety_eligibility', 'label' => 'Surety is', 'type' => 'select', 'options' => ['In the permanent service of Government', 'Ordinarily resident in India'], 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Witness to Probationer / परिवीक्षाधीन के साक्षी',
                    'fields'  => [
                        ['name' => 'prob_witness_name', 'label' => 'Name of Witness', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'prob_witness_address', 'label' => 'Address', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'prob_witness_occupation', 'label' => 'Occupation', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Witness to Surety / प्रतिभू के साक्षी',
                    'fields'  => [
                        ['name' => 'surety_witness_name', 'label' => 'Name of Witness', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'surety_witness_address', 'label' => 'Address', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'surety_witness_occupation', 'label' => 'Occupation', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'declaration' => 'NOW THE CONDITION of the above-written bond is that if the Probationer shall faithfully serve the Government for the prescribed period and shall duly fulfil the terms of his/her appointment, then this bond shall be void; otherwise the Probationer and the Surety bind themselves, their heirs, executors and administrators, jointly and severally, to pay to the Government the amounts due under the bond.',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Probationer', 'Signature of the Surety'],
        ];
    }

    private static function suretyBondOthers(): array
    {
        $tpl = self::suretyBondIas();
        $tpl['key']      = 'surety_bond_others';
        $tpl['title']    = 'Surety Bond (Central Civil Services, Group-A — Other than All India Services)';
        // Service becomes a free-text field for other services.
        $tpl['sections'][0]['fields'][1] = ['name' => 'service', 'label' => 'Name of Service', 'type' => 'text', 'width' => 'col-md-3'];

        return $tpl;
    }

    private static function assumptionCharge(): array
    {
        return [
            'key'      => 'assumption_charge',
            'title'    => 'Certificate of Assumption of Charge',
            'sections' => [
                [
                    'heading' => 'Charge Details / कार्यभार विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'post_assumed', 'label' => 'Post Assumed', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'place_of_posting', 'label' => 'Place of Posting', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_assumption', 'label' => 'Date of Assumption', 'type' => 'date', 'width' => 'col-md-6'],
                        ['name' => 'time_of_assumption', 'label' => 'Time (FN / AN)', 'type' => 'select', 'options' => ['Forenoon (FN)', 'Afternoon (AN)'], 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'declaration' => 'Certified that I have assumed charge of the post indicated above on the date and time stated.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Officer'],
        ];
    }

    private static function policeVerification(): array
    {
        return [
            'key'      => 'police_verification',
            'title'    => 'Police Verification / Attestation Form',
            'sections' => [
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Full Name / पूरा नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'father_name', 'label' => "Father's Name", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'place_of_birth', 'label' => 'Place of Birth', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'mobile', 'label' => 'Mobile No.', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Present Address / वर्तमान पता',
                    'fields'  => [
                        ['name' => 'present_address', 'label' => 'Present Address', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'present_since', 'label' => 'Residing Since', 'type' => 'date', 'width' => 'col-md-3'],
                        ['name' => 'present_police_station', 'label' => 'Police Station', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'Permanent Address / स्थायी पता',
                    'fields'  => [
                        ['name' => 'permanent_address', 'label' => 'Permanent Address', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'permanent_police_station', 'label' => 'Police Station', 'type' => 'text', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'references',
                    'heading' => 'References / संदर्भ (two responsible persons)',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                        ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                        ['name' => 'contact', 'label' => 'Contact No.', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the particulars furnished above are true, complete and correct to the best of my knowledge and belief.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Applicant'],
        ];
    }
}
