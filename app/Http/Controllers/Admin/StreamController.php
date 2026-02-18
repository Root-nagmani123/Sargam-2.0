<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\DataTables\StreamDataTable;
use App\Models\Stream;
use Illuminate\Http\Request;


class StreamController extends Controller
{
    public function index(StreamDataTable $dataTable)
    {
        return $dataTable->render('admin.stream.index');
    }

    public function create()
    {
        return view('admin.stream.create');
    }


    public function store(Request $request)
{
    $data = $request->input('stream_name');

    // Force array always
    $streams = is_array($data) ? $data : [$data];

    $request->validate([
        'stream_name.*' => 'required|string|max:100',
    ]);

    foreach ($streams as $name) {
        Stream::create([
            'stream_name' => trim($name),
            'active_inactive' => 1,
        ]);
    }

    return redirect()->route('stream.index')->with('success', 'Streams added successfully!');
}


    public function store_17022026(Request $request)
    {
            $request->validate([
            'stream_name' => 'required|array|min:1',
            'stream_name.*' => 'required|string|max:100',
            ], [
                'stream_name.required' => 'At least one stream is required.',
                'stream_name.*.required' => 'The stream name field is required.',
                'stream_name.*.max' => 'Stream name may not be greater than 100 characters.',
            ]);

        foreach ($request->stream_name as $name) {

            if(trim($name) == '') continue;

            Stream::create([
                'stream_name' => $name,
                'active_inactive' => 1,
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

    public function toggleStatus(Request $request)
    {
    $stream = Stream::where('pk', $request->id)->first();

    if (!$stream) {
        return response()->json(['success' => false]);
    }

    $stream->active_inactive = $stream->active_inactive == 1 ? 0 : 1;
    $stream->save();

    return response()->json([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
    }


  public function destroy($id)
{
    $deleted = Stream::where('pk',$id)
        ->where('active_inactive',0)
        ->delete();

    if($deleted){
        return response()->json(['success'=>true]);
    }

    return response()->json([
        'success'=>false,
        'message'=>'Only inactive streams can be deleted'
    ]);
}


}
