@extends('admin.layouts.master')

@section('title', 'Meter Reading')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Meter Reading" />
    
    <!-- Possession Details -->
    <div class="card mb-3" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Possession Details</h4>
            <div class="row">
                <div class="col-md-3">
                    <strong>Employee:</strong> {{ $possession->employee->name ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Unit:</strong> {{ $possession->unit->unit_name ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Meter 1:</strong> {{ $possession->meter_no_one ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Meter 2:</strong> {{ $possession->meter_no_two ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Reading -->
    <div class="card mb-3" style="border-left: 4px solid #28a745;">
        <div class="card-body">
            <h4 class="mb-3">Add New Meter Reading</h4>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <form action="{{ route('estate.meter-reading.store') }}" method="POST">
                @csrf
                <input type="hidden" name="estate_possession_pk" value="{{ $possession->pk }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="reading_date" class="form-label">Reading Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('reading_date') is-invalid @enderror" 
                                   id="reading_date" name="reading_date" value="{{ old('reading_date', date('Y-m-d')) }}" required>
                            @error('reading_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="meter_reading_one" class="form-label">Meter Reading 1</label>
                            <input type="number" step="0.01" class="form-control @error('meter_reading_one') is-invalid @enderror" 
                                   id="meter_reading_one" name="meter_reading_one" value="{{ old('meter_reading_one') }}">
                            @error('meter_reading_one')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="meter_reading_two" class="form-label">Meter Reading 2</label>
                            <input type="number" step="0.01" class="form-control @error('meter_reading_two') is-invalid @enderror" 
                                   id="meter_reading_two" name="meter_reading_two" value="{{ old('meter_reading_two') }}">
                            @error('meter_reading_two')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <input type="text" class="form-control @error('remarks') is-invalid @enderror" 
                                   id="remarks" name="remarks" value="{{ old('remarks') }}">
                            @error('remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success"><i class="ti ti-device-floppy"></i> Save Reading</button>
                <a href="{{ route('estate.possession.index') }}" class="btn btn-secondary"><i class="ti ti-arrow-back"></i> Back</a>
            </form>
        </div>
    </div>

    <!-- Reading History -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Reading History</h4>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Meter 1 Reading</th>
                            <th>Meter 2 Reading</th>
                            <th>Units Consumed 1</th>
                            <th>Units Consumed 2</th>
                            <th>Electric Charge</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($possession->meterReadings as $reading)
                        <tr>
                            <td>{{ $reading->reading_date->format('d-m-Y') }}</td>
                            <td>{{ $reading->meter_reading_one }}</td>
                            <td>{{ $reading->meter_reading_two }}</td>
                            <td>{{ $reading->units_consumed_one }}</td>
                            <td>{{ $reading->units_consumed_two }}</td>
                            <td>₹{{ number_format($reading->electric_charge, 2) }}</td>
                            <td>{{ $reading->remarks }}</td>
                            <td>
                                <button class="btn btn-danger btn-sm delete-reading-btn" data-id="{{ $reading->pk }}">Delete</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No readings recorded yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.delete-reading-btn').on('click', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to delete this reading?')) {
            $.ajax({
                url: "{{ route('estate.meter-reading.destroy', '') }}/" + id,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    if(response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
});
</script>
@endpush
