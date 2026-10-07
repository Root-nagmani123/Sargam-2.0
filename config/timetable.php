<?php

/*
 * Printed weekly timetable (CalendarController PDFs and the P.T.O. info sheet).
 * Values that name master rows live here rather than in code, so a deployment
 * whose masters differ can be corrected without a release.
 */
return [

    // course_group_type_master.pk of the Counsellor Group type. Cadre
    // counsellors on the info sheet and its editor are read from this type.
    'counsellor_group_type' => (int) env('TIMETABLE_COUNSELLOR_GROUP_TYPE', 8),

];
