@extends('admin.layouts.master')

@section('title', 'Preview Bulk Upload')

@section('content')
<div class="container-fluid py-5">
    <x-breadcrum title="Preview Bulk Upload" />
    <x-session_message />

    <div class="card" style="border-left: 5px solid #004a93;">
        <div class="card-body">
            <h3 class="fw-bold mb-2" style="color: #004a93;">Preview Bulk Registration Upload</h3>

            <form action="{{ route('fc.confirm.upload') }}" method="POST">
                @csrf
                <input type="hidden" name="data" value="{{ $previewData->toJson() }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table">
                            <tr>
                                <th>Display Name</th>
                                <th>Contact No</th>
                                <th>Rank</th>
                                <th>Generated OT Code</th>
                                <th>Service PK</th>
                                <th>Cadre PK</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previewData as $row)
                                <tr class="{{ $row['exists'] == 'Update' ? 'table-warning' : '' }}">
                                    <td>{{ $row['display_name'] }}</td>
                                    <td>{{ $row['contact_no'] }}</td>
                                    <td>{{ $row['rank'] }}</td>
                                    <td>{{ $row['generated_OT_code'] }}</td>
                                    <td>{{ $row['service_master_pk'] }}</td>
                                    <td>{{ $row['cadre_master_pk'] }}</td>
                                    <td>{{ $row['exists'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Confirm Upload
                    </button>
                    <a href="{{ route('admin.registration.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
