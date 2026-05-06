<?php

namespace App\Services\FC;

use Illuminate\Support\Collection;

/**
 * Builds bilingual (EN + HI) section rows for FC student registration PDF.
 * Add or remove entries in the *_rows() methods to change exported fields.
 */
class FcStudentRegistrationPdfBuilder
{
    public function __construct(
        public object $step1,
        public ?object $step2,
        public ?object $master,
        public ?object $bank,
        public Collection $qualifications,
        public Collection $employments,
        public Collection $languages,
        public Collection $additionalDynamicFields,
        public string $username,
    ) {}

    /**
     * @return list<array{key:string,title_en:string,title_hi:string,type:string,rows?:list<array{en:string,hi:string,value:mixed}>,columns?:list<string>,head_hi?:list<string>,body?:list<list<string>>}>
     */
    public function sections(): array
    {
        $out = [];

        $out[] = [
            'key' => 'identity',
            'title_en' => 'Candidate particulars',
            'title_hi' => 'अभ्यर्थी विवरण',
            'type' => 'fields',
            'rows' => $this->identityRows(),
        ];

        $out[] = [
            'key' => 'personal',
            'title_en' => 'Personal & contact',
            'title_hi' => 'व्यक्तिगत एवं सम्पर्क',
            'type' => 'fields',
            'rows' => $this->personalRows(),
        ];

        if ($this->qualifications->isNotEmpty()) {
            $out[] = [
                'key' => 'qualifications',
                'title_en' => 'Educational qualifications',
                'title_hi' => 'शैक्षिक योग्यता',
                'type' => 'table',
                'columns' => ['Qualification / योग्यता', 'Degree', 'Board / Univ.', 'Institution', 'Year', '% / CGPA'],
                'head_hi' => ['', '', '', '', '', ''],
                'body' => $this->qualifications->map(function ($q) {
                    return [
                        (string) ($q->qualification_name ?? '-'),
                        (string) ($q->degree_name ?? '-'),
                        (string) ($q->board_name ?? '-'),
                        (string) ($q->institution_name ?? '-'),
                        (string) ($q->year_of_passing ?? '-'),
                        (string) ($q->percentage_cgpa ?? '-'),
                    ];
                })->all(),
            ];
        }

        if ($this->employments->isNotEmpty()) {
            $out[] = [
                'key' => 'employment',
                'title_en' => 'Employment history',
                'title_hi' => 'कार्य अनुभव',
                'type' => 'table',
                'columns' => ['Organisation', 'Designation', 'Type', 'From', 'To', 'Current'],
                'head_hi' => ['संस्था', 'पद', 'प्रकार', 'से', 'तक', 'वर्तमान'],
                'body' => $this->employments->map(function ($e) {
                    return [
                        (string) ($e->organisation_name ?? '-'),
                        (string) ($e->designation ?? '-'),
                        (string) ($e->job_type_name ?? '-'),
                        (string) ($e->from_date ?? '-'),
                        (string) ($e->to_date ?? '-'),
                        ($e->is_current ?? false) ? 'Yes / हाँ' : 'No / नहीं',
                    ];
                })->all(),
            ];
        }

        if ($this->languages->isNotEmpty()) {
            $out[] = [
                'key' => 'languages',
                'title_en' => 'Languages known',
                'title_hi' => 'ज्ञात भाषाएँ',
                'type' => 'table',
                'columns' => ['Language', 'Proficiency', 'Read', 'Write', 'Speak'],
                'head_hi' => ['भाषा', 'प्रवीणता', 'पढ़ना', 'लिखना', 'बोलना'],
                'body' => $this->languages->map(function ($l) {
                    return [
                        (string) ($l->language_name ?? '-'),
                        (string) ($l->proficiency ?? '-'),
                        !empty($l->can_read) ? 'Y' : '-',
                        !empty($l->can_write) ? 'Y' : '-',
                        !empty($l->can_speak) ? 'Y' : '-',
                    ];
                })->all(),
            ];
        }

        $out[] = [
            'key' => 'bank',
            'title_en' => 'Bank details',
            'title_hi' => 'बैंक विवरण',
            'type' => 'fields',
            'rows' => $this->bankRows(),
        ];

        return $out;
    }

    /** @return list<array{en:string,hi:string,value:mixed}> */
    private function identityRows(): array
    {
        $s1 = $this->step1;

        return $this->filterEmptyRows([
            ['en' => 'Username / OT ID', 'hi' => 'उपयोगकर्ता नाम', 'value' => $this->username],
            ['en' => 'Full name', 'hi' => 'पूरा नाम', 'value' => $s1->full_name ?? null],
            ['en' => 'Roll number', 'hi' => 'अनुक्रमांक', 'value' => $s1->roll_no ?? null],
            ['en' => 'Session / course', 'hi' => 'सत्र / पाठ्यक्रम', 'value' => $s1->session->session_name ?? null],
            ['en' => 'Service', 'hi' => 'सेवा', 'value' => $this->fmtService()],
            ['en' => 'Cadre', 'hi' => 'केडर', 'value' => $s1->cadre ?? null],
            ['en' => 'Allotted state', 'hi' => 'आवंटित राज्य', 'value' => $s1->allottedState->state_name ?? null],
            ['en' => 'Registration status', 'hi' => 'पंजीकरण स्थिति', 'value' => $this->master?->status ?? 'INCOMPLETE'],
            ...$this->dynamicRowsForStep('step1'),
        ]);
    }

    /** @return list<array{en:string,hi:string,value:mixed}> */
    private function personalRows(): array
    {
        $s1 = $this->step1;
        $s2 = $this->step2;

        $perm = $s2 ? implode(', ', array_filter([
            $s2->perm_address_line1 ?? null,
            $s2->perm_city ?? null,
            $s2->permState->state_name ?? null,
            $s2->perm_pincode ?? null,
        ])) : '';

        $pres = $s2 ? implode(', ', array_filter([
            $s2->pres_address_line1 ?? null,
            $s2->pres_city ?? null,
            $s2->presState->state_name ?? null,
            $s2->pres_pincode ?? null,
        ])) : '';

        $em = $s2 && $s2->emergency_contact_name
            ? "{$s2->emergency_contact_name} ({$s2->emergency_contact_relation}) - {$s2->emergency_contact_mobile}"
            : null;

        return $this->filterEmptyRows([
            ['en' => "Father's name", 'hi' => 'पिता का नाम', 'value' => $s1->fathers_name ?? null],
            ['en' => "Mother's name", 'hi' => 'माता का नाम', 'value' => $s1->mothers_name ?? null],
            ['en' => 'Date of birth', 'hi' => 'जन्म तिथि', 'value' => $s1->date_of_birth?->format('d/m/Y')],
            ['en' => 'Gender', 'hi' => 'लिंग', 'value' => $s1->gender ?? null],
            ['en' => 'Mobile', 'hi' => 'मोबाइल', 'value' => $s1->mobile_no ?? null],
            ['en' => 'E-mail', 'hi' => 'ई-मेल', 'value' => $s1->email ?? null],
            ['en' => 'Category', 'hi' => 'वर्ग', 'value' => $s2?->category?->category_name],
            ['en' => 'Religion', 'hi' => 'धर्म', 'value' => $s2?->religion?->religion_name],
            ['en' => 'Nationality', 'hi' => 'राष्ट्रीयता', 'value' => $s2?->nationality],
            ['en' => 'Domicile state', 'hi' => 'अधिवास राज्य', 'value' => $s2?->domicile_state],
            ['en' => 'Marital status', 'hi' => 'वैवाहिक स्थिति', 'value' => $s2?->marital_status],
            ['en' => 'Blood group', 'hi' => 'रक्त समूह', 'value' => $s2?->blood_group],
            ['en' => 'Height', 'hi' => 'ऊँचाई', 'value' => $s2 && isset($s2->height_cm) ? $s2->height_cm.' cm' : null],
            ['en' => 'Weight', 'hi' => 'वजन', 'value' => $s2 && isset($s2->weight_kg) ? $s2->weight_kg.' kg' : null],
            ['en' => 'Identification mark (1)', 'hi' => 'पहचान चिह्न (१)', 'value' => $s2?->identification_mark1],
            ['en' => 'Identification mark (2)', 'hi' => 'पहचान चिह्न (२)', 'value' => $s2?->identification_mark2],
            ['en' => 'Permanent address', 'hi' => 'स्थायी पता', 'value' => $perm ?: null],
            ['en' => 'Present address', 'hi' => 'वर्तमान पता', 'value' => $pres ?: null],
            ['en' => 'Emergency contact', 'hi' => 'आपातकालीन सम्पर्क', 'value' => $em],
            ['en' => "Father's profession", 'hi' => 'पिता का व्यवसाय', 'value' => $s2?->fatherProfession?->profession_name],
            ['en' => "Father's occupation details", 'hi' => 'पिता का कार्य विवरण', 'value' => $s2?->father_occupation_details],
            ...$this->dynamicRowsForStep('step2'),
            ...$this->dynamicRowsForStep('step3'),
        ]);
    }

    /** @return list<array{en:string,hi:string,value:mixed}> */
    private function bankRows(): array
    {
        $b = $this->bank;
        if (!$b) {
            return [['en' => 'Bank details', 'hi' => 'बैंक विवरण', 'value' => 'Not submitted / जमा नहीं']];
        }

        return $this->filterEmptyRows([
            ['en' => 'Bank name', 'hi' => 'बैंक का नाम', 'value' => $b->bank_name ?? null],
            ['en' => 'Branch', 'hi' => 'शाखा', 'value' => $b->branch_name ?? null],
            ['en' => 'IFSC', 'hi' => 'आईएफएससी', 'value' => $b->ifsc_code ?? null],
            ['en' => 'Account number', 'hi' => 'खाता संख्या', 'value' => $b->account_no ?? null],
            ['en' => 'Account holder', 'hi' => 'खाताधारक', 'value' => $b->account_holder_name ?? null],
            ['en' => 'Account type', 'hi' => 'खाता प्रकार', 'value' => $b->account_type ?? null],
            ['en' => 'Verified', 'hi' => 'सत्यापित', 'value' => !empty($b->is_verified) ? 'Yes / हाँ' : 'No / नहीं'],
            ...$this->dynamicRowsForStep('bank'),
        ]);
    }

    /** @return list<array{en:string,hi:string,value:mixed}> */
    private function dynamicRowsForStep(string $stepSlug): array
    {
        return $this->additionalDynamicFields
            ->filter(fn ($f) => (string) ($f->step_slug ?? '') === $stepSlug)
            ->map(fn ($f) => [
                'en' => (string) ($f->label ?? ''),
                'hi' => 'अतिरिक्त फ़ील्ड',
                'value' => $f->value ?? null,
            ])
            ->values()
            ->all();
    }

    private function fmtService(): ?string
    {
        $svc = $this->step1->service ?? null;
        if (!$svc) {
            return null;
        }
        $name = $svc->service_name ?? '';
        $code = $svc->service_code ?? '';

        return trim($name.(($name && $code) ? ' ('.$code.')' : $code));
    }

    /**
     * @param list<array{en:string,hi:string,value:mixed}> $rows
     * @return list<array{en:string,hi:string,value:mixed}>
     */
    private function filterEmptyRows(array $rows): array
    {
        return array_values(array_filter($rows, fn ($r) => $r['value'] !== null && $r['value'] !== ''));
    }
}
