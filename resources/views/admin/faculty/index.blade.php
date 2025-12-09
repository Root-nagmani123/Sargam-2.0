@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('setup_content')
<div class="container-fluid">
    <!--<x-session_message />-->
    <div id="status-msg"></div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4 class="fw-semibold text-primary mb-0" style="color:#004a93 !important;">
                                Faculty
                            </h4>
                        </div>

                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-3">

                                <!-- Add Faculty -->
                                <a href="{{ route('faculty.create') }}"
                                    class="btn btn-primary d-flex align-items-center gap-1 shadow-sm"
                                    style="background-color:#004a93; border-color:#004a93;"
                                    aria-label="Add New Faculty">
                                    <span class="material-symbols-rounded fs-5">add</span>
                                    Add Faculty
                                </a>

                                <!-- Export Excel -->
                                <a href="{{ route('faculty.excel.export') }}"
                                    class="btn btn-outline-primary d-flex align-items-center gap-1 shadow-sm"
                                    style="border-color:#004a93; color:#004a93;" aria-label="Export Faculty Excel">
                                    <span class="material-symbols-rounded fs-5">export_notes</span>
                                    Export Excel
                                </a>
                                <a href="{{ route('faculty.printBlank') }}"  class="btn btn-success">
									<i class="material-icons">print</i> Print Blank Form
								</a>

                            </div>
                        </div>
                    </div>

                    <hr>
                    {!! $dataTable->table(['class' => 'table w-100']) !!}
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
@endsection
@push('scripts')
{!! $dataTable->scripts() !!}
@endpush
