# Estate Module — Change Request Detail
## Complete Technical Documentation

---

## 1. WHAT IS CHANGE REQUEST?

**Change Request** means:
> An employee who **already has a house allotted** wants to **shift to a different house** within the campus.

### Key Rule:
- This feature is **ONLY available** if the employee already has a house allotted (`current_alot` column is NOT NULL).
- An employee **without any allotted house CANNOT raise a change request**.
- This is checked via `current_alot` column in `estate_home_request_details` table.

---

## 2. WHY "ONLY APPLICABLE WHEN HOUSE IS ALLOTTED"

| Condition | `current_alot` value | Change Request |
|---|---|---|
| No house allotted | NULL or empty | NOT allowed (button hidden/disabled) |
| House allotted | Has house number (e.g., H-205) | ALLOWED |

In Controller (`ChangeRequestController.java`):
```java
@GetMapping("/changeRequestDetails")
public String changeRequestDetails(
    @RequestParam("current_alot") String current_alot,  // ← current house is mandatory
    @RequestParam("estate_request_for_house_pk") long pk,
    @RequestParam("status") int status,
    @RequestParam("estate_unit_sub_type_master_pk") long eligibility_type_pk,
    @RequestParam("req_id") String req_id,
    @RequestParam("salary_grade") String salary_grade
)
```
The `current_alot` parameter must carry the currently allotted house number. Without it, the form cannot load.

---

## 3. MODULE DIRECTORY STRUCTURE

```
LBSNAA/src/main/java/com/org/ils/il/estate/
├── controller/
│   ├── ChangeRequestController.java          ← Change Request endpoints
│   ├── ApprovedRequestController.java        ← House allotment/approval
│   ├── RequestDeatilsController.java         ← Request details
│   ├── ApprovedExtensionRequestController.java
│   ├── NextApproverRequestController.java
│   ├── PossessionController.java
│   ├── CampusController.java
│   ├── EsateHouseController.java
│   └── ... (19 controllers total)
├── service/
│   ├── ChangeRequestService.java             ← Interface
│   └── ... (19 interfaces total)
├── serviceImpl/
│   ├── ChangeRequestServiceImpl.java         ← Implementation (uses MyBatis)
│   └── ... (19 implementations total)
├── model/
│   ├── ChangeRequestModel.java               ← Change request entity
│   ├── ApprovedRequestModel.java             ← Allotment entity
│   ├── RequestDeatilsModel.java              ← Request details entity
│   ├── PossessionModel.java                  ← Possession/meter entity
│   └── ... (19 models total)
└── ibatis/
    ├── ChangeRequestIbatis.java              ← MyBatis mapper (DB queries)
    ├── ApprovedRequestIbatis.java
    └── ... (19 mappers total)
```

---

## 4. ALL RELATED DATABASE TABLES

| # | Table Name | Purpose |
|---|---|---|
| 1 | `estate_home_request_details` | Master record of employee's house request & current allotment |
| 2 | `estate_change_home_req_details` | Stores the actual change request (current → new house) |
| 3 | `estate_home_req_forward_change` | Tracks approval forwarding chain for change requests |
| 4 | `estate_home_req_forward` | Tracks approval forwarding for original house request |
| 5 | `estate_house_master` | All available houses in the campus |
| 6 | `estate_hac_home_req_app` | HAC (House Allocation Committee) approval records |
| 7 | `estate_possession_details` | Possession date and meter reading records |
| 8 | `estate_campus_master` | Campus/location master |
| 9 | `estate_block_master` | Block/building master |
| 10 | `estate_unit_type_master` | Unit type (1BHK, 2BHK, etc.) |
| 11 | `estate_unit_sub_type_master` | Sub-type (Faculty, Staff, etc.) |

---

## 5. TABLE COLUMN DETAILS

### Table 1: `estate_home_request_details`
> Master record — employee's house allotment request

| Column | Type | Description |
|---|---|---|
| `pk` | BIGINT | Primary Key |
| `req_id` | VARCHAR | Request ID (e.g., REQ-2024-001) |
| `req_date` | VARCHAR | Date of original request |
| `emp_name` | VARCHAR | Employee full name |
| `employee_id` | VARCHAR | Employee ID |
| `emp_designation` | VARCHAR | Designation |
| `pay_scale` | VARCHAR | Pay scale |
| `doj_pay_scale` | VARCHAR | Date of joining current pay scale |
| `doj_academic` | VARCHAR | Academic joining date |
| `doj_service` | VARCHAR | Service joining date |
| `current_alot` | VARCHAR | **Currently allotted house number** ← KEY FIELD |
| `eligibility_type_pk` | BIGINT | FK → unit sub-type eligibility |
| `status` | INT | 0=pending, 1=approved, 2=disapproved |
| `app_status` | INT | Application approval status |
| `hac_status` | INT | 0=not done, 1=HAC approved |
| `f_status` | INT | Forward status |
| `change_status` | INT | **0=no change pending, 1=change request raised** |
| `extension_date` | VARCHAR | Extension date if applicable |
| `possession_date` | VARCHAR | Date house was taken possession of |
| `approved_by` | VARCHAR | Approved by whom |
| `employee_pk` | BIGINT | FK → employee_master |
| `estate_house_master_pk` | BIGINT | FK → estate_house_master |
| `estate_campus_master_pk` | BIGINT | FK → estate_campus_master |
| `estate_unit_type_master_pk` | BIGINT | FK → estate_unit_type_master |
| `estate_block_master_pk` | BIGINT | FK → estate_block_master |

---

### Table 2: `estate_change_home_req_details`
> The change request — stores what new house is being requested

| Column | Type | Description |
|---|---|---|
| `pk` | BIGINT | Primary Key |
| `estate_home_req_details_pk` | BIGINT | **FK → links to employee's allotment (Table 1)** |
| `estate_change_req_ID` | VARCHAR | Change request unique ID (e.g., CHG-2024-001) |
| `change_house_no` | VARCHAR | **New house being requested** |
| `change_req_date` | DATETIME | Date change request was raised |
| `remarks` | VARCHAR | Reason for change |
| `f_status` | INT | **1=waiting for approval, 0=decision made** |
| `change_ap_dis_status` | INT | **0=pending, 1=approved, 2=disapproved** |
| `estate_campus_master_pk` | BIGINT | FK → requested campus |
| `estate_unit_type_master_pk` | BIGINT | FK → requested unit type |
| `estate_block_master_pk` | BIGINT | FK → requested block |
| `estate_unit_sub_type_master_pk` | BIGINT | FK → requested sub-type |

---

### Table 3: `estate_home_req_forward_change`
> Approval forwarding chain for change requests

| Column | Type | Description |
|---|---|---|
| `pk` | BIGINT | Primary Key |
| `estate_chg_home_req_details_pk` | BIGINT | FK → estate_change_home_req_details |
| `for_emp_pk` | BIGINT | **Forwarded TO this employee** |
| `forward_by` | BIGINT | **Forwarded BY this employee** |
| `remarks` | VARCHAR | Remarks while forwarding |
| `forw_status` | INT | **1=active/pending, 0=closed** |
| `app_disapp_status` | INT | **0=pending, 1=approved, 2=disapproved** |

---

### Table 4: `estate_house_master`
> All available houses in the campus

| Column | Type | Description |
|---|---|---|
| `pk` | BIGINT | Primary Key |
| `house_no` | VARCHAR | House number |
| `used_home_status` | INT | 0=vacant, 1=occupied |
| `licence_fee` | DOUBLE | Monthly license fee |
| `elec_charge` | FLOAT | Electricity charge |
| `water_charge` | FLOAT | Water charge |
| `meter_one` | VARCHAR | Meter 1 number |
| `meter_two` | VARCHAR | Meter 2 number |
| `estate_campus_master_pk` | BIGINT | FK → campus |
| `estate_unit_master_pk` | BIGINT | FK → unit type |
| `estate_block_master_pk` | BIGINT | FK → block |
| `estate_unit_sub_type_master_pk` | BIGINT | FK → sub-type |

---

### Table 5: `estate_hac_home_req_app`
> HAC (House Allocation Committee) approval record

| Column | Type | Description |
|---|---|---|
| `pk` | BIGINT | Primary Key |
| `estate_home_request_details_pk` | BIGINT | FK → allotment request |
| `house_no` | VARCHAR | Approved/allotted house number |
| `approved_date` | VARCHAR | Approval date |
| `possession_status` | INT | 0=not taken, 1=possession taken |
| `hac_status` | INT | HAC status |
| `estate_change_req_ID_pk` | BIGINT | FK → change request if any |
| `estate_campus_master_pk` | BIGINT | FK → campus |
| `estate_block_master` | BIGINT | FK → block |
| `estate_unit_type_master` | BIGINT | FK → unit type |
| `estate_unit_sub_type_master_pk` | BIGINT | FK → sub-type |

---

## 6. STATUS FLAGS — COMPLETE REFERENCE

### In `estate_home_request_details`:

| Column | 0 | 1 | 2 |
|---|---|---|---|
| `status` | Pending | Approved | Disapproved |
| `app_status` | Pending | Approved | Disapproved |
| `hac_status` | HAC not done | HAC approved | — |
| `f_status` | Not forwarded | Forwarded | — |
| `change_status` | No change request | **Change request raised** | — |

### In `estate_change_home_req_details`:

| Column | 0 | 1 | 2 |
|---|---|---|---|
| `f_status` | Not forwarded / closed | **Forwarded / pending** | — |
| `change_ap_dis_status` | **Pending** | **Approved** | **Disapproved** |

### In `estate_home_req_forward_change`:

| Column | 0 | 1 | 2 |
|---|---|---|---|
| `forw_status` | Closed | **Active / pending** | — |
| `app_disapp_status` | **Pending** | **Approved** | **Disapproved** |

---

## 7. COMPLETE WORKFLOW — STEP BY STEP

```
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 1: EMPLOYEE HAS A HOUSE ALLOTTED                              │
│  estate_home_request_details.current_alot = 'H-101' (not null)     │
│  estate_home_request_details.hac_status = 1 (HAC approved)         │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 2: EMPLOYEE RAISES CHANGE REQUEST                             │
│  Fills form: selects new campus, block, unit type, house            │
│  Controller: POST /saveChangeRequestDetails                         │
│                                                                     │
│  DB Operations:                                                     │
│  INSERT INTO estate_change_home_req_details (...)                   │
│  UPDATE estate_home_request_details SET change_status = 1 ...       │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  STEP 3: APPROVER SEES THE REQUEST                                  │
│  Controller: GET /listOfChangeHomeRequest                           │
│  Approver is identified either via:                                 │
│    (a) estate_home_req_forward (original allotment approver)        │
│    (b) estate_hac_home_req_app (HAC assigned approver)             │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                    ┌────────┴────────┐
                    │                 │
                    ▼                 ▼
        ┌───────────────────┐  ┌──────────────────────┐
        │  FORWARD TO NEXT  │  │  APPROVE / DISAPPROVE│
        │  APPROVER         │  │                      │
        │                   │  │  POST                │
        │  POST             │  │  /approveChangeHome  │
        │  /forwardReqToNext│  │  Request             │
        │                   │  │  OR                  │
        │  INSERT into      │  │  /disapproveChange   │
        │  estate_home_req_ │  │  HomeRequest         │
        │  forward_change   │  └──────────┬───────────┘
        └────────┬──────────┘             │
                 │              ┌─────────┴──────────┐
                 │              │                    │
                 ▼              ▼                    ▼
        Next approver       APPROVED            DISAPPROVED
        sees request    (update current_alot  (current_alot
        (GET /listOf    to new house)          stays same)
        changeHomeReq
        forwrd)
```

---

## 8. ALL CONTROLLER ENDPOINTS

### ChangeRequestController.java

| Method | URL | Type | Description |
|---|---|---|---|
| `changeRequestDetails()` | `/changeRequestDetails` | GET | Load change request form |
| `saveChangeRequestDetails()` | `/saveChangeRequestDetails` | POST | Save new change request |
| `listOfChangeHomeRequest()` | `/listOfChangeHomeRequest` | GET | List requests for approver |
| `findListOfApprover()` | `/findListOfApprover` | GET (AJAX) | Get next approvers list |
| `forwardReqToNext()` | `/forwardReqToNext` | POST | Forward to next approver |
| `listOfchangeHomeReqforwrd()` | `/listOfchangeHomeReqforwrd` | GET | List forwarded requests |
| `forwardToOtherEmployee()` | `/forwardToOtherEmployee` | POST | Re-forward to other employee |
| `approveChangeHomeRequest()` | `/approveChangeHomeRequest` | POST | Approve change request |
| `disapproveChangeHomeRequest()` | `/disapproveChangeHomeRequest` | POST | Disapprove change request |

---

## 9. SERVICE LAYER METHODS

### ChangeRequestService.java (Interface) → ChangeRequestServiceImpl.java (Implementation)

| Method | Purpose |
|---|---|
| `getDeatilsForChange(ModelMap)` | Get available house options for change |
| `getDeatilsForForward1(ModelMap)` | Get data needed for forwarding screen |
| `saveChangeRequestDetails(ModelMap, HttpServletRequest)` | Save new change request |
| `listOfChangeHomeRequest(ModelMap, HttpServletRequest)` | List pending change requests for approver |
| `findListOfApprover(ModelMap)` | Get list of possible next approvers |
| `forwardReqToNext(ModelMap, HttpServletRequest)` | Forward to next approver |
| `listOfchangeHomeReqforwrd(ModelMap, HttpServletRequest)` | List forwarded requests for next approver |
| `forwardToOtherEmployee(ModelMap, HttpServletRequest)` | Re-forward to different employee |
| `approveChangeHomeRequest(ModelMap, HttpServletRequest)` | Approve the change request |
| `disapproveChangeHomeRequest(ModelMap, HttpServletRequest)` | Reject the change request |

---

## 10. MYSQL QUERIES — COMPLETE SET

### Query 1: Gate Check — Is house allotted to employee?
```sql
SELECT pk, req_id, emp_name, employee_id, current_alot
FROM estate_home_request_details
WHERE pk = ?
  AND current_alot IS NOT NULL
  AND current_alot != '';
-- If no rows returned → NO house allotted → Change Request NOT allowed
```

---

### Query 2: Load Change Request Form (get current house details)
```sql
SELECT
    ehrd.pk,
    ehrd.eligibility_type_pk,
    ehrd.current_alot AS current_house_no,
    ecm.pk AS campus_pk,
    ecm.campus_name,
    ebm.pk AS block_pk,
    ebm.block_name,
    eustm.pk AS unit_sub_type_pk,
    eustm.unit_sub_type,
    eutm.pk AS unit_type_pk,
    eutm.unit_type
FROM estate_home_request_details ehrd
INNER JOIN estate_house_master ehm
    ON ehrd.eligibility_type_pk = ehm.estate_unit_sub_type_master_pk
INNER JOIN estate_campus_master ecm
    ON ehm.estate_campus_master_pk = ecm.pk
INNER JOIN estate_block_master ebm
    ON ehm.estate_block_master_pk = ebm.pk
INNER JOIN estate_unit_sub_type_master eustm
    ON ehm.estate_unit_sub_type_master_pk = eustm.pk
INNER JOIN estate_unit_type_master eutm
    ON ehm.estate_unit_master_pk = eutm.pk
WHERE ehrd.pk = ?;
-- ? = estate_request_for_house_pk (employee's allotment PK)
```

---

### Query 3: Get Available Houses for Change (dropdown)
```sql
SELECT ehm.pk, ehm.house_no, ecm.campus_name, ebm.block_name, eustm.unit_sub_type
FROM estate_house_master ehm
INNER JOIN estate_campus_master ecm ON ehm.estate_campus_master_pk = ecm.pk
INNER JOIN estate_block_master ebm ON ehm.estate_block_master_pk = ebm.pk
INNER JOIN estate_unit_sub_type_master eustm ON ehm.estate_unit_sub_type_master_pk = eustm.pk
WHERE ehm.used_home_status = 0                         -- only vacant houses
  AND ehm.estate_unit_sub_type_master_pk = ?           -- match eligibility
  AND ehm.house_no != (
      SELECT current_alot FROM estate_home_request_details WHERE pk = ?
  );
-- First ? = eligibility_type_pk, Second ? = employee's allotment PK
```

---

### Query 4: Save Change Request (Step 1 — Insert)
```sql
INSERT INTO estate_change_home_req_details (
    estate_home_req_details_pk,
    estate_change_req_ID,
    estate_campus_master_pk,
    estate_unit_type_master_pk,
    estate_block_master_pk,
    estate_unit_sub_type_master_pk,
    change_house_no,
    remarks,
    change_req_date,
    f_status,
    change_ap_dis_status
) VALUES (
    ?,          -- FK to employee's allotment PK
    ?,          -- Generated change request ID (e.g., CHG-2024-001)
    ?,          -- Requested campus PK
    ?,          -- Requested unit type PK
    ?,          -- Requested block PK
    ?,          -- Requested sub-type PK
    ?,          -- New house number being requested
    ?,          -- Reason/remarks
    NOW(),      -- Current timestamp
    1,          -- f_status = 1 (pending approval)
    0           -- change_ap_dis_status = 0 (not yet decided)
);
```

### Query 4b: Save Change Request (Step 2 — Update master table)
```sql
UPDATE estate_home_request_details
SET change_status = 1
WHERE pk = ?;
-- ? = estate_home_request_details_pk (employee's allotment PK)
```

---

### Query 5: List Change Requests for Approver (UNION query)
```sql
-- Part A: Requests where approver is in the original forward chain
SELECT
    echr.pk AS change_req_pk,
    echr.estate_change_req_ID,
    echr.change_house_no AS new_house,
    echr.remarks,
    echr.change_req_date,
    ehrd.current_alot AS current_house,
    ehrd.emp_name,
    ehrd.employee_id,
    ehrd.emp_designation,
    ecm.campus_name,
    ebm.block_name,
    eustm.unit_sub_type,
    'forwarded' AS source_type
FROM estate_home_req_forward ehrf
INNER JOIN estate_home_request_details ehrd
    ON ehrf.estate_home_request_details_pk = ehrd.pk
INNER JOIN estate_change_home_req_details echr
    ON echr.estate_home_req_details_pk = ehrd.pk
INNER JOIN estate_campus_master ecm
    ON echr.estate_campus_master_pk = ecm.pk
INNER JOIN estate_block_master ebm
    ON echr.estate_block_master_pk = ebm.pk
INNER JOIN estate_unit_sub_type_master eustm
    ON echr.estate_unit_sub_type_master_pk = eustm.pk
WHERE ehrf.for_emp_pk = ?              -- logged-in approver
  AND ehrd.change_status = 1           -- change request raised
  AND ehrf.f_status = 1                -- request is still active

UNION

-- Part B: Requests where approver is assigned via HAC
SELECT
    echr.pk AS change_req_pk,
    echr.estate_change_req_ID,
    echr.change_house_no AS new_house,
    echr.remarks,
    echr.change_req_date,
    ehrd.current_alot AS current_house,
    ehrd.emp_name,
    ehrd.employee_id,
    ehrd.emp_designation,
    ecm.campus_name,
    ebm.block_name,
    eustm.unit_sub_type,
    'hac' AS source_type
FROM estate_hac_home_req_app ehac
INNER JOIN estate_home_request_details ehrd
    ON ehac.estate_home_request_details_pk = ehrd.pk
INNER JOIN estate_change_home_req_details echr
    ON echr.estate_home_req_details_pk = ehrd.pk
INNER JOIN estate_campus_master ecm
    ON echr.estate_campus_master_pk = ecm.pk
INNER JOIN estate_block_master ebm
    ON echr.estate_block_master_pk = ebm.pk
INNER JOIN estate_unit_sub_type_master eustm
    ON echr.estate_unit_sub_type_master_pk = eustm.pk
WHERE ehrd.change_status = 1
  AND echr.f_status = 1
  AND echr.change_ap_dis_status = 0;
-- ? = logged-in approver's employee PK
```

---

### Query 6: Forward Change Request to Next Approver
```sql
-- Step 1: Create forward record
INSERT INTO estate_home_req_forward_change (
    estate_chg_home_req_details_pk,
    for_emp_pk,
    forward_by,
    remarks,
    forw_status,
    app_disapp_status
) VALUES (
    ?,   -- estate_change_home_req_details PK
    ?,   -- forward TO this employee PK
    ?,   -- forward BY (logged-in user PK)
    ?,   -- remarks
    1,   -- forw_status = 1 (active)
    0    -- not decided yet
);

-- Step 2: Remove from current approver's queue
UPDATE estate_change_home_req_details
SET f_status = 0
WHERE pk = ?;
-- ? = estate_change_home_req_details PK
```

---

### Query 7: List Forwarded Requests (for Next Approver)
```sql
SELECT
    efwc.pk AS forward_pk,
    efwc.remarks AS forward_remarks,
    echr.pk AS change_req_pk,
    echr.estate_change_req_ID,
    echr.change_house_no AS new_house,
    echr.change_req_date,
    ehrd.current_alot AS current_house,
    ehrd.emp_name,
    ehrd.employee_id,
    ehrd.emp_designation,
    ecm.campus_name,
    ebm.block_name,
    eustm.unit_sub_type
FROM estate_home_req_forward_change efwc
INNER JOIN estate_change_home_req_details echr
    ON efwc.estate_chg_home_req_details_pk = echr.pk
INNER JOIN estate_home_request_details ehrd
    ON echr.estate_home_req_details_pk = ehrd.pk
INNER JOIN estate_campus_master ecm
    ON echr.estate_campus_master_pk = ecm.pk
INNER JOIN estate_block_master ebm
    ON echr.estate_block_master_pk = ebm.pk
INNER JOIN estate_unit_sub_type_master eustm
    ON echr.estate_unit_sub_type_master_pk = eustm.pk
WHERE efwc.for_emp_pk = ?          -- logged-in next approver
  AND efwc.forw_status = 1         -- still pending
  AND echr.change_ap_dis_status = 0; -- not decided
-- ? = logged-in approver's employee PK
```

---

### Query 8: Re-Forward to Another Employee
```sql
UPDATE estate_home_req_forward_change
SET for_emp_pk = ?,        -- new forwarded-to employee PK
    forward_by = ?,        -- current user (logged-in)
    remarks = ?,           -- new remarks
    forw_status = 1,       -- still active
    app_disapp_status = 0  -- still pending
WHERE estate_chg_home_req_details_pk = ?;
-- Last ? = change request PK
```

---

### Query 9: APPROVE Change Request (3 steps)
```sql
-- Step 1: Close the forward chain entry as approved
UPDATE estate_home_req_forward_change
SET forw_status = 0,          -- close the forward
    app_disapp_status = 1     -- 1 = APPROVED
WHERE estate_chg_home_req_details_pk = ?;

-- Step 2: Mark change request itself as approved
UPDATE estate_change_home_req_details
SET f_status = 0,             -- no longer pending
    change_ap_dis_status = 1  -- 1 = APPROVED
WHERE pk = ?;

-- Step 3: Update employee's actual allotted house to new house
UPDATE estate_home_request_details
SET current_alot = ?          -- new house number (e.g., 'H-205')
WHERE pk = ?;
-- First ? = new house number from estate_change_home_req_details.change_house_no
-- Second ? = estate_home_request_details PK
```

---

### Query 10: DISAPPROVE Change Request (2 steps)
```sql
-- Step 1: Close the forward chain entry as disapproved
UPDATE estate_home_req_forward_change
SET forw_status = 0,          -- close the forward
    app_disapp_status = 2     -- 2 = DISAPPROVED
WHERE estate_chg_home_req_details_pk = ?;

-- Step 2: Mark change request itself as disapproved
UPDATE estate_change_home_req_details
SET f_status = 0,             -- no longer pending
    change_ap_dis_status = 2  -- 2 = DISAPPROVED
WHERE pk = ?;

-- NOTE: estate_home_request_details.current_alot is NOT touched
-- Employee continues staying in their original house
```

---

### Query 11: Check Current Status of a Change Request
```sql
SELECT
    ehrd.emp_name,
    ehrd.employee_id,
    ehrd.current_alot AS current_house,
    echr.change_house_no AS requested_house,
    echr.change_req_date,
    echr.remarks,
    CASE echr.change_ap_dis_status
        WHEN 0 THEN 'Pending'
        WHEN 1 THEN 'Approved'
        WHEN 2 THEN 'Disapproved'
    END AS change_status,
    CASE echr.f_status
        WHEN 0 THEN 'Not Forwarded / Decision Made'
        WHEN 1 THEN 'Forwarded / Pending'
    END AS forward_status
FROM estate_change_home_req_details echr
INNER JOIN estate_home_request_details ehrd
    ON echr.estate_home_req_details_pk = ehrd.pk
WHERE echr.pk = ?;
-- ? = change request PK
```

---

## 11. ENTITY/MODEL CLASS SUMMARY

### ChangeRequestModel.java → `estate_change_home_req_details`
```java
private long pk;
private long estate_home_request_details_pk;  // FK to allotment
private String estate_change_req_ID;           // Change request ID
private String house_no;                        // Current house
private String curr_house_name;                 // Current house display
private String change_house_name;               // New house display
private String change_req_date;
private String remarks;
private long employee_master_pk;
private String emp_name;
private String designation_name;
private long estate_campus_master_pk;
private long estate_block_master_pk;
private long estate_unit_sub_type_master_pk;
private long eligibility_type_pk;
```

### ApprovedRequestModel.java → `estate_home_request_details`
```java
private long pk;
private String req_id;
private String req_date;
private String emp_name;
private String employee_id;
private String emp_designation;
private String pay_scale;
private String house_no;
private String current_alot;           // ← KEY: currently allotted house
private String eligibility_type;
private String remarks;
private long status;
private int hac_status;
private int change_status;             // ← 1 means change request raised
private String request_type;           // "New Request" or "Change Request"
private long employee_pk;
private long estate_house_master_pk;
```

---

## 12. COMPLETE CHANGE REQUEST FLOW SUMMARY TABLE

| Step | Action | Table | Column Change |
|---|---|---|---|
| 1 | Original house allotted by HAC | `estate_hac_home_req_app` | `house_no` set, `hac_status = 1` |
| 2 | `current_alot` updated | `estate_home_request_details` | `current_alot = 'H-101'` |
| 3 | Employee raises change request | `estate_change_home_req_details` | New row inserted, `f_status=1`, `change_ap_dis_status=0` |
| 4 | Master table flagged | `estate_home_request_details` | `change_status = 1` |
| 5 | Approver forwards | `estate_home_req_forward_change` | New row inserted, `forw_status=1` |
| 6a | **APPROVED** | `estate_home_req_forward_change` | `forw_status=0`, `app_disapp_status=1` |
| 6a | **APPROVED** | `estate_change_home_req_details` | `f_status=0`, `change_ap_dis_status=1` |
| 6a | **APPROVED** | `estate_home_request_details` | `current_alot = 'H-205'` (new house) |
| 6b | **DISAPPROVED** | `estate_home_req_forward_change` | `forw_status=0`, `app_disapp_status=2` |
| 6b | **DISAPPROVED** | `estate_change_home_req_details` | `f_status=0`, `change_ap_dis_status=2` |
| 6b | **DISAPPROVED** | `estate_home_request_details` | `current_alot` unchanged |

---

## 13. CORE RULE — ONE LINE SUMMARY

> **`current_alot` column in `estate_home_request_details` must be non-null for Change Request to be available. When approved, this same column is updated to the new house number. When disapproved, it remains unchanged.**

---

*Document generated for LBSNAA Estate Module — Change Request Detail functionality*
*Codebase: Sargam 2.0 | Technology: Java Spring MVC + MyBatis (iBatis)*
