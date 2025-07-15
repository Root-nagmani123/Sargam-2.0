@extends('admin.layouts.master')
@section('title', 'Report Joining Documents')

@section('content')
<x-session_message />

{{-- Filter Section --}}
<div class="container-fluid mt-4">
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search OT Name</label>
                    <input type="text" name="search" class="form-control" placeholder="Search OT Name..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">-- All Status --</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Complete</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2 mt-2 mt-md-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.joining-documents.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Serial Number</th>
                            <th>OT Name</th>
                            <th>Programme Structure</th>
                            @foreach ($fields as $label)
                            <th>{{ $label }}</th>
                            @endforeach
                            <th>Check Status</th>
                            <th>Download All</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $index => $student)
                        @php
                        // $upload = $uploads[$student->pk] ?? null;
                        $upload = $uploads[$student->id] ?? null;

                        @endphp
                        <tr>
                            <td>{{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}</td>
                            {{-- <td>{{ $student->display_name }}</td> --}}
                            <td>{{ $student->name }}</td>
                            {{-- <td>{{ $student->schema_id }}</td> --}}
                            <td>{{ $student->id }}</td>

                            @foreach ($fields as $fieldKey => $fieldLabel)
                            <td>
                                @if ($upload && !empty($upload->$fieldKey))
                                <a href="{{ asset('storage/' . $upload->$fieldKey) }}" target="_blank"
                                    class="btn btn-link p-0 text-primary">View</a> |
                                <a href="{{ asset('storage/' . $upload->$fieldKey) }}" download
                                    class="btn btn-link p-0 text-primary">Download</a>
                                @else
                                <span class="text-danger">Pending</span>
                                @endif
                            </td>
                            @endforeach
                            <td>
                                @php
                                // $upload = $uploads[$student->pk] ?? null;
                                $upload = $uploads[$student->id] ?? null;

                                // Check if all fields are uploaded (non-empty)
                                $allDone =
                                $upload &&
                                collect($fields)->every(function ($label, $key) use ($upload) {
                                return !empty($upload->$key);
                                });
                                @endphp

                                <span class="badge {{ $allDone ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $allDone ? 'Success' : 'Pending' }}
                                </span>
                            </td>
                            <td>

                                {{-- <a href="{{ route('admin.joining-documents.download-all', $student->pk) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-download"></i> Download All
                                </a> --}}
                                <a href="{{ route('admin.joining-documents.download-all', $student->id) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-download"></i> Download All
                            </td>

                            {{-- <td><textarea class="form-control form-control-sm" placeholder="Enter remarks"></textarea></td> --}}
                            <td style="min-width: 250px;">
                                <form method="POST" {{-- action="{{ route('admin.joining-documents.save-remark', $student->pk) }}"> --}}
                                    action="{{ route('admin.joining-documents.save-remark', $student->id) }}">
                                    @csrf
                                    <textarea name="remark" class="form-control form-control-sm text-center" rows="4"
                                        style="width: 100%; min-height: 60px; resize: vertical;"
                                        onchange="this.form.submit()"
                                        placeholder="Enter remarks">{{ $upload->remark ?? '' }}</textarea>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-4">
                    {!! $students->links() !!}

                    {{-- {!! $uploads->appends(request()->query())->links() !!} --}}
                </div>
        </div>
    </div>
</div>
@endsection