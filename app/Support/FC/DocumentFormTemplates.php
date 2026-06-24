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
 * Table shape:  ['key'=>'nominees','heading'=>'...','columns'=>[ <field>, ... ]]
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
            'title'    => 'Nomination – Central Govt. Employees Group Insurance Scheme (Form 7 / Form 8)',
            'subtitle' => 'CGEGIS, 1980',
            'sections' => [
                [
                    'heading' => 'Employee Details / कर्मचारी विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_appointment', 'label' => 'Date of Appointment / नियुक्ति तिथि', 'type' => 'date', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'nominees',
                    'heading' => 'Nominees / नामिती',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name & Address of Nominee', 'type' => 'text'],
                        ['name' => 'relationship', 'label' => 'Relationship', 'type' => 'text'],
                        ['name' => 'dob', 'label' => 'Date of Birth (if minor)', 'type' => 'date'],
                        ['name' => 'share', 'label' => 'Share %', 'type' => 'text'],
                        ['name' => 'contingency', 'label' => 'Contingency on which share lapses', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby nominate the person(s) mentioned above to receive the amount payable under the Central Government Employees Group Insurance Scheme in the event of my death.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Employee', 'Signature of Two Witnesses'],
        ];
    }

    private static function npsSubscription(): array
    {
        return [
            'key'      => 'nps_subscription',
            'title'    => 'NPS Subscriber Registration Form',
            'subtitle' => 'National Pension System',
            'sections' => [
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Full Name / पूरा नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'father_spouse_name', 'label' => "Father's / Spouse's Name", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth / जन्म तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'gender', 'label' => 'Gender / लिंग', 'type' => 'select', 'options' => ['Male', 'Female', 'Other'], 'width' => 'col-md-4'],
                        ['name' => 'marital_status', 'label' => 'Marital Status', 'type' => 'select', 'options' => ['Single', 'Married'], 'width' => 'col-md-4'],
                        ['name' => 'pan', 'label' => 'PAN', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'aadhaar', 'label' => 'Aadhaar No.', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'mobile', 'label' => 'Mobile No.', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Address / पता',
                    'fields'  => [
                        ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'width' => 'col-md-12'],
                        ['name' => 'city', 'label' => 'City', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'state', 'label' => 'State', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'pincode', 'label' => 'Pincode', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Bank Details / बैंक विवरण',
                    'fields'  => [
                        ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'account_number', 'label' => 'Account Number', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'ifsc', 'label' => 'IFSC Code', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'nominees',
                    'heading' => 'Nominee Details / नामिती विवरण',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Nominee Name', 'type' => 'text'],
                        ['name' => 'relationship', 'label' => 'Relationship', 'type' => 'text'],
                        ['name' => 'dob', 'label' => 'Date of Birth', 'type' => 'date'],
                        ['name' => 'percentage', 'label' => 'Share %', 'type' => 'text'],
                        ['name' => 'guardian', 'label' => 'Guardian (if minor)', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I declare that the information furnished above is true to the best of my knowledge and belief.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Subscriber'],
        ];
    }

    private static function employeeInfoSheet(): array
    {
        return [
            'key'      => 'employee_info_sheet',
            'title'    => 'Employee Information Sheet',
            'sections' => [
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Full Name / पूरा नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'date_of_joining', 'label' => 'Date of Joining', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'blood_group', 'label' => 'Blood Group', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'pan', 'label' => 'PAN', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'aadhaar', 'label' => 'Aadhaar No.', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'marital_status', 'label' => 'Marital Status', 'type' => 'select', 'options' => ['Single', 'Married'], 'width' => 'col-md-4'],
                        ['name' => 'mobile', 'label' => 'Mobile No.', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Address / पता',
                    'fields'  => [
                        ['name' => 'present_address', 'label' => 'Present Address', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'permanent_address', 'label' => 'Permanent Address', 'type' => 'textarea', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Emergency Contact / आपातकालीन संपर्क',
                    'fields'  => [
                        ['name' => 'emergency_name', 'label' => 'Contact Name', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'emergency_relation', 'label' => 'Relationship', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'emergency_phone', 'label' => 'Contact Phone', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Employee'],
        ];
    }

    private static function debtsLiabilities(): array
    {
        return [
            'key'      => 'debts_liabilities',
            'title'    => 'Form No. 6-C: Statement of Debts and Other Liabilities',
            'sections' => [self::officerSection()],
            'tables'   => [
                [
                    'key'     => 'liabilities',
                    'heading' => 'Debts & Liabilities / ऋण एवं देयताएँ',
                    'columns' => [
                        ['name' => 'creditor', 'label' => 'Name & Address of Creditor', 'type' => 'text'],
                        ['name' => 'amount', 'label' => 'Amount (₹)', 'type' => 'text'],
                        ['name' => 'date_incurred', 'label' => 'Date Incurred', 'type' => 'date'],
                        ['name' => 'details', 'label' => 'Details / Reasons', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the above statement of my debts and other liabilities is true and complete to the best of my knowledge and belief.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function immovableProperty(): array
    {
        return [
            'key'      => 'immovable_property',
            'title'    => 'Form No. 6-A: Statement of Immovable Property',
            'sections' => [self::officerSection()],
            'tables'   => [
                [
                    'key'     => 'properties',
                    'heading' => 'Immovable Property / अचल संपत्ति',
                    'columns' => [
                        ['name' => 'description', 'label' => 'Description & Location', 'type' => 'text'],
                        ['name' => 'area', 'label' => 'Area / Extent', 'type' => 'text'],
                        ['name' => 'how_acquired', 'label' => 'How Acquired', 'type' => 'select', 'options' => ['Purchase', 'Lease', 'Mortgage', 'Inheritance', 'Gift', 'Other']],
                        ['name' => 'date_acquired', 'label' => 'Date of Acquisition', 'type' => 'date'],
                        ['name' => 'value', 'label' => 'Value (₹)', 'type' => 'text'],
                        ['name' => 'in_whose_name', 'label' => 'In Whose Name', 'type' => 'text'],
                        ['name' => 'annual_income', 'label' => 'Annual Income (₹)', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the above statement of immovable property is true and complete to the best of my knowledge and belief.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function movableProperty(): array
    {
        return [
            'key'      => 'movable_property',
            'title'    => 'Form No. 6-B: Statement of Movable Property',
            'sections' => [self::officerSection()],
            'tables'   => [
                [
                    'key'     => 'movables',
                    'heading' => 'Movable Property / चल संपत्ति',
                    'columns' => [
                        ['name' => 'description', 'label' => 'Description of Item', 'type' => 'text'],
                        ['name' => 'how_acquired', 'label' => 'How Acquired', 'type' => 'select', 'options' => ['Purchase', 'Gift', 'Inheritance', 'Other']],
                        ['name' => 'date_acquired', 'label' => 'Date of Acquisition', 'type' => 'date'],
                        ['name' => 'value', 'label' => 'Value (₹)', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the above statement of movable property is true and complete to the best of my knowledge and belief.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function closeRelation(): array
    {
        return [
            'key'      => 'close_relation',
            'title'    => 'Declaration of Close Relation in Government Service',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'has_relative', 'label' => 'Any close relative in Government service?', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'relatives',
                    'heading' => 'Relatives in Government Service / सरकारी सेवा में संबंधी',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                        ['name' => 'relationship', 'label' => 'Relationship', 'type' => 'text'],
                        ['name' => 'designation', 'label' => 'Designation', 'type' => 'text'],
                        ['name' => 'department', 'label' => 'Department / Office', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the particulars given above regarding my close relations in Government service are true and complete to the best of my knowledge.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function dowryDeclaration(): array
    {
        return [
            'key'      => 'dowry_declaration',
            'title'    => 'Dowry Declaration',
            'subtitle' => 'Under the Dowry Prohibition Act, 1961',
            'sections' => [
                [
                    'heading' => 'Declarant Details / घोषणाकर्ता विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'spouse_name', 'label' => "Spouse's Name (if married)", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_marriage', 'label' => 'Date of Marriage (if applicable)', 'type' => 'date', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'declaration' => 'I hereby solemnly declare that I have neither demanded nor taken/given any dowry, directly or indirectly, in connection with my marriage, in accordance with the provisions of the Dowry Prohibition Act, 1961.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Declarant'],
        ];
    }

    private static function homeTown(): array
    {
        return [
            'key'      => 'home_town',
            'title'    => 'Home Town Declaration',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'home_town', 'label' => 'Declared Home Town', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'district', 'label' => 'District', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'state', 'label' => 'State', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'basis', 'label' => 'Basis of Declaration / Criteria', 'type' => 'textarea', 'width' => 'col-md-12'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the home town indicated above is correct and is the place which requires my physical presence at intervals for discharging various domestic and social obligations.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function maritalStatus(): array
    {
        return [
            'key'      => 'marital_status',
            'title'    => 'Marital Status Declaration',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'marital_status', 'label' => 'Marital Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Divorced', 'Widowed'], 'required' => true, 'width' => 'col-md-4'],
                        ['name' => 'spouse_name', 'label' => "Spouse's Name (if married)", 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'date_of_marriage', 'label' => 'Date of Marriage (if applicable)', 'type' => 'date', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that I do not have more than one spouse living and the marital status stated above is true and correct.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant'],
        ];
    }

    private static function oathAffirmation(): array
    {
        return [
            'key'      => 'oath_affirmation',
            'title'    => 'Form of Oath / Affirmation of Allegiance',
            'sections' => [
                [
                    'heading' => 'Details / विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'mode', 'label' => 'Mode', 'type' => 'select', 'options' => ['Solemnly affirm', 'Swear'], 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'declaration' => 'I do solemnly affirm/swear that I will bear true faith and allegiance to the Constitution of India as by law established, that I will uphold the sovereignty and integrity of India, and that I will faithfully discharge the duties of my office.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Officer', 'Signature of Witness'],
        ];
    }

    private static function suretyBondIas(): array
    {
        return [
            'key'      => 'surety_bond_ias',
            'title'    => 'Surety Bond (IAS / IPS / IFoS)',
            'sections' => [
                [
                    'heading' => 'Officer Details / अधिकारी विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of Officer / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Service', 'type' => 'select', 'options' => ['IAS', 'IPS', 'IFoS'], 'width' => 'col-md-3'],
                        ['name' => 'bond_amount', 'label' => 'Bond Amount (₹)', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'sureties',
                    'heading' => 'Sureties / प्रतिभू',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name of Surety', 'type' => 'text'],
                        ['name' => 'address', 'label' => 'Address', 'type' => 'text'],
                        ['name' => 'occupation', 'label' => 'Occupation', 'type' => 'text'],
                        ['name' => 'amount', 'label' => 'Amount (₹)', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I bind myself and my heirs, executors and administrators to pay the Government the sum stated above in accordance with the terms of this surety bond.',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Officer', 'Signature of Sureties'],
        ];
    }

    private static function suretyBondOthers(): array
    {
        $tpl = self::suretyBondIas();
        $tpl['key']   = 'surety_bond_others';
        $tpl['title'] = 'Surety Bond (Other Services)';
        // Service becomes a free-text field for other services.
        $tpl['sections'][0]['fields'][1] = ['name' => 'service', 'label' => 'Service', 'type' => 'text', 'width' => 'col-md-3'];

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
