<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Stream;
use App\DataTables\StreamMasterDataTable;
use Illuminate\Http\Request;


class StreamController extends Controller
{
    // Server-side grid: search, sorting, page size and paging (StreamMasterDataTable).
    public function index(StreamMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.stream.index');
    }

    public function create()
    {
        return view('admin.stream.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'stream_name' => 'required|array|min:1',
        'stream_name.*' => 'required|string|max:100',
    ], [
        'stream_name.*.required' => 'The stream name field is required.',
        'stream_name.*.max' => 'Stream name may not be greater than 100 characters.',
    ]);

    // active_inactive is NOT NULL with no default, so it must be set or the
    // insert fails (500). The form has no status field: new streams start
    // Active. One transaction, so a failure can't leave half the batch saved.
    \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
        foreach ((array) $request->stream_name as $name) {
            $stream = new Stream();
            $stream->stream_name = $name;
            $stream->active_inactive = 1;
            $stream->save();
        }
    });

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
        // Same rule as the grid's disabled Delete (StreamMasterDataTable): an
        // active stream is deactivated first, so a hand-made request can't skip it.
        if (Stream::where('pk', $id)->where('active_inactive', 1)->exists()) {
            return redirect()->route('stream.index')->with('error', 'Cannot delete an active stream. Deactivate it first.');
        }

        Stream::where('pk', $id)->delete();
        return redirect()->route('stream.index')->with('success', 'Stream deleted successfully!');
    }
}