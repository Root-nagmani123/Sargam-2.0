@extends('admin.layouts.master')

@section('title', 'Generate Bill')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Generate Bill" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Generate New Bill</h4>
            <hr>
            <form action="{{ route('estate.billing.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="estate_possession_pk" class="form-label">Possession <span class="text-danger">*</span></label>
                            <select class="form-select @error('estate_possession_pk') is-invalid @enderror" 
                                    id="estate_possession_pk" name="estate_possession_pk" required>
                                <option value="">Select Possession</option>
                                @foreach($possessions as $possession)
                                    <option value="{{ $possession->pk }}" {{ old('estate_possession_pk') == $possession->pk ? 'selected' : '' }}>
                                        {{ $possession->employee->name ?? 'N/A' }} - {{ $possession->unit->unit_name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('estate_possession_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                            <select class="form-select @error('bill_month') is-invalid @enderror" 
                                    id="bill_month" name="bill_month" required>
                                <option value="">Select Month</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('bill_month') == $i ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                            @error('bill_month')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="bill_year" class="form-label">Bill Year <span class="text-danger">*</span></label>
                            <select class="form-select @error('bill_year') is-invalid @enderror" 
                                    id="bill_year" name="bill_year" required>
                                <option value="">Select Year</option>
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ old('bill_year', date('Y')) == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                            @error('bill_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="estate_meter_reading_pk" class="form-label">Meter Reading (Optional)</label>
                            <select class="form-select @error('estate_meter_reading_pk') is-invalid @enderror" 
                                    id="estate_meter_reading_pk" name="estate_meter_reading_pk">
                                <option value="">Select Reading or Enter Manually</option>
                            </select>
                            @error('estate_meter_reading_pk')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="licence_fee" class="form-label">License Fee (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('licence_fee') is-invalid @enderror" 
                                   id="licence_fee" name="licence_fee" value="{{ old('licence_fee', 0) }}">
                            @error('licence_fee')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="water_charge" class="form-label">Water Charge (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('water_charge') is-invalid @enderror" 
                                   id="water_charge" name="water_charge" value="{{ old('water_charge', 0) }}">
                            @error('water_charge')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="electric_charge" class="form-label">Electric Charge (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('electric_charge') is-invalid @enderror" 
                                   id="electric_charge" name="electric_charge" value="{{ old('electric_charge', 0) }}">
                            @error('electric_charge')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="other_charges" class="form-label">Other Charges (₹)</label>
                            <input type="number" step="0.01" class="form-control @error('other_charges') is-invalid @enderror" 
                                   id="other_charges" name="other_charges" value="{{ old('other_charges', 0) }}">
                            @error('other_charges')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control @error('remarks') is-invalid @enderror" 
                                      id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Generate Bill</button>
                        <a href="{{ route('estate.billing.index') }}" class="btn btn-secondary"><i class="ti ti-x"></i> Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load meter readings when possession is selected
    $('#estate_possession_pk').on('change', function() {
        var possessionId = $(this).val();
        if(possessionId) {
            $.ajax({
                url: "{{ url('estate/possession') }}/" + possessionId + "/meter-reading",
                type: 'GET',
                success: function(response) {
                    var readings = response.meterReadings || [];
                    var options = '<option value="">Select Reading or Enter Manually</option>';
                    readings.forEach(function(reading) {
                        options += '<option value="' + reading.pk + '">' + 
                                   reading.reading_date + ' - ₹' + reading.electric_charge + '</option>';
                    });
                    $('#estate_meter_reading_pk').html(options);
                }
            });
        }
    });
    
    // Auto-calculate total
    $('input[name="licence_fee"], input[name="water_charge"], input[name="electric_charge"], input[name="other_charges"]').on('input', function() {
        var licenceFee = parseFloat($('input[name="licence_fee"]').val()) || 0;
        var waterCharge = parseFloat($('input[name="water_charge"]').val()) || 0;
        var electricCharge = parseFloat($('input[name="electric_charge"]').val()) || 0;
        var otherCharges = parseFloat($('input[name="other_charges"]').val()) || 0;
        var total = licenceFee + waterCharge + electricCharge + otherCharges;
    });
});
</script>
@endpush
