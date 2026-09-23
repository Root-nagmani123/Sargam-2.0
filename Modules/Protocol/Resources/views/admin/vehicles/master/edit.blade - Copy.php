@extends('admin.layouts.master')

@section('title', 'Create New Vehicle')
@section('page_title', 'Create New Vehicle')
@section('page_subtitle', 'Add new vehicle')

@section('setup_content')
  @include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="Add Vehicle"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Add Vehicle',
        ]"
        :showBack="true"
        :buttonUrl="route('protocol.vehicle-master.index')"
        buttonIcon="arrow_back"
    />

    <div class="card-clean p-3">
        <div class="container">
        <div class="form-header">
            <h2><i class="fas fa-car"></i> Define Vehicle Information</h2>
            <p>Please add the vehicle details in the form below</p>
        </div>

        <form id="vehicleForm">
            <!-- VEHICLE INFORMATION SECTION -->
            <div class="form-section">
                <div class="section-title mt-2">
                    <i class="fas fa-info-circle"></i> Vehicle Information
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="vehicleName" class="form-label required">Vehicle Name</label>
                        <input type="text" class="form-control" id="vehicleName" placeholder="Enter vehicle name">
                        <div class="helper-text">Enter a descriptive name for the vehicle</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="vehicleNumber" class="form-label required">Vehicle Number</label>
                        <input type="text" class="form-control" id="vehicleNumber" placeholder="Enter vehicle number">
                        <div class="helper-text">Enter vehicle registration number</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="manufacturer" class="form-label required">Manufacturer</label>
                        <input type="text" class="form-control" id="manufacturer" placeholder="Enter manufacturer">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="engineNumber" class="form-label">Engine Number</label>
                        <input type="text" class="form-control" id="engineNumber" placeholder="Enter engine number">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="chassisNo" class="form-label required">Chassis No.</label>
                        <input type="text" class="form-control" id="chassisNo" placeholder="Enter chassis number">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" class="form-control" id="model" placeholder="Enter model">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="avgCommitted" class="form-label required">Average Committed</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="avgCommitted" placeholder="0" step="0.01">
                            <span class="input-group-text">km/L</span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="currentReading" class="form-label required">Current Reading</label>
                        <input type="number" class="form-control" id="currentReading" placeholder="0">
                        <div class="helper-text">Enter odometer reading</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fuelType" class="form-label">Fuel Type</label>
                        <select class="form-select" id="fuelType">
                            <option selected disabled>--Select--</option>
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="cng">CNG</option>
                            <option value="lpg">LPG</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="vehicleType" class="form-label required">Vehicle Type</label>
                        <select class="form-select" id="vehicleType">
                            <option selected disabled>--Select--</option>
                            <option value="car">Car</option>
                            <option value="bus">Bus</option>
                            <option value="truck">Truck</option>
                            <option value="van">Van</option>
                            <option value="suv">SUV</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="capacity" class="form-label required">Capacity</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="capacity" placeholder="0">
                            <span class="input-group-text">Seats/Units</span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Active/Deactive</label>
                        <div class="radio-group">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="active" name="status" value="active" checked>
                                <label class="form-check-label" for="active">Active</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="inactive" name="status" value="inactive">
                                <label class="form-check-label" for="inactive">Deactive</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="manufactureYear" class="form-label">Manufacture Year</label>
                        <select class="form-select" id="manufactureYear">
                            <option selected disabled>--Select--</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="maintainedBy" class="form-label required">Maintained By</label>
                        <select class="form-select" id="maintainedBy">
                            <option selected disabled>--Select--</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="registrationNumber" class="form-label required">Registration Number</label>
                        <input type="text" class="form-control" id="registrationNumber" placeholder="Enter registration number">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="colonyName" class="form-label required">Colony Name</label>
                        <select class="form-select" id="colonyName">
                            <option selected disabled>--Select--</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="stateName" class="form-label required">State Name</label>
                        <select class="form-select" id="stateName">
                            <option selected disabled>--select--</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="region" class="form-label required">Region</label>
                        <select class="form-select" id="region">
                            <option selected disabled>--Select--</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="driverName" class="form-label required">Driver Name</label>
                        <select class="form-select" id="driverName">
                            <option selected disabled>--Select--</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="registrationAuth" class="form-label required">Registration Authority</label>
                        <select class="form-select" id="registrationAuth">
                            <option selected disabled>--Select--</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="vehicleNotes" class="form-label">Vehicle Notes</label>
                        <textarea class="form-control" id="vehicleNotes" rows="3" placeholder="Enter vehicle notes"></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="vehiclePart" class="form-label">Vehicle Part</label>
                        <input type="text" class="form-control" id="vehiclePart" placeholder="Enter vehicle part">
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Validity in KM/Year(s)</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <div style="flex: 1;">
                                <input type="number" class="form-control" placeholder="KM">
                            </div>
                            <div style="color: #138808; font-weight: bold;">✓</div>
                            <div style="color: #dc3545; font-weight: bold;">✕</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VEHICLE FINANCIAL INFORMATION SECTION -->
            <div class="form-section">
                <div class="section-title mt-2">
                    <i class="fas fa-credit-card"></i> Vehicle Financial Information
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ownedBy" class="form-label required">Owned By</label>
                        <input type="text" class="form-control" id="ownedBy" placeholder="Enter owner name">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="financedBy" class="form-label">Financed By</label>
                        <input type="text" class="form-control" id="financedBy" placeholder="Enter financed by">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="totalCost" class="form-label required">Total Vehicle Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="totalCost" placeholder="0" step="0.01">
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="actualCost" class="form-label required">Actual Vehicle Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="actualCost" placeholder="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Option</label>
                        <div class="radio-group">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="purchase" name="option" value="purchase" checked>
                                <label class="form-check-label" for="purchase">Purchase</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="rent" name="option" value="rent">
                                <label class="form-check-label" for="rent">Rent</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="purchaseDate" class="form-label required">Purchase Date</label>
                        <input type="date" class="form-control" id="purchaseDate">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="purchaseCost" class="form-label required">Purchase Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="purchaseCost" placeholder="0" step="0.01">
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="marginMoney" class="form-label">Margin Money</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="marginMoney" placeholder="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="loanAmount" class="form-label">Loan Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="loanAmount" placeholder="0" step="0.01">
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="emi" class="form-label">EMI</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="emi" placeholder="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="rateOfInterest" class="form-label">Rate Of Interest</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="rateOfInterest" placeholder="0" step="0.01">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="lastInstallDate" class="form-label required">Last Installment Date</label>
                        <input type="date" class="form-control" id="lastInstallDate">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="firstInstallDate" class="form-label required">First Installment Date</label>
                        <input type="date" class="form-control" id="firstInstallDate">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="vendorName" class="form-label required">Vendor Name</label>
                        <select class="form-select" id="vendorName">
                            <option selected disabled>--Select--</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="purchaseNotes" class="form-label">Purchase/Rental Notes</label>
                        <textarea class="form-control" id="purchaseNotes" rows="3" placeholder="Enter purchase or rental notes"></textarea>
                    </div>
                </div>
            </div>

            <!-- VEHICLE INSURANCE INFORMATION SECTION -->
            <div class="form-section">
                <div class="section-title my-2" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" id="insuranceCheckbox" class="form-check-input" style="margin: 0; cursor: pointer;">
                    <label for="insuranceCheckbox" style="margin: 0; cursor: pointer; flex: 1;">
                        <i class="fas fa-shield-alt"></i> Vehicle Insurance Information
                    </label>
                </div>

                <div id="insuranceContent" style="display: none;">
                

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="insurerName" class="form-label required">Insurer Name</label>
                        <input type="text" class="form-control" id="insurerName" placeholder="Enter insurer name">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="companyName" class="form-label required">Company Name</label>
                        <input type="text" class="form-control" id="companyName" placeholder="Enter company name">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="insuranceDate" class="form-label required">Insurance Date</label>
                        <input type="date" class="form-control" id="insuranceDate">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="expiryDate" class="form-label required">Expiry Date</label>
                        <input type="date" class="form-control" id="expiryDate">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="premiumAmount" class="form-label">Premium Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" id="premiumAmount" placeholder="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="insuranceNotes" class="form-label">Insurance Notes</label>
                        <textarea class="form-control" id="insuranceNotes" rows="3" placeholder="Enter insurance notes"></textarea>
                    </div>
                </div>
                </div>
            </div>

            <!-- BUTTON GROUP -->
            <div class="button-group">
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save"></i> Save
                </button>
                <button type="reset" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>

  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Define Vehicle Information - LBSNAA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --saffron: #FF9933;
            --white: #FFFFFF;
            --green: #138808;
            --light-bg: #F5F5DC;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-header {
            background: linear-gradient(135deg, var(--saffron) 0%, var(--green) 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 24px;
        }

        .form-header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.95;
        }

        .section-title {
            background-color: var(--saffron);
            color: white;
            padding: 12px 15px;
            border-radius: 4px;
            margin-top: 25px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 16px;
            border-left: 4px solid var(--green);
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 14px;
            background-color: #FFFEF0;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--saffron);
            box-shadow: 0 0 0 0.2rem rgba(255, 153, 51, 0.25);
        }

        .required::after {
            content: ' *';
            color: #dc3545;
            font-weight: bold;
        }

        .form-section {
            background-color: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .btn-save {
            background-color: var(--green);
            border-color: var(--green);
            color: white;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            background-color: #0d6106;
            border-color: #0d6106;
            color: white;
        }

        .btn-cancel {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            color: white;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }

        .form-check {
            display: flex;
            align-items: center;
        }

        .form-check-input:checked {
            background-color: var(--green);
            border-color: var(--green);
        }

        .form-check-input:focus {
            border-color: var(--saffron);
            box-shadow: 0 0 0 0.25rem rgba(255, 153, 51, 0.25);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .helper-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .form-header h2 {
                font-size: 20px;
            }

            .section-title {
                font-size: 14px;
                margin-top: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-save, .btn-cancel {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
  

   
  
    </div>
  </div>
@endsection
@push('scripts')
  <script>
        // Populate manufacture year dropdown
        const currentYear = new Date().getFullYear();
        const yearSelect = document.getElementById('manufactureYear');
        for (let i = currentYear; i >= currentYear - 50; i--) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            yearSelect.appendChild(option);
        }

        // Form submission
        document.getElementById('vehicleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Form submitted! (This is a demo - connect to your backend to save)');
        });

        // Form reset
        document.getElementById('vehicleForm').addEventListener('reset', function() {
            console.log('Form reset');
        });

        // Insurance checkbox toggle
        document.getElementById('insuranceCheckbox').addEventListener('change', function() {
            const insuranceContent = document.getElementById('insuranceContent');
            if (this.checked) {
                insuranceContent.style.display = 'block';
            } else {
                insuranceContent.style.display = 'none';
            }
        });
    </script>
@endpush
