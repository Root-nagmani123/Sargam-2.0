# LBSNAA — Estate Module: Complete Flow Diagram
## Technology: Java Spring MVC + MyBatis (iBatis)

---

## MASTER OVERVIEW — ALL 9 STAGES

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                         ESTATE MODULE — END TO END LIFECYCLE                          │
└───────────────────────────────────────────────────────────────────────────────────────┘

   [STAGE 1]         [STAGE 2]        [STAGE 3]       [STAGE 4]       [STAGE 5]
  HOUSE REQUEST  →  FORWARD TO    →  NEXT APPROVER →  HAC APPROVAL →  ALLOTMENT &
  (Employee)        APPROVER          REVIEW            COMMITTEE       POSSESSION
                    (Warden/Admin)    (Senior)          (Final Auth)    (Physical)
       │                │                │                  │               │
       ▼                ▼                ▼                  ▼               ▼
   [STAGE 6]         [STAGE 7]        [STAGE 8]       [STAGE 9]
  METER READING  →  CHANGE       →  EXTENSION    →  VACATION OF
  & BILLING         REQUEST          REQUEST          HOUSE
  (Monthly)         (New House)      (Stay Longer)    (Return)
```

---

## STAGE 1 — EMPLOYEE RAISES HOUSE REQUEST

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Employee                                                                 │
│  CONTROLLER: RequestDeatilsController.java                                      │
└─────────────────────────────────────────────────────────────────────────────────┘

  Employee Logs In
       │
       ▼
  GET /addRequestDetails
  (Shows blank request form)
       │
       ├── System loads: eligibility type based on pay scale & designation
       │   DB: SELECT from estate_unit_sub_type_master
       │
       ▼
  Employee Fills Form:
  ┌─────────────────────────────────┐
  │ • Name, Employee ID             │
  │ • Designation                   │
  │ • Pay Scale                     │
  │ • Date of Joining (3 types)     │
  │ • Eligibility Type              │
  │ • Remarks                       │
  └─────────────────────────────────┘
       │
       ▼
  POST /saveRequestDetails
       │
       ▼
  DB: INSERT INTO estate_home_request_details
  ┌─────────────────────────────────────────────────┐
  │ req_id         = 'REQ-2024-001' (auto-generated)│
  │ req_date       = NOW()                          │
  │ emp_name       = 'John Doe'                     │
  │ employee_id    = 'EMP-001'                      │
  │ emp_designation= 'Director'                     │
  │ pay_scale      = 'Level-14'                     │
  │ eligibility_type_pk = 3                         │
  │ current_alot   = NULL  ← no house yet           │
  │ status         = 0     ← pending                │
  │ app_status     = 0     ← not approved           │
  │ hac_status     = 0     ← HAC not done           │
  │ f_status       = 0     ← not forwarded          │
  │ change_status  = 0     ← no change request      │
  └─────────────────────────────────────────────────┘
       │
       ▼
  Request visible in employee's dashboard
  GET /lisOfRequestDetails
```

### Special Case: Non-Employee (Guest / Trainee / Other)
```
  POST /newRequestForOthHouse
       │
       ▼
  DB: INSERT INTO estate_house_other_request
  DB: INSERT INTO estate_house_other_request_approval
       │
       ▼
  Department Reviews: POST /DepOthHouseReqApp
  ├── chkval = 2  → department_app_status = 2 (REJECTED)
  └── chkval = 3  → department_app_status = 3 (APPROVED)
       │
       ▼
  Final Approval: POST /DepOthHouseReqAppFinal
```

---

## STAGE 2 — FORWARD TO APPROVER (Warden/Admin Assigns House)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Warden / Admin / First Approver                                         │
│  CONTROLLER: ApprovedRequestController.java                                     │
└─────────────────────────────────────────────────────────────────────────────────┘

  GET /listApprovedRequest
  DB: SELECT from estate_home_request_details
      WHERE app_status = 0
        AND hac_status = 0
        AND f_status   = 0
       │
       ▼
  Approver selects a request
  → Opens approval form
       │
       ├── STEP A: Select Campus
       │   GET /getAllUnitTypeAccrToEstateName?estate_pk=2
       │   DB: SELECT from estate_unit_type_master
       │       WHERE estate_campus_master_pk = 2
       │
       ├── STEP B: Select Unit Type (1BHK/2BHK/etc)
       │   GET /getAllBuidTypeAccrToEstateName?unit_type_pk=3&estate_pk=2
       │   DB: SELECT from estate_block_master
       │       WHERE estate_unit_type_master_pk = 3
       │
       ├── STEP C: Select Building/Block
       │   GET /getAllHomeAccrTobuildNameAnduSubType?build_pk=4&sub_unit_type_pk=5
       │   DB: SELECT from estate_house_master
       │       WHERE estate_block_master_pk = 4
       │         AND used_home_status = 0  ← only vacant
       │
       └── STEP D: Verify House is Empty
           GET /checkHomeForEmpty?house_no=H-101&house_pk=6
           DB: SELECT used_home_status from estate_house_master
               WHERE pk = 6
               → used_home_status = 0? → Proceed
               → used_home_status = 1? → STOP, pick another
       │
       ▼
  POST /forwardApprovalToNextEmployee
  ┌──────────────────────────────────────────────────────────┐
  │ Parameters:                                              │
  │ • estate_home_request_details_pk                        │
  │ • estate_campus_master_pk                               │
  │ • estate_unit_type_master_pk                            │
  │ • estate_block_master_pk                                │
  │ • house_no (selected house)                             │
  │ • forward_to (next approver employee_pk)                │
  │ • remarks                                               │
  └──────────────────────────────────────────────────────────┘
       │
       ▼
  DB Operations (in sequence):
  ┌─────────────────────────────────────────────────────────────────────┐
  │ 1. INSERT INTO estate_home_req_forward                              │
  │    (estate_home_request_details_pk, for_emp_pk, house_no, remarks) │
  │                                                                     │
  │ 2. UPDATE estate_home_req_approval_mgmt                             │
  │    SET is_forword = 0                                               │
  │    WHERE pk = ?                                                     │
  │                                                                     │
  │ 3. UPDATE estate_home_request_details                               │
  │    SET f_status = 1   ← now in forward queue                       │
  │    WHERE pk = ?                                                     │
  └─────────────────────────────────────────────────────────────────────┘
       │
       ▼
  → Goes to STAGE 3 (Next Approver)
```

---

## STAGE 3 — NEXT APPROVER REVIEW

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Senior Officer / Next Approver in Chain                                 │
│  CONTROLLER: NextApproverRequestController.java                                 │
└─────────────────────────────────────────────────────────────────────────────────┘

  GET /listNextApprovedRequest
  DB: SELECT from estate_home_req_forward
      INNER JOIN estate_home_request_details
      WHERE for_emp_pk = [logged-in user]
        AND forw_status = 1   ← pending with this approver
       │
       ▼
  Approver sees request with house details
       │
       ├─────────────────────────┬──────────────────────────┐
       │                         │                          │
       ▼                         ▼                          ▼
  FORWARD AGAIN           APPROVE REQUEST            DISAPPROVE REQUEST
  (Another Level)
  POST /forwApprovalToNextEmployee    POST /ApproveRequestOfHome    POST /disApproveRequestOfHome
       │                                   │                              │
       ▼                                   ▼                              ▼
  INSERT estate_home_req_forward     UPDATE estate_home_request_details   Goes back to previous
  (new forward record)               SET app_status = 1                   approver in chain
  UPDATE forw_status = 0 (old)       → Moves to STAGE 4 (HAC)            DB: Updates f_status
                                                                          Returns to Stage 2
```

---

## STAGE 4 — HAC (HOUSE ALLOCATION COMMITTEE) FINAL APPROVAL

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: HAC Committee / Estate Officer                                          │
│  CONTROLLER: ApprovedRequestController.java                                     │
└─────────────────────────────────────────────────────────────────────────────────┘

  POST /updateHacStatusOfEstateHomeReqDel
  DB: UPDATE estate_home_request_details
      SET hac_status = 1   ← submitted to HAC
      WHERE pk = ?
       │
       ▼
  GET /listOfHacRequest
  DB: SELECT from estate_home_request_details
      WHERE hac_status = 1
        AND app_status = 0
       │
       ▼
  GET /approveFormForHac
  (Shows final approval form with all details)
       │
       ├────────────────────────────────────┐
       │                                    │
       ▼                                    ▼
  POST /approveRequestByHac          POST /disapproveRequestByHac
  (HAC APPROVES)                     (HAC REJECTS)
       │                                    │
       ▼                                    ▼
  DB Operations:                      DB: UPDATE estate_home_request_details
  ┌─────────────────────────────┐          SET app_status = 2 (REJECTED)
  │ 1. INSERT INTO              │     → Request is closed. Employee must
  │    estate_hac_home_req_app  │       re-apply if needed.
  │    (house_no, campus, block,│
  │     approved_date)          │
  │                             │
  │ 2. UPDATE                   │
  │    estate_home_request_details│
  │    SET app_status  = 1 ✓    │
  │    SET hac_status  = 1 ✓    │
  │    SET change_status = 1    │
  │                             │
  │ 3. UPDATE estate_house_master│
  │    SET used_home_status = 1 │
  │    (house now OCCUPIED)     │
  │                             │
  │ 4. UPDATE                   │
  │    estate_home_req_approval_│
  │    mgmt (close chain)       │
  └─────────────────────────────┘
       │
       ▼
  → Goes to STAGE 5 (Possession)
```

---

## STAGE 5 — ALLOTMENT & POSSESSION (Physical Handover)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Estate Office Staff                                                     │
│  CONTROLLER: PossessionController.java                                          │
└─────────────────────────────────────────────────────────────────────────────────┘

  GET /listPossession
  (Lists all employees with HAC approved allotments)
       │
       ▼
  GET /addPossession
  (Opens possession entry form)
       │
       ▼
  GET /getDeatailsOfEmployee?employee_master=[emp_pk]
  DB: SELECT from estate_hac_home_req_app
      INNER JOIN estate_home_request_details
      WHERE app_status = 1
        AND possession_status = 0   ← not yet taken possession
       │
       ▼
  Staff fills possession form:
  ┌─────────────────────────────────┐
  │ • Allotment Date                │
  │ • Possession Date               │
  │ • Electric Meter Reading 1      │
  │ • Electric Meter Reading 2      │
  │ • Water Meter Reading           │
  └─────────────────────────────────┘
       │
       ▼
  POST /savePossessionDetails
  DB: INSERT INTO estate_possession_details
  ┌──────────────────────────────────────────────────────┐
  │ estate_home_request_details_pk = ?                   │
  │ emploee_master_pk               = ?                  │
  │ allotment_date                  = '2024-01-15'       │
  │ possession_date                 = '2024-01-20'       │
  │ electric_meter_reading          = 1250  (opening)    │
  │ electric_meter_reading_2        = 800   (opening)    │
  │ current_alot                    = 'H-101'            │
  │ return_home_status              = 0  ← still occupied│
  └──────────────────────────────────────────────────────┘
       │
       ▼
  DB: UPDATE estate_home_request_details
      SET current_alot = 'H-101'   ← house now recorded
       │
       ▼
  DB: UPDATE estate_hac_home_req_app
      SET possession_status = 1   ← possession taken
       │
       ▼
  → Employee physically moves into house
  → Goes to STAGE 6 (Monthly Meter Reading & Billing)
```

---

## STAGE 6 — MONTHLY METER READING & BILLING

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Estate Office Staff + Employee                                          │
│  CONTROLLER: PossessionController.java                                          │
└─────────────────────────────────────────────────────────────────────────────────┘

  Every Month → Staff records meter readings

  GET /getAllSubUnitTypeAccrToBuidType?building_pk=4
  (Get all units in a building)
       │
       ▼
  GET /listMeterReading?month_year=2024-02&building=4
  (View existing readings for this building/month)
       │
       ▼
  POST /saveTheMonthWiseReading
  DB: INSERT INTO estate_month_reading_details
  ┌─────────────────────────────────────────────┐
  │ estate_possession_details_pk = ?            │
  │ meter_reading_date = '2024-02-28'           │
  │ current_meter_reading   = 1420  (meter 1)   │
  │ current_meter_reading2  = 950   (meter 2)   │
  │ last_meter_reading      = 1250  (prev month)│
  │ last_meter_reading2     = 800   (prev month)│
  │ units_consumed = 170  (1420 - 1250)         │
  │ units_consumed2 = 150  (950 - 800)          │
  └─────────────────────────────────────────────┘
       │
       ▼
  ┌─────────────────────── BILLING ──────────────────────┐
  │                                                       │
  │  GET /listEstateBillSummary                          │
  │      ?meter_reading_date=2024-02-28                  │
  │      &year_month=2024-02                             │
  │      &unit_sub_type=Faculty                          │
  │                                                       │
  │  DB: SELECT with calculations:                        │
  │  ┌─────────────────────────────────────────────┐    │
  │  │ Bill = (units consumed × electric_charge)   │    │
  │  │      + water_charge                         │    │
  │  │      + licence_fee                          │    │
  │  └─────────────────────────────────────────────┘    │
  │                                                       │
  │  POST /notifytoEployeeForBillGenerated               │
  │  (Sends EMAIL to employee with bill details)          │
  │                                                       │
  │  Employee views bill:                                 │
  │  GET /estateBillListByEmployee                       │
  │  GET /viewBillDetails?bill_Id=BILL-2024-02-001       │
  └───────────────────────────────────────────────────────┘
       │
       ▼
  Staff can update charges:
  ├── POST /updateLicenceCharge  → UPDATE estate_possession_details.licence_fee
  └── POST /updateWaterCharge   → UPDATE estate_possession_details.water_charge

  If meter is damaged:
  POST /saveMeterNumberWhenMeterDamage
  DB: UPDATE estate_house_master SET meter_one = new_meter_no
      INSERT estate_month_reading_details with new meter reset reading
```

---

## STAGE 7 — CHANGE REQUEST (Employee Wants Different House)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Employee (must already HAVE a house) + Approver                         │
│  CONTROLLER: ChangeRequestController.java                                       │
│  GATE CHECK: current_alot must NOT be NULL                                      │
└─────────────────────────────────────────────────────────────────────────────────┘

  ┌─────────────────────────────────────────────────┐
  │  PRECONDITION CHECK                             │
  │  SELECT current_alot FROM                       │
  │  estate_home_request_details WHERE pk = ?       │
  │                                                 │
  │  current_alot = NULL  →  ❌ BLOCKED             │
  │  current_alot = 'H-101' →  ✅ ALLOWED           │
  └─────────────────────────────────────────────────┘
       │
       ▼ (only if house allotted)
  GET /changeRequestDetails
      ?current_alot=H-101
      &estate_request_for_house_pk=101
      &estate_unit_sub_type_master_pk=3
       │
       ▼
  Employee fills Change Request Form:
  ┌─────────────────────────────────────────────────┐
  │ Current House : H-101 (auto-filled, read-only) │
  │ Reason/Remarks: [text input]                   │
  │ New Campus    : [dropdown]                     │
  │ New Block     : [dropdown]                     │
  │ New House     : [dropdown - vacant only]       │
  └─────────────────────────────────────────────────┘
       │
       ▼
  POST /saveChangeRequestDetails
  DB Step 1: INSERT INTO estate_change_home_req_details
  ┌──────────────────────────────────────────────────────┐
  │ estate_home_req_details_pk    = 101                  │
  │ estate_change_req_ID          = 'CHG-2024-001'       │
  │ change_house_no               = 'H-205' (requested)  │
  │ change_req_date               = NOW()                │
  │ remarks                       = 'Closer to office'   │
  │ estate_campus_master_pk       = 2                    │
  │ estate_block_master_pk        = 4                    │
  │ estate_unit_sub_type_master_pk= 3                    │
  │ f_status                      = 1  ← pending        │
  │ change_ap_dis_status          = 0  ← not decided     │
  └──────────────────────────────────────────────────────┘
  DB Step 2: UPDATE estate_home_request_details
             SET change_status = 1  ← change request raised
             WHERE pk = 101
       │
       ▼
  ┌───────────────── APPROVAL CHAIN ─────────────────┐
  │                                                   │
  │  GET /listOfChangeHomeRequest                    │
  │  (Approver sees pending change requests)          │
  │       │                                           │
  │  GET /findListOfApprover?pk=[emp_pk]             │
  │  (Get dropdown of possible approvers)             │
  │       │                                           │
  │  POST /forwardReqToNext                          │
  │  DB: INSERT INTO estate_home_req_forward_change   │
  │      (estate_chg_home_req_details_pk,             │
  │       for_emp_pk, forward_by, remarks,            │
  │       forw_status=1, app_disapp_status=0)         │
  │       │                                           │
  │  UPDATE estate_change_home_req_details            │
  │  SET f_status = 0                                 │
  │       │                                           │
  │  GET /listOfchangeHomeReqforwrd                  │
  │  (Next approver sees forwarded request)           │
  │       │                                           │
  │  POST /forwardToOtherEmployee (re-forward)       │
  │  UPDATE estate_home_req_forward_change            │
  │  SET for_emp_pk = new_employee                    │
  │       │                                           │
  └─────────────────────┬────────────────────────────┘
                        │
           ┌────────────┴─────────────┐
           │                          │
           ▼                          ▼
  POST /approveChangeHomeRequest    POST /disapproveChangeHomeRequest
  (APPROVED)                        (REJECTED)
           │                          │
           ▼                          ▼
  DB — 5 Operations:            DB — 2 Operations:
  ┌───────────────────────────┐  ┌──────────────────────────────┐
  │ 1. UPDATE                 │  │ 1. UPDATE                    │
  │    estate_home_req_       │  │    estate_home_req_          │
  │    forward_change         │  │    forward_change            │
  │    SET forw_status = 0    │  │    SET forw_status = 0       │
  │    SET app_disapp_status=1│  │    SET app_disapp_status = 2 │
  │                           │  │                              │
  │ 2. UPDATE                 │  │ 2. UPDATE                    │
  │    estate_change_home_req │  │    estate_change_home_req    │
  │    _details               │  │    _details                  │
  │    SET f_status = 0       │  │    SET f_status = 0          │
  │    SET change_ap_dis = 1  │  │    SET change_ap_dis = 2     │
  │                           │  │                              │
  │ 3. UPDATE                 │  │ current_alot stays = 'H-101' │
  │    estate_possession_details│ │ Employee stays in old house  │
  │    SET return_home_status=1│ └──────────────────────────────┘
  │    (old house returned)   │
  │                           │
  │ 4. UPDATE estate_house_   │
  │    master                 │
  │    SET used_home_status=0 │
  │    WHERE house_no='H-101' │
  │    (old house now VACANT) │
  │                           │
  │ 5. UPDATE                 │
  │    estate_home_request_   │
  │    details                │
  │    SET current_alot='H-205│
  │    SET change_status = 2  │
  │    (new house recorded)   │
  └───────────────────────────┘
           │
           ▼
  New Possession Entry created for H-205
  → Back to STAGE 6 (Billing continues for new house)
```

---

## STAGE 8 — EXTENSION REQUEST (Employee Wants to Stay Longer)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Employee (must already HAVE a house) + Approvers                        │
│  CONTROLLER: ApprovedExtensionRequestController.java                            │
│              NextApprovedExtensionRequestController.java                        │
│  GATE CHECK: current_alot must NOT be NULL + within lease period                │
└─────────────────────────────────────────────────────────────────────────────────┘

  GET /ExtensionRequestDetails
      ?current_alot=H-101
      &state_request_for_house_pk=101
      &status=1
       │
       ▼
  Employee fills Extension Form:
  ┌─────────────────────────────────────────────────┐
  │ Current House: H-101 (auto-filled)             │
  │ Current To Date: [existing end date]           │
  │ Extension To Date: [new end date requested]    │
  │ Ground/Reason Type: [dropdown from             │
  │                      estate_ground_type table] │
  │ Remarks: [text]                                │
  └─────────────────────────────────────────────────┘
       │
       ▼
  POST /saveExtensionRequestDetails
  DB: INSERT INTO estate_exd_home_request_details
  ┌───────────────────────────────────────────────────┐
  │ estate_home_request_details_pk = 101              │
  │ to_date          = '2024-12-31'  (extension till) │
  │ ground_type      = 'Medical'                      │
  │ remarks          = 'Medical treatment ongoing'    │
  │ extd_status      = 1  ← extension requested       │
  │ f_status         = 1  ← pending                  │
  │ app_status       = 0  ← not decided              │
  └───────────────────────────────────────────────────┘
       │
       ▼
  ┌─────────────── FIRST LEVEL APPROVER ─────────────┐
  │                                                   │
  │  GET /listApprovedExtensionRequest               │
  │  DB: SELECT from estate_exd_home_request_details │
  │      WHERE extd_status = 1                        │
  │        AND f_status = 1                           │
  │       │                                           │
  │  GET /forwardRequesttoOther                      │
  │  (Shows forward form with extension details)      │
  │       │                                           │
  │  POST /forwardExtenApprovalToNextEmployee        │
  │  DB Step 1: INSERT INTO estate_home_req_forward_exten│
  │             (with new extension to_date)          │
  │  DB Step 2: UPDATE estate_home_req_approval_mgmt  │
  │             SET is_forword = 0                    │
  │  DB Step 3: UPDATE estate_exd_home_request_details│
  │             SET f_status = 0                      │
  └──────────────────────┬────────────────────────────┘
                         │
                         ▼
  ┌─────────────── NEXT LEVEL APPROVER ──────────────┐
  │                                                   │
  │  GET /listNextApprovedExtensionRequest           │
  │  (Sees forwarded extension requests)              │
  │       │                                           │
  │  POST /forwExtenApprovalToNextEmployee (re-fwd)  │
  │       │                                           │
  └───────────────────┬───────────────────────────────┘
                      │
         ┌────────────┴──────────────┐
         │                           │
         ▼                           ▼
  POST /ApproveExtensionRequest    POST /disApproveExtensionRequest
  OfHome                           OfHome
  (APPROVED)                       (REJECTED)
         │                           │
         ▼                           ▼
  DB Operations:               DB Operations:
  ┌─────────────────────────┐  ┌───────────────────────────────┐
  │ UPDATE estate_exd_home_ │  │ UPDATE estate_exd_home_       │
  │ request_details         │  │ request_details               │
  │ SET app_status = 1      │  │ SET app_status = 2 (REJECTED) │
  │ SET to_date = new_date  │  │                               │
  │                         │  │ to_date unchanged             │
  │ UPDATE estate_home_req_ │  │ Employee must vacate on       │
  │ forward_exten           │  │ original end date             │
  │ SET app_status = 1      │  └───────────────────────────────┘
  └─────────────────────────┘
         │
         ▼
  Extension period now active
  → Billing continues till new to_date
  → Goes back to STAGE 6
```

---

## STAGE 9 — VACATION / RETURN OF HOUSE

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  ACTOR: Estate Office Staff                                                     │
│  CONTROLLER: PossessionController.java + ApprovedRequestController.java        │
└─────────────────────────────────────────────────────────────────────────────────┘

  Employee Vacates (end of tenure / transfer / resignation)
       │
       ├── Option A: Triggered by Change Request Approval
       │   (Automatic — handled in Stage 7)
       │
       └── Option B: Manual Vacation
           POST /saveDate (estate officer records return)
           DB: INSERT INTO employee_home_deactive_info
               (employee_pk, deactivation_date, reason)
                │
                ▼
           DB: UPDATE estate_possession_details
               SET return_home_status = 1   ← house returned
               WHERE estate_home_request_details_pk = ?
                │
                ▼
           DB: UPDATE estate_house_master
               SET used_home_status = 0    ← house now VACANT
               WHERE house_no = 'H-101'
                │
                ▼
           DB: UPDATE estate_home_request_details
               SET current_alot = NULL    ← no house assigned
               WHERE pk = ?

       │
       ▼
  Final meter reading recorded:
  POST /saveTheMonthWiseReading (closing reading)
       │
       ▼
  Final bill generated:
  GET /listEstateBillSummary (closing bill)
       │
       ▼
  House is now available for new allotment
  → Appears in /getAllHomeAccrTobuildNameAnduSubType
    (used_home_status = 0)
  → Cycle starts again from STAGE 2
```

---

## COMPLETE TABLE INTERACTION MAP

```
TABLE                              ← WRITTEN BY (Stage)       ← READ BY (Stage)
─────────────────────────────────────────────────────────────────────────────────
estate_home_request_details        Stage 1 (INSERT)           Stage 2,3,4,5,7,8
                                   Stage 2,3,4,5,7,9 (UPDATE)

estate_home_req_forward            Stage 2 (INSERT)           Stage 3
                                   Stage 3 (UPDATE)

estate_home_req_approval_mgmt      Stage 2 (UPDATE)           Stage 2,3

estate_hac_home_req_app            Stage 4 (INSERT)           Stage 5,7
                                   Stage 7 (INSERT new row)

estate_house_master                Stage 4 (UPDATE occupied)  Stage 2,3,4,7
                                   Stage 7 (UPDATE vacant+occ)
                                   Stage 9 (UPDATE vacant)

estate_possession_details          Stage 5 (INSERT)           Stage 6
                                   Stage 6 (UPDATE meter)
                                   Stage 7,9 (UPDATE return)

estate_month_reading_details       Stage 6 (INSERT monthly)   Stage 6 (bills)

estate_change_home_req_details     Stage 7 (INSERT)           Stage 7 approvers
                                   Stage 7 (UPDATE status)

estate_home_req_forward_change     Stage 7 (INSERT forward)   Stage 7 next approver
                                   Stage 7 (UPDATE decision)

estate_exd_home_request_details    Stage 8 (INSERT)           Stage 8 approvers
                                   Stage 8 (UPDATE status)

estate_home_req_forward_exten      Stage 8 (INSERT forward)   Stage 8 next approver
                                   Stage 8 (UPDATE decision)

employee_home_deactive_info        Stage 9 (INSERT return)    —

estate_campus_master               Masters (read-only)        Stage 2,7
estate_block_master                Masters (read-only)        Stage 2,7
estate_unit_type_master            Masters (read-only)        Stage 2,7
estate_unit_sub_type_master        Masters (read-only)        Stage 1,2,7
estate_ground_type                 Masters (read-only)        Stage 8
```

---

## STATUS FLAG LIFECYCLE — VISUAL

### `estate_home_request_details`

```
                 Stage 1     Stage 2     Stage 3    Stage 4     Stage 5      Stage 7
                 (Created)   (Forward)   (Approve)  (HAC)       (Possession) (Change)
                 ─────────   ─────────   ─────────  ─────────   ──────────── ────────
status         :    0                                               1
app_status     :    0            0           1         1                         0→reset
hac_status     :    0            0           0       0 → 1
f_status       :    0         0 → 1                                              0
change_status  :    0                                           0          0 → 1 → 2
current_alot   :   NULL         NULL        NULL     NULL      H-101    H-101 → H-205
```

### `estate_change_home_req_details`

```
                Stage 7        Stage 7         Stage 7
                (INSERT)       (Forward)       (Approve/Reject)
                ────────       ─────────       ────────────────
f_status    :    1              0               0
change_ap_  :    0              0               1 (approved) / 2 (rejected)
dis_status
```

### `estate_house_master`

```
                Initial     HAC Approves     Change Approved    Vacation
                ──────────  ──────────────   ─────────────────  ───────────
H-101 status :    0            1                   0               0
               (vacant)     (occupied)          (vacant again)   (vacant)

H-205 status :    0            0                   1               0
               (vacant)     (vacant)            (occupied)      (vacant)
```

---

## CONTROLLER → TABLE → IBATIS MAPPER MAPPING

| Controller | Key Tables | Mapper Interface |
|---|---|---|
| RequestDeatilsController | estate_home_request_details | RequestDeatilsIbatis |
| ApprovedRequestController | estate_home_req_forward, estate_hac_home_req_app | ApprovedRequestIbatis |
| NextApproverRequestController | estate_home_req_forward | ApprovedRequestIbatis |
| PossessionController | estate_possession_details, estate_month_reading_details | PossessionIbatis |
| ChangeRequestController | estate_change_home_req_details, estate_home_req_forward_change | ChangeRequestIbatis |
| ApprovedExtensionRequestController | estate_exd_home_request_details, estate_home_req_forward_exten | ApprovedExtensionRequestIbatis |
| NextApprovedExtensionRequestController | estate_exd_home_request_details, estate_home_req_forward_exten | ApprovedExtensionRequestIbatis |

---

## NOTIFICATION FLOW

```
  Stage 1: Request Submitted
       │
       └── findNotificationofHomeReq()
           DB: SELECT where app_status=0 AND hac_status=0 AND f_status=0
           → Shows badge/count to Warden on dashboard

  Stage 2: Request Forwarded
       │
       └── findNotificationofHomeReq2()
           DB: SELECT from estate_home_req_forward where for_emp_pk = [user]
           → Shows badge/count to Next Approver

  Stage 7: Change Request Raised
       │
       └── findNotificationForChangeRequestforw()
           DB: SELECT where change_status=1 AND f_status=1
           → Shows badge to Change Request Approver

  Stage 8: Extension Request Raised
       │
       └── findNotificationForExtdRequest()
           DB: SELECT where extd_status=1 AND f_status=1
           → Shows badge to Extension Approver

  Stage 6: Bill Generated
       │
       └── POST /notifytoEployeeForBillGenerated
           Sends EMAIL to employee
           Employee accesses: GET /estateBillListByEmployeeAtLink?pk=[token]
```

---

## ONE-PAGE SUMMARY

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                    ESTATE MODULE COMPLETE FLOW                               │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  EMPLOYEE                    APPROVER CHAIN              ESTATE OFFICE       │
│  ─────────                   ──────────────              ─────────────       │
│                                                                              │
│  1. Apply for House  ──────→  2. Assign House        3. HAC Final Approval   │
│     (saveRequestDetails)        (forwardApproval)       (approveRequestByHac)│
│                                                              │               │
│                                                              ▼               │
│                                                      4. Record Possession    │
│                                                         (savePossession      │
│                                                          Details)            │
│                                                              │               │
│                              ┌───────────────────────────── ▼ ─────────────┐│
│                              │         ONGOING STAY                        ││
│                              │                                             ││
│  5. View Monthly Bill ←──── Estate Office records monthly meter readings   ││
│     (estateBillList           (saveTheMonthWiseReading)                    ││
│      ByEmployee)                    │                                      ││
│                              ───────┼───────────────────────               ││
│                                     │                                      ││
│  6. Change House     ──────→  Approver approves change                     ││
│     (saveChangeRequest          (approveChangeHomeRequest)                 ││
│      Details)                       → old house freed                      ││
│     [ONLY IF house allotted]        → new house occupied                   ││
│                                                                             ││
│  7. Extend Stay      ──────→  Approver approves extension                  ││
│     (saveExtension              (ApproveExtension                          ││
│      RequestDetails)             RequestOfHome)                            ││
│     [ONLY IF house allotted]        → new to_date set                      ││
│                              └─────────────────────────────────────────────┘│
│                                                                              │
│  8. Vacate House     ──────→ Estate Office records return                   │
│                               → house_status = VACANT                       │
│                               → available for next employee                 │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

*Document: LBSNAA Estate Module Flow Diagram*
*Codebase: Sargam 2.0 | Java Spring MVC + MyBatis (iBatis)*
*Total Controllers: 7 | Total Tables: 15+ | Total Stages: 9*
