# Security Management Module - Quick Setup Guide

## 🚀 Quick Start

### Step 1: Run Migrations
```bash
cd /Users/vivekkumar/Desktop/codebase/Sargam-2.0
php artisan migrate
```

### Step 2: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### Step 3: Set Permissions
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

---

## 📋 What's Been Implemented

### ✅ Migrations Created (7 files)
1. `sec_vehicle_type` - Vehicle types master
2. `sec_vehcl_pass_config` - Pass configuration
3. `vehicle_pass_tw_apply` - Vehicle pass applications
4. `vehicle_pass_tw_apply_approval` - Approval workflow
5. `sec_visitor_card_generated` - Visitor passes
6. `sec_visitor_names` - Multiple visitors support
7. `security_employee_type` - Employee classifications

### ✅ Models Created (7 files)
- `SecVehicleType`
- `SecVehiclePassConfig`
- `VehiclePassTWApply`
- `VehiclePassTWApplyApproval`
- `SecVisitorCardGenerated`
- `SecVisitorName`
- `SecurityEmployeeType`

### ✅ Controllers Created (5 files)
- `VehicleTypeController` - Manage vehicle types
- `VehiclePassConfigController` - Configure passes
- `VehiclePassController` - User applications
- `VehiclePassApprovalController` - Admin approvals
- `VisitorPassController` - Visitor management

### ✅ Views Created (5+ files)
- Vehicle Type index
- Vehicle Pass index & create
- Vehicle Pass Approval index
- Visitor Pass index

### ✅ Routes Added
All routes added to `routes/web.php` under `/security/*` prefix

### ✅ Sidebar Menu Added
New menu section "Security Management" added after Mess Management in sidebar

---

## 🎯 Access Points

### For Users (Employees)
1. **Apply for Vehicle Pass**: `/security/vehicle-pass/create`
2. **My Applications**: `/security/vehicle-pass`
3. **Vehicle Types**: `/security/vehicle-type`

### For Security Admin
1. **Pending Approvals**: `/security/vehicle-pass-approval`
2. **All Applications**: `/security/vehicle-pass-approval/all`
3. **Visitor Management**: `/security/visitor-pass`
4. **Register Visitor**: `/security/visitor-pass/create`

### For Super Admin
1. **Vehicle Type Master**: `/security/vehicle-type`
2. **Pass Configuration**: `/security/vehicle-pass-config`

---

## 📱 Features

### Vehicle Pass System
- ✅ Apply for vehicle pass with document upload
- ✅ Auto-generated request IDs
- ✅ Multi-level approval workflow
- ✅ Government vs Private vehicle tracking
- ✅ Validity period management
- ✅ Status tracking (Pending/Approved/Rejected)
- ✅ Card status (Not Forwarded/Forwarded/Card Ready)

### Visitor Pass System
- ✅ Multiple visitors per pass
- ✅ Entry-exit time tracking
- ✅ Host employee linkage
- ✅ Company and purpose tracking
- ✅ Vehicle number recording
- ✅ Identity document upload
- ✅ Flexible validity period

---

## 🔧 Configuration

### Default Settings
- Max file upload size: 2MB
- Allowed file types: PDF, JPG, JPEG, PNG
- Auto-generated pass numbers
- Date format: dd-mm-YYYY

### Storage Locations
- Vehicle documents: `storage/app/public/vehicle_documents/`
- Visitor documents: `storage/app/public/visitor_documents/`

---

## 🧪 Testing

### Test Vehicle Pass Flow
1. Login as employee
2. Navigate to Security Management → Vehicle Management → Apply for Pass
3. Fill form and submit
4. Login as security admin
5. Navigate to Pending Approvals
6. Approve/Reject the application

### Test Visitor Pass Flow
1. Login as security personnel
2. Navigate to Security Management → Visitor Pass → Register Visitor
3. Add visitor details (can add multiple names)
4. Save visitor pass
5. Later, mark visitor as checked out

---

## 📊 Database Tables Summary

| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| sec_vehicle_type | Vehicle types | vehicle_type, description |
| sec_vehcl_pass_config | Pass config | charges, start_counter |
| vehicle_pass_tw_apply | Applications | vehicle_no, validity dates |
| vehicle_pass_tw_apply_approval | Approvals | status, remarks |
| sec_visitor_card_generated | Visitor passes | pass_number, in_time, out_time |
| sec_visitor_names | Visitor names | visitor_name |
| security_employee_type | Employee types | employee_type_name |

---

## ⚠️ Important Notes

1. **Existing Modules**: All existing modules remain unaffected
2. **Menu Position**: Security menu appears after Mess Management
3. **Employee Integration**: Uses existing employee_master table
4. **Authentication**: All routes require authentication
5. **File Upload**: Ensure storage directory has proper permissions

---

## 🐛 Troubleshooting

### Routes not working?
```bash
php artisan route:clear
php artisan optimize
```

### Sidebar not showing new menu?
```bash
php artisan view:clear
php artisan cache:clear
```

### File upload failing?
```bash
php artisan storage:link
chmod -R 775 storage
```

### Migration errors?
Check that `employee_master` table exists before running migrations.

---

## 📞 Quick Reference

### Route Names
- Vehicle Types: `admin.security.vehicle_type.*`
- Vehicle Pass Config: `admin.security.vehicle_pass_config.*`
- Vehicle Pass: `admin.security.vehicle_pass.*`
- Vehicle Approvals: `admin.security.vehicle_pass_approval.*`
- Visitor Pass: `admin.security.visitor_pass.*`

### Model Relationships
```php
// Vehicle Pass
$pass->vehicleType; // Get vehicle type
$pass->employee; // Get employee
$pass->approval; // Get approval record

// Visitor Pass
$visitor->employee; // Get host employee
$visitor->visitorNames; // Get all visitor names
```

---

## ✨ Ready to Use!

The Security Management module is now fully integrated and ready for use. Navigate to the sidebar menu to access all features.

**Documentation**: See `SECURITY_MODULE_IMPLEMENTATION.md` for detailed information.
