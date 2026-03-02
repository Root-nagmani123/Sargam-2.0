<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index() { return redirect()->route('admin.dashboard'); }
    public function create() { return redirect()->route('admin.dashboard'); }
    public function store(Request $request) { return redirect()->back(); }
}
