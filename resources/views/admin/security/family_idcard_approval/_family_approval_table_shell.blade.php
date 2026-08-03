{{-- @include vars: tableId. Rows are loaded server-side via FamilyIdCardApprovalDataTable ajax. --}}
<div class="table-responsive">
    <table class="table text-nowrap mb-0" id="{{ $tableId }}">
        <thead>
            <tr>
                <th>Submitted By</th>
                <th>Employee Type</th>
                <th>Employee ID</th>
                <th>Member Count</th>
                <th>Status</th>
                <th>Applied On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
