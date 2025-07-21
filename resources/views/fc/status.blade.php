@extends('fc.layouts.master')

@section('title', 'Foundation Course Status | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main style="flex: 1;">
        <div class="container mt-5">
            <div class="text-center">
                <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Foundation Course Status</h4>
                <p class="text-muted" style="font-size: 20px;">
                    Check your current status in the Foundation Course.
                </p>
            </div>
            <!-- Tabs as Status Buttons -->
  <div class="container">
    <ul class="nav nav-tabs justify-content-center mb-4" id="statusTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active text-danger" id="not-responded-tab" data-bs-toggle="tab" data-bs-target="#not-responded" type="button" role="tab">Not Responded <span class="badge bg-danger">653</span></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link text-success" id="registered-tab" data-bs-toggle="tab" data-bs-target="#registered" type="button" role="tab">CSE 2024 Registered <span class="badge bg-success">137</span></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link text-primary" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab">Service wise List <span class="badge bg-primary">1</span></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link text-secondary" id="exemption-tab" data-bs-toggle="tab" data-bs-target="#exemption" type="button" role="tab">Applied for Exemption <span class="badge bg-secondary">59</span></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link text-warning" id="incomplete-tab" data-bs-toggle="tab" data-bs-target="#incomplete" type="button" role="tab">Incomplete <span class="badge bg-warning text-dark">160</span></button>
      </li>
    </ul>

    <div class="tab-content" id="statusTabsContent">
      <!-- Not Responded Tab -->
      <div class="tab-pane fade show active" id="not-responded" role="tabpanel">
        <div class="card shadow-sm">
          <div class="card-header bg-danger text-white fw-bold">List of Participants Who Have Not Responded</div>
          <div class="table-responsive">
            <table class="table table-bordered text-center m-0">
              <thead class="table-danger">
                <tr><th>S.No</th><th>Name</th><th>Service</th><th>Rank</th></tr>
              </thead>
              <tbody>
                <tr><td>1</td><td>MAYANK TRIPATHI</td><td>NOT APPLICABLE</td><td>10</td></tr>
                <tr><td>2</td><td>NISA UNNIRAJAN</td><td>NOT APPLICABLE</td><td>1000</td></tr>
                <tr><td>3</td><td>SHUBHAM AGARWAL</td><td>NOT APPLICABLE</td><td>1001</td></tr>
              </tbody>
            </table>
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
                <tr><th>S.No</th><th>Name</th><th>Service</th><th>Rank</th></tr>
              </thead>
              <tbody>
                <tr><td>1</td><td>Example User</td><td>IAS</td><td>1</td></tr>
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
                <tr><th>Service</th><th>Count</th></tr>
              </thead>
              <tbody>
                <tr><td>IAS</td><td>50</td></tr>
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
                <tr><th>S.No</th><th>Name</th><th>Reason</th></tr>
              </thead>
              <tbody>
                <tr><td>1</td><td>John Doe</td><td>Medical</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Incomplete Tab -->
      <div class="tab-pane fade" id="incomplete" role="tabpanel">
        <div class="card shadow-sm">
          <div class="card-header bg-warning text-dark fw-bold">Incomplete Registrations</div>
          <div class="table-responsive">
            <table class="table table-bordered text-center m-0">
              <thead class="table-warning">
                <tr><th>S.No</th><th>Name</th><th>Missing Fields</th></tr>
              </thead>
              <tbody>
                <tr><td>1</td><td>Amit Kumar</td><td>Photo, ID</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
        </div>
    </main>

@endsection