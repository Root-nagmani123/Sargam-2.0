@extends('admin.layouts.master')

@section('title', 'Joining Documents')

@section('content')
    <x-session_message />
    <x-breadcrum title="Joining Documents" />
    <form action="{{ route('fc.joining.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Administration Section Related Documents -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="fw-bold text-primary mb-3">Administration Section Related Documents</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle table-hover table-striped text-nowrap">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Sr.No.</th>
                                <th>Document Title</th>
                                <th>Upload</th>
                                <th>View Uploaded Forms</th>
                                <th>Sample Documents</th>
                                <th>Download Forms</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td>Family Details Form (Form - 3)</td>
                                <td><input type="file" name="admin_family_details_form" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_family_details_form))
                                        <a href="{{ asset('storage/' . $documents->admin_family_details_form) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_family_details_form))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_family_details_form)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if (!empty($documents->admin_family_details_form))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="text-center">2</td>
                                <td colspan="6"><strong>Declaration of Close Relation</strong></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Nationality/Domicile Declaration</td>
                                <td><input type="file" name="admin_close_relation_declaration" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_close_relation_declaration))
                                        <a href="{{ asset('storage/' . $documents->admin_close_relation_declaration) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_close_relation_declaration))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_close_relation_declaration)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_close_relation_declaration))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="text-center">3</td>
                                <td>Dowry Declaration</td>
                                <td><input type="file" name="admin_dowry_declaration" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_dowry_declaration))
                                        <a href="{{ asset('storage/' . $documents->admin_dowry_declaration) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_dowry_declaration))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_dowry_declaration)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_dowry_declaration))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="text-center">4</td>
                                <td>Marital Status Declaration</td>
                                <td><input type="file" name="admin_marital_status" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_marital_status))
                                        <a href="{{ asset('storage/' . $documents->admin_marital_status) }}" target="_blank"
                                            class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>

                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_marital_status))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_marital_status)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_marital_status))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>

                            </tr>

                            <tr>
                                <td class="text-center">5</td>
                                <td>Home Town Declaration</td>
                                <td><input type="file" name="admin_home_town_declaration" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_home_town_declaration))
                                        <a href="{{ asset('storage/' . $documents->admin_home_town_declaration) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_home_town_declaration))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_home_town_declaration)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_home_town_declaration))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="text-center">6</td>
                                <td colspan="6"><strong>Property Declaration</strong></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>6-A: Immovable Property</td>
                                <td><input type="file" name="admin_property_immovable" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_immovable))
                                        <a href="{{ asset('storage/' . $documents->admin_property_immovable) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_immovable))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_property_immovable)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_immovable))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>6-B: Movable Property</td>
                                <td><input type="file" name="admin_property_movable" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_movable))
                                        <a href="{{ asset('storage/' . $documents->admin_property_movable) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_movable))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_property_movable)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_movable))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>6-C: Debts and Liabilities</td>
                                <td><input type="file" name="admin_property_liabilities" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_liabilities))
                                        <a href="{{ asset('storage/' . $documents->admin_property_liabilities) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_liabilities))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_property_liabilities)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_property_liabilities))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="text-center">7</td>
                                <td colspan="6"><strong>Surety Bond</strong></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>IAS/IPS/IFoS Bond</td>
                                <td><input type="file" name="admin_bond_ias_ips_ifos" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_bond_ias_ips_ifos))
                                        <a href="{{ asset('storage/' . $documents->admin_bond_ias_ips_ifos) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_bond_ias_ips_ifos))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_bond_ias_ips_ifos)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_bond_ias_ips_ifos))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>

                            </tr>
                            <tr>
                                <td></td>
                                <td>Other Services Bond</td>
                                <td><input type="file" name="admin_bond_other_services" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_bond_other_services))
                                        <a href="{{ asset('storage/' . $documents->admin_bond_other_services) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_bond_other_services))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_bond_other_services)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_bond_other_services))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="text-center">8</td>
                                <td>Other Documents</td>
                                <td><input type="file" name="admin_other_documents" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_other_documents))
                                        <a href="{{ asset('storage/' . $documents->admin_other_documents) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_other_documents))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_other_documents)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_other_documents))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Form of OATH / Affirmation</td>
                                <td><input type="file" name="admin_oath_affirmation" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_oath_affirmation))
                                        <a href="{{ asset('storage/' . $documents->admin_oath_affirmation) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_oath_affirmation))
                                        <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_oath_affirmation)) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_oath_affirmation))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Certificate of Assumption of Charge</td>
                                <td><input type="file" name="admin_certificate_of_charge" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_certificate_of_charge))
                                        <a href="{{ asset('storage/' . $documents->admin_certificate_of_charge) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center"><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_certificate_of_charge))
                                        <a href="{{ asset('storage/' . $documents->admin_certificate_of_charge) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->admin_certificate_of_charge))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Accounts Section Related Documents -->
        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold text-primary mb-3">Accounts Section Related Documents</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center table-hover table-striped text-nowrap">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Sr. No.</th>
                                <th>Document Title</th>
                                <th>Upload</th>
                                <th>View Uploaded Forms</th>
                                <th>Sample Documents</th>
                                <th>Download Forms</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td class="text-start" colspan="6"><strong>Nomination under CGEGIS</strong></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Form-7 (Unmarried) or Form-8 (Married)</td>
                                <td><input type="file" name="accounts_nomination_form" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->accounts_nomination_form))
                                        <a href="{{ asset('storage/' . $documents->accounts_nomination_form) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td class="text-center">
                                    @if (!empty($documents->accounts_nomination_form))
                                        <a href="{{ asset('storage/' . $documents->accounts_nomination_form) }}" download
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->accounts_nomination_form))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>NPS Registration Form</td>
                                <td><input type="file" name="accounts_nps_registration" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->accounts_nps_registration))
                                        <a href="{{ asset('storage/' . $documents->accounts_nps_registration) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td>
                                    @if (!empty($documents->accounts_nps_registration))
                                        <a href="{{ asset('storage/' . $documents->accounts_nps_registration) }}" download
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->accounts_nps_registration))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>

                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Employee Information Sheet</td>
                                <td><input type="file" name="accounts_employee_info_sheet" class="form-control"></td>
                                <td class="text-center">
                                    @if (!empty($documents->accounts_employee_info_sheet))
                                        <a href="{{ asset('storage/' . $documents->accounts_employee_info_sheet) }}"
                                            target="_blank" class="btn btn-link p-0">View</a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td><a href="#" class="btn btn-link p-0">View Sample</a></td>
                                <td>
                                    @if (!empty($documents->accounts_employee_info_sheet))
                                        <a href="{{ asset('storage/' . $documents->accounts_employee_info_sheet) }}"
                                            download class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    @else
                                        <span class="text-muted">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($documents->accounts_employee_info_sheet))
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="text-end mb-4">
            <button type="submit" class="text btn btn-primary">Submit</button>
        </div>
    </form>
@endsection
