<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    | log     = local test (writes to laravel.log, no API call)
    | gupshup = production SMS via enterprise.smsgupshup.com
    */
    'driver' => env('SMS_DRIVER', 'log'),

    'base_url' => env('GUPSHUP_BASE_URL', 'https://enterprise.smsgupshup.com/GatewayAPI/rest'),

    'userid' => env('GUPSHUP_USERID'),

    'password' => env('GUPSHUP_PASSWORD'),

    'principal_entity_id' => env('GUPSHUP_PRINCIPAL_ENTITY_ID', '1201160982667694594'),

    'mask' => env('GUPSHUP_MASK', 'LBSNAA'),

    'institute_name' => env('FC_SMS_INSTITUTE_NAME', 'LBSNAA'),

    'portal_url' => env('FC_SMS_PORTAL_URL', env('APP_URL', 'https://sargam2.lbsnaa.gov.in')),

    'default_programme_name' => env('FC_SMS_DEFAULT_PROGRAMME', 'Foundation Course'),

    'otp_validity_minutes' => (int) env('FC_SMS_OTP_VALIDITY_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | DLT variable tag limits (per TRAI/Gupshup variable tagging policy)
    |--------------------------------------------------------------------------
    | #num# = 40, #alp# = 40, #url#/#uro# = 120, #cbn# = 3-14, #eml# = 40.
    | Used as the default max length for a placeholder when a template doesn't
    | override it in its own 'max_lengths' entry below.
    */
    'default_max_lengths' => [
        'alphanumeric' => 40,
        'numeric' => 40,
        'url' => 120,
        'email' => 40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Approved DLT templates (exact registered bodies)
    | Memo/Notice/Chat (FC-MEM-*, FC-NOTICE-*, FC-NM) skipped for now.
    |--------------------------------------------------------------------------
    | 'max_lengths' caps each placeholder to its DLT-registered tag width
    | (#alp#/#num# = 40 chars, #uro# = 120 chars) so a long name, step list, or
    | category can't push the rendered message past what the approved template
    | allows — GupshupSmsService::sendTemplate() truncates before sending.
    */
    'templates' => [

        // FC-REG1 — A1 (DLT-approved body has Programme_Name as a variable, not fixed text)
        'registration_otp' => [
            'dlt_name' => 'FC-REG1',
            'dlt_template_id' => env('GUPSHUP_DLT_A1_REGISTRATION_OTP', '1477178461100263808'),
            'body' => 'Dear {Applicant_Name}, your OTP to register for {Programme_Name} is {OTP}. Valid for {OTP_Validity} minutes. Do not share this OTP. - {Institute_Name}',
            'max_lengths' => [
                'Applicant_Name' => 40, 'Programme_Name' => 40, 'OTP' => 40,
                'OTP_Validity' => 40, 'Institute_Name' => 40,
            ],
        ],

        // FC-CRED1 — A2
        'credentials_created' => [
            'dlt_name' => 'FC-CRED1',
            'dlt_template_id' => env('GUPSHUP_DLT_A2_CREDENTIALS', '1477178461144840794'),
            'body' => 'Dear {Participant_Name}, your login credentials for {Programme_Name} are created. Username: {Registration_ID} Password :{Password}. Login at {Portal_Link} - {Institute_Name}',
            'max_lengths' => [
                'Participant_Name' => 40, 'Programme_Name' => 40, 'Registration_ID' => 40,
                'Password' => 40, 'Portal_Link' => 120, 'Institute_Name' => 40,
            ],
        ],

        // FC-REG1-S — A3
        'registration_successful' => [
            'dlt_name' => 'FC-REG1-S',
            'dlt_template_id' => env('GUPSHUP_DLT_A3_REGISTRATION', '1477178461158204831'),
            'body' => 'Dear {Participant_Name}, your registration for {Programme_Name} is successful. Reg ID: {Registration_ID}. Login at {Portal_Link} - {Institute_Name}',
            'max_lengths' => [
                'Participant_Name' => 40, 'Programme_Name' => 40, 'Registration_ID' => 40,
                'Portal_Link' => 120, 'Institute_Name' => 40,
            ],
        ],

        // FC-PF — A4
        'forgot_password_otp' => [
            'dlt_name' => 'FC-PF',
            'dlt_template_id' => env('GUPSHUP_DLT_A4_FORGOT_PASSWORD_OTP', '1477178461231145722'),
            'body' => 'Dear {Participant_Name}, your OTP to reset your forgotten password is {OTP}. Valid for {OTP_Validity} minutes. Do not share this OTP. - {Institute_Name}',
            'max_lengths' => [
                'Participant_Name' => 40, 'OTP' => 40, 'OTP_Validity' => 40, 'Institute_Name' => 40,
            ],
        ],

        // FC-PCOTP — A5
        'password_change_otp' => [
            'dlt_name' => 'FC-PCOTP',
            'dlt_template_id' => env('GUPSHUP_DLT_A5_PASSWORD_CHANGE_OTP', '1477178461257395562'),
            'body' => 'Dear {Participant_Name}, your OTP for password reset is {OTP}. Valid for {OTP_Validity} minutes. Do not share this OTP with anyone. - {Institute_Name}',
            'max_lengths' => [
                'Participant_Name' => 40, 'OTP' => 40, 'OTP_Validity' => 40, 'Institute_Name' => 40,
            ],
        ],

        // FC-IFSI — B1 (DLT portal lists this as FC-IFSI, not FC-IFM)
        'form_step_incomplete' => [
            'dlt_name' => 'FC-IFSI',
            'dlt_template_id' => env('GUPSHUP_DLT_B1_STEP_INCOMPLETE', '1477178461322515097'),
            'body' => 'Dear {Participant_Name}, the \'{Step_Name}\' section of your Foundation Course registration is incomplete. Complete it at {Portal_Link} - {Institute_Name}',
            'max_lengths' => [
                'Participant_Name' => 40, 'Step_Name' => 40, 'Portal_Link' => 120, 'Institute_Name' => 40,
            ],
        ],

        // FC-RSP — B2
        'registration_pending' => [
            'dlt_name' => 'FC-RSP',
            'dlt_template_id' => env('GUPSHUP_DLT_B2_REGISTRATION_PENDING', '1477178461353333216'),
            'body' => 'Dear {Participant_Name}, your registration for {Programme_Name} is incomplete. Complete pending steps by {Last_Date}. Portal: {Portal_Link} - {Institute_Name}',
            'max_lengths' => [
                'Participant_Name' => 40, 'Programme_Name' => 40, 'Last_Date' => 40,
                'Portal_Link' => 120, 'Institute_Name' => 40,
            ],
        ],

        // FC-EC — C1 (approved wording: "opted for"; no date field in the DLT body)
        'exemption_confirmation' => [
            'dlt_name' => 'FC-EC',
            'dlt_template_id' => env('GUPSHUP_DLT_C1_EXEMPTION', '1477178461379104735'),
            'body' => 'Dear {Applicant_Name}, you have opted for exemption from {Programme_Name} under the category \'{Exemption_Category}\'. App No: {Application_No}. - {Institute_Name}',
            'max_lengths' => [
                'Applicant_Name' => 40, 'Programme_Name' => 40, 'Exemption_Category' => 40,
                'Application_No' => 40, 'Institute_Name' => 40,
            ],
        ],

        // PC-FB — D6 (skipped for now — not used from Admin or auto flows)
        // 'feedback_request' => [
        //     'dlt_name' => 'PC-FB',
        //     'dlt_template_id' => env('GUPSHUP_DLT_D6_FEEDBACK', '1477178461631580688'),
        //     'body' => 'Dear {Participant_Name}, please submit your feedback for {Programme_Name} by {Last_Date}. Your feedback is important. Submit here: {Feedback_Link} - {Institute_Name}',
        // ],

    ],

];
