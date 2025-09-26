@extends('admin.layouts.master')

@section('title', 'Registration List')

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Registration List" />
        <x-session_message />

        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">

                <!-- Filters Form -->
                <form id="registrationFilterForm">
                    <div class="row align-items-end mb-4">

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

                        <!-- Reset Button -->
                        <div class="col-md-1 mb-2">
                            <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">Reset</button>
                        </div>
                        <!-- Filter Button -->
                        {{-- <div class="col-md-1 col-sm-6 mb-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div> --}}

                        <!-- Reset Button -->
                        {{-- <div class="col-md-1 col-sm-6 mb-2">
                            <a href="{{ route('admin.registration.index') }}"
                                class="btn btn-outline-secondary w-100">Reset</a>
                        </div> --}}

                        <!-- Export Format -->
                        {{-- <div class="col-md-2 col-sm-6 mb-2">
                            <label for="format" class="form-label">Export Format</label>
                            <select name="format" id="format" class="form-select">
                                <option value="">-- All Formats --</option>
                                <option value="pdf">PDF</option>
                                <option value="xlsx">Excel</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>

                        <!-- Export Button -->
                        <div class="col-md-2 col-sm-12 mb-2">
                            <button type="submit" formaction="{{ route('admin.registration.export') }}"
                                class="btn btn-success w-100">
                                Export
                            </button> --}}
                        {{-- </div> --}}

                    </div>
                </form>

                <!-- ✅ Export Form OUTSIDE filter form -->
                <form id="exportForm" action="{{ route('admin.registration.export') }}" method="GET"
                    class="d-flex align-items-center gap-2 mb-3">
                    <input type="hidden" name="course_name" id="export_course_name">
                    <input type="hidden" name="exemption_category" id="export_exemption_category">
                    <input type="hidden" name="application_type" id="export_application_type">
                    <input type="hidden" name="service_master" id="export_service_master">

                    <label for="format" class="form-label me-2 mb-0 fw-semibold">Export:</label>
                    <select name="format" id="format" class="form-select w-auto" required>
                        <option value="">Select Format</option>
                        <option value="xlsx">Excel (.xlsx)</option>
                        <option value="csv">CSV (.csv)</option>
                        <option value="pdf">PDF (.pdf)</option>
                    </select>
                    <button type="submit" class="btn btn-success">Export</button>
                </form>

                <!-- DataTable -->
                <div class="table-responsive">
                    {{ $dataTable->table(['class' => 'table table-striped table-bordered text-nowrap align-middle', 'id' => 'fcregistrationmasterlistdatable-table']) }}
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
            $('#course_name, #exemption_category, #application_type, #service_master').on('change', function() {
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
            });
        });

        
            $(document).ready(function() {
                // ✅ Sync filter values into export form before submit
                $('#exportForm').on('submit', function() {
                    $('#export_course_name').val($('#course_name').val());
                    $('#export_exemption_category').val($('#exemption_category').val());
                    $('#export_application_type').val($('#application_type').val());
                    $('#export_service_master').val($('#service_master').val());
                });

                // ✅ Disable Export button until format is selected
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
@endpush
