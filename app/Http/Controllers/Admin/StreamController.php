<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Stream;
use Illuminate\Http\Request;


class StreamController extends Controller
{
    /** Whitelist for the footer's rows-per-page select (docs/new-design-index-page.md §4B). */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 10;
        }

        // withQueryString(), or the pager links drop per_page and page 2 snaps
        // back to 10 rows.
        $streams = Stream::paginate($perPage)->withQueryString();

        return view('admin.stream.index', [
            'streams' => $streams,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    public function create()
    {
        return view('admin.stream.create');
    }

    public function store(Request $request)
{
    // 'stream_name' => required|array as well as the per-row rules: the modal
    // drops blank extra cards before posting, so a form with nothing filled in
    // would otherwise arrive with no stream_name key at all and foreach on null.
    $request->validate([
        'stream_name' => 'required|array|min:1',
        'stream_name.*' => 'required|string|max:100',
    ], [
        'stream_name.required' => 'Enter at least one stream name.',
        'stream_name.*.required' => 'The stream name field is required.',
        'stream_name.*.max' => 'Stream name may not be greater than 100 characters.',
    ]);

    foreach ($request->stream_name as $name) {
        Stream::create([
            'stream_name' => $name
        ]);
    }

    return redirect()->route('stream.index')->with('success', 'Streams added successfully!');
}

    public function edit($id)
    {
        $stream = Stream::findOrFail($id);
        return view('admin.stream.edit', compact('stream'));
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'stream_name' => 'required|string|max:100',
    ]);

    $stream = Stream::findOrFail($id);
    $stream->stream_name = $request->stream_name;
    $stream->save();

    return redirect()->route('stream.index')->with('success', 'Stream updated successfully.');
}

    public function destroy($id)
    {
        Stream::where('pk', $id)->delete();
        return redirect()->route('stream.index')->with('success', 'Stream deleted successfully!');
    }
}