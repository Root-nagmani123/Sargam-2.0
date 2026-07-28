<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FC notification email templates (paired with SMS in FcNotifyService)
    | Memo/Notice and Feedback skipped (same as SMS).
    |--------------------------------------------------------------------------
    */
    'templates' => [

        'registration_otp' => [
            'subject' => 'OTP for Foundation Course Registration',
            'body' => "Dear {Applicant_Name},\n\nThank you for initiating your registration for {Programme_Name}.\n\nPlease use the following One-Time Password (OTP) to verify your mobile number/email and proceed with registration:\n\n{OTP}\n\nThis OTP is valid for {OTP_Validity} minutes.\n\nPlease do not share this OTP with anyone.\n\n{Institute_Name}",
        ],

        'credentials_created' => [
            'subject' => 'Login Credentials Created Successfully',
            'body' => "Dear {Participant_Name},\n\nYour login credentials for the Training Portal have been created successfully.\n\nUsername: {Registration_ID}\nPassword: {Password}\nProgramme: {Programme_Name}\n\nYou may now log in and complete the remaining sections of your registration form.\n\nClick here to login:\n{Portal_Link}\n\n{Institute_Name}",
        ],

        'registration_successful' => [
            'subject' => 'Registration Successful',
            'body' => "Dear {Participant_Name},\n\nYour registration for the training programme {Programme_Name} has been successfully completed.\n\nRegistration ID (Username): {Registration_ID}\nProgramme Dates: {Programme_Dates}\n\nYou can log in to the Training Portal using your registered credentials.\n\nClick here to login:\n{Portal_Link}\n\nThank you.\n{Institute_Name}",
        ],

        'forgot_password_otp' => [
            'subject' => 'OTP to Reset Your Password',
            'body' => "Dear {Participant_Name},\n\nWe received a request to reset the password for your Training Portal account.\n\nYour One-Time Password (OTP) is:\n\n{OTP}\n\nThis OTP is valid for {OTP_Validity} minutes.\n\nIf you did not request this, please ignore this message and do not share this OTP with anyone.\n\n{Institute_Name}",
        ],

        'password_change_otp' => [
            'subject' => 'Password Reset OTP',
            'body' => "Dear {Participant_Name},\n\nYour One-Time Password (OTP) for resetting your password is:\n\n{OTP}\n\nThis OTP is valid for {OTP_Validity} minutes.\n\nPlease do not share this OTP with anyone.\n\n{Institute_Name}",
        ],

        'form_step_incomplete' => [
            'subject' => 'Action Required: {Step_Name} Section Incomplete',
            'body' => "Dear {Participant_Name},\n\nWe noticed that the '{Step_Name}' section of your Foundation Course registration form is yet to be completed.\n\nPlease log in to the Training Portal and complete this section at the earliest to avoid delays in processing your registration.\n\nClick here to login:\n{Portal_Link}\n\n{Institute_Name}",
        ],

        'registration_pending' => [
            'subject' => 'Registration Pending',
            'body' => "Dear {Participant_Name},\n\nYour registration for {Programme_Name} is incomplete.\n\nPending Step(s): {Pending_Steps}\n\nPlease complete the pending requirements before {Last_Date} to confirm your registration.\n\nClick here to login:\n{Portal_Link}\n\n{Institute_Name}",
        ],

        'exemption_confirmation' => [
            'subject' => 'Exemption Confirmation - {Exemption_Category}',
            'body' => "Dear {Applicant_Name},\n\nThis is to confirm that you have opted for exemption from attending {Programme_Name} under the following category:\n\nExemption Category: {Exemption_Category}\nApplication No.: {Application_No}\nDate: {Submission_Date}\n\nThis has been recorded on the Training Portal against your profile.\n\n{Institute_Name}",
        ],

    ],

];
