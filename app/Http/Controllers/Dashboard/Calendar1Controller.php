<?php
namespace App\Http\Controllers;


use Illuminate\Http\Request;


class CalendarController extends Controller
{
public function index(Request $request)
{
$year = $request->get('year', now()->year);
$month = $request->get('month', now()->month);


// sample events - in real app query DB
$events = [
"{$year}-{$month}-21" => ['label' => 'Selected'],
"{$year}-{$month}-30" => ['label' => 'Important']
];


return view('calendar-example', compact('year','month','events'));
}
}