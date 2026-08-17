<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document upload limits
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the Course Repository upload rules. Three places
    | read this and used to hold their own copy of the numbers:
    |
    |   - the validation rules in CourseRepositoryController
    |   - the client-side checks in the upload modal
    |   - the "Allowed file types / Maximum size" hint shown next to the picker
    |
    | max_file_kb feeds Laravel's `max:` rule, which is measured in kilobytes.
    | Note that PHP's own upload_max_filesize / post_max_size still cap this — a
    | value larger than upload_max_filesize cannot take effect, so the upload
    | screen shows whichever limit is actually lower.
    |
    */
    'max_file_kb' => (int) env('COURSE_REPOSITORY_MAX_FILE_KB', 25600), // 25 MB

    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],

];
