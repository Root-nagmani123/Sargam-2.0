<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rotation anchor (calendar mode)
    |--------------------------------------------------------------------------
    |
    | Cycle index is computed as whole days between this date (midnight, app
    | timezone) and today. Change this to align day 0 of the rotation with a
    | specific launch date. Words with a scheduled_date still override for that day.
    |
    */
    'rotation_anchor_date' => env('WORD_OF_DAY_ANCHOR_DATE', '1970-01-01'),

    /*
    |--------------------------------------------------------------------------
    | Cache key prefix
    |--------------------------------------------------------------------------
    */
    'cache_key_prefix' => 'word_of_the_day:',

];
