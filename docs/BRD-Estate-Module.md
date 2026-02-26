# Business Requirements Document (BRD)  
# Estate Module — Sargam 2.0

**Document Version:** 1.0  
**Last Updated:** February 25, 2026  
**Module:** Estate Management  
**System:** Sargam 2.0 (LBSNAA)

---

## 1. Executive Summary

The **Estate Module** supports end-to-end management of institutional housing: from employee housing requests and HAC (House Allotment Committee) approval to possession, meter readings, billing, and house returns. It distinguishes between **LBSNAA (in-house)** housing and **Other** estates, and provides master data setup, workflows, and reports for estate operations.

---

## 2. Business Objectives

| Objective | Description |
|-----------|-------------|
| **Request lifecycle** | Capture, track, and process housing requests from submission through HAC approval to allotment. |
| **Dual estate handling** | Support both LBSNAA possession details and “Estate Possession for Other” (external/other campuses). |
| **Billing & utilities** | Manage meter readings (electric/water), electric slabs, and generate estate bills. |
| **Inventory & status** | Maintain house definitions, vacant/occupied status, and return-of-house process. |
| **Compliance & reporting** | Provide house status, pending meter readings, bill reports, and migration reports (e.g. 1998–2026). |

---

## 3. Scope

### 3.1 In Scope

- Request for Estate (create, list, delete) with employee and eligibility linkage  
- Put in HAC and HAC-approved workflow (change request approve/disapprove, new request allot)  
- Estate Approval Setting and Add Approved Request House  
- Estate Request for Others and Add Other Estate Request  
- Possession Details (LBSNAA), Possession for Others, Possession View (Add)  
- Update Meter Reading (LBSNAA) and Update Meter Reading of Other  
- Update Meter No., List Meter Reading  
- Generate Estate Bill and Estate Bill Summary  
- Return House (mark return for estate_possession_other)  
- Define House (CRUD with blocks/unit sub types)  
- Define Electric Slab (CRUD)  
- Estate Master: Define Campus, Unit Type, Unit Sub Type, Block/Building, Pay Scale, Eligibility Criteria  
- Reports: Pending Meter Reading, House Status, Estate Bill Report (Grid, Print, Print All, PDF), Migration Report  

### 3.2 Out of Scope (for this BRD)

- Payroll integration details (only reference to payroll bill head mapping)  
- Physical asset/ maintenance management  
- External payment gateway integration  

---

## 4. Stakeholders

| Role | Responsibility |
|------|----------------|
| **Estate Admin** | Master data, approval settings, define house/electric slab, campus/block/unit setup |
| **HAC / Authority** | Put requests in HAC, approve/ disapprove change requests, allot new requests |
| **Estate Operator** | Possession entry, meter readings, generate bills, return house |
| **Employees** | Request for estate (via applicable screens), view own request status (as per application design) |
| **Finance / Audit** | Bill reports, migration report, house status for reconciliation |

---

## 5. Functional Requirements

### 5.1 Estate Management Workflow

#### 5.1.1 Request for Estate

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-001 | System shall allow creation of estate housing requests with: Request ID (e.g. home-req-01, home-req-02), request date, employee linkage, designation, pay scale, DOJ (pay scale/academic/service), eligibility type. | Must |
| REQ-EST-002 | Eligibility Type dropdown shall show only unit sub types present in `estate_eligibility_mapping`. | Must |
| REQ-EST-003 | System shall support listing of requests (from `estate_home_request_details` with possession details) with filters as per UI. | Must |
| REQ-EST-004 | System shall allow deletion of request-for-estate records where business rules permit. | Should |
| REQ-EST-005 | System shall provide next Request ID (e.g. next-req-id API) for new request creation. | Must |
| REQ-EST-006 | System shall support fetching employees and employee details for request creation. | Must |
| REQ-EST-007 | System shall support fetching vacant houses for estate request (e.g. for selection/display). | Should |

#### 5.1.2 Put in HAC

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-010 | Authority shall view list of estate requests not yet in HAC (`hac_status = 0`). | Must |
| REQ-EST-011 | Authority shall select one or more requests and put them in HAC (update `hac_status = 1`, `f_status = 1`). | Must |
| REQ-EST-012 | System shall prevent putting the same request in HAC more than once. | Must |

#### 5.1.3 HAC Approved & Change Request

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-020 | System shall list HAC-approved requests for change/allotment actions. | Must |
| REQ-EST-021 | For change request: system shall allow viewing approve details, vacant houses, and approve/disapprove actions. | Must |
| REQ-EST-022 | For new request: system shall allow viewing allot details and perform allot. | Must |

#### 5.1.4 Estate Approval Setting & Add Approved Request House

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-030 | System shall allow configuration of estate approval settings (list, add, delete as per UI). | Must |
| REQ-EST-031 | System shall allow adding approved request house: assign employees to an approver (e.g. dual list: approver selection, employee assignment). | Must |
| REQ-EST-032 | System shall support storing and retrieving approved request house mapping. | Must |

#### 5.1.5 Estate Request for Others & Other Estate Request

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-040 | System shall list “Estate Request for Others” from relevant tables (e.g. `estate_other_req`). | Must |
| REQ-EST-041 | System shall allow add and delete of “other estate request” records. | Must |

---

### 5.2 Possession

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-050 | System shall maintain **Possession Details** for LBSNAA (from `estate_possession_details` or equivalent). | Must |
| REQ-EST-051 | System shall maintain **Estate Possession for Other** (from `estate_possession_other`), including return_home_status. | Must |
| REQ-EST-052 | System shall allow deletion of possession record by id where business rules permit. | Should |
| REQ-EST-053 | Possession View (Add): user shall add possession with selection of block, unit sub type, house, and related fields. | Must |
| REQ-EST-054 | System shall provide APIs: possession blocks, unit-sub-types, houses (filtered by campus/block/type as applicable). | Must |

---

### 5.3 Meter Reading & Billing

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-060 | System shall support **Update Meter Reading** for LBSNAA possessions with date, block, unit sub type, and house-level readings. | Must |
| REQ-EST-061 | System shall support **Update Meter Reading of Other** for other estate possessions with same logical capability. | Must |
| REQ-EST-062 | System shall support **List Meter Reading** with data export/display as per UI. | Must |
| REQ-EST-063 | System shall support **Update Meter No.** (list and update meter numbers for houses). | Should |
| REQ-EST-064 | System shall support **Generate Estate Bill** and estate bill summary using meter readings and electric/water charges. | Must |
| REQ-EST-065 | Electric billing shall use **Define Electric Slab** (start/end unit range, rate per unit, house type) for calculation. | Must |

---

### 5.4 Return House

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-070 | System shall list employees/possessions where return is pending (`estate_possession_other.return_home_status = 0` or equivalent). | Must |
| REQ-EST-071 | System shall allow marking a house as returned (update return status, dates as per business rules). | Must |
| REQ-EST-072 | System shall support fetching return-house request details and employee list for the return-house screen. | Must |

---

### 5.5 Define House

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-080 | System shall allow CRUD on house master: campus, unit, block, unit sub type, house no., licence fee, water/electric charge, meter one/two, vacant/renovation status, remarks. | Must |
| REQ-EST-081 | System shall provide define-house list (data) with filters; blocks and related dropdowns (blocks, unit sub types) for create/edit. | Must |
| REQ-EST-082 | System shall enforce referential integrity with estate_campus_master, estate_block_master, estate_unit_master, estate_unit_sub_type_master. | Must |

---

### 5.6 Define Electric Slab

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-090 | System shall allow CRUD on electric slab: start unit range, end unit range, rate per unit, house (type/category). | Must |
| REQ-EST-091 | Slabs shall be used in estate bill generation for electricity component. | Must |

---

### 5.7 Estate Master

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-100 | **Define Estate/Campus:** CRUD for campus name and description. | Must |
| REQ-EST-101 | **Define Unit Type:** CRUD for unit type master. | Must |
| REQ-EST-102 | **Define Unit Sub Type:** CRUD for unit sub type master; linkage to unit type where applicable. | Must |
| REQ-EST-103 | **Define Block/Building:** CRUD for block/building name (per campus where applicable). | Must |
| REQ-EST-104 | **Define Pay Scale:** CRUD for pay scale used in eligibility and request. | Must |
| REQ-EST-105 | **Eligibility – Criteria:** CRUD for eligibility criteria and mapping to unit sub type (estate_eligibility_mapping). | Must |

---

### 5.8 Reports

| ID | Requirement | Priority |
|----|-------------|----------|
| REQ-EST-110 | **Pending Meter Reading:** Report of houses/possessions with pending meter reading entry. | Must |
| REQ-EST-111 | **House Status:** Report of current status of all houses (vacant/occupied/under renovation etc.). | Must |
| REQ-EST-112 | **Estate Bill Report – Grid View:** Grid/data table of estate bills with filters. | Must |
| REQ-EST-113 | **Estate Bill Report for Print:** Print view for selected period/scope. | Must |
| REQ-EST-114 | **Estate Bill Report – Print All / Print All PDF:** Bulk print and PDF export of bill report. | Should |
| REQ-EST-115 | **Migration Report (1998–2026):** Historical migration data with filter options (e.g. date range, campus, type). | Must |

---

## 6. User Roles & Access (Summary)

- Access to estate routes is assumed under admin/estate middleware and role checks as implemented in the application.
- Distinct menus: **Estate Management**, **Estate Master**, **Estate Reports**.
- Authority users: Put in HAC, HAC Approved (change/new allot).
- Estate operators: Possession, meter reading, bills, return house.
- Admin: Master data, approval settings, define house, electric slab.

*(Detailed permission matrix to be aligned with existing Sargam RBAC.)*

---

## 7. Data Entities (Summary)

| Entity | Table(s) | Purpose |
|--------|----------|---------|
| Campus | estate_campus_master | Estate/campus definition |
| Block | estate_block_master | Block/building under campus |
| Unit Type / Sub Type | estate_unit_type_master, estate_unit_sub_type_master, estate_unit_sub_unit_mapping | Housing type hierarchy |
| House | estate_house_master | House definition, meter refs, charges, status |
| Home Request | estate_home_request_details | Request for estate (req_id, employee, eligibility, hac_status, etc.) |
| Approval Mgmt | estate_home_req_approval_mgmt | Approver–employee mapping |
| HAC / Forward | estate_hac_home_req_app, estate_home_req_forward, etc. | HAC and forward history |
| Change Request | estate_change_home_req_details | Change request details |
| Possession (LBSNAA) | estate_possession_details | LBSNAA possession |
| Possession Other | estate_possession_other | Other estate possession, return status |
| Other Request | estate_other_req | Estate request for others |
| Meter Reading | estate_month_reading_details, estate_month_reading_details_other | Monthly readings |
| Electric Slab | estate_electric_slab | Slab rates for billing |
| Eligibility | estate_eligibility_mapping | Eligibility to unit sub type mapping |
| Payroll mapping | estate_payroll_bill_head, estate_payroll_bill_head_mapping | Link to payroll (out of scope detail) |

---

## 8. Business Rules (Key)

1. **Request ID:** Sequential, format `home-req-NN` (e.g. home-req-01, home-req-02).  
2. **HAC:** A request can be put in HAC only once (`hac_status` 0 → 1); thereafter `f_status = 1`.  
3. **Eligibility:** Only unit sub types present in `estate_eligibility_mapping` are shown for eligibility type in request.  
4. **Return House:** Applicable to “Estate Possession for Other”; return_home_status updated on mark return.  
5. **Billing:** Estate bill uses electric slabs (unit range and rate) and house-level water/electric charges.  
6. **Possession:** Two streams—LBSNAA (possession details) and Other (possession other); separate screens and APIs.

---

## 9. Assumptions

- Employee master and payroll/salary data exist and are used for eligibility and display.  
- HAC workflow is internal (no external system integration).  
- Meter readings are entered manually or via bulk upload as per existing screens.  
- Migration report scope (1998–2026) is as per existing business need.  
- All estate routes are under `admin.estate` prefix and protected by admin auth.

---

## 10. Dependencies

- **Employee Master:** Employee PK, name, designation, DOJ, pay scale.  
- **Authentication & RBAC:** Admin login and role-based access to estate menus.  
- **Database:** All estate_* tables as per schema (refer estate_module_tables SQL).  
- **Payroll (optional):** estate_payroll_bill_head mapping for deduction/bill head linkage.

---

## 11. Acceptance Criteria (High Level)

- Request for Estate can be created, listed, and deleted; next request ID generated.  
- Put in HAC updates selected requests to HAC-approved state; no duplicate put-in.  
- HAC Approved screen allows change request approve/disapprove and new request allot.  
- Possession can be added (Possession View) and listed (Possession Details / Possession for Others).  
- Meter reading (LBSNAA and Other) can be updated; list meter reading available.  
- Estate bill can be generated using electric slabs and house charges.  
- Return House marks possession as returned for “other” estate.  
- Define House and Define Electric Slab support full CRUD.  
- Estate Master (Campus, Unit Type, Unit Sub Type, Block, Pay Scale, Eligibility) supports CRUD.  
- All listed reports (Pending Meter Reading, House Status, Bill Report variants, Migration Report) are available and filterable as per UI.

---

## 12. Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-02-25 | — | Initial BRD for Estate Module |

---

*This BRD is derived from the existing Sargam 2.0 Estate module implementation (routes, controllers, models, and menus) and is intended for business alignment, onboarding, and future change control.*
