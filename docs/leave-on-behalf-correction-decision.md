# Leave on Behalf correction decision request (F-068)

Status: **Product decision recorded; correction/cancel implementation deferred to a separate PR**.
Recorded: 2026-10-14.
Review reference supplied by the operator: `pr-334-review(20261013-055305)`.
That review reference is context, not a report revalidated in this task.

The operator supplied and confirmed the Product Owner decision for PR #334:
incorrect Leave-on-Behalf records should have a correction/cancellation
facility, with implementation handled in a separate PR. An overlapping OT
Draft of the same leave type blocks Leave-on-Behalf regularisation. The
approving person's name and a separate authoritative decision-record reference
were not supplied; this document records the operator-reported approval, not
independently verified identity.

## Current implementation observed

- `LeaveOnBehalfController::store()` records a leave as Approved, with
  `applied_by_user_pk` identifying the operator and no faculty approver.
- The Leave on Behalf route group provides index/create/export/store/students/
  context only; it has no update or cancel endpoint.
- The operator's overlap check includes Drafts. An overlapping OT Draft of the
  same leave type blocks regularisation; it remains unchanged.
- The same-type overlap check still considers Pending and Approved leave.
  `LeaveApplicationService::findOverlappingApplication()` defaults to
  `includeDrafts: true` for other callers.
- `LeaveOnBehalfDoubleSubmitTest` covers a same-type Draft and a Pending leave
  blocking operator regularisation.

The prior 2026-10-09 behavior claim is superseded by the decision recorded
above. Current code behavior is established by the implementation; the updated
regression test still requires execution on a schema-compatible test database.

## Product Owner decisions required

| Decision | Options to resolve | Status / accountable role |
| --- | --- | --- |
| Approved on-behalf record entered incorrectly | Provide a correction/cancellation facility | Approved by Product Owner per operator; implementation deferred to a separate PR |
| Who may correct/cancel | Original operator, authorized Training Section operators, or a narrower authority; course/access scope and approval requirements | To be specified in the implementation PR |
| Correction scope and cutoff | Dates/times, type/nature, reason, attachments and other fields; past/effective leave and downstream attendance/report recalculation | To be specified in the implementation PR |
| Draft overlap policy | Same-type overlapping OT Draft blocks operator regularisation and remains unchanged | Approved by Product Owner per operator; implementation changed in PR #334 |
| What happens to the retained Draft later | Existing overlap validation on submission, any user notification, and whether explicit supersession is needed | To be specified in the implementation PR |
| Separate follow-up delivery | Owning role/person, tracking issue/PR, priority, target date and release condition | No issue/PR reference or owner supplied |

Do not silently label a Draft as cancelled or remove it. The approved separate
PR must define correction/cancellation authorization, retained history, and
downstream recalculation before implementation.

## Separate-PR follow-up acceptance criteria

The final scope depends on the decisions above. The follow-up must:

- Provide an authorized, course-scoped correction/cancel path for on-behalf
  records; refuse trainee and out-of-scope requests server-side.
- Retain the original record/history and record actor, timestamp and reason.
  Decide status/active semantics explicitly rather than inventing a cancelled
  status or treating deletion as an audit trail.
- Revalidate configuration, date/time and overlap rules for corrections.
  Preserve atomicity and student locking across creation, correction,
  cancellation and any replacement to avoid concurrent conflicting records.
- Investigate every consumer of Approved leave, including attendance,
  exemptions, overlap checks, lists, dashboard counts and PDF/Excel exports.
  A cancelled record must stop affecting these consistently, without loss of
  historical attribution.
- Preserve the approved same-type Draft policy and verify Draft, Pending and
  Approved controls, including later Draft submission after operator
  regularisation.
- Add regression tests for mistaken-entry recovery, recipient/access isolation,
  duplicate/concurrent actions, audit retention and downstream consistency.

These are follow-up requirements, not claims that a correction/cancellation
mechanism exists today.

## Required decision record and closure evidence

For traceable implementation planning, the Product Owner / Engineering lead
must supply:

1. Approving identity and an authoritative decision reference.
2. Permitted actors, timing limits, correction scope, and the Draft policy's
   downstream behavior.
3. Follow-up owner, issue/PR reference, and target date.
4. Release owner's confirmation of how the decision gates release.

For implementation closure, link the separate PR and executed regression
evidence.

The Product Owner decision and the same-type Draft behavior are recorded here;
the correction/cancellation feature itself remains deferred and is not
implemented by PR #334.
