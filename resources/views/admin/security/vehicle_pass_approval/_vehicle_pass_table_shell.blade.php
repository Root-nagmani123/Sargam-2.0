{{-- @include vars: tableId. Rows are loaded server-side via VehiclePassApprovalDataTable ajax. --}}
<div class="datatables">
    <div class="table-responsive">
        <table class="table text-nowrap align-middle mb-0 vehicle-pass-approval-table" id="{{ $tableId }}">
            <thead>
                <tr>
                    <th scope="col">Employee Name</th>
                    <th scope="col">Vehicle Number</th>
                    <th scope="col">Vehicle Type</th>
                    <th scope="col">Status</th>
                    <th scope="col">Vehicle Pass No</th>
                    <th scope="col">Employee ID</th>
                    <th scope="col">Applied On</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
