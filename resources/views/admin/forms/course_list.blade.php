@extends('admin.layouts.master')
@section('title', 'Course List')

@section('content')
    <div class="container-fluid mt-4">
        <h4>Total Registered Students: {{ $total_students }}</h4>
        <hr>

        <form method="GET" class="mb-3">
            <label>Status Filter:</label>
            <select name="statusval" onchange="this.form.submit()" class="form-select w-auto d-inline-block ms-2">
                <option value="">All</option>
                <option value="1" {{ $statusval == 1 ? 'selected' : '' }}>confirm</option>
                <option value="2" {{ $statusval == 2 ? 'selected' : '' }}>Not confirm</option>
            </select>
        </form>

        </a>

        <table class="table table-striped table-bordered text-nowrap">
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
                                        <img src="{{ asset('uploads/' . $value) }}" width="100" alt="Image">
                                    @elseif (strtolower($extension) === 'pdf')
                                        <a href="{{ asset('uploads/' . $value) }}" target="_blank">View PDF</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                <a
                                    href="{{ route('forms.downloadpdf', ['formid' => $formid, 'uid' => $uid]) }}">Download</a>
                            </td>
                            {{-- <td>{{ $record->confirm_status }}</td> --}}
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="d-flex justify-content-center">
            {{-- {{ $records->appends(['statusval' => $statusval])->links() }} --}}
        </div>
    </div>
@endsection
