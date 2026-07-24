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
    | Approved DLT templates (exact registered bodies)
    | Memo/Notice/Chat (FC-MEM-*, FC-NOTICE-*, FC-NM) skipped for now.
    |--------------------------------------------------------------------------
    */
    'templates' => [

        // FC-OTP — A1 (programme name is fixed in DLT text)
        'registration_otp' => [
            'dlt_name' => 'FC-OTP',
            'dlt_template_id' => env('GUPSHUP_DLT_A1_REGISTRATION_OTP', '1477170161102987810'),
            'body' => 'Dear {Applicant_Name}, your OTP to register for Foundation Course 2025 Batch-I is {OTP}. Valid for {OTP_Validity} minutes. Do not share this OTP. - {Institute_Name}',
        ],

        // FC-CRED-I — A2
        'credentials_created' => [
            'dlt_name' => 'FC-CRED-I',
            'dlt_template_id' => env('GUPSHUP_DLT_A2_CREDENTIALS', '1477170161144840719'),
            'body' => 'Dear {Participant_Name}, your login credentials for {Programme_Name} are created. Username: {Registration_ID} Password: {Password}. Login at {Portal_Link} - {Institute_Name}',
        ],

        // FC-REG-S — A3
        'registration_successful' => [
            'dlt_name' => 'FC-REG-S',
            'dlt_template_id' => env('GUPSHUP_DLT_A3_REGISTRATION', '1477170161150204131'),
            'body' => 'Dear {Participant_Name}, your registration for {Programme_Name} is successful. Reg ID: {Registration_ID}. Login at {Portal_Link} - {Institute_Name}',
        ],

        // FC-RF — A4
        'forgot_password_otp' => [
            'dlt_name' => 'FC-RF',
            'dlt_template_id' => env('GUPSHUP_DLT_A4_FORGOT_PASSWORD_OTP', '1477170161211515712'),
            'body' => 'Dear {Participant_Name}, your OTP to reset your forgotten password is {OTP}. Valid for {OTP_Validity} minutes. Do not share this OTP. - {Institute_Name}',
        ],

        // FC-F-OTP — A5
        'password_change_otp' => [
            'dlt_name' => 'FC-F-OTP',
            'dlt_template_id' => env('GUPSHUP_DLT_A5_PASSWORD_CHANGE_OTP', '1477170161214227912'),
            'body' => 'Dear {Participant_Name}, your OTP for password reset is {OTP}. Valid for {OTP_Validity} minutes. Do not share this OTP with anyone. - {Institute_Name}',
        ],

        // FC-IFM — B1
        'form_step_incomplete' => [
            'dlt_name' => 'FC-IFM',
            'dlt_template_id' => env('GUPSHUP_DLT_B1_STEP_INCOMPLETE', '1477170161237295562'),
            'body' => 'Dear {Participant_Name}, the \'{Step_Name}\' section of your Foundation Course registration is incomplete. Complete it at {Portal_Link} - {Institute_Name}',
        ],

        // FC-R-P — B2
        'registration_pending' => [
            'dlt_name' => 'FC-R-P',
            'dlt_template_id' => env('GUPSHUP_DLT_B2_REGISTRATION_PENDING', '1477170161251515867'),
            'body' => 'Dear {Participant_Name}, your registration for {Programme_Name} is incomplete. Complete pending steps by {Last_Date}. Portal: {Portal_Link} - {Institute_Name}',
        ],

        // FC-EX — C1 (approved wording: "asked for")
        'exemption_confirmation' => [
            'dlt_name' => 'FC-EX',
            'dlt_template_id' => env('GUPSHUP_DLT_C1_EXEMPTION', '1477170161270034735'),
            'body' => 'Dear {Applicant_Name}, you have asked for exemption from {Programme_Name} under the category \'{Exemption_Category}\'. App No: {Application_No}. - {Institute_Name}',
        ],

        // PC-FB — D6
        'feedback_request' => [
            'dlt_name' => 'PC-FB',
            'dlt_template_id' => env('GUPSHUP_DLT_D6_FEEDBACK', '1477170161515505030'),
            'body' => 'Dear {Participant_Name}, please submit your feedback for {Programme_Name} by {Last_Date}. Your feedback is important. Submit here: {Feedback_Link} - {Institute_Name}',
        ],

    ],

];
