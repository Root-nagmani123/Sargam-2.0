<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

function view_file_link($path)
{
    return $path ? asset('storage/' . $path) : null;
}

function format_date($date, $format = 'd-m-Y')
{
    return \Carbon\Carbon::parse($date)->format($format);
}

function createDirectory($path)
{
    $directory = public_path('storage/' . $path);
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
}

function safeDecrypt($value, $default = null)
{
    try {
        return decrypt($value);
    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        return $default;
    }
}
function hasRole($role)
{
    $user = Auth::user();
    if (!$user) return false;

    // Step 1: Check session roles first (Student static role bhi yahi me milega)
    $sessionRoles = Session::get('user_roles', []);
    if (in_array($role, $sessionRoles)) {
        return true;
    }

    // Step 2: Check database roles + cache
    $roles = Cache::remember('user_roles_' . $user->pk, 10, function () use ($user) {
        return $user->roles()->pluck('user_role_name')->toArray();
    });

    return in_array($role, $roles);
}
function get_Role_by_course()
{
    $user = Auth::user();
    
    // Return empty array if user is not authenticated
    if (!$user) {
        return [];
    }
    
    $sessionRoles = Session::get('user_roles', []);
    if (empty($sessionRoles)) {
        return [];
    }
    $cacheKey = 'role_by_course_' . $user->user_id;
    $role_course = Cache::remember($cacheKey, 600, function () use ($user, $sessionRoles) {
        return DB::table('course_master as cm')
            ->join('user_role_master as urm', 'cm.user_role_master_pk', '=', 'urm.pk')
            ->whereIn('urm.user_role_name', $sessionRoles)
            ->pluck('cm.pk')
            ->toArray();
    });
    return $role_course;
}

function service_find()
{
    $user = Auth::user();

    // Return null if user is not authenticated
    if (!$user) {
        return null;
    }

    $cacheKey = 'service_name_' . $user->user_id;

    $service_name = Cache::remember($cacheKey, 600, function () use ($user) {
        return DB::table('student_master')
            ->join('service_master', 'student_master.service_master_pk', '=', 'service_master.pk')
            ->where('student_master.pk', $user->user_id)
            ->value('service_master.service_short_name');
    });
    return $service_name;
}
function employee_designation_search()
{
    $user = Auth::user();
    
    // Return null if user is not authenticated
    if (!$user) {
        return null;
    }
    
    // print_r($user);
    $cacheKey = 'employee_designation_' . $user->user_id;
    $designation = Cache::remember($cacheKey, 600, function () use ($user) {
        return DB::table('employee_master')
            ->join('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
            ->where('employee_master.pk', $user->user_id)
            ->value('designation_master.designation_name', 'designation_master.*');
    });
    return $designation;
}
function get_profile_pic()
{
    $user = Auth::user();
    
    // Return default image if user is not authenticated
    if (!$user) {
        return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
    }
    
    $cacheKey = 'profile_pic_' . $user->user_id;
    if ($user->user_category == 'S') {

        $profile_pic = Cache::remember($cacheKey, 600, function () use ($user) {

            $data = DB::table('student_master')
                ->where('pk', $user->user_id)
                ->value('photo_path');

            if ($data == null) {
                return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
            } else {
                return asset('storage/form-uploads/photo/' . $data);
            }
        });

        return $profile_pic;
    } else {
        $profile_pic = Cache::remember($cacheKey, 600, function () use ($user) {
            $data = DB::table('employee_master')
                ->where('employee_master.pk', $user->user_id)
                ->value('profile_picture');
            if ($data == null) {
                return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
            } else {
                // return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
                return asset('storage/' . $data);
            }
        });
        return $profile_pic;
    }
}
if (!function_exists('get_notice_notification_by_role')) {
    function get_notice_notification_by_role()
    {
        $user = Auth::user();
        
        // Return empty collection if user is not authenticated
        if (!$user) {
            return collect([]);
        }
        
        $sessionRoles = Session::get('user_roles', []);

        $roleStaffFaculty = ['Internal Faculty', 'Guest Faculty', 'Training', 'Staff'];
        $roleStudent      = ['Student-OT'];

        $isStaffFaculty = !empty(array_intersect($roleStaffFaculty, $sessionRoles));
        $isStudent      = !empty(array_intersect($roleStudent, $sessionRoles));


        $commonNotices = DB::table('notices_notification')
            ->where('target_audience', 'All')
            ->where('active_inactive', 1)
            ->where('expiry_date', '>=', date('Y-m-d'))
            ->orderBy('display_date', 'desc')
            ->get();

        // 🔥 Staff/Faculty Notices
        if ($isStaffFaculty) {

            $data = DB::table('notices_notification')
                ->where('target_audience', 'like', '%Staff/Faculty%')
                ->where('active_inactive', 1)
                ->where('expiry_date', '>=', date('Y-m-d'))
                ->orderBy('display_date', 'desc')
                ->get();


            return $commonNotices->merge($data);
        }

        // 🔥 Student OT Notices
        if ($isStudent) {
            $roleNotices =  DB::table('notices_notification')
                ->join('student_master_course__map as smcm', 'notices_notification.course_master_pk', '=', 'smcm.course_master_pk')
                ->where('target_audience', 'like', '%Office trainee%')
                ->where('notices_notification.active_inactive', 1)
                ->where('smcm.student_master_pk', $user->user_id)
                ->where('expiry_date', '>=', date('Y-m-d'))
                ->orderBy('display_date', 'desc')
                ->get();


            return $commonNotices->merge($roleNotices);
        }

        // Roles not matching → return only "All"
        return $commonNotices;
    }
}

/**
 * Get NotificationService instance
 * 
 * @return \App\Services\NotificationService
 */
if (!function_exists('notification')) {
    function notification()
    {
        return app(\App\Services\NotificationService::class);
    }
}

    function getcoursevalue()
    {
        $user_role_master_pk = Session::get('user_role_master_pk');
        $courseval = DB::table('course_master')
            ->select('course_name','pk')
            ->where('user_role_master_pk', $user_role_master_pk)
            ->get();

        return $courseval;
    }
