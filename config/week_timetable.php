<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Official-style footnotes (below the grid on PDF / print / screen)
    |--------------------------------------------------------------------------
    |
    | Same role as lines (i), (ii) on the LBSNAA revised week timetable PDF.
    | Example:
    |   '(i) *Exams on Law on 13.4.2026 will begin at 0930 hrs.',
    |   '(ii) Cultural Programme on 15 April, 2026 at 1830 hrs. (Attendance compulsory).',
    |
    */
    'footnotes' => array_values(array_unique(array_filter(array_map('trim', array_filter([
        env('WEEK_TIMETABLE_FOOTNOTE_1'),
        env('WEEK_TIMETABLE_FOOTNOTE_2'),
        env('WEEK_TIMETABLE_FOOTNOTE_3'),
        env('WEEK_TIMETABLE_FOOTNOTE_4'),
        env('WEEK_TIMETABLE_FOOTNOTE_5'),
    ]))))),

    /*
    |--------------------------------------------------------------------------
    | PDF body font stack (Dompdf; DejaVu ships with Dompdf for Unicode)
    |--------------------------------------------------------------------------
    */
    'pdf_body_font' => 'DejaVu Sans, DejaVu Sans Condensed, Arial, Helvetica, sans-serif',

    /*
    |--------------------------------------------------------------------------
    | Row grouping (implicit)
    |--------------------------------------------------------------------------
    |
    | Week grid rows are keyed by clock band (start/end minutes), not calendar date, so the
    | same 09:45–10:35 style slot appears once with Mon–Fri columns — serial order by time of day.
    |
    */

];
