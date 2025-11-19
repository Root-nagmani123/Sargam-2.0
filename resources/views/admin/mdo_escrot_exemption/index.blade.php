@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('content')
<style>
/* Header style - deep red + rounded corners */
table.mdodutytable thead th {
    background-color: #b32826 !important;
    color: #fff !important;
    font-weight: 600 !important;
    border: none !important;
    padding: 14px;
    text-align: center !important;
}

table.mdodutytable thead th:first-child {
    border-top-left-radius: 12px;
}

table.mdodutytable thead th:last-child {
    border-top-right-radius: 12px;
}

/* Body rows - clean white, no borders */
table.mdodutytable tbody tr {
    border: none !important;
}

table.mdodutytable tbody td {
    border: none !important;
    padding: 18px 12px !important;
    text-align: center !important;
    font-size: 15px;
    color: #000;
}

/* Remove DataTable default border */
table.dataTable.no-footer {
    border-bottom: none !important;
}

/* Remove row hover highlight (optional) */
table.mdodutytable tbody tr:hover {
    background: #f8f8f8 !important;
}
</style>
    <div class="container-fluid">

        <div class="datatables">
            <!-- start Zero Configuration -->
            <div class="card" style="border-left: 4px solid #004a93;">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-6">
                                <h4>MDO Escrot Exemption</h4>
                            </div>
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{route('mdo-escrot-exemption.create')}}" class="btn btn-primary">+ Add MDO
                                        Escrot Exemption</a>
                                </div>
                            </div>
                        </div>
                        <hr>
                        
                        {!! $dataTable->table([
    'class' => 'table table-hover align-middle w-100 mdodutytable table-striped'
]) !!}

                        
                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>

<!-- MDO Escort Exemption Modal -->
<div class="modal fade" id="mdoExemptionModal" tabindex="-1" aria-labelledby="mdoExemptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="mdoExemptionModalLabel">Loading...</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="mdoExemptionModalBody">
              <div class="row mb-4">
                <div class="col-md-8">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="material-icons me-2">person</i>
                        <div>
                            <strong>Student Name:</strong>
                            <span class="ms-2 fs-5" id="studentName">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('mdo-escrot-exemption.update') }}" method="POST" id="mdoDutyTypeForm">
                @csrf
                <input type="hidden" name="pk" id="mdoDutyTypePk" value="">
                <div class="row">

                    

                    <div class="col-md-3">
                        <div class="mb-3">

                            <x-select name="mdo_duty_type_master_pk" label="Duty Type :" formLabelClass="form-label"
                                formSelectClass="select2 "
                                value="" :options="$MDODutyTypeMaster" labelRequired="true" />
                        </div>

                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <x-input type="date" name="mdo_date" label="Select Date & Time :"
                                placeholder="Select Date & Time : " formLabelClass="form-label" 
                                value="" labelRequired="true"/>
                        </div>
                    </div>

                    <div class="col-md-3">

                        <x-input type="time" name="Time_from" label="From Time :" placeholder="From Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="" />

                    </div>
                    <div class="col-md-3">

                        <x-input type="time" name="Time_to" label="To Time :" placeholder="To Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="" />

                    </div>
                </div>
                <hr>
                
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">save</i>
                        Update
                    </button>
                    <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
                        <i class="material-icons menu-icon">arrow_back</i>
                        Back
                    </a>
                </div>

            </form>
            </div>

        </div>
    </div>
</div>
<script>
$(document).on('click', '.openMdoExemptionModal', function() {

    var pk = $(this).data('id');

    $('#studentName').text('Loading...');
    $('#mdoDutyTypePk').val('');

    $.ajax({
        url: '/admin/mdo-escrot-exemption/details/' + pk,
        type: 'GET',
        success: function(response) {
            $('#studentName').text(response.student_name || '—');
            $('#mdoDutyTypePk').val(response.pk || '');
        },
        error: function() {
            $('#studentName').text('Error loading data');
        }
    });

    $('#mdoExemptionModal').modal('show');
});
</script>

@endsection
@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush