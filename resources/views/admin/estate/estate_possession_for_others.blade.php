@extends('admin.layouts.master')

@section('title', 'Estate Possession for Other - Sargam')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Estate Possession for Others"></x-breadcrum>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Estate Possession for Others</h2>
        <div>
            <a href="{{ route('admin.estate.update-meter-reading-of-other') }}" class="btn btn-link text-decoration-none me-2">Update Reading</a>
            <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-success btn-sm" title="Add">
                <i class="material-symbols-rounded">add</i>
            </a>
            <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-light btn-sm" title="Edit">
                <i class="material-symbols-rounded">edit</i>
            </a>
            <button class="btn btn-danger btn-sm" title="Delete">
                <i class="material-symbols-rounded">delete</i>
            </button>
        </div>
    </div>

    <!-- Info Card -->
    <div class="alert alert-info mb-4">
        <p class="mb-0">This page displays all Possession added in the system, and provides options to manage records such as add, edit, delete, excel upload, excel download, print etc.</p>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="estatePossessionTable">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="select_all">
                            </th>
                            <th>S.NO.</th>
                            <th>REQUEST ID</th>
                            <th>NAME</th>
                            <th>SECTION NAME</th>
                            <th>ESTATE NAME</th>
                            <th>UNIT TYPE</th>
                            <th>BUILDING NAME</th>
                            <th>UNIT SUB TYPE</th>
                            <th>HOUSE NO.</th>
                            <th>ALLOTMENT DATE</th>
                            <th>POSSESSION DATE</th>
                            <th>LAST MONTH ELECTRIC METER READING</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>1</td>
                            <td>oth-req-2</td>
                            <td>AMAR SINGH RANA</td>
                            <td>AMAR SINGH RANA</td>
                            <td>Behind Karamshilla Building</td>
                            <td>Residential</td>
                            <td>Alakhnanda Awas</td>
                            <td>Type -I</td>
                            <td>AA-04</td>
                            <td>2013-10-01</td>
                            <td>2014-04-30</td>
                            <td>24050</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>2</td>
                            <td>oth-req-5</td>
                            <td>PRITAM S PAWAR</td>
                            <td>MEDICAL CENTRE</td>
                            <td>Above Himachal Avas</td>
                            <td>Residential</td>
                            <td>Deodar -II</td>
                            <td>Type -II</td>
                            <td>DEO-04</td>
                            <td>2011-07-15</td>
                            <td>2014-04-30</td>
                            <td>7498</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>3</td>
                            <td>oth-req-2</td>
                            <td>AMAR SINGH RANA</td>
                            <td>AMAR SINGH RANA</td>
                            <td>Behind Karamshilla Building</td>
                            <td>Residential</td>
                            <td>Alakhnanda Awas</td>
                            <td>Type -I</td>
                            <td>AA-04</td>
                            <td>2013-10-01</td>
                            <td>2014-04-30</td>
                            <td>24050</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>4</td>
                            <td>oth-req-5</td>
                            <td>PRITAM S PAWAR</td>
                            <td>MEDICAL CENTRE</td>
                            <td>Above Himachal Avas</td>
                            <td>Residential</td>
                            <td>Deodar -II</td>
                            <td>Type -II</td>
                            <td>DEO-04</td>
                            <td>2011-07-15</td>
                            <td>2014-04-30</td>
                            <td>7498</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>5</td>
                            <td>oth-req-2</td>
                            <td>AMAR SINGH RANA</td>
                            <td>AMAR SINGH RANA</td>
                            <td>Behind Karamshilla Building</td>
                            <td>Residential</td>
                            <td>Alakhnanda Awas</td>
                            <td>Type -I</td>
                            <td>AA-04</td>
                            <td>2013-10-01</td>
                            <td>2014-04-30</td>
                            <td>24050</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>6</td>
                            <td>oth-req-5</td>
                            <td>PRITAM S PAWAR</td>
                            <td>MEDICAL CENTRE</td>
                            <td>Above Himachal Avas</td>
                            <td>Residential</td>
                            <td>Deodar -II</td>
                            <td>Type -II</td>
                            <td>DEO-04</td>
                            <td>2011-07-15</td>
                            <td>2014-04-30</td>
                            <td>7498</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>7</td>
                            <td>oth-req-2</td>
                            <td>AMAR SINGH RANA</td>
                            <td>AMAR SINGH RANA</td>
                            <td>Behind Karamshilla Building</td>
                            <td>Residential</td>
                            <td>Alakhnanda Awas</td>
                            <td>Type -I</td>
                            <td>AA-04</td>
                            <td>2013-10-01</td>
                            <td>2014-04-30</td>
                            <td>24050</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>8</td>
                            <td>oth-req-5</td>
                            <td>PRITAM S PAWAR</td>
                            <td>MEDICAL CENTRE</td>
                            <td>Above Himachal Avas</td>
                            <td>Residential</td>
                            <td>Deodar -II</td>
                            <td>Type -II</td>
                            <td>DEO-04</td>
                            <td>2011-07-15</td>
                            <td>2014-04-30</td>
                            <td>7498</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>9</td>
                            <td>oth-req-2</td>
                            <td>AMAR SINGH RANA</td>
                            <td>AMAR SINGH RANA</td>
                            <td>Behind Karamshilla Building</td>
                            <td>Residential</td>
                            <td>Alakhnanda Awas</td>
                            <td>Type -I</td>
                            <td>AA-04</td>
                            <td>2013-10-01</td>
                            <td>2014-04-30</td>
                            <td>24050</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>10</td>
                            <td>oth-req-5</td>
                            <td>PRITAM S PAWAR</td>
                            <td>MEDICAL CENTRE</td>
                            <td>Above Himachal Avas</td>
                            <td>Residential</td>
                            <td>Deodar -II</td>
                            <td>Type -II</td>
                            <td>DEO-04</td>
                            <td>2011-07-15</td>
                            <td>2014-04-30</td>
                            <td>7498</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>3</td>
                            <td>oth-req-2</td>
                            <td>AMAR SINGH RANA</td>
                            <td>AMAR SINGH RANA</td>
                            <td>Behind Karamshilla Building</td>
                            <td>Residential</td>
                            <td>Alakhnanda Awas</td>
                            <td>Type -I</td>
                            <td>AA-04</td>
                            <td>2013-10-01</td>
                            <td>2014-04-30</td>
                            <td>24050</td>
                            <td>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.estate.possession-view') }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-responsive {
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #estatePossessionTable_wrapper .dataTables_scrollBody,
    #estatePossessionTable_wrapper .dataTables_scroll {
        max-width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .estate-possession-table-wrap,
        #estatePossessionTable_wrapper .dataTables_scrollBody {
            max-height: 60vh;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(document).ready(function() {
        let deleteUrl = '';

        $(document).on('click', '.btn-delete-possession', function(e) {
            e.preventDefault();
            deleteUrl = $(this).data('url');
            $('#deletePossessionModal').modal('show');
        });

        $('#confirmDeleteBtn').on('click', function() {
            if (!deleteUrl) return;
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {
                    $('#deletePossessionModal').modal('hide');
                    if (response.success) {
                        $('#estatePossessionTable').DataTable().ajax.reload(null, false);
                        const alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">' + response.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#possessionCardBody').find('.alert-success').remove();
                        $('#possessionCardBody').prepend(alert);
                        setTimeout(function() { $('.alert-success').fadeOut(); }, 3000);
                    }
                },
                error: function(xhr) {
                    $('#deletePossessionModal').modal('hide');
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete.';
                    const alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#possessionCardBody').find('.alert-danger').remove();
                    $('#possessionCardBody').prepend(alert);
                }
            });
            deleteUrl = '';
        });
    });
    </script>
@endpush
