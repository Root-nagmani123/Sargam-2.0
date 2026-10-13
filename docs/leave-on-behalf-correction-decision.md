# Leave on Behalf correction decision request (F-068)

Status: **OPEN / partially implemented; correction/cancel follow-up pending**.
Recorded: 2026-10-13.
Review reference supplied by the operator: `pr-334-review(20261013-055305)`.
That review reference is context, not a report revalidated in this task.

The operator requested documentation of the decision request and a separate-PR
follow-up, not implementation or acceptance of the remaining risk. No Product
Owner approval of a correction/cancel policy was supplied during this task.

## Current implementation observed

- `LeaveOnBehalfController::store()` records a leave as Approved, with
  `applied_by_user_pk` identifying the operator and no faculty approver.
- The Leave on Behalf route group provides index/create/export/store/students/
  context only; it has no update or cancel endpoint.
- The operator's overlap check passes `includeDrafts: false`. An OT's Draft
  therefore does not block operator regularisation. Drafts are not deleted,
  overwritten or automatically submitted by this operation.
- The same-type overlap check still considers Pending and Approved leave.
  `LeaveApplicationService::findOverlappingApplication()` defaults to
  `includeDrafts: true` for other callers.
- `LeaveOnBehalfDoubleSubmitTest` contains a regression for a Draft not blocking
  the operator, remaining a Draft, and a Pending leave still blocking.

The service comment and that test's comment cite a Product Owner decision dated
2026-10-09. This is a **repository claim**, not independently confirmed approval:
the decision record, approving owner and acceptance evidence have not been
provided here. Current code behavior is established by reading the implementation;
the existing test was not executed for this documentation-only task.

## Product Owner decisions required

| Decision | Options to resolve | Status / accountable role |
| --- | --- | --- |
| Approved on-behalf record entered incorrectly | Cancellation plus replacement; in-place correction with retained history; or explicit deferral/risk acceptance with an approved operational remedy | Pending - Product Owner |
| Who may correct/cancel | Original operator, authorized Training Section operators, or a narrower authority; course/access scope and approval requirements | Pending - Product Owner / Module owner |
| Correction scope and cutoff | Dates/times, type/nature, reason, attachments and other fields; past/effective leave and downstream attendance/report recalculation | Pending - Product Owner |
| Draft overlap policy | Confirm current behavior: Draft does not block operator leave and remains intact; or require Draft to block and commission a separate behavior change | Implemented behavior; approval confirmation pending - Product Owner |
| What happens to the retained Draft later | Existing overlap validation on submission, any user notification, and whether explicit supersession is needed | Pending - Product Owner / Module owner |
| Separate follow-up delivery | Owning role/person, tracking issue/PR, priority, target date and release condition | Unassigned - Engineering lead / Release owner |

Do not silently label a Draft as cancelled, remove it, or change overlap rules
while documenting this decision. A deferral is not approval until the Product
Owner records the accepted risk, operational remedy, accountable owner and
expiry/revisit date.

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
- Implement the approved Draft policy and verify Draft, Pending and Approved
  controls. Test later Draft submission after operator regularisation.
- Add regression tests for mistaken-entry recovery, recipient/access isolation,
  duplicate/concurrent actions, audit retention and downstream consistency.

These are follow-up requirements, not claims that a mechanism exists today.
No issue/PR was created or application code changed by this documentation task.

## Required decision record and closure evidence

Product Owner must supply:

1. Approving identity/role, date and an authoritative decision reference.
2. Selected correction/cancel approach (or explicit deferral), permitted actors,
   timing limits and the Draft policy.
3. Follow-up owner, issue/PR reference and target date; if deferred, accepted
   impact and a safe operator escalation/remedy.
4. Release owner's confirmation of how the decision gates release.

For implementation closure, link the separate PR and executed regression
evidence. For the decision-only alternative, record the actual Product Owner
approval and explicit risk acceptance rather than treating this request document
as the decision itself.

**F-068 remains open.** Ignoring Drafts addresses only the blocking behavior;
it does not repair an incorrect Approved record or provide cancellation.
