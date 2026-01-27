# Security Module - Troubleshooting Guide

## ✅ Module Successfully Fixed!

### What Was Done

1. **Added Mini Icon** - Added shield icon (🛡️) to the sidebar mini-nav section
2. **Cleared All Caches** - Removed compiled views and cached routes
3. **Verified Migrations** - All 7 database tables created successfully
4. **Verified Routes** - All 33 security routes are registered

### Current Status

- ✅ **Database**: 7 tables migrated
- ✅ **Routes**: 33 routes registered
- ✅ **Views**: Component files created
- ✅ **Sidebar**: Menu icon and component added
- ✅ **Controllers**: 5 controllers implemented
- ✅ **Models**: 7 models created

---

## 🔧 If Module Still Not Visible

### Quick Fix (Run this script):
```bash
cd /Users/vivekkumar/Desktop/codebase/Sargam-2.0
./setup_security_module.sh
```

### Manual Steps:

#### 1. Clear Browser Cache
```
Hard Refresh: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)
```

#### 2. Clear Laravel Caches
```bash
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan optimize:clear
```

#### 3. Verify Routes Loaded
```bash
docker-compose exec app php artisan route:list | grep security
```
Should show 33 routes

#### 4. Check Permissions
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app php artisan storage:link
```

#### 5. Restart Docker Containers
```bash
docker-compose restart
```

---

## 📍 How to Access

### In Sidebar
Look for the **shield icon (🛡️)** in the left mini navigation bar, below the Mess Management icon.

Click it to expand the **Security Management** section with:
- **Vehicle Management** (6 sub-menus)
- **Visitor Pass** (2 sub-menus)

### Direct URLs
After login, you can access:

**Vehicle Management:**
- Vehicle Types: `http://localhost:8080/security/vehicle-type`
- Pass Config: `http://localhost:8080/security/vehicle-pass-config`
- My Applications: `http://localhost:8080/security/vehicle-pass`
- Apply for Pass: `http://localhost:8080/security/vehicle-pass/create`
- Pending Approvals: `http://localhost:8080/security/vehicle-pass-approval`
- All Applications: `http://localhost:8080/security/vehicle-pass-approval/all`

**Visitor Management:**
- All Visitors: `http://localhost:8080/security/visitor-pass`
- Register Visitor: `http://localhost:8080/security/visitor-pass/create`

---

## 🔍 Verification Checklist

- [ ] Shield icon visible in left sidebar (mini-nav)
- [ ] "Security Management" menu expands when clicking shield icon
- [ ] Can access `/security/vehicle-type` without errors
- [ ] Can access `/security/visitor-pass` without errors
- [ ] Database tables exist (check phpMyAdmin or database client)
- [ ] No 404 errors when clicking menu items

---

## 🐛 Common Issues

### Issue 1: Sidebar Not Showing
**Solution:** 
- Clear browser cache (Hard refresh)
- Clear Laravel view cache
- Check if you're logged in with correct permissions

### Issue 2: Routes Return 404
**Solution:**
```bash
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan optimize
```

### Issue 3: Database Errors
**Solution:**
```bash
docker-compose exec app php artisan migrate:status
# If tables missing:
docker-compose exec app php artisan migrate --force
```

### Issue 4: Blank Page or White Screen
**Solution:**
Check logs:
```bash
docker-compose logs app
# or
docker-compose exec app tail -f storage/logs/laravel.log
```

### Issue 5: Permission Denied Errors
**Solution:**
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

---

## 📊 Database Tables

Verify these tables exist:
```sql
-- Run in phpMyAdmin or MySQL client
SHOW TABLES LIKE 'sec_%';
SHOW TABLES LIKE 'vehicle_%';
```

Expected tables:
1. `sec_vehicle_type`
2. `sec_vehcl_pass_config`
3. `vehicle_pass_tw_apply`
4. `vehicle_pass_tw_apply_approval`
5. `sec_visitor_card_generated`
6. `sec_visitor_names`
7. `security_employee_type`

---

## ✨ Testing the Module

### Test Vehicle Pass Flow:
1. Login as employee
2. Click shield icon in sidebar
3. Navigate to: Vehicle Management → Apply for Pass
4. Fill form and submit
5. Login as admin
6. Navigate to: Vehicle Management → Pending Approvals
7. Approve/Reject the application

### Test Visitor Pass Flow:
1. Login as security staff
2. Click shield icon in sidebar
3. Navigate to: Visitor Pass → Register Visitor
4. Add visitor details
5. Submit
6. View in "All Visitors"

---

## 📞 Need Help?

If the module still doesn't show after trying all steps:

1. Check Docker logs: `docker-compose logs app`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify user permissions/roles
4. Try accessing direct URL: `http://localhost:8080/security/vehicle-type`

---

## 🎉 Success Indicators

You'll know it's working when:
- ✅ Shield icon appears in sidebar mini-nav
- ✅ Menu expands with "Security Management" header
- ✅ Sub-menus are clickable and load pages
- ✅ No console errors in browser
- ✅ Forms load and submit successfully

**The module is now fully integrated and ready to use!**
