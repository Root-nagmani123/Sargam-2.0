<?php
use Illuminate\Support\Facades\Cache;
function view_file_link($path) {
    return $path ? asset('storage/' . $path) : null;
}

function format_date($date, $format = 'd-m-Y') {
    return \Carbon\Carbon::parse($date)->format($format);
}

function createDirectory($path)
{
    $directory = public_path('storage/'. $path);
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
    $roles = Cache::remember('user_roles_'.$user->pk, 10, function () use ($user) {
        return $user->roles()->pluck('user_role_name')->toArray();
    });

    return in_array($role, $roles);
}
function service_find()
{
    $user = Auth::user();

$cacheKey = 'service_name_'.$user->user_id;

$service_name = Cache::remember($cacheKey, 600, function () use ($user) {
    return DB::table('student_master')
        ->join('service_master', 'student_master.service_master_pk', '=', 'service_master.pk')
        ->where('student_master.pk', $user->user_id)
        ->value('service_master.service_short_name');
});
return $service_name; 
}
function employee_designation_search(){
    $user = Auth::user();
    // print_r($user);
$cacheKey = 'employee_designation_'.$user->user_id;
$designation = Cache::remember($cacheKey, 600, function () use ($user) {
    return DB::table('employee_master')
        ->join('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
        ->where('employee_master.pk', $user->user_id)
        ->value('designation_master.designation_name','designation_master.*');
});
return $designation;

    
}
function get_profile_pic(){
    $user = Auth::user();
    $cacheKey = 'profile_pic_'.$user->user_id;
    if($user->user_category == 'S'){
        return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
    }else{
$profile_pic = Cache::remember($cacheKey, 600, function () use ($user) {
        $data = DB::table('employee_master')
            ->where('employee_master.pk', $user->user_id)
            ->value('profile_picture');
            if($data == null ){
             return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
            }else{
                // return 'https://images.unsplash.com/photo-1650110002977-3ee8cc5eac91?q=80&w=737&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D';
                return asset('storage/'.$data);
            }
    });
    return $profile_pic;
}

}


