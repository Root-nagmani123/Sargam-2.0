<?php
// app/Http/Controllers/Admin/MemoNoticeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DashboardController extends Controller
{
function active_course(Request $request)
{
   $active_courses = DB::table('course_master')
        ->where('active_inactive', 1)
        ->where('start_year', '<', now())
        ->where('end_date', '>=', now())
        ->get();
    return view('admin.dashboard.active_course', compact('active_courses'));
    
}
function incoming_course(Request $request)
{
   $incoming_courses = DB::table('course_master')
        ->where('active_inactive', 1)
        ->where('start_year', '>', now())
        ->get();
    return view('admin.dashboard.incoming_course', compact('incoming_courses'));

}
function guest_faculty()
{
   $guest_faculty = DB::table('faculty_master')->where('faculty_type', 2)->where('active_inactive', 1)->get();
    return view('admin.dashboard.guest_faculty', compact('guest_faculty'));
    
}
function inhouse_faculty(){
   $inhouse_faculty  = DB::table('faculty_master')->where('faculty_type', 1)->where('active_inactive', 1)->get();
    return view('admin.dashboard.inhouse_faculty', compact('inhouse_faculty'));
    
}

}