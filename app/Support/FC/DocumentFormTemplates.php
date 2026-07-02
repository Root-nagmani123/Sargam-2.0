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
            // Dedicated document-faithful, bilingual Document-1 layout (Form No. 3, 10-row grid + notes).
            'form_view' => 'fc.registration.document-forms.family_details',
            'pdf_view'  => 'fc.registration.document-forms.pdf.family_details',
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
            'title_hi' => 'नामांकन — केंद्रीय सरकार कर्मचारी समूह बीमा योजना, 1980',
            'subtitle' => 'Form 7 (member without family) / Form 8 (member with family) — See Para 19.7',
            // Dedicated views reproducing the official Form 7 / Form 8 nomination layout.
            'form_view' => 'fc.registration.document-forms.group_insurance',
            'pdf_view'  => 'fc.registration.document-forms.pdf.group_insurance',
            'intro'    => 'Use <strong>Form 7</strong> if you have <strong>no family</strong> (nomination may be in favour of any person). Use <strong>Form 8</strong> if you <strong>have a family</strong> (nomination must be in favour of one or more members of the family). Indicate which applies below. <br>यदि आपका <strong>कोई परिवार नहीं</strong> है तो <strong>प्रपत्र 7</strong> का उपयोग करें (नामांकन किसी भी व्यक्ति के पक्ष में किया जा सकता है)। यदि आपका <strong>परिवार है</strong> तो <strong>प्रपत्र 8</strong> का उपयोग करें (नामांकन परिवार के एक या अधिक सदस्यों के पक्ष में ही किया जाना चाहिए)। नीचे यह दर्शाएं कि कौन-सा लागू होता है।',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'form_variant', 'label' => 'Applicable Form / लागू प्रपत्र', 'type' => 'select', 'options' => ['Form 7 — Member without family', 'Form 8 — Member with family'], 'required' => true, 'width' => 'col-md-6'],
                    ],
                ],
                [
                    // Account Section (LBSNAA cover page of the sample).
                    'heading' => 'Account Section / लेखा अनुभाग',
                    'fields'  => [
                        ['name' => 'service', 'label' => 'Service to which you belong / जिस सेवा से आप संबंधित हैं', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_joining', 'label' => 'Date of Joining / कार्यग्रहण की तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'joining_time', 'label' => 'Forenoon / Afternoon / पूर्वाह्न / अपराह्न', 'type' => 'select', 'options' => ['Forenoon', 'Afternoon'], 'width' => 'col-md-2'],
                        ['name' => 'trainee_type', 'label' => 'Whether a fresh Trainee or a Departmental candidate / नया प्रशिक्षु अथवा विभागीय अभ्यर्थी', 'type' => 'select', 'options' => ['Fresh Trainee', 'Departmental Candidate'], 'width' => 'col-md-6'],
                        ['name' => 'department_details', 'label' => 'If Departmental candidate, name &amp; address of your Department paying salary during the Foundation Course / यदि विभागीय अभ्यर्थी हैं, तो आधार पाठ्यक्रम के दौरान वेतन देने वाले विभाग का नाम एवं पता', 'type' => 'textarea', 'width' => 'col-md-12'],
                        ['name' => 'earlier_member', 'label' => 'Were you earlier a member of the CGEGIS? If so, the monthly subscription and the name &amp; address of the office maintaining the account / क्या आप पहले सीजीईजीआईएस के सदस्य थे? यदि हाँ, तो मासिक अंशदान तथा खाता रखने वाले कार्यालय का नाम एवं पता', 'type' => 'textarea', 'width' => 'col-md-12'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'nominees',
                    'heading' => 'Nomination / नामांकन',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name(s) and address(es) of nominee / nominees / नामिती/नामितियों के नाम और पते', 'type' => 'text'],
                        ['name' => 'relationship', 'label' => 'Relationship with Government servant / सरकारी कर्मचारी के साथ संबंध', 'type' => 'text'],
                        ['name' => 'age', 'label' => 'Age / आयु', 'type' => 'text'],
                        ['name' => 'share', 'label' => 'Share of amount to be paid to each / प्रत्येक को देय राशि का हिस्सा', 'type' => 'text'],
                        ['name' => 'contingency', 'label' => 'Contingencies on the happening of which the nomination shall become invalid / वे परिस्थितियाँ जिनके घटित होने पर नामांकन अमान्य हो जाएगा', 'type' => 'text'],
                        ['name' => 'successor', 'label' => 'Name, address &amp; relationship of the person to whom the right shall pass if the nominee predeceases the Government servant / उस व्यक्ति का नाम, पता एवं संबंध जिसे यह अधिकार अंतरित होगा यदि नामिती सरकारी कर्मचारी से पूर्व मृत्यु को प्राप्त होता है', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby nominate the person(s) mentioned above to receive the amount of the Savings Fund / Insurance under the Central Government Employees Group Insurance Scheme, 1980 in the event of my death, and confer on him/her the right to receive the said amount. / मैं एतद्द्वारा उपर्युक्त व्यक्ति/व्यक्तियों को केंद्रीय सरकार कर्मचारी समूह बीमा योजना, 1980 के अंतर्गत मेरी मृत्यु की स्थिति में बचत निधि / बीमा की राशि प्राप्त करने के लिए नामांकित करता/करती हूँ, और उसे/उन्हें उक्त राशि प्राप्त करने का अधिकार प्रदान करता/करती हूँ।',
            'notes' => [
                'Draw a line across the blank space below the last entry to prevent the insertion of any name after it. / अंतिम प्रविष्टि के नीचे रिक्त स्थान पर एक रेखा खींच दें ताकि उसके बाद किसी नाम की प्रविष्टि न की जा सके।',
                'Where the amount of share payable to each nominee is not specified, the amount shall be distributed among the nominees in equal shares. / जहाँ प्रत्येक नामिती को देय हिस्से की राशि निर्दिष्ट नहीं की गई है, वहाँ राशि नामितियों के बीच समान हिस्सों में वितरित की जाएगी।',
                'A member without a family shall make nomination in Form 7; a member with a family shall make nomination in Form 8 in favour of one or more members of the family only. / परिवार रहित सदस्य प्रपत्र 7 में नामांकन करेगा; परिवार वाला सदस्य प्रपत्र 8 में केवल परिवार के एक या अधिक सदस्यों के पक्ष में नामांकन करेगा।',
            ],
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर', 'Signature of Witness 1 / साक्षी 1 के हस्ताक्षर', 'Signature of Witness 2 / साक्षी 2 के हस्ताक्षर'],
        ];
    }

    private static function npsSubscription(): array
    {
        return [
            'key'      => 'nps_subscription',
            'title'    => 'NPS Subscriber Registration Form (CSRF-1)',
            'title_hi' => 'एनपीएस अंशदाता पंजीकरण प्रपत्र (सीएसआरएफ-1)',
            'subtitle' => 'National Pension System',
            // Dedicated views reproducing the official CSRF-1 numbered-section layout.
            'form_view' => 'fc.registration.document-forms.nps',
            'pdf_view'  => 'fc.registration.document-forms.pdf.nps',
            'sections' => [
                [
                    'heading' => 'Subscriber Category / अंशदाता श्रेणी',
                    'fields'  => [
                        ['name' => 'category', 'label' => 'Subscriber Category / अंशदाता श्रेणी', 'type' => 'select', 'options' => ['Central Government', 'Central Autonomous Body', 'State Government', 'State Autonomous Body', 'All Citizen Model', 'NPS Lite (GDS)', 'Corporate Sector'], 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'salutation', 'label' => 'Salutation / अभिवादन', 'type' => 'select', 'options' => ['Shri', 'Smt', 'Kumari'], 'width' => 'col-md-3'],
                        ['name' => 'first_name', 'label' => 'First Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-4'],
                        ['name' => 'middle_name', 'label' => 'Middle Name / मध्य नाम', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'last_name', 'label' => 'Last Name / उपनाम', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'father_name', 'label' => "Father's Name / पिता का नाम", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'mother_name', 'label' => "Mother's Name / माता का नाम", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth / जन्म तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'place_of_birth', 'label' => 'Place of Birth (City &amp; Country) / जन्म स्थान (शहर एवं देश)', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'gender', 'label' => 'Gender / लिंग', 'type' => 'select', 'options' => ['Male', 'Female', 'Transgender'], 'width' => 'col-md-4'],
                        ['name' => 'nationality', 'label' => 'Nationality / राष्ट्रीयता', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'marital_status', 'label' => 'Marital Status / वैवाहिक स्थिति', 'type' => 'select', 'options' => ['Single', 'Married'], 'width' => 'col-md-4'],
                        ['name' => 'spouse_name', 'label' => "Spouse's Name (if married) / जीवनसाथी का नाम (यदि विवाहित हों)", 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Identity &amp; Contact / पहचान एवं संपर्क',
                    'fields'  => [
                        ['name' => 'pan', 'label' => 'PAN / पैन', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'aadhaar', 'label' => 'Aadhaar No. / आधार संख्या', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'mobile', 'label' => 'Mobile No. / मोबाइल संख्या', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'email', 'label' => 'Email / ईमेल', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Correspondence Address / पत्राचार का पता',
                    'fields'  => [
                        ['name' => 'corr_address', 'label' => 'Address / पता', 'type' => 'textarea', 'width' => 'col-md-12'],
                        ['name' => 'corr_city', 'label' => 'City / शहर', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'corr_state', 'label' => 'State / राज्य', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'corr_pincode', 'label' => 'Pincode / पिन कोड', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Permanent Address / स्थायी पता',
                    'fields'  => [
                        ['name' => 'perm_address', 'label' => 'Address / पता', 'type' => 'textarea', 'width' => 'col-md-12'],
                        ['name' => 'perm_city', 'label' => 'City / शहर', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'perm_state', 'label' => 'State / राज्य', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'perm_pincode', 'label' => 'Pincode / पिन कोड', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Bank Details / बैंक विवरण',
                    'fields'  => [
                        ['name' => 'account_type', 'label' => 'Account Type / खाता प्रकार', 'type' => 'select', 'options' => ['Savings', 'Current'], 'width' => 'col-md-3'],
                        ['name' => 'account_number', 'label' => 'Account Number / खाता संख्या', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'bank_name', 'label' => 'Bank Name / बैंक का नाम', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'branch_name', 'label' => 'Branch / शाखा', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'branch_address', 'label' => 'Branch Address / शाखा का पता', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'micr', 'label' => 'MICR Code / एमआईसीआर कोड', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'ifsc', 'label' => 'IFSC Code / आईएफएससी कोड', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'Scheme Preference / योजना वरीयता',
                    'fields'  => [
                        ['name' => 'pension_fund', 'label' => 'Pension Fund Manager (PFM) / पेंशन निधि प्रबंधक (पीएफएम)', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'investment_option', 'label' => 'Investment Option / निवेश विकल्प', 'type' => 'select', 'options' => ['Active Choice', 'Auto Choice'], 'width' => 'col-md-3'],
                        ['name' => 'tier_ii', 'label' => 'Tier II Account? / टियर II खाता?', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-3'],
                        ['name' => 'tax_resident_outside', 'label' => 'Tax resident of any country other than India? (FATCA) / क्या भारत के अतिरिक्त किसी अन्य देश के कर निवासी हैं? (एफएटीसीए)', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'nominees',
                    'heading' => 'Nomination Details / नामांकन विवरण',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Nominee Name / नामिती का नाम', 'type' => 'text'],
                        ['name' => 'relationship', 'label' => 'Relationship / संबंध', 'type' => 'text'],
                        ['name' => 'age', 'label' => 'Age / आयु', 'type' => 'text'],
                        ['name' => 'percentage', 'label' => 'Share % / हिस्सा %', 'type' => 'text'],
                        ['name' => 'guardian', 'label' => 'Guardian (if nominee is a minor) / अभिभावक (यदि नामिती अवयस्क हो)', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the information furnished above is true and correct to the best of my knowledge and belief. I undertake to intimate any change in the above information to the CRA / Nodal Office. I have read and understood the terms and conditions of the NPS and agree to be bound by them. / मैं एतद्द्वारा घोषणा करता/करती हूँ कि उपर्युक्त दी गई जानकारी मेरी जानकारी एवं विश्वास के अनुसार सत्य और सही है। मैं उपर्युक्त जानकारी में किसी भी परिवर्तन की सूचना सीआरए / नोडल कार्यालय को देने का वचन देता/देती हूँ। मैंने एनपीएस के नियम एवं शर्तों को पढ़ और समझ लिया है तथा उनसे बाध्य होने के लिए सहमत हूँ।',
            'notes' => [
                'Up to three nominees may be registered here; for more, use the prescribed annexure. / यहाँ अधिकतम तीन नामिती पंजीकृत किए जा सकते हैं; अधिक के लिए निर्धारित अनुबंध का उपयोग करें।',
                'If the total share does not add up to 100%, it will be treated as invalid. / यदि कुल हिस्सा 100% नहीं होता है, तो इसे अमान्य माना जाएगा।',
            ],
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature / Thumb Impression of the Subscriber / अंशदाता के हस्ताक्षर / अंगूठे का निशान'],
        ];
    }

    private static function employeeInfoSheet(): array
    {
        return [
            'key'      => 'employee_info_sheet',
            'title'    => 'Employee Information Sheet (EIS)',
            'title_hi' => 'कर्मचारी सूचना पत्रक (ईआईएस)',
            'subtitle' => 'PFMS — Form EIS/B/1',
            // Dedicated views reproducing the official EIS/B/1 numbered-grid layout.
            'form_view' => 'fc.registration.document-forms.eis',
            'pdf_view'  => 'fc.registration.document-forms.pdf.eis',
            'sections' => [
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'gender', 'label' => 'Gender / लिंग', 'type' => 'select', 'options' => ['Male', 'Female', 'Transgender'], 'width' => 'col-md-3'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth / जन्म तिथि', 'type' => 'date', 'width' => 'col-md-3'],
                        ['name' => 'employee_type', 'label' => 'Type / प्रकार', 'type' => 'select', 'options' => ['Pensionable', 'NPS'], 'width' => 'col-md-3'],
                        ['name' => 'pan', 'label' => 'PAN / पैन', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'aadhaar', 'label' => 'Aadhaar No. / आधार संख्या', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'employee_code', 'label' => 'Employee Code / कर्मचारी कोड', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'date_of_entry_govt', 'label' => 'Date of Entry in Govt. Service / सरकारी सेवा में प्रवेश की तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'date_of_superannuation', 'label' => 'Date of Superannuation / अधिवर्षिता की तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'physically_disabled', 'label' => 'Physically Disabled? / शारीरिक रूप से दिव्यांग?', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Posting Details / तैनाती विवरण',
                    'fields'  => [
                        ['name' => 'current_office', 'label' => 'Current Office / वर्तमान कार्यालय', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पदनाम', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'group', 'label' => 'Group / समूह', 'type' => 'select', 'options' => ['A', 'B', 'C'], 'width' => 'col-md-3'],
                        ['name' => 'date_of_joining_office', 'label' => 'Date of Joining Office / कार्यालय में कार्यभार ग्रहण करने की तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'date_in_current_post', 'label' => 'Date in Current Post / वर्तमान पद पर तैनाती की तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'city_class', 'label' => 'City Class (X/Y/Z) / नगर श्रेणी (X/Y/Z)', 'type' => 'select', 'options' => ['X', 'Y', 'Z'], 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Pay Details / वेतन विवरण',
                    'fields'  => [
                        ['name' => 'pay_commission', 'label' => 'Pay Commission / वेतन आयोग', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pay_level', 'label' => 'Pay Level / वेतन स्तर', 'type' => 'text', 'width' => 'col-md-2'],
                        ['name' => 'basic_pay', 'label' => 'Basic Pay (₹) / मूल वेतन (₹)', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pay_wef', 'label' => 'Pay w.e.f. Date / वेतन प्रभावी तिथि', 'type' => 'date', 'width' => 'col-md-2'],
                        ['name' => 'next_increment_date', 'label' => 'Next Increment Date / अगली वेतन वृद्धि की तिथि', 'type' => 'date', 'width' => 'col-md-2'],
                    ],
                ],
                [
                    'heading' => 'PF / NPS Details / पीएफ-एनपीएस विवरण',
                    'fields'  => [
                        ['name' => 'pf_type', 'label' => 'PF Type / पीएफ प्रकार', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pf_agency', 'label' => 'PF Agency / पीएफ अभिकरण', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pf_series', 'label' => 'PF Series / पीएफ शृंखला', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'pran_no', 'label' => 'PF / PRAN No. / पीएफ / प्रान संख्या', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'CGEGIS / CGHS / Category / सीजीईजीआईएस / सीजीएचएस / श्रेणी',
                    'fields'  => [
                        ['name' => 'cgegis', 'label' => 'CGEGIS Applicable? / सीजीईजीआईएस लागू?', 'type' => 'select', 'options' => ['Yes', 'No'], 'width' => 'col-md-2'],
                        ['name' => 'cghs', 'label' => 'CGHS Applicable? / सीजीएचएस लागू?', 'type' => 'select', 'options' => ['Yes', 'No'], 'width' => 'col-md-2'],
                        ['name' => 'cghs_card_no', 'label' => 'CGHS Card No. / सीजीएचएस कार्ड संख्या', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'category', 'label' => 'Category / श्रेणी', 'type' => 'select', 'options' => ['General', 'OBC', 'SC', 'ST'], 'width' => 'col-md-2'],
                        ['name' => 'ex_serviceman', 'label' => 'Ex-Serviceman? / भूतपूर्व सैनिक?', 'type' => 'select', 'options' => ['No', 'Yes'], 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'Bank &amp; Contact / बैंक एवं संपर्क',
                    'fields'  => [
                        ['name' => 'ifsc', 'label' => 'IFSC Code / आईएफएससी कोड', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'bank_name', 'label' => 'Bank Name / बैंक का नाम', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'branch', 'label' => 'Branch / शाखा', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'account_number', 'label' => 'Savings A/c No. / बचत खाता संख्या', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'mobile', 'label' => 'Mobile No. / मोबाइल संख्या', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'email', 'label' => 'Email / ईमेल', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Officer / Official / अधिकारी / कर्मचारी के हस्ताक्षर'],
        ];
    }

    private static function debtsLiabilities(): array
    {
        return [
            'key'      => 'debts_liabilities',
            'title'    => 'Statement of Debts and Other Liabilities on First Appointment',
            'title_hi' => 'प्रथम नियुक्ति पर ऋणों तथा अन्य देयताओं का विवरण',
            'subtitle' => 'Form No. 6-C',
            // Dedicated document-faithful, bilingual Document-6-C layout (numbered columns).
            'form_view' => 'fc.registration.document-forms.debts_liabilities',
            'pdf_view'  => 'fc.registration.document-forms.pdf.debts_liabilities',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name (in Block Letters) / नाम (स्पष्ट अक्षरों में)', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Service (Name of Service) / सेवा', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'present_post', 'label' => 'Present Post held / वर्तमान में धारित पद', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'as_on_date', 'label' => 'Statement as on Date / जिस तिथि तक विवरण है', 'type' => 'date', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'liabilities',
                    'heading' => 'Debts &amp; Other Liabilities / ऋण एवं अन्य देयताएँ',
                    'columns' => [
                        ['name' => 'amount', 'label' => 'Amount / राशि', 'type' => 'text'],
                        ['name' => 'creditor', 'label' => 'Name and address of Creditor / ऋणदाता का नाम एवं पता', 'type' => 'text'],
                        ['name' => 'date_incurred', 'label' => 'Date of incurring Liability / देयता उत्पन्न होने की तिथि', 'type' => 'date'],
                        ['name' => 'details', 'label' => 'Details of Transaction / लेन-देन का विवरण', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks / टिप्पणी', 'type' => 'text'],
                    ],
                ],
            ],
            'notes' => [
                'Indicate the source from which the loan was raised and the purpose for which it was utilised. / उस स्रोत को इंगित करें जिससे ऋण लिया गया तथा वह प्रयोजन जिसके लिए उसका उपयोग किया गया।',
                'In the Remarks column, indicate the sanction/permission obtained from, or the report made to, the competent authority, if any. / टिप्पणी स्तंभ में, सक्षम प्राधिकारी से प्राप्त स्वीकृति/अनुमति अथवा उसे की गई सूचना, यदि कोई हो, इंगित करें।',
            ],
            'declaration' => 'I hereby declare that the above statement of my debts and other liabilities is true and complete to the best of my knowledge and belief. / मैं एतद्द्वारा घोषणा करता/करती हूँ कि मेरे ऋणों तथा अन्य देयताओं का उपर्युक्त विवरण मेरी जानकारी एवं विश्वास के अनुसार सत्य एवं पूर्ण है।',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर'],
        ];
    }

    private static function immovableProperty(): array
    {
        return [
            'key'      => 'immovable_property',
            'title'    => 'Statement of Immovable Property on First Appointment',
            'title_hi' => 'प्रथम नियुक्ति पर अचल संपत्ति का विवरण',
            'subtitle' => 'Form No. 6-A (Form 1 — see Rule 16 AIS (Conduct) Rules / Rule 18 CCS (Conduct) Rules)',
            // Dedicated document-faithful, bilingual Document-6(a) layout (numbered columns).
            'form_view' => 'fc.registration.document-forms.immovable_property',
            'pdf_view'  => 'fc.registration.document-forms.pdf.immovable_property',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of the Officer (in full) and Service to which he/she belongs / अधिकारी का नाम (पूर्ण रूप में) तथा वह सेवा जिससे वह संबंधित है', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'present_post', 'label' => 'Present Post held / वर्तमान में धारित पद', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'cadre', 'label' => 'Cadre of the State on which borne / जिस राज्य के संवर्ग में सम्मिलित है', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'present_pay', 'label' => 'Present Pay (₹) / वर्तमान वेतन (₹)', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'properties',
                    'heading' => 'Immovable Property / अचल संपत्ति',
                    'columns' => [
                        ['name' => 'location', 'label' => 'Name of District, Sub-Division, Taluk and Village in which property is situated / जिला, उप-मंडल, तालुक तथा गाँव का नाम जिसमें संपत्ति स्थित है', 'type' => 'text'],
                        ['name' => 'property_details', 'label' => 'Name and details of Property (Housing/other building and Land) / संपत्ति का नाम एवं विवरण (आवास/अन्य भवन तथा भूमि)', 'type' => 'text'],
                        ['name' => 'present_value', 'label' => 'Present Value / वर्तमान मूल्य', 'type' => 'text'],
                        ['name' => 'in_whose_name', 'label' => 'If not in own name, in whose name held and his/her relationship to the officer / यदि स्वयं के नाम पर नहीं है, तो किसके नाम पर धारित है तथा अधिकारी के साथ उसका संबंध', 'type' => 'text'],
                        ['name' => 'how_acquired', 'label' => 'How acquired (purchase/lease/mortgage/inheritance/gift, etc.) with date of acquisition and name &amp; details of persons from whom acquired / किस प्रकार अर्जित की गई (क्रय/पट्टा/बंधक/उत्तराधिकार/उपहार आदि) अर्जन की तिथि तथा जिन व्यक्तियों से अर्जित की गई उनके नाम एवं विवरण सहित', 'type' => 'text'],
                        ['name' => 'annual_income', 'label' => 'Annual Income from the Property / संपत्ति से वार्षिक आय', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks / टिप्पणी', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the above statement of immovable property is true and complete to the best of my knowledge and belief. / मैं एतद्द्वारा घोषणा करता/करती हूँ कि अचल संपत्ति का उपर्युक्त विवरण मेरी जानकारी एवं विश्वास के अनुसार सत्य एवं पूर्ण है।',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर'],
        ];
    }

    private static function movableProperty(): array
    {
        return [
            'key'      => 'movable_property',
            'title'    => 'Statement of Movable Property on First Appointment',
            'title_hi' => 'प्रथम नियुक्ति पर चल संपत्ति का विवरण',
            'subtitle' => 'Form No. 6-B',
            // Dedicated document-faithful, bilingual Document-6-B layout (5 numbered columns).
            'form_view' => 'fc.registration.document-forms.movable_property',
            'pdf_view'  => 'fc.registration.document-forms.pdf.movable_property',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of the Officer (in full) and Service to which he/she belongs / अधिकारी का नाम (पूर्ण रूप में) तथा वह सेवा जिससे वह संबंधित है', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'present_post', 'label' => 'Present Post held / वर्तमान में धारित पद', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'present_pay', 'label' => 'Present Pay (₹) / वर्तमान वेतन (₹)', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'year', 'label' => 'Year / वर्ष', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'movables',
                    'heading' => 'Movable Property / चल संपत्ति',
                    'columns' => [
                        ['name' => 'description', 'label' => 'Name and details of Movable Property / चल संपत्ति का नाम एवं विवरण', 'type' => 'text'],
                        ['name' => 'present_value', 'label' => 'Present Value / वर्तमान मूल्य', 'type' => 'text'],
                        ['name' => 'in_whose_name', 'label' => 'If not in own name, in whose name held and his/her relationship to the Government Servant / यदि स्वयं के नाम पर नहीं है, तो किसके नाम पर धारित है तथा सरकारी कर्मचारी के साथ उसका संबंध', 'type' => 'text'],
                        ['name' => 'how_acquired', 'label' => 'How acquired (purchase/inheritance/gift, etc.) with date of acquisition and name &amp; details of persons from whom acquired / किस प्रकार अर्जित की गई (क्रय/उत्तराधिकार/उपहार आदि) अर्जन की तिथि तथा जिन व्यक्तियों से अर्जित की गई उनके नाम एवं विवरण सहित', 'type' => 'text'],
                        ['name' => 'remarks', 'label' => 'Remarks / टिप्पणी', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the above statement of movable property is true and complete to the best of my knowledge and belief. / मैं एतद्द्वारा घोषणा करता/करती हूँ कि चल संपत्ति का उपर्युक्त विवरण मेरी जानकारी एवं विश्वास के अनुसार सत्य एवं पूर्ण है।',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर'],
        ];
    }

    private static function closeRelation(): array
    {
        $columns = [
            ['name' => 'relation', 'label' => 'Relation / संबंध', 'type' => 'text'],
            ['name' => 'name', 'label' => 'Name / नाम', 'type' => 'text'],
            ['name' => 'nationality', 'label' => 'Nationality / राष्ट्रीयता', 'type' => 'text'],
            ['name' => 'present_address', 'label' => 'Present Address / वर्तमान पता', 'type' => 'text'],
            ['name' => 'place_of_birth', 'label' => 'Place of Birth / जन्म स्थान', 'type' => 'text'],
            ['name' => 'occupation', 'label' => 'Occupation / व्यवसाय', 'type' => 'text'],
        ];

        return [
            'key'      => 'close_relation',
            'title'    => 'Declaration of Close Relations who are Foreign Nationals / Domiciled Abroad',
            'title_hi' => 'विदेशी नागरिक / विदेश में अधिवासित निकट संबंधियों की घोषणा',
            'subtitle' => 'Form to be filled by Government employees on first appointment (MHA O.M. No. F.3/12(S)/64-Ests.(B), dated 12-10-1965)',
            // Dedicated document-faithful, bilingual Document-2 layout (fixed relation rows i–vii).
            'form_view' => 'fc.registration.document-forms.close_relation',
            'pdf_view'  => 'fc.registration.document-forms.pdf.close_relation',
            'intro'    => 'Give particulars of close relations (father, mother, wife/husband, sons, daughters, brothers, sisters) who are (A) nationals of, or domiciled in, other countries, or (B) of non-Indian origin residing in India. If there are no such relations, write &ldquo;NIL&rdquo;. ऐसे निकट संबंधियों (पिता, माता, पत्नी/पति, पुत्र, पुत्रियाँ, भाई, बहन) का विवरण दें जो (क) अन्य देशों के नागरिक हैं या वहाँ अधिवासित हैं, अथवा (ख) भारत में निवास कर रहे गैर-भारतीय मूल के हैं। यदि ऐसा कोई संबंधी नहीं है, तो &ldquo;निरंक&rdquo; लिखें।',
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
                    'heading' => 'A. Close relations who are nationals of, or are domiciled in, other countries / क. ऐसे निकट संबंधी जो अन्य देशों के नागरिक हैं अथवा वहाँ अधिवासित हैं',
                    'columns' => $columns,
                ],
                [
                    'key'     => 'non_indian_origin',
                    'heading' => 'B. Close relations residing in India who are of non-Indian origin / ख. भारत में निवास कर रहे ऐसे निकट संबंधी जो गैर-भारतीय मूल के हैं',
                    'columns' => $columns,
                ],
            ],
            'declaration' => 'I certify that the foregoing information is correct and complete to the best of my knowledge and belief. / मैं प्रमाणित करता/करती हूँ कि उपर्युक्त जानकारी मेरी जानकारी और विश्वास के अनुसार सही और पूर्ण है।',
            'notes' => [
                'Suppression of any factual information which the Government servant is required to convey would be regarded as a serious matter and would expose the official concerned to serious consequences. / सरकारी कर्मचारी द्वारा दी जाने वाली किसी भी तथ्यात्मक जानकारी को छिपाना गंभीर मामला माना जाएगा और संबंधित अधिकारी को गंभीर परिणामों का सामना करना पड़ेगा।',
                'Any subsequent change in the above particulars should be reported to the Head of Office. / उपर्युक्त विवरण में बाद में हुए किसी भी परिवर्तन की सूचना कार्यालय प्रमुख को दी जानी चाहिए।',
            ],
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर'],
        ];
    }

    private static function dowryDeclaration(): array
    {
        return [
            'key'      => 'dowry_declaration',
            'title'    => 'Dowry Declaration',
            'title_hi' => 'दहेज घोषणा',
            'subtitle' => 'Under the Dowry Prohibition Act, 1961 (Rule 13-A, CCS (Conduct) Rules, 1964)',
            // Dedicated document-faithful, bilingual Document-3 layout (declaration + rule clarification).
            'form_view' => 'fc.registration.document-forms.dowry_declaration',
            'pdf_view'  => 'fc.registration.document-forms.pdf.dowry_declaration',
            'sections' => [
                [
                    'heading' => 'Declarant Details / घोषणाकर्ता विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name of Officer (in Block Letters) / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Name of Service / सेवा का नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'marital_choice', 'label' => 'Marital Status / वैवाहिक स्थिति', 'type' => 'select', 'options' => ['Unmarried', 'Married'], 'width' => 'col-md-4'],
                        ['name' => 'parent_guardian_name', 'label' => 'Name of Parent / Guardian / माता-पिता / संरक्षक का नाम', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'parent_guardian_address', 'label' => 'Address of Parent / Guardian / माता-पिता / संरक्षक का पता', 'type' => 'textarea', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'declaration' => 'I hereby solemnly declare that I have neither demanded nor taken/given, nor shall I demand or take/give, any dowry, directly or indirectly, in connection with my marriage, in accordance with the provisions of the Dowry Prohibition Act, 1961. / मैं एतद्द्वारा सत्यनिष्ठा से घोषणा करता/करती हूँ कि दहेज प्रतिषेध अधिनियम, 1961 के उपबंधों के अनुसार, मैंने अपने विवाह के संबंध में प्रत्यक्ष अथवा अप्रत्यक्ष रूप से न तो कोई दहेज माँगा है और न ही लिया/दिया है, और न ही भविष्य में कोई दहेज माँगूँगा/माँगूँगी अथवा लूँगा/लूँगी/दूँगा/दूँगी।',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature (Name of Officer in Block Letters) / हस्ताक्षर (अधिकारी का नाम बड़े अक्षरों में)'],
        ];
    }

    private static function homeTown(): array
    {
        return [
            'key'      => 'home_town',
            'title'    => 'Home Town Declaration',
            'title_hi' => 'गृह नगर घोषणा',
            'subtitle' => 'For the purpose of Leave Travel Concession (MHA Memo No. 43/715/57-Ests.(A), dated 24-06-1958)',
            // Dedicated document-faithful, bilingual Document-5 declaration layout.
            'form_view' => 'fc.registration.document-forms.home_town',
            'pdf_view'  => 'fc.registration.document-forms.pdf.home_town',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation (name of service followed by "Probationer") / पद नाम (सेवा का नाम, तत्पश्चात "परिवीक्षाधीन")', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'town_village', 'label' => 'Name of Town / Village / नगर / गाँव का नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'district', 'label' => 'District / जिला', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'state', 'label' => 'State / राज्य', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'reason', 'label' => 'Reason (mention a, b, c or d whichever is applicable) / कारण (क, ख, ग अथवा घ में से जो लागू हो उसका उल्लेख करें)', 'type' => 'text', 'width' => 'col-md-12'],
                    ],
                ],
            ],
            'notes' => [
                '(a) The place is the one which requires your physical presence at intervals for discharging various domestic and social obligations, and you visit it frequently. / (क) वह स्थान जहाँ विभिन्न घरेलू और सामाजिक दायित्वों के निर्वहन हेतु समय-समय पर आपकी शारीरिक उपस्थिति आवश्यक होती है और जहाँ आप प्रायः जाते हैं।',
                '(b) You own residential property in that place, or you are a member of a joint family having such property there. / (ख) उस स्थान पर आपकी आवासीय संपत्ति है, अथवा आप ऐसे संयुक्त परिवार के सदस्य हैं जिसकी वहाँ ऐसी संपत्ति है।',
                '(c) Your near relations are permanently residing in that place. / (ग) आपके निकट संबंधी उस स्थान पर स्थायी रूप से निवास कर रहे हैं।',
                '(d) Prior to your entry into Government service, you had been living there for some years. / (घ) सरकारी सेवा में प्रवेश से पूर्व आप कुछ वर्षों तक वहाँ निवास कर रहे थे।',
            ],
            'declaration' => "I declare that my 'Home Town' for the purpose of Leave Travel Concession is as indicated above and the particulars given are correct. / मैं घोषणा करता/करती हूँ कि अवकाश यात्रा रियायत के प्रयोजन हेतु मेरा 'गृह नगर' उपर्युक्त अनुसार है तथा दिया गया विवरण सही है।",
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर'],
        ];
    }

    private static function maritalStatus(): array
    {
        return [
            'key'      => 'marital_status',
            'title'    => 'Declaration Regarding Marital Status',
            'title_hi' => 'वैवाहिक स्थिति संबंधी घोषणा',
            'subtitle' => 'Under Rule 21, CCS (Conduct) Rules, 1964',
            // Dedicated document-faithful, bilingual Document-4 layout (declaration + exemption application).
            'form_view' => 'fc.registration.document-forms.marital_status',
            'pdf_view'  => 'fc.registration.document-forms.pdf.marital_status',
            'sections' => [
                [
                    'heading' => 'Government Servant Details / सरकारी कर्मचारी का विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'status_clause', 'label' => 'Applicable Declaration (select the clause that applies) / लागू घोषणा (जो खंड लागू हो उसे चुनें)', 'type' => 'select', 'required' => true, 'width' => 'col-md-12', 'options' => [
                            'I am unmarried / a widower / a widow',
                            'I am married and have only one spouse living',
                            'I am married and have more than one spouse living (exemption applied for)',
                            'I am about to marry a person who has a spouse living (exemption applied for)',
                        ]],
                        ['name' => 'exemption_reasons', 'label' => 'Reasons for seeking exemption (if applicable) / छूट माँगने के कारण (यदि लागू हो)', 'type' => 'textarea', 'width' => 'col-md-12'],
                    ],
                ],
            ],
            'declaration' => 'I solemnly affirm that the above declaration is true, and I understand that if the declaration is found to be false, I shall be liable to be dismissed from service. / मैं सत्यनिष्ठा से पुष्टि करता/करती हूँ कि उपर्युक्त घोषणा सत्य है, तथा मैं समझता/समझती हूँ कि यदि यह घोषणा असत्य पाई जाती है, तो मैं सेवा से पदच्युत किए जाने का दायी होऊँगा/होऊँगी।',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Full Signature of the Government Servant / सरकारी कर्मचारी के पूर्ण हस्ताक्षर'],
        ];
    }

    private static function oathAffirmation(): array
    {
        return [
            'key'      => 'oath_affirmation',
            'title'    => 'FORM OF OATH/AFFIRMATION',
            'subtitle' => '[MHA OM No. 31/3/65-Estt.(A) dated 23-3-1964- as amended from time to time]',
            'title_hi' => 'शपथ / पुष्टि प्रपत्र',
            // Dedicated document-faithful, bilingual views (fill-in-the-blank layout).
            'form_view' => 'fc.registration.document-forms.oath',
            'pdf_view'  => 'fc.registration.document-forms.pdf.oath',
            'sections' => [
                [
                    'heading' => 'Details / विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name (in capital letters) / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Service / सेवा', 'type' => 'text', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Officer / अधिकारी के हस्ताक्षर'],
        ];
    }

    private static function suretyBondIas(): array
    {
        return [
            'key'      => 'surety_bond_ias',
            'title'    => 'Surety Bond (IAS / IPS / IFoS)',
            'title_hi' => 'प्रतिभूति बंधपत्र (आई.ए.एस. / आई.पी.एस. / आई.एफ़.ओ.एस.)',
            'subtitle' => 'To be executed on Non-Judicial Stamp Paper of ₹100',
            // Dedicated document-faithful, bilingual bond (Document-7-A) — fill-in-the-blank prose.
            'form_view' => 'fc.registration.document-forms.surety_bond_ias',
            'pdf_view'  => 'fc.registration.document-forms.pdf.surety_bond_ias',
            'intro'    => 'Bond executed by the Probationer together with one Surety, binding the Probationer to serve the Government for the prescribed period and to refund the cost of training / pay and allowances in the event of default, in accordance with the applicable Service Rules. / परिवीक्षाधीन अधिकारी द्वारा एक प्रतिभू के साथ निष्पादित बंधपत्र, जो परिवीक्षाधीन अधिकारी को निर्धारित अवधि तक सरकार की सेवा करने और चूक की स्थिति में लागू सेवा नियमों के अनुसार प्रशिक्षण की लागत / वेतन एवं भत्तों की प्रतिपूर्ति करने के लिए आबद्ध करता है।',
            'sections' => [
                [
                    'heading' => 'Probationer / परिवीक्षाधीन अधिकारी',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Full Name and Address of the Probationer / परिवीक्षाधीन अधिकारी का पूरा नाम और पता', 'type' => 'textarea', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'service', 'label' => 'Service / सेवा', 'type' => 'select', 'options' => ['IAS', 'IPS', 'IFoS'], 'width' => 'col-md-3'],
                        ['name' => 'exam_year', 'label' => 'Year of Examination / परीक्षा का वर्ष', 'type' => 'text', 'width' => 'col-md-3'],
                        ['name' => 'service_rule', 'label' => 'Applicable Service Rule / लागू सेवा नियम', 'type' => 'text', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Surety / प्रतिभू',
                    'fields'  => [
                        ['name' => 'surety_name', 'label' => 'Full Name of Surety / प्रतिभू का पूरा नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'surety_address', 'label' => 'Address of Surety / प्रतिभू का पता', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'surety_occupation', 'label' => 'Occupation of Surety / प्रतिभू का व्यवसाय', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'surety_eligibility', 'label' => 'Surety is / प्रतिभू है', 'type' => 'select', 'options' => ['In the permanent service of Government', 'Ordinarily resident in India'], 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Witness to Probationer / परिवीक्षाधीन के साक्षी',
                    'fields'  => [
                        ['name' => 'prob_witness_name', 'label' => 'Name of Witness / साक्षी का नाम', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'prob_witness_address', 'label' => 'Address / पता', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'prob_witness_occupation', 'label' => 'Occupation / व्यवसाय', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
                [
                    'heading' => 'Witness to Surety / प्रतिभू के साक्षी',
                    'fields'  => [
                        ['name' => 'surety_witness_name', 'label' => 'Name of Witness / साक्षी का नाम', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'surety_witness_address', 'label' => 'Address / पता', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'surety_witness_occupation', 'label' => 'Occupation / व्यवसाय', 'type' => 'text', 'width' => 'col-md-4'],
                    ],
                ],
            ],
            'declaration' => 'NOW THE CONDITION of the above-written bond is that if the Probationer shall faithfully serve the Government for the prescribed period and shall duly fulfil the terms of his/her appointment, then this bond shall be void; otherwise the Probationer and the Surety bind themselves, their heirs, executors and administrators, jointly and severally, to pay to the Government the amounts due under the bond. / उपर्युक्त बंधपत्र की शर्त यह है कि यदि परिवीक्षाधीन अधिकारी निर्धारित अवधि तक सरकार की निष्ठापूर्वक सेवा करता है और अपनी नियुक्ति की शर्तों को विधिवत पूरा करता है, तो यह बंधपत्र शून्य हो जाएगा; अन्यथा परिवीक्षाधीन अधिकारी और प्रतिभू स्वयं को, अपने उत्तराधिकारियों, निष्पादकों एवं प्रशासकों को संयुक्त रूप से तथा पृथक-पृथक रूप से इस बंधपत्र के अंतर्गत देय राशि सरकार को भुगतान करने के लिए आबद्ध करते हैं।',
            'sections_footer' => [self::dateOnlySection()],
            'signatures' => ['Signature of the Probationer / परिवीक्षाधीन अधिकारी के हस्ताक्षर', 'Signature of the Surety / प्रतिभू के हस्ताक्षर'],
        ];
    }

    private static function suretyBondOthers(): array
    {
        $tpl = self::suretyBondIas();
        $tpl['key']      = 'surety_bond_others';
        $tpl['title']    = 'Surety Bond (Central Civil Services, Group-A — Other than All India Services)';
        $tpl['title_hi'] = 'प्रतिभूति बंधपत्र (केंद्रीय सिविल सेवाएँ, समूह-क — अखिल भारतीय सेवाओं से इतर)';
        // Service becomes a free-text field for other services.
        $tpl['sections'][0]['fields'][1] = ['name' => 'service', 'label' => 'Name of Service / सेवा का नाम', 'type' => 'text', 'width' => 'col-md-3'];
        // Dedicated document-faithful, bilingual bond (Document-7-B) — fill-in-the-blank prose.
        $tpl['form_view'] = 'fc.registration.document-forms.surety_bond_others';
        $tpl['pdf_view']  = 'fc.registration.document-forms.pdf.surety_bond_others';

        return $tpl;
    }

    private static function assumptionCharge(): array
    {
        return [
            'key'      => 'assumption_charge',
            'title'    => 'Certificate of Assumption of Charge',
            'title_hi' => 'कार्यभार ग्रहण प्रमाण-पत्र',
            // Dedicated document-faithful, bilingual prose certificate (fill-in-the-blank).
            'form_view' => 'fc.registration.document-forms.assumption_charge',
            'pdf_view'  => 'fc.registration.document-forms.pdf.assumption_charge',
            'sections' => [
                [
                    'heading' => 'Charge Details / कार्यभार विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Name / नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'designation', 'label' => 'Designation / पद नाम', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'post_assumed', 'label' => 'Post Assumed / ग्रहण किया गया पद', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'place_of_posting', 'label' => 'Place of Posting / तैनाती का स्थान', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_assumption', 'label' => 'Date of Assumption / कार्यभार ग्रहण की तिथि', 'type' => 'date', 'width' => 'col-md-6'],
                        ['name' => 'time_of_assumption', 'label' => 'Time (FN / AN) / समय (पूर्वाह्न / अपराह्न)', 'type' => 'select', 'options' => ['Forenoon (FN)', 'Afternoon (AN)'], 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'declaration' => 'Certified that I have assumed charge of the post indicated above on the date and time stated. / प्रमाणित किया जाता है कि मैंने उपर्युक्त दर्शाए गए पद का कार्यभार उल्लिखित तिथि और समय पर ग्रहण कर लिया है।',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Officer / अधिकारी के हस्ताक्षर'],
        ];
    }

    private static function policeVerification(): array
    {
        return [
            'key'      => 'police_verification',
            'title'    => 'Police Verification / Attestation Form',
            'title_hi' => 'पुलिस सत्यापन / प्रमाणन प्रपत्र',
            'sections' => [
                [
                    'heading' => 'Personal Details / व्यक्तिगत विवरण',
                    'fields'  => [
                        ['name' => 'officer_name', 'label' => 'Full Name / पूरा नाम', 'type' => 'text', 'required' => true, 'width' => 'col-md-6'],
                        ['name' => 'father_name', 'label' => "Father's Name / पिता का नाम", 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'date_of_birth', 'label' => 'Date of Birth / जन्म तिथि', 'type' => 'date', 'width' => 'col-md-4'],
                        ['name' => 'place_of_birth', 'label' => 'Place of Birth / जन्म स्थान', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'nationality', 'label' => 'Nationality / राष्ट्रीयता', 'type' => 'text', 'width' => 'col-md-4'],
                        ['name' => 'mobile', 'label' => 'Mobile No. / मोबाइल नं.', 'type' => 'text', 'width' => 'col-md-6'],
                        ['name' => 'email', 'label' => 'Email / ई-मेल', 'type' => 'email', 'width' => 'col-md-6'],
                    ],
                ],
                [
                    'heading' => 'Present Address / वर्तमान पता',
                    'fields'  => [
                        ['name' => 'present_address', 'label' => 'Present Address / वर्तमान पता', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'present_since', 'label' => 'Residing Since / निवास की तिथि से', 'type' => 'date', 'width' => 'col-md-3'],
                        ['name' => 'present_police_station', 'label' => 'Police Station / पुलिस थाना', 'type' => 'text', 'width' => 'col-md-3'],
                    ],
                ],
                [
                    'heading' => 'Permanent Address / स्थायी पता',
                    'fields'  => [
                        ['name' => 'permanent_address', 'label' => 'Permanent Address / स्थायी पता', 'type' => 'textarea', 'width' => 'col-md-6'],
                        ['name' => 'permanent_police_station', 'label' => 'Police Station / पुलिस थाना', 'type' => 'text', 'width' => 'col-md-6'],
                    ],
                ],
            ],
            'tables' => [
                [
                    'key'     => 'references',
                    'heading' => 'References / संदर्भ (two responsible persons)',
                    'columns' => [
                        ['name' => 'name', 'label' => 'Name / नाम', 'type' => 'text'],
                        ['name' => 'address', 'label' => 'Address / पता', 'type' => 'text'],
                        ['name' => 'contact', 'label' => 'Contact No. / संपर्क नं.', 'type' => 'text'],
                    ],
                ],
            ],
            'declaration' => 'I hereby declare that the particulars furnished above are true, complete and correct to the best of my knowledge and belief. / मैं एतद्द्वारा घोषणा करता/करती हूँ कि ऊपर दिए गए विवरण मेरी जानकारी और विश्वास के अनुसार सत्य, पूर्ण और सही हैं।',
            'sections_footer' => [self::placeDateSection()],
            'signatures' => ['Signature of the Applicant / आवेदक के हस्ताक्षर'],
        ];
    }
}
