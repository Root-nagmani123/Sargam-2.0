@extends('admin.layouts.master')

@section('title', 'Registration List')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Registration List" />
    <x-session_message />

      <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <!-- Header Row with Title and Buttons -->
                <div class="row">
                    <div class="col-7">
                        <h4>Fc Registration Master</h4>
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

                <div class="table-responsive">
                {{ $dataTable->table(['class' => 'table table-striped table-bordered text-nowrap align-middle']) }}
                </div>
            </div>
 
</div>
@endsection

@push('scripts')
{{ $dataTable->scripts() }}
@endpush