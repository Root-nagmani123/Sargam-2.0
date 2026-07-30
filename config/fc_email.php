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
            'body' => "Dear {Applicant_Name},\n\nThank you for initiating your registration for {Programme_Name}.\n\nYour One-Time Password (OTP) for registration is:\n\n{OTP}\n\nThis OTP is valid for {OTP_Validity} minutes.\n\nFor your security, please do not share this OTP with anyone.\n\n{Institute_Name}",
        ],

        'credentials_created' => [
            'subject' => 'Login Credentials for Foundation Course Registration',
            'body' => "Dear {Participant_Name},\n\nThank you for registering for {Programme_Name}.\n\nYour login credentials have been created successfully.\n\nUsername: {Registration_ID}\nPassword: {Password}\n\nYou may now log in to complete the remaining sections of your registration.\n\nLogin Link:\n{Portal_Link}\n\nPlease keep your login credentials confidential and do not share them with anyone.\n\n{Institute_Name}",
        ],

        'registration_successful' => [
            'subject' => 'Registration Successful for Foundation Course',
            'body' => "Dear {Participant_Name},\n\nThank you for completing your registration for {Programme_Name}.\n\nYour registration has been completed successfully.\n\nUsername: {Registration_ID}\nProgramme Dates: {Programme_Dates}\n\nYou may now log in to the Training Portal using your registered credentials.\n\nLogin Link:\n{Portal_Link}\n\n{Institute_Name}",
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
            'subject' => 'Action Required: Complete Your Foundation Course Registration',
            'body' => "Dear {Participant_Name},\n\nWe noticed that the {Step_Name} section of your {Programme_Name} registration is still pending.\n\nPlease log in and complete this section at the earliest to avoid delays in processing your registration.\n\nLogin Link:\n{Portal_Link}\n\n{Institute_Name}",
        ],

        'registration_pending' => [
            'subject' => 'Registration Pending for Foundation Course',
            'body' => "Dear {Participant_Name},\n\nYour registration for {Programme_Name} is currently incomplete.\n\nPending Step(s): {Pending_Steps}\n\nPlease complete the pending requirements before {Last_Date} to avoid any delay in processing your registration.\n\nLogin Link:\n{Portal_Link}\n\n{Institute_Name}",
        ],

        'exemption_confirmation' => [
            'subject' => 'Exemption Confirmation – {Exemption_Category}',
            'body' => "Dear {Applicant_Name},\n\nThank you for submitting your exemption request for {Programme_Name}.\n\nYour request has been recorded successfully under the following category:\n\nExemption Category: {Exemption_Category}\nSubmission Date: {Submission_Date}\n\nThis exemption request has been recorded against your profile on the Training Portal.\n\n{Institute_Name}",
        ],

    ],

];
