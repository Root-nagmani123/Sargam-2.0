@extends('admin.layouts.master')

@section('title', 'Member')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Member" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Member</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('member.create') }}" class="btn btn-primary">+ Add Member</a>
                                <a href="#" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#vertical-center-scroll-modal">Bulk Upload</a>

                            </div>
                        </div>
                    </div>
                    <!-- Vertically centered modal -->
                    <div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1"
                        aria-labelledby="vertical-center-modal" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header d-flex align-items-center">
                                    <h4 class="modal-title" id="myLargeModalLabel">
                                        Bulk Upload for member
                                    </h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="" method="POST">
                                        <label for="" class="form-label">Upload CSV</label>
                                        <input type="file" name="file" id="file" class="form-control">
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit"
                                        class="btn bg-success-subtle text-success  waves-effect text-start">
                                        Submit
                                    </button>
                                    <button type="button"
                                        class="btn bg-danger-subtle text-danger  waves-effect text-start"
                                        data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    {{ $dataTable->table() }}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection

@push('scripts')
{{ $dataTable->scripts() }}
@endpush