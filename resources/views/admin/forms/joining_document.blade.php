 @extends('admin.layouts.master')

 @section('title', 'Joining Documents')

 @section('content')
     <div class="container-fluid mt-4">
         {{-- Show validation error messages --}}
         @if ($errors->any())
             <div class="alert alert-danger alert-dismissible fade show" role="alert">
                 <ul class="mb-0">
                     @foreach ($errors->all() as $error)
                         <li>{{ $error }}</li>
                     @endforeach
                 </ul>
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
         @endif


         <x-session_message />
         <x-breadcrum title="Joining Documents" />
         <div class="card card-body mb-4" style="border-left:4px solid #004a93;">
             <ul class="mb-0">
                 <li>
                     All the documents are compulsory to fill up and upload.
                     <ol class="mt-2">
                         <li>Download the forms.</li>
                         <li>Fill up all the required fields / details and duly sign the document and upload it.</li>
                         <li>Only PDF format is allowed for upload.</li>
                         <li>Maximum file size allowed is 1 MB.</li>
                     </ol>
                 </li>
                 <li>
                     {{-- The FAQ for the following forms:
            <a href="#" class="text-primary text-decoration-underline">Click here</a> --}}
                 </li>
             </ul>
         </div>

         <form action="{{ route('fc.joining.upload') }}" method="POST" enctype="multipart/form-data">
             @csrf

             <!-- Administration Section Related Documents -->
             <div class="card mb-4" style="border-left:4px solid #004a93;">
                 <div class="card-body">
                     <h5 class="fw-bold text-primary mb-3">Administration Section Related Documents</h5>
                     <div class="table-responsive">
                         <table class="table table-bordered align-middle table-hover table-striped">
                             <thead class="table-light text-center">
                                 <tr>
                                     <th class="col">Sr.No.</th>
                                     <th class="col">Document Title</th>
                                     <th class="col">Upload</th>
                                     <th class="col">View Uploaded Forms</th>
                                     <th class="col">Sample Documents</th>
                                     <th class="col">Download Forms</th>
                                     <th class="col">Status</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <tr>
                                     <td class="text-center">1</td>
                                     <td> Family Details Form (Form - 3) of Rules 54(12) of CCS (Pension) Rules, 1972</td>
                                     <td><input type="file" name="admin_family_details_form" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_family_details_form))
                                             <a href="{{ asset('storage/' . $documents->admin_family_details_form) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_family_details1.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">2</td>
                                     <td colspan="6"><strong>Declaration of Close Relation (two copies)</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>
                                         a) National of or are domiciled in other countries and
                                         <br>
                                         b) Residing in India, who are non-Indian origin
                                     </td>
                                     <td><input type="file" name="admin_close_relation_declaration" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_close_relation_declaration))
                                             <a href="{{ asset('storage/' . $documents->admin_close_relation_declaration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_close_relations_2.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">3</td>
                                     <td>Dowry Declaration - Declaration under Rule 13 of CCS (Conduct)
                                         Rule 1964 (two copies)</td>
                                     <td><input type="file" name="admin_dowry_declaration" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_dowry_declaration))
                                             <a href="{{ asset('storage/' . $documents->admin_dowry_declaration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_dowry_declaration3.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">4</td>
                                     <td>Marital Status - Declaration under Rule 13 of CCS (Conduct) Rule
                                         1964 (two copies)</td>
                                     <td><input type="file" name="admin_marital_status" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_marital_status))
                                             <a href="{{ asset('storage/' . $documents->admin_marital_status) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>

                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_marital_declaration4.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>

                                 </tr>

                                 <tr>
                                     <td class="text-center">5</td>
                                     <td>Home Town Declaration (two copies)</td>
                                     <td><input type="file" name="admin_home_town_declaration" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_home_town_declaration))
                                             <a href="{{ asset('storage/' . $documents->admin_home_town_declaration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_home_town5.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">6</td>
                                     <td colspan="6"><strong> Declaration of Movable, Immovable and valuable property on
                                             first appointment (two copies)</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>6-A: Statement of Immovable Property on first appointment</td>
                                     <td><input type="file" name="admin_property_immovable" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_immovable))
                                             <a href="{{ asset('storage/' . $documents->admin_property_immovable) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_immovable_property6a.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>6-B: Statement of Movable Property on first appointment</td>
                                     <td><input type="file" name="admin_property_movable" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_movable))
                                             <a href="{{ asset('storage/' . $documents->admin_property_movable) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_movable_property6b.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>6-C: Statement of Debts and Other Liabilities on first
                                         appointment</td>
                                     <td><input type="file" name="admin_property_liabilities" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_liabilities))
                                             <a href="{{ asset('storage/' . $documents->admin_property_liabilities) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_debts_other_liabilities6c.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">7</td>
                                     <td colspan="6"><strong>Surety Bond-for</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Surety Bond for IAS or IPS or IFoS (whichever is applicable)</td>
                                     <td><input type="file" name="admin_bond_ias_ips_ifos" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_ias_ips_ifos))
                                             <a href="{{ asset('storage/' . $documents->admin_bond_ias_ips_ifos) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_surety_bond_iasips7a.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>

                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Surety Bond for other services (other than All India
                                         Services)
                                         (if applicable)</td>
                                     <td><input type="file" name="admin_bond_other_services" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_other_services))
                                             <a href="{{ asset('storage/' . $documents->admin_bond_other_services) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_surety_bond_other_services7b.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">8</td>
                                     <td><strong>Other Documents</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Form of OATH / Affirmation</td>
                                     <td><input type="file" name="admin_oath_affirmation" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_oath_affirmation))
                                             <a href="{{ asset('storage/' . $documents->admin_oath_affirmation) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_main_assumption_charge.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Certificate of Assumption of Charge</td>
                                     <td><input type="file" name="admin_certificate_of_charge" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_certificate_of_charge))
                                             <a href="{{ asset('storage/' . $documents->admin_certificate_of_charge) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_main_assumption_charge.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>

             <!-- Accounts Section Related Documents -->
             <div class="card" style="border-left:4px solid #004a93;">
                 <div class="card-body">
                     <h5 class="fw-bold text-primary mb-3">Accounts Section Related Documents</h5>
                     <div class="table-responsive">
                         <table
                             class="table table-bordered align-middle table-hover table-striped">
                             <thead class="table-light">
                                 <tr>
                                     <th class="col">Sr.No.</th>
                                     <th class="col">Document Title</th>
                                     <th class="col">Upload</th>
                                     <th class="col">View Uploaded Forms</th>
                                     <th class="col">Sample Documents</th>
                                     <th class="col">Download Forms</th>
                                     <th class="col">Status</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <tr>
                                     <td class="text-start">1</td>
                                     <td class="text-start" colspan="6"><strong>Nomination for benefits
                                             under the Central Government Employees Group Insurance
                                             Scheme, 1980</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>a) Form-7 (if Unmarried) or ii) Form-8 (if Married)</td>
                                     <td><input type="file" name="accounts_nomination_form" class="form-control"></td>
                                     <td class="text-start">
                                         @if (!empty($documents->accounts_nomination_form))
                                             <a href="{{ asset('storage/' . $documents->accounts_nomination_form) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td><a href="{{ asset('admin_assets/sample/joining_documents/sample_close_relations_2.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_nomination_form))
                                             <a href="{{ asset('storage/' . $documents->accounts_nomination_form) }}"
                                                 download class="btn btn-sm btn-outline-primary">
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td>2</td>
                                     <td>National Pensions System (NPS) - subscription Registration Form</td>
                                     <td><input type="file" name="accounts_nps_registration" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_nps_registration))
                                             <a href="{{ asset('storage/' . $documents->accounts_nps_registration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td><a href="{{ asset('admin_assets/sample/joining_documents/sample_nps_form10.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td>
                                         @if (!empty($documents->accounts_nps_registration))
                                             <a href="{{ asset('storage/' . $documents->accounts_nps_registration) }}"
                                                 download class="btn btn-sm btn-outline-primary">
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
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>

                                 </tr>
                                 <tr>
                                     <td>3</td>
                                     <td>Employee Information Sheet Form</td>
                                     <td><input type="file" name="accounts_employee_info_sheet" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_employee_info_sheet))
                                             <a href="{{ asset('storage/' . $documents->accounts_employee_info_sheet) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td><a href="{{ asset('admin_assets/sample/joining_documents/sample_employee_information11.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
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
                                             <span class="badge bg-warning ">Pending</span>
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
     </div>
 @endsection
 {{-- <script>
    document.addEventListener('DOMContentLoaded', function () {
        const maxSize = 1024 * 1024; // 1MB
        const allowedType = 'application/pdf';

        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function () {
                const file = this.files[0];

                if (file) {
                    if (file.size > maxSize) {
                        alert(`"${file.name}" exceeds the 1MB size limit.`);
                        this.value = ''; // Clear the file input
                        return;
                    }

                    if (file.type !== allowedType) {
                        alert(`"${file.name}" must be a PDF file.`);
                        this.value = ''; // Clear the file input
                        return;
                    }
                }
            });
        });
    });
</script> --}}

 <script>
     document.addEventListener('DOMContentLoaded', function() {
         const maxSize = 1024 * 1024; // 1MB
         const allowedType = 'application/pdf';

         document.querySelectorAll('input[type="file"]').forEach(input => {
             // Create error container just after each file input
             const errorDiv = document.createElement('div');
             errorDiv.className = 'text-danger mt-1 fw-semibold small';
             input.parentNode.appendChild(errorDiv);

             input.addEventListener('change', function() {
                 const file = this.files[0];
                 errorDiv.textContent = ''; // Clear old errors

                 if (file) {
                     if (file.size > maxSize) {
                         errorDiv.textContent = `"${file.name}" exceeds the 1MB size limit.`;
                         this.value = ''; // Clear file input
                         return;
                     }

                     if (file.type !== allowedType) {
                         errorDiv.textContent = `"${file.name}" must be a PDF file.`;
                         this.value = ''; // Clear file input
                         return;
                     }
                 }
             });
         });
     });
 </script>
