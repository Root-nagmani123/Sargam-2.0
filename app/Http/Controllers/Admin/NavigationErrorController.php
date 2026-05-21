<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NavigationErrorController extends Controller
{
    public function show(Request $request)
    {
        $reason = $request->query('reason', 'not_found');
        $allowed = ['missing_path', 'invalid_route', 'not_found'];
        if (!in_array($reason, $allowed, true)) {
            $reason = 'not_found';
        }

        return response()->view('errors.configured-path', [
            'reason' => $reason,
            'menu_id' => $request->query('menu_id'),
        ], $reason === 'not_found' ? 404 : 422);
    }
}
