<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Stream;
use Illuminate\Http\Request;


class StreamController extends Controller
{
    public function index()
    {
        $streams = Stream::all();
        return view('admin.stream.index', compact('streams'));
    }

    public function create()
    {
        return view('admin.stream.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'stream_name.*' => 'required|string|max:100',
    ], [
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