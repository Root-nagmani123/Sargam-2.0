{{-- @include vars: tableId. Rows are loaded server-side via EmployeeIdcardApproval2/3DataTable ajax. --}}
<div class="table-responsive">
    <table class="table text-nowrap align-middle mb-0" id="{{ $tableId }}">
        <thead class="table-primary">
            <tr>
                <th style="width:50px;" class="text-center">S.No.</th>
                <th>EMPLOYEE NAME</th>
                <th>DESIGNATION</th>
                <th>ID CARD NO</th>
                <th>ID TYPE</th>
                <th>REQUEST TYPE</th>
                <th style="width:70px;" class="text-center">PHOTO</th>
                <th>CONTACT NO</th>
                <th class="text-center">APPROVED/REJECT</th>
                <th>REQUEST DATE</th>
                <th>REQUESTED SECTION</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
