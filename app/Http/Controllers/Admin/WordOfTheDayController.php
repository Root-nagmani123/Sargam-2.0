<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWordOfTheDayRequest;
use App\Http\Requests\Admin\UpdateWordOfTheDayRequest;
use App\Models\WordOfTheDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WordOfTheDayController extends Controller
{
    protected function wantsFormFragment(Request $request): bool
    {
        return $request->ajax()
            || $request->wantsJson()
            || $request->boolean('fragment');
    }

    public function index()
    {
        $this->authorize('viewAny', WordOfTheDay::class);

        $words = WordOfTheDay::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $todaysWord = WordOfTheDay::wordForToday();
        $rotationPreview = WordOfTheDay::previewRotation(7);

        return view('admin.word-of-day.index', compact('words', 'todaysWord', 'rotationPreview'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', WordOfTheDay::class);

        if ($this->wantsFormFragment($request)) {
            return view('admin.word-of-day._form');
        }

        return redirect()->route('admin.word-of-day.index');
    }

    public function store(StoreWordOfTheDayRequest $request)
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['active_inactive'] = $request->boolean('active_inactive');
        $data['scheduled_date'] = $data['scheduled_date'] ?? null;
        $pk = Auth::user()?->pk;
        $data['created_by_pk'] = $pk;
        $data['updated_by_pk'] = $pk;

        WordOfTheDay::create($data);

        return redirect()->route('admin.word-of-day.index')
            ->with('success', 'Word added successfully.');
    }

    public function edit(Request $request, WordOfTheDay $word)
    {
        $this->authorize('update', $word);

        if ($this->wantsFormFragment($request)) {
            return view('admin.word-of-day._form', compact('word'));
        }

        return redirect()->route('admin.word-of-day.index');
    }

    public function update(UpdateWordOfTheDayRequest $request, WordOfTheDay $word)
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['active_inactive'] = $request->boolean('active_inactive');
        $data['scheduled_date'] = $data['scheduled_date'] ?? null;
        $data['updated_by_pk'] = Auth::user()?->pk;

        $word->update($data);

        return redirect()->route('admin.word-of-day.index')
            ->with('success', 'Word updated successfully.');
    }

    public function destroy(WordOfTheDay $word)
    {
        $this->authorize('delete', $word);

        $word->delete();

        return redirect()->route('admin.word-of-day.index')
            ->with('success', 'Word removed.');
    }

    public function reorder(Request $request)
    {
        $this->authorize('viewAny', WordOfTheDay::class);

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'min:1'],
        ]);

        $ids = $validated['order'];
        $existing = WordOfTheDay::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count(array_unique($ids)) !== count($ids) || count($existing) !== count($ids)) {
            abort(422, 'Invalid reorder payload.');
        }

        foreach (array_values($ids) as $i => $id) {
            WordOfTheDay::query()->where('id', $id)->update([
                'sort_order' => $i + 1,
                'updated_by_pk' => Auth::user()?->pk,
            ]);
        }

        WordOfTheDay::forgetTodayCache();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.word-of-day.index')
            ->with('success', 'Order saved.');
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', WordOfTheDay::class);

        $filename = 'word-of-the-day-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['hindi_text', 'english_text', 'sort_order', 'active_inactive', 'scheduled_date']);

            WordOfTheDay::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->each(function (WordOfTheDay $row) use ($out) {
                    fputcsv($out, [
                        $row->hindi_text,
                        $row->english_text,
                        $row->sort_order,
                        $row->active_inactive ? '1' : '0',
                        $row->scheduled_date?->format('Y-m-d') ?? '',
                    ]);
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        $this->authorize('create', WordOfTheDay::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('file')->getRealPath();
        if ($path === false) {
            return redirect()->route('admin.word-of-day.index')
                ->with('error', 'Could not read the uploaded file.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return redirect()->route('admin.word-of-day.index')
                ->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        $pk = Auth::user()?->pk;
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $hindi = trim((string) ($row[0] ?? ''));
            $english = trim((string) ($row[1] ?? ''));
            if ($hindi === '' || $english === '') {
                continue;
            }

            $sort = isset($row[2]) && $row[2] !== '' ? (int) $row[2] : 0;
            $active = ! isset($row[3]) || ! in_array(strtolower(trim((string) $row[3])), ['0', 'no', 'false'], true);
            $scheduled = null;
            if (isset($row[4]) && trim((string) $row[4]) !== '') {
                try {
                    $scheduled = \Carbon\Carbon::parse(trim((string) $row[4]))->toDateString();
                } catch (\Throwable $e) {
                    $scheduled = null;
                }
            }

            $w = WordOfTheDay::query()->firstOrNew([
                'hindi_text' => $hindi,
                'english_text' => $english,
            ]);
            $w->sort_order = $sort;
            $w->active_inactive = $active;
            $w->scheduled_date = $scheduled;
            $w->updated_by_pk = $pk;
            if (! $w->exists) {
                $w->created_by_pk = $pk;
            }
            $w->save();
            $imported++;
        }

        fclose($handle);
        WordOfTheDay::forgetTodayCache();

        return redirect()->route('admin.word-of-day.index')
            ->with('success', $imported.' row(s) imported or updated.');
    }
}
