# Faculty access rules (PR #335, review finding F-008)

Decided by the Product owner (Nibhash Mishra) on 12-10-2026 and recorded in the code-review
remediation register ("PR #335 F-008 decision"). The Engineering lead's (Ravi Patel)
countersignature is still owed in his own words; until it is recorded, F-008 stays open.

## Who the rules apply to

A login holding the **Faculty**, **Internal Faculty** or **Guest Faculty** role.

The login's faculty record is found by `login_faculty_key_column()` (`app/helpers.php`):

- `user_category = 'F'`: `faculty_master.pk = user_credentials.user_id`
- any other category: `faculty_master.employee_master_pk = user_credentials.user_id`

There is no fallback from one to the other. A login with no matching `faculty_master` row
sees no course-scoped rows on any of the four screens.

"CC or ACC" means `CourseCordinatorMaster::courseIdsForFaculty()`: the faculty pk appears in
`Coordinator_name` or `Assistant_Coordinator_name`, either alone or in a comma list of pks
(older rows), read the same way as the Course Information sheet (`peopleIn()`).

## The rules

| # | Screen | A faculty login sees | Enforced in |
|---|--------|----------------------|-------------|
| 1 | Attendance | only courses it is CC or ACC of (grid, export, course and filter lists) | `AttendanceController::index()`, `applyInternalFacultyCourseScope()` |
| 2 | Feedback | only its own feedback rows | `FeedbackController` (`feedbackDetails` and its export: `fm.<identity column> = user_id`) |
| 3 | Medical Exception | medical exemptions only on courses it is CC or ACC of | `MedicalExceptionFacultyViewController::facultyLoginView()` |
| 4 | Faculty MDO / Escort Exception | every escort duty on courses it is CC or ACC of; on other courses only the duties it is assigned to | `FacultyMDOEscortExceptionViewController::facultyDutiesQuery()` |

A faculty with a `faculty_master` row but no CC/ACC mapping therefore sees nothing on
Attendance and Medical Exception, its own rows on Feedback, and only its assigned duties on
Escort Exception.

## Tests

- `tests/Feature/FacultyRoleScopingTest.php`: rules 1–3 for each of the three roles (CC, ACC,
  ACC in a comma list), for employee (E) and faculty (F) logins, for a faculty with no CC/ACC
  mapping, and for a login with no faculty record.
- `tests/Feature/FacultyMdoEscortExceptionViewTest.php`: rule 4 (CC, ACC, assigned-only, no
  mapping, no faculty record) for Internal Faculty, Faculty and Guest Faculty logins.

## Changing a rule

Any change needs a new written decision from the Product owner, co-signed by the
Engineering lead, and a matching change to the tests above.
