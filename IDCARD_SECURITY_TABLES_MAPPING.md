# ID Card Management – Security Tables Mapping

Backend-only mapping of ID Card Management sidebar links to security tables. **UI unchanged.**

## Sidebar → Route → Security Table(s)

| Sidebar Link           | Route Name / Controller                         | Security Table(s)                          |
|------------------------|--------------------------------------------------|--------------------------------------------|
| **ID Card List**       | `admin.employee_idcard.index`                    | `security_parm_id_apply`                   |
| **Generate New ID Card** | `admin.employee_idcard.create` / `store`       | `security_parm_id_apply`                   |
| **Request Family ID Card** | `admin.family_idcard.index` / `create` / `store` | `security_family_id_apply`             |
| **Vehicle Pass Request** | `admin.security.vehicle_pass.index`            | `vehicle_pass_TW_apply`, `vehicle_pass_FW_apply` (already in use) |
| **Approval I**         | `admin.security.employee_idcard_approval.approval1` | `security_parm_id_apply` + `security_parm_id_apply_approval` (status=1) |
| **Approval II**        | `admin.security.employee_idcard_approval.approval2` | `security_parm_id_apply` + `security_parm_id_apply_approval` (status=2) |
| **All ID Card Requests** | `admin.security.employee_idcard_approval.all`  | `security_parm_id_apply` + `security_parm_id_apply_approval` |

## Backend Changes (no UI change)

1. **Models**
   - `SecurityParmIdApply` → table `security_parm_id_apply`
   - `SecurityParmIdApplyApproval` → table `security_parm_id_apply_approval`
   - `SecurityFamilyIdApply` → table `security_family_id_apply`
   - `SecurityFamilyIdApplyApproval` → table `security_family_id_apply_approval`
   - `IDCardRequestApprovarMasterNew` → table `IDCard_request_approvar_master_new`

2. **Mapper**
   - `App\Support\IdCardSecurityMapper` – maps security rows to view-compatible DTOs (same property names as before).

3. **Controllers**
   - **EmployeeIDCardRequestController** – uses `SecurityParmIdApply`; list/create/show/edit/update/destroy/export read/write `security_parm_id_apply` (and approval rows where needed).
   - **FamilyIDCardRequestController** – uses `SecurityFamilyIdApply`; list/create/show/edit/update/destroy/export read/write `security_family_id_apply`.
   - **EmployeeIDCardApprovalController** – uses `SecurityParmIdApply` + `SecurityParmIdApplyApproval`; Approval I/II and “All” use `security_parm_id_apply_approval` (status 1 = Approval I, 2 = Approval II, 3 = Rejected).
   - **VehiclePassController** – already uses `VehiclePassTWApply` (`vehicle_pass_TW_apply`); no change.

4. **Routes**
   - Employee ID Card: `show/{id}`, `edit/{id}`, `update/{id}`, `destroy/{id}`, `amendDuplicationExtension/{id}` use numeric `id` (maps to `security_parm_id_apply.pk`).
   - Family ID Card: `show/{id}`, `edit/{id}`, `update/{id}`, `destroy/{id}` use `id` as `security_family_id_apply.fml_id_apply` (e.g. FMD00003).

## Status / Approval Mapping

- **security_parm_id_apply.id_status**: 1 = Pending, 2 = Approved, 3 = Rejected.
- **security_parm_id_apply_approval.status**: 1 = Approval I done, 2 = Approval II done, 3 = Rejected.
- **security_family_id_apply.id_status**: 1 = Pending, 2 = Approved, 3 = Rejected.

## Files Touched

- `app/Models/SecurityParmIdApply.php` (new)
- `app/Models/SecurityParmIdApplyApproval.php` (new)
- `app/Models/SecurityFamilyIdApply.php` (new)
- `app/Models/SecurityFamilyIdApplyApproval.php` (new)
- `app/Models/IDCardRequestApprovarMasterNew.php` (new)
- `app/Support/IdCardSecurityMapper.php` (new)
- `app/Http/Controllers/Admin/EmployeeIDCardRequestController.php`
- `app/Http/Controllers/Admin/FamilyIDCardRequestController.php`
- `app/Http/Controllers/Admin/Security/EmployeeIDCardApprovalController.php`
- `app/Exports/EmployeeIDCardExport.php`
- `app/Exports/FamilyIDCardExport.php`
- `routes/web.php` (employee + family routes use `{id}`)

UI (Blade views, sidebar, forms) is unchanged; only backend functions and table mapping were updated.
