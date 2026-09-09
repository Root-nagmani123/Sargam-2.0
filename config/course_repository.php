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
    | That clamp is applied in two places, and both must stay in step:
    |   - CourseRepositoryController::uploadMaxKb()  (the server `max:` rule)
    |   - course-repository/show.blade.php           (the hint + client-side check)
    |
    | Raising this above the deployed php.ini therefore does nothing except widen
    | the band where PHP drops the file before Laravel sees it. Raise
    | upload_max_filesize (and post_max_size, which bounds the whole batch) first.
    |
    */
    'max_file_kb' => (int) env('COURSE_REPOSITORY_MAX_FILE_KB', 25600), // 25 MB

    'allowed_extensions' => ['pdf'],

    /*
    |--------------------------------------------------------------------------
    | Where uploaded documents live
    |--------------------------------------------------------------------------
    |
    | These files are access-controlled, so they must NOT sit on the `public`
    | disk. That disk is exposed through the public/storage symlink, which the
    | web server resolves before Laravel ever loads — so anything on it is
    | readable by anyone who can reach the host, and no controller check can
    | prevent it. Stored names are predictable too ("{title}_{unix ts}.pdf"),
    | so "nobody knows the URL" was never the protection it looked like.
    |
    | `disk` is where new uploads go: `local` is storage/app, which no vhost
    | serves. Reads go through CourseRepositoryController's authenticated
    | stream/download actions instead of a direct URL.
    |
    | `legacy_disk` is the old location. Documents uploaded before this change
    | are still there, so reads fall back to it and REMAIN directly reachable
    | until they are moved. Run `php artisan course-repository:secure-documents`
    | to migrate them, then the fallback stops matching anything.
    |
    */
    'disk' => env('COURSE_REPOSITORY_DISK', 'local'),

    'legacy_disk' => env('COURSE_REPOSITORY_LEGACY_DISK', 'public'),

];
