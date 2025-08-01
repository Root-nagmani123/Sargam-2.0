@extends('admin.layouts.master')
@section('title', 'Course List')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-body">
                <h4>Total Registered Students: {{ $total_students }}</h4>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <form method="GET" class="mb-3">
                            <label>Status Filter:</label>
                            <select name="statusval" onchange="this.form.submit()"
                                class="form-select w-auto d-inline-block ms-2">
                                <option value="">All</option>
                                <option value="1" {{ $statusval == 1 ? 'selected' : '' }}>confirm</option>
                                <option value="2" {{ $statusval == 2 ? 'selected' : '' }}>Not confirm</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-6">
                        <!-- Export Section -->
                        <div class="d-flex justify-content-between align-items-center mb-4 float-end">
                            <form action="{{ route('forms.export', ['formid' => $formid]) }}" method="GET"
                                class="d-flex align-items-center gap-2">
                                <label for="format" class="form-label me-2 mb-0 fw-semibold">Export:</label>
                                <select name="format" id="format" class="form-select w-auto" required>
                                    <option value="">Select Format </option>
                                    <option value="xlsx">Excel (.xlsx)</option>
                                    <option value="csv">CSV (.csv)</option>
                                    <option value="pdf">PDF (.pdf)</option>
                                </select>
                                <input type="hidden" name="statusval" value="{{ request('statusval') }}">
                                <button type="submit" class="btn btn-primary ms-2">Download</button>
                            </form>
                        </div>
                    </div>
                </div>





                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-nowrap table-responsive">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                @foreach ($fields as $field)
                                    <th>{{ ucfirst($field) }}</th>
                                @endforeach
                                <th>Download PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $record)
                                @php $uid = $record->uid; @endphp
                                @if (isset($users[$uid]))
                                    <tr>
                                        <td>{{ $loop->iteration }}</td> {{-- S.No column --}}
                                        @foreach ($fields as $field)
                                            <td>
                                                @php
                                                    $value = $users[$uid][$field] ?? '';
                                                    $extension = pathinfo($value, PATHINFO_EXTENSION);
                                                @endphp

                                                @if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                                    <img src="{{ asset('storage/' . $value) }}" width="100"
                                                        alt="Image">
                                                @elseif (strtolower($extension) === 'pdf')
                                                    <a href="{{ asset('storage/' . $value) }}" target="_blank "
                                                        style="color: #007bff; text-decoration: underline;">View PDF</a>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            <a href="{{ route('forms.pdf', ['form_id' => $formid, 'user_id' => $uid]) }}"
                                                style="color: #007bff; text-decoration: underline;">Download</a>
                                        </td>
                                        {{-- <td>{{ $record->confirm_status }}</td> --}}
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{-- {{ $records->appends(['statusval' => $statusval])->links() }} --}}
                </div>
            </div>
        </div>
    </div>
@endsection
