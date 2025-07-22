@extends('fc.layouts.master')

@section('title', 'Foundation Course Status | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<style>
  .card-body{
    padding: 0.5rem !important;
  }
</style>
<main style="flex: 1;">
    <div class="container-fluid mt-5">
        <!-- Page Title -->
        <div class="text-center mb-4">
            <h2 style="color: #004a93; font-size: 42px; font-weight: 700;">105<sup>th</sup> Foundation Course</h2>
            <p class="text-muted" style="font-size: 20px;">
                <i class="bi bi-calendar3"></i> (June 18th, 2025 – December 31st, 2025)
            </p>
        </div>
        <!-- Status Overview -->
        <div class="row mb-4">
          <h4 class="fw-bold text-muted text-center bg-primary-subtle text-primary p-2">Status count</h4>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Not Responded</h5>
                        <p class="card-text">653 Participants</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">CSE 2024 Registered</h5>
                        <p class="card-text">137 Participants</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Service wise List</h5>
                        <p class="card-text">1 Service</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h5 class="card-title">Applied for Exemption</h5>
                        <p class="card-text">59 Participants</p>
                    </div>
                </div>    
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white" style="background-color: #ffa500 !important;">
                    <div class="card-body text-center">
                        <h5 class="card-title">Incomplete Registrations</h5>
                        <p class="card-text">160 Participants</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs as Status Buttons -->
        
        <!-- Tabs as Status Buttons -->
        <div class="container-fluid">
            <ul class="nav nav-tabs justify-content-start mb-4" id="statusTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-danger" id="not-responded-tab" data-bs-toggle="tab"
                        data-bs-target="#not-responded" type="button" role="tab">Not Responded</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-success" id="registered-tab" data-bs-toggle="tab"
                        data-bs-target="#registered" type="button" role="tab">CSE 2024 Registered</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-primary" id="service-tab" data-bs-toggle="tab"
                        data-bs-target="#service" type="button" role="tab">Service wise List</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-secondary" id="exemption-tab" data-bs-toggle="tab"
                        data-bs-target="#exemption" type="button" role="tab">Applied for Exemption</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-warning" id="incomplete-tab" data-bs-toggle="tab"
                        data-bs-target="#incomplete" type="button" role="tab" style="color: #ffa500 !important;">Incomplete</button>
                </li>
            </ul>

            <div class="tab-content" id="statusTabsContent">
                <!-- Not Responded Tab -->
                <div class="tab-pane fade show active" id="not-responded" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white fw-bold">Not Responded Participants</div>
                      <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center m-0 text-nowrap align-middle table-hover 
                                    table-striped">
                                <thead class="table-danger">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Name</th>
                                        <th>Service</th>
                                        <th>Rank</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>MAYANK TRIPATHI</td>
                                        <td>NOT APPLICABLE</td>
                                        <td>10</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                      </div>
                        
                        
                    </div>
                </div>

                <!-- CSE 2024 Registered Tab -->
                <div class="tab-pane fade" id="registered" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white fw-bold">CSE 2024 Registered</div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center m-0">
                                <thead class="table-success">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Name</th>
                                        <th>Service</th>
                                        <th>Rank</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Example User</td>
                                        <td>IAS</td>
                                        <td>1</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Service wise Tab -->
                <div class="tab-pane fade" id="service" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white fw-bold">Service-wise List</div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center m-0">
                                <thead class="table-primary">
                                    <tr>
                                      <th>Sr.No</th>
                                        <th>Service</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                      <td>1</td>
                                        <td>IAS</td>
                                        <td>50</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Exemption Tab -->
                <div class="tab-pane fade" id="exemption" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white fw-bold">Applied for Exemption</div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center m-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Name</th>
                                        <th>Service</th>
                                        <th>Rank</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>John Doe</td>
                                        <td>IPS</td>
                                        <td>1</td>
                                        <td>Medical</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Incomplete Tab -->
                <div class="tab-pane fade" id="incomplete" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-white fw-bold" style="background-color: #ffa500 !important;">Incomplete Registrations</div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center m-0">
                                <thead class="table-warning">
                                    <tr>
                                        <th>S.No</th>
                                        <th>Name</th>
                                         <th>Service</th>
                                          <th>Rank</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Amit Kumar</td>
                                        <td>IAS</td>
                                        <td>2</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </>
</main>

@endsection