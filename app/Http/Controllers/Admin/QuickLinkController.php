<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickLink;
use Illuminate\Http\Request;

class QuickLinkController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', 'url'],
            'target_blank' => ['required', 'boolean'],
        ]);

        $url = trim($data['url']);
        $targetBlank = (bool) $data['target_blank'];

        $position = (int) (QuickLink::query()->max('position') ?? 0) + 1;

        QuickLink::create([
            'label' => $data['label'],
            'url' => $url,
            'target_blank' => $targetBlank,
            'position' => $position,
            'active_inactive' => 1,
        ]);

        return redirect()->back()->with('success', 'Quick link added successfully.');
    }

    public function destroy($id)
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        QuickLink::query()->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Quick link deleted successfully.');
    }
}

