@extends('admin.layouts.master')

@section('title', 'Registration List')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Registration List" />
        <x-session_message />

        <div class="card" style="border-left: 4px solid #004a93;">
            {{-- <div class="card-body">

                <!-- Filters Form -->
                <form id="registrationFilterForm">
                    <div class="row align-items-end mb-4">
                        <a href="{{ route('admin.registration.import.form') }}" class="btn btn-secondary">
                            <i class="bi bi-upload me-1"></i> Bulk Upload
                        </a>
                        <!-- Course Name -->
                        <div class="col-md-3 mb-2">
                            <label for="course_name" class="form-label">Course Name</label>
                            <select id="course_name" class="form-select">
                                <option value="">-- All Courses --</option>
                                @foreach ($courses as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Exemption Category -->
                        <div class="col-md-3 mb-2">
                            <label for="exemption_category" class="form-label">Exemption Category</label>
                            <select id="exemption_category" class="form-select">
                                <option value="">-- All Categories --</option>
                                @foreach ($exemptionCategories as $id => $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Application Type -->
                        <div class="col-md-2 mb-2">
                            <label for="application_type" class="form-label">Application Type</label>
                            <select id="application_type" class="form-select">
                                <option value="">-- All Types --</option>
                                @foreach ($applicationTypes as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Service -->
                        <div class="col-md-3 mb-2">
                            <label for="service_master" class="form-label">Service</label>
                            <select id="service_master" class="form-select">
                                <option value="">-- All Services --</option>
                                @foreach ($serviceMasters as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="year">Select Year</label>
                            <select name="year" id="year" class="form-control">
                                <option value="">-- All Years --</option>
                                @foreach ($years as $key => $year)
                                    <option value="{{ $key }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="group_type">Group Type</label>
                            <select id="group_type" name="group_type" class="form-control">
                                <option value="">All</option>
                                <option value="A" {{ request('group_type') == 'A' ? 'selected' : '' }}>A</option>
                                <option value="B" {{ request('group_type') == 'B' ? 'selected' : '' }}>B</option>
                                <option value="NULL" {{ request('group_type') == 'NULL' ? 'selected' : '' }}>Other
                                </option>
                            </select>
                        </div>


                        <!-- Reset Button -->
                        <div class="col-md-1 mb-2">
                            <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">Reset</button>
                        </div>
                    </div>
                </form>

                <!--  Export Form OUTSIDE filter form -->
                <form id="exportForm" action="{{ route('admin.registration.export') }}" method="GET"
                    class="d-flex align-items-center gap-2 mb-3">
                    <input type="hidden" name="course_name" id="export_course_name">
                    <input type="hidden" name="exemption_category" id="export_exemption_category">
                    <input type="hidden" name="application_type" id="export_application_type">
                    <input type="hidden" name="service_master" id="export_service_master">
                    <input type="hidden" name="year" id="export_year">
                    <input type="hidden" name="group_type" id="export_group_type">

                    <label for="format" class="form-label me-2 mb-0 fw-semibold">Export:</label>
                    <select name="format" id="format" class="form-select w-auto" required>
                        <option value="">Select Format</option>
                        <option value="xlsx">Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                        <option value="pdf">PDF (.pdf)</option>
                    </select>
                    <button type="submit" class="btn btn-success">Export</button>
                </form>
               
                <form action="{{ route('fc.preview.upload') }}" method="POST" enctype="multipart/form-data"
                    class="mb-3 d-flex align-items-center gap-2">
                    @csrf
                    <!-- Download Template -->
                    <a href="{{ route('fc.download.template') }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Download Template
                    </a>

                    <!-- File Input -->
                    <input type="file" name="file" class="form-control w-auto" accept=".xlsx,.xls,.csv" required>

                    <!-- Submit Preview -->
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-eye me-1"></i> Preview
                    </button>
                </form>


                <!-- DataTable -->
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table table-striped table-bordered text-nowrap align-middle', 'id' => 'fcregistrationmasterlistdatable-table']) }}
                </div>

            </div> --}}
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">

                    <!-- Filters Form -->
                    <form id="registrationFilterForm">
                        <!-- Row 1: 4 Filters -->
                        <div class="row g-3 mb-3 align-items-end">
                            <div class="col-md-3">
                                <label for="course_name" class="form-label">Course Name</label>
                                <select id="course_name" class="form-select">
                                    <option value="">-- All Courses --</option>
                                    @foreach ($courses as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="exemption_category" class="form-label">Exemption Category</label>
                                <select id="exemption_category" class="form-select">
                                    <option value="">-- All Categories --</option>
                                    @foreach ($exemptionCategories as $id => $name)
                                        <option value="{{ $name }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="application_type" class="form-label">Application Type</label>
                                <select id="application_type" class="form-select">
                                    <option value="">-- All Types --</option>
                                    @foreach ($applicationTypes as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="service_master" class="form-label">Service</label>
                                <select id="service_master" class="form-select">
                                    <option value="">-- All Services --</option>
                                    @foreach ($serviceMasters as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 d-flex justify-content-end">
                                <a href="{{ route('admin.registration.import.form') }}" class="btn btn-secondary w-100">
                                    <i class="bi bi-upload me-1"></i> Bulk Upload
                                </a>
                            </div>
                        </div>


                        <!-- Row 2: Year, Group Type, Reset -->
                        <div class="row g-3 mb-3 align-items-end">
                            <div class="col-md-3">
                                <label for="year" class="form-label">Year</label>
                                <select id="year" class="form-select">
                                    <option value="">-- All Years --</option>
                                    @foreach ($years as $key => $year)
                                        <option value="{{ $key }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="group_type" class="form-label">Service Type</label>
                                <select id="group_type" class="form-select">
                                    <option value="">-- All Groups --</option>
                                    <option value="A" {{ request('group_type') == 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ request('group_type') == 'B' ? 'selected' : '' }}>B</option>
                                    <option value="NULL" {{ request('group_type') == 'NULL' ? 'selected' : '' }}>Other
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Row 3: Export & Template/Preview Buttons -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 d-flex gap-2">
                            <form id="exportForm" action="{{ route('admin.registration.export') }}" method="GET"
                                class="d-flex w-100 gap-2">
                                <input type="hidden" name="course_name" id="export_course_name">
                                <input type="hidden" name="exemption_category" id="export_exemption_category">
                                <input type="hidden" name="application_type" id="export_application_type">
                                <input type="hidden" name="service_master" id="export_service_master">
                                <input type="hidden" name="year" id="export_year">
                                <input type="hidden" name="group_type" id="export_group_type">

                                <select name="format" id="format" class="form-select">
                                    <option value="">Format</option>
                                    <option value="xlsx">Excel</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                                <button type="submit" class="btn btn-success w-100">Export</button>
                            </form>
                        </div>

                        <div class="col-md-6 d-flex gap-2">
                            <form action="{{ route('fc.preview.upload') }}" method="POST" enctype="multipart/form-data"
                                class="d-flex w-100 gap-2">
                                @csrf
                                <button type="button" onclick="window.location='{{ route('fc.download.template') }}'"
                                    class="btn btn-outline-success w-100">
                                    <i class="bi bi-download me-1"></i> Template
                                </button>
                                <input type="file" name="file" class="form-control w-100" accept=".xlsx,.xls,.csv"
                                    required>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-eye me-1"></i> Preview
                                </button>
                            </form>
                        </div>

                        <form id="bulkDeactivateForm" action="{{ route('admin.registration.deactivate.filtered') }}"
                            method="POST" class="d-inline-block mb-3">
                            @csrf
                            <input type="hidden" name="group_type" id="deactivate_group_type">
                            <button type="submit" class="btn btn-danger" id="deactivateButton" disabled>
                                <i class="bi bi-slash-circle me-1"></i> Deactivate Service Type Records
                            </button>
                        </form>


                    </div>

                    <!-- DataTable -->
                    <div class="table-responsive">
                        {{ $dataTable->table(['class' => 'table table-striped table-bordered text-nowrap align-middle', 'id' => 'fcregistrationmasterlistdatable-table']) }}
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    {{ $dataTable->scripts() }}

    <script>
        $(document).ready(function() {
            var table = $('#fcregistrationmasterlistdatable-table').DataTable();

            // Reload DataTable on filter change
            $('#course_name, #exemption_category, #application_type, #service_master, #year, #group_type').on(
                'change',
                function() {
                    table.ajax.reload();
                });

            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#registrationFilterForm select').val('');
                table.ajax.reload();
            });

            // Pass filters to server
            $.fn.dataTable.ext.ajax = table.settings()[0].ajax;
            $('#fcregistrationmasterlistdatable-table').on('preXhr.dt', function(e, settings, data) {
                data.course_name = $('#course_name').val();
                data.exemption_category = $('#exemption_category').val();
                data.application_type = $('#application_type').val();
                data.service_master = $('#service_master').val();
                data.year = $('#year').val(); // Added Year
                data.group_type = $('#group_type').val(); // Added Group Type


            });

            // $('#fcregistrationmasterlistdatable-table').on('draw.dt', function() {
            //     var table = $('#fcregistrationmasterlistdatable-table').DataTable();
            //     table.rows().every(function() {
            //         var data = this.data();
            //         var rowNode = this.node();
            //         if (parseInt(data.email_count) > 1) {
            //             // highlight only the email cell
            //             $(rowNode).find('td').eq(table.column('email:name').index()).css(
            //                 'background-color', '#ffe6e6');
            //         } else {
            //             $(rowNode).find('td').eq(table.column('email:name').index()).css(
            //                 'background-color', '');
            //         }
            //     });
            // });
            $('#fcregistrationmasterlistdatable-table').on('draw.dt', function() {
                var table = $('#fcregistrationmasterlistdatable-table').DataTable();
                table.rows().every(function() {
                    var data = this.data();
                    var rowNode = this.node();

                    if (parseInt(data.email_count) > 1) {
                        $(rowNode).addClass('highlight-row');
                    } else {
                        $(rowNode).removeClass('highlight-row');
                    }
                });
            });




            //  Sync Group Type into deactivate form hidden input
            function syncGroupType() {
                $('#deactivate_group_type').val($('#group_type').val());
            }
            $('#group_type').on('change', syncGroupType);
            syncGroupType(); // set initial value on load
        });


        $(document).ready(function() {
            //  Sync filter values into export form before submit
            $('#exportForm').on('submit', function() {
                $('#export_course_name').val($('#course_name').val());
                $('#export_exemption_category').val($('#exemption_category').val());
                $('#export_application_type').val($('#application_type').val());
                $('#export_service_master').val($('#service_master').val());
                $('#export_year').val($('#year').val()); // Added Year
                $('#export_group_type').val($('#group_type').val()); // Added Group Type
            });

            //  Disable Export button until format is selected
            $('#format').on('change', function() {
                if ($(this).val()) {
                    $('#exportForm button[type="submit"]').prop('disabled', false);
                } else {
                    $('#exportForm button[type="submit"]').prop('disabled', true);
                }
            });

            // Set initial state (disabled)
            $('#exportForm button[type="submit"]').prop('disabled', true);
        });
    </script>

    <!-- Script to enable/disable Deactivate button based on Group Type selection -->
    <script>
        $(document).ready(function() {
            const deactivateBtn = $('#deactivateButton');
            const groupSelect = $('#group_type');
            const deactivateGroupInput = $('#deactivate_group_type');

            function toggleDeactivateButton() {
                const groupVal = groupSelect.val();
                deactivateGroupInput.val(groupVal); // sync hidden input

                if (groupVal === 'B') {
                    deactivateBtn.prop('disabled', false); // enable only for B
                } else {
                    deactivateBtn.prop('disabled', true); // disable for others
                }
            }

            // On load
            toggleDeactivateButton();

            // On change
            groupSelect.on('change', toggleDeactivateButton);
        });
    </script>
    <style>
        .highlight-row td {
            background-color: #ffe6e6 !important;
        }
    </style>
@endpush
