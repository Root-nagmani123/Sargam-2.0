<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\WordOfDayMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WordOfDayMasterController extends Controller
{
    public function index()
    {
        $words = WordOfDayMaster::orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('admin.master.word_of_day_master.index', compact('words'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hindi_text' => 'required|string|max:255',
            'english_text' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active_inactive' => 'required|in:0,1',
            'scheduled_date' => 'nullable|date',
        ]);

        WordOfDayMaster::create([
            'hindi_text' => $data['hindi_text'],
            'english_text' => $data['english_text'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active_inactive' => (int) $data['active_inactive'],
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'created_by_pk' => Auth::id(),
            'updated_by_pk' => Auth::id(),
        ]);

        return back()->with('success', 'Word of the Day added successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'hindi_text' => 'required|string|max:255',
            'english_text' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active_inactive' => 'required|in:0,1',
            'scheduled_date' => 'nullable|date',
        ]);

        $word = WordOfDayMaster::findOrFail($id);
        $word->update([
            'hindi_text' => $data['hindi_text'],
            'english_text' => $data['english_text'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active_inactive' => (int) $data['active_inactive'],
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'updated_by_pk' => Auth::id(),
        ]);

        return back()->with('success', 'Word of the Day updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        WordOfDayMaster::findOrFail($id)->delete();

        return back()->with('success', 'Word of the Day deleted successfully.');
    }
}

