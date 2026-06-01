<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Stream;
use Illuminate\Http\Request;


class StreamController extends Controller
{
    public function index()
    {
        $streams = Stream::paginate(10);
        return view('admin.stream.index', compact('streams'));
    }

    public function create(Request $request)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.stream._form');
        }

        return redirect()->route('stream.index', ['open_stm_modal' => 'add']);
    }

    public function store(Request $request)
    {
        if (is_array($request->stream_name)) {
            $request->validate([
                'stream_name.*' => 'required|string|max:100',
            ], [
                'stream_name.*.required' => 'The stream name field is required.',
                'stream_name.*.max' => 'Stream name may not be greater than 100 characters.',
            ]);

            foreach ($request->stream_name as $name) {
                Stream::create([
                    'stream_name' => $name,
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Streams added successfully!',
                ]);
            }

            return redirect()->route('stream.index')->with('success', 'Streams added successfully!');
        }

        $request->validate([
            'stream_name' => 'required|string|max:100',
            'status' => 'required|in:0,1',
        ], [
            'stream_name.required' => 'The stream name field is required.',
            'stream_name.max' => 'Stream name may not be greater than 100 characters.',
            'status.required' => 'Status is required.',
        ]);

        Stream::create([
            'stream_name' => $request->stream_name,
            'status' => (int) $request->status,
        ]);

        $message = 'Stream added successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('stream.index')->with('success', $message);
    }

    public function edit(Request $request, $id)
    {
        $stream = Stream::findOrFail($id);

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.stream._form', compact('stream'));
        }

        return redirect()->route('stream.index', [
            'open_stm_modal' => 'edit',
            'stm_id' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'stream_name' => 'required|string|max:100',
            'status' => 'required|in:0,1',
        ], [
            'stream_name.required' => 'The stream name field is required.',
            'status.required' => 'Status is required.',
        ]);

        $stream = Stream::findOrFail($id);
        $stream->stream_name = $request->stream_name;
        $stream->status = (int) $request->status;
        $stream->save();

        $message = 'Stream updated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route('stream.index')->with('success', $message);
    }

    public function destroy($id)
    {
        Stream::where('pk', $id)->delete();
        return redirect()->route('stream.index')->with('success', 'Stream deleted successfully!');
    }
}
