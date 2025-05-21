@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="MDO Escrot Exemption" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>MDO Escrot Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('mdo-escrot-exemption.create')}}" class="btn btn-primary">+ Add MDO Escrot Exemption</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Date</th>
                                    <th>Time From</th>
                                    <th>Time To</th>
                                    <th>Programme Name</th>
                                    <th>MDO Name</th>
                                    <th>Remarks</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($mdoEscotDuty) && count($mdoEscotDuty) > 0)
                                    @foreach ($mdoEscotDuty as $mdoEscot)
                                        <tr class="odd">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $mdoEscot->mdo_date ?? 'N/A' }}</td>
                                            <td>{{ $mdoEscot->Time_from ?? 'N/A' }}</td>
                                            <td>{{ $mdoEscot->Time_to ?? 'N/A' }}</td>
                                            <td>{{ optional($mdoEscot->courseMaster)->course_name ?? 'N/A' }}</td>
                                            <td>{{ optional($mdoEscot->mdoDutyTypeMaster)->mdo_duty_type_name ?? 'N/A' }}</td>
                                            <td>{{ $mdoEscot->Remark ?? 'N/A' }}</td>
                                            
                                        </tr>
                                    @endforeach
                                @endif

                            </tbody>
                        </table>
                        
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection