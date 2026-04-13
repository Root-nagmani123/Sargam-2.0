<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordOfTheDay;
use Illuminate\Http\Request;

class WordOfTheDayController extends Controller
{
    public function index()
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        $words = WordOfTheDay::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $todaysWord = WordOfTheDay::wordForToday();

        return view('admin.word-of-day.index', compact('words', 'todaysWord'));
    }

    public function create(Request $request)
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        if ($request->ajax()) {
            return view('admin.word-of-day._form');
        }

        return redirect()->route('admin.word-of-day.index');
    }

    public function store(Request $request)
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        $data = $request->validate([
            'hindi_text' => ['required', 'string', 'max:255'],
            'english_text' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['active_inactive'] = $request->boolean('active_inactive');

        WordOfTheDay::create($data);

        return redirect()->route('admin.word-of-day.index')
            ->with('success', 'Word added successfully.');
    }

    public function edit(Request $request, int $id)
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        $word = WordOfTheDay::findOrFail($id);

        if ($request->ajax()) {
            return view('admin.word-of-day._form', compact('word'));
        }

        return redirect()->route('admin.word-of-day.index');
    }

    public function update(Request $request, int $id)
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        $word = WordOfTheDay::findOrFail($id);

        $data = $request->validate([
            'hindi_text' => ['required', 'string', 'max:255'],
            'english_text' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['active_inactive'] = $request->boolean('active_inactive');

        $word->update($data);

        return redirect()->route('admin.word-of-day.index')
            ->with('success', 'Word updated successfully.');
    }

    public function destroy(int $id)
    {
        abort_unless(hasRole('Admin') || hasRole('Super Admin'), 403);

        WordOfTheDay::where('id', $id)->delete();

        return redirect()->route('admin.word-of-day.index')
            ->with('success', 'Word removed.');
    }
}
