@extends('admin.layouts.master')

@section('title', 'Estate Bill Report for Print - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
<x-breadcrum title="Estate Bill Report for Print"></x-breadcrum>

    <!-- Report Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Institution Banner -->
            <div class="bg-primary text-white text-center py-3 mb-4 rounded">
                <h4 class="mb-0 text-uppercase">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION MUSSOORIE ESTATE</h4>
            </div>

            <!-- Bill Details -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Bill No:</td>
                            <td><strong>39701</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Name of Employee:</td>
                            <td><strong>LAL BAHADUR SHASTRI</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Employee Type:</td>
                            <td><strong>LDC(HCL)</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">From Date:</td>
                            <td><strong>01.12.2024</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Month:</td>
                            <td><strong>January 2025</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Designation:</td>
                            <td><strong>Multi Staff Tasking</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">House No:</td>
                            <td><strong>BH-14</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">To Date:</td>
                            <td><strong>31.01.2025</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Motor One Section -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">MOTOR ONE:</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Motor To:</td>
                                    <td><strong>50302</strong></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Previous Reading:</td>
                                    <td><strong>50250</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Current Reading:</td>
                                    <td><strong>50302</strong></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Consumed Unit:</td>
                                    <td><strong>491</strong></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Electricity Bill:</td>
                                    <td><strong>1056.4</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Total Consumed Unit:</td>
                            <td><strong>491</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Total Electricity Bill:</td>
                            <td><strong>1,060.40</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Licence Fee:</td>
                            <td><strong>300.00</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Water Charge:</td>
                            <td><strong>20.0</strong></td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold fs-5">Grand Total Bill:</td>
                            <td><strong class="text-primary fs-5">1,420.40</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
