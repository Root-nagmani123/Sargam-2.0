<?php

namespace App\Support;

/**
 * Minimal GROUP BY key sets for the session-feedback reports.
 *
 * The reports originally grouped by every non-aggregated column they selected. That is
 * valid SQL but needlessly slow: columns that are functionally dependent on a primary key
 * already in the key cannot split a group further, they only widen the sort key. Several of
 * them are TEXT / long VARCHAR (timetable.subject_topic, faculty_master.Permanent_Address,
 * course_master.course_name), which pushed MySQL from a plain key sort into a "Sort row IDs"
 * pass over the whole join — measurably the dominant cost in EXPLAIN ANALYZE.
 *
 * Grouping on the determining keys alone produces the identical set of groups. MySQL 8
 * resolves the remaining selected columns through primary-key functional dependency, so this
 * stays valid under ONLY_FULL_GROUP_BY (which this schema runs with).
 *
 * Dependency chains relied on below:
 *   topic_feedback.timetable_pk = timetable.pk  → determines every timetable column
 *   timetable.course_master_pk  = course_master.pk → determines every course_master column
 *   topic_feedback.faculty_pk   = faculty_master.pk → determines every faculty_master column
 *
 * IMPORTANT: only drop a column here when a primary key that determines it is present.
 * topic_feedback.topic_name is stored per feedback row and is NOT determined by timetable_pk,
 * so it stays in every key that originally carried it.
 *
 * IMPORTANT: group the determining primary key itself, not merely a column joined to it.
 * MySQL infers dependency across a join equality only while that join survives query
 * preparation. When the WHERE clause is constant-false -- which Laravel emits for
 * whereIn($column, []) and which ScopesSessionFeedbackReports writes literally as
 * whereRaw('1 = 0') for a viewer with no accessible courses -- the optimiser drops the join,
 * the equality carrying the proof disappears, and ONLY_FULL_GROUP_BY rejects the query with
 * error 1055. Grouping the primary key is a dependency MySQL recognises unconditionally, and
 * it never changes the grouping because the key is already constant within each group.
 */
final class FeedbackReportGrouping
{
    /**
     * Feedback Database grid (FeedbackController::baseDatabaseQuery, FeedbackDatabaseDataTable).
     *
     * Replaces: f.pk, f.full_name, f.email_id, f.Permanent_Address,
     *           c.course_name, t.subject_topic, t.START_DATE, t.pk
     *
     * c.pk is grouped even though t.course_master_pk determines it: see the note on
     * constant-false predicates below.
     */
    public const DATABASE_GRID = ['f.pk', 't.pk', 'c.pk'];

    /**
     * Faculty Average report (showFacultyAverage, exportExcel, exportPdf, printFacultyAverage).
     *
     * Replaces: tf.faculty_pk, tf.topic_name, cm.course_name, fm.full_name,
     *           tt.START_DATE, tt.class_session
     *
     * fm.full_name is dropped and fm.pk grouped in its place. Dropping the name alone is not
     * enough: it is logically determined by tf.faculty_pk through the fm.pk join equality, but
     * MySQL's ONLY_FULL_GROUP_BY does not infer dependency across that join and rejects the
     * query with error 1055. Grouping fm.pk — faculty_master's primary key — is a dependency
     * MySQL does recognise, so every fm.* column becomes selectable while the group key stays
     * an integer instead of a varchar. fm.pk equals tf.faculty_pk on every row (inner join), so
     * the grouping is unchanged.
     *
     * cm.course_name, tt.START_DATE and tt.class_session are NOT determined by anything in this
     * key (the key carries no timetable or course primary key), so removing them would merge
     * rows that the report currently keeps apart. They stay.
     */
    public const FACULTY_AVERAGE = [
        'tf.faculty_pk',
        'fm.pk',
        'tf.topic_name',
        'cm.course_name',
        'tt.START_DATE',
        'tt.class_session',
    ];

    /**
     * Faculty View / Feedback Details (facultyView, exportFacultyFeedback, printFacultyFeedback,
     * FacultyFeedbackReportService).
     *
     * Replaces: tf.topic_name, cm.pk, cm.course_name, cm.active_inactive, cm.end_date,
     *           fm.full_name, tt.faculty_type, tf.faculty_pk, tt.START_DATE, tt.END_DATE,
     *           tt.class_session, tf.timetable_pk
     *
     * tf.timetable_pk determines every tt.* and (through timetable.course_master_pk) every cm.*
     * column; tf.faculty_pk determines fm.full_name. Twelve keys collapse to five.
     *
     * tt.pk, cm.pk and fm.pk are grouped alongside, for the reason given below. All three add
     * no group boundary: tt.pk equals tf.timetable_pk and fm.pk equals tf.faculty_pk on every
     * row of these inner joins, and cm.pk is determined by tt.course_master_pk, so each is
     * already constant within a group. One primary key is needed per joined table whose columns
     * are selected -- grouping only some of them still leaves the rest resolved by inference.
     */
    public const FACULTY_VIEW = [
        'tf.timetable_pk',
        'tf.faculty_pk',
        'tf.topic_name',
        'tt.pk',
        'cm.pk',
        'fm.pk',
    ];
}
