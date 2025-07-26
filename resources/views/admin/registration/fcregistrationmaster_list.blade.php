@extends('admin.layouts.master')

@section('title', 'Registration List')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Registration List" />
        <x-session_message />

        <div class="datatables">
            <!-- start Zero Configuration -->
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="row mb-3">

                    </div>

                    <div class="table-responsive">
                        <!-- Header Row with Title and Buttons -->
                        <div class="row">
                            <div class="col-5">
                                <h4>Fc Registration Master</h4>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3 d-flex align-items-end gap-2">
                                    <!-- <label for="rank_filter" class="form-label fw-semibold">Filter by Rank </label> -->
                                    <input type="number" id="rank_filter" class="form-control" placeholder="Filter by Rank"
                                        aria-label="Filter by Rank" style="width: 100%;" />
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="float-end d-flex gap-2">
                                    <form action="{{ route('admin.registration.export') }}" method="GET"
                                        class="d-flex align-items-center gap-2">
                                        <label for="format" class="form-label me-2 mb-0 fw-semibold">Export:</label>
                                        <select name="format" id="format" class="form-select w-auto" required>
                                            <option value="">Select Format</option>
                                            <option value="xlsx">Excel (.xlsx)</option>
                                            <option value="csv">CSV (.csv)</option>
                                            <option value="pdf">PDF (.pdf)</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary ms-2">Download</button>
                                    </form>
                                    <a href="{{ route('admin.registration.import.form') }}" class="btn btn-secondary">
                                        <i class="bi bi-upload me-1"></i> Bulk Upload
                                    </a>
                                </div>

                            </div>

                        </div>

                        <hr>

                        <!-- Table Section -->
                        <div id="zero_config_wrapper" class="dataTables_wrapper">
                            <div class ="table-responsive">
                            <table id="zero_config"
                                class="table table-striped table-bordered text-nowrap align-middle dataTable"
                                aria-describedby="zero_config_info">
                                <caption class="visually-hidden">Registration Data Table</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">S No</th>
                                        <th scope="col">Display Name</th> {{-- NEW --}}
                                        <th scope="col">First Name</th>
                                        <th scope="col">Middle Name</th>
                                        <th scope="col">Last Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Contact No</th>
                                        <th scope="col">Schema ID</th> {{-- NEW --}}
                                        <th scope="col">Service Master PK</th> {{-- NEW --}}
                                        <th scope="col">Rank</th>
                                        <th scope="col">Exam Year</th>
                                        <th scope="col">Web Auth</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @if (!empty($registrations) && count($registrations) > 0)
                                        @foreach ($registrations as $reg)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $reg->display_name }}</td> {{-- NEW --}}
                                                <td>{{ $reg->first_name }}</td>
                                                <td>{{ $reg->middle_name }}</td>
                                                <td>{{ $reg->last_name }}</td>
                                                <td>{{ $reg->email }}</td>
                                                <td>{{ $reg->contact_no }}</td>
                                                <td>{{ $reg->schema_id }}</td> {{-- NEW --}}
                                                <td>{{ $reg->service_master_pk }}</td> {{-- NEW --}}
                                                <td>{{ $reg->rank }}</td>
                                                <td>{{ $reg->exam_year }}</td>
                                                <td>{{ $reg->web_auth }}</td>
                                                <td>
                                                    <a href="{{ route('admin.registration.edit', $reg->pk) }}"
                                                        class="btn btn-sm btn-primary">Edit</a>
                                                    <form action="{{ route('admin.registration.delete', $reg->pk) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button onclick="return confirm('Delete this record?')"
                                                            class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="12" class="text-center">No registration records found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#zero_config').DataTable();

            $('#rank_filter').on('keyup change', function() {
                let value = this.value.trim();
                table
                    .column(6) // Rank column index (0-based)
                    .search(value ? '^' + value + '$' : '', true, false)
                    .draw();
            });
        });
    </script>
@endpush
