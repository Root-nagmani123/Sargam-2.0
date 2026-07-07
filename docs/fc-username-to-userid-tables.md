# FC Migration: `username` → `user_id` — Affected Tables

Migration file: `database/migrations/FC/2026_05_24_000001_migrate_fc_username_to_user_id.php`

---

## Group 1 — UNIQUE tables (one row per user, `username` was UNIQUE)

| # | Table Name |
|---|------------|
| 1 | `student_masters` *(composite unique: username + form_id)* |
| 2 | `student_master_firsts` |
| 3 | `student_master_seconds` |
| 4 | `student_master_spouse_masters` |
| 5 | `student_knowledge_hindi_masters` |
| 6 | `student_master_hobbies_details` |
| 7 | `student_master_module_masters` |
| 8 | `student_master_exempted_masters` |
| 9 | `student_fc_scale_masters` |
| 10 | `student_confirm_masters` |
| 11 | `student_master_incomplet_masters` |
| 12 | `new_registration_bank_details_masters` |
| 13 | `registration_bank_details_masters` |
| 14 | `fc_joining_attendance_ganga_masters` |
| 15 | `fc_joining_attendance_kaveri_masters` |
| 16 | `fc_joining_attendance_narmada_masters` |
| 17 | `fc_joining_attendance_mahanadi_masters` |
| 18 | `fc_joining_attendance_happy_valley_masters` |
| 19 | `fc_joining_attendance_silverwood_masters` |
| 20 | `fc_joining_medical_details_masters` |
| 21 | `fc_joining_covid_details_masters` |
| 22 | `student_travel_plan_masters` |
| 23 | `student_iosr_details_masters` |
| 24 | `student_iosr_reasonable_adjust_masters` |
| 25 | `student_register_masters` |
| 26 | `student_exemption_med_doc_masters` |
| 27 | `registration_covid_report_masters` |
| 28 | `online_assigment_masters` |
| 29 | `fc_ot_details` |

---

## Group 2 — MULTI tables (multiple rows per user, `username` was NOT unique)

| # | Table Name |
|---|------------|
| 30 | `student_master_qualification_details` |
| 31 | `student_master_higher_educational_details` |
| 32 | `student_master_employment_details` |
| 33 | `student_master_language_knowns` |
| 34 | `student_skill_details_masters` |
| 35 | `student_master_academic_distinctions` |
| 36 | `student_sports_fitness_teach_masters` |
| 37 | `student_sports_trg_teach_masters` |
| 38 | `fc_joining_related_documents_details_masters` |
| 39 | `mctp_student_travel_plan_details` |
| 40 | `student_iosr_details_doc_path_masters` |
| 41 | `student_master_movable_property_details` |
| 42 | `student_master_immovable_property_details` |
| 43 | `fc_otactivity_details` |

---

## Group 3 — `userid` tables (column named `userid` not `username`)

| # | Table Name |
|---|------------|
| 44 | `fc_pre_history` |
| 45 | `fc_path_report` |

---

## Group 4 — Metadata table (value updated, column unchanged)

| # | Table Name | Change |
|---|------------|--------|
| 46 | `fc_forms` | `user_identifier` value: `'username'` → `'user_id'` |

---

**Total: 46 tables**
