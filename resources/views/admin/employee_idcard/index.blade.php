@extends('admin.layouts.master')
@section('title', 'Employee ID Card Request - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid idcard-index-page">
    <!-- Breadcrumb + Search (reference: Setup > User Management, search icon right) -->
    <x-breadcrum title="Request Employee ID Card"></x-breadcrum>

    <div class="card border border-body-secondary rounded-4 shadow-sm idcard-index-card overflow-hidden">
        <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 p-3 p-md-4 border border-body-secondary rounded-4 bg-body shadow-sm">
        <ul class="nav nav-pills gap-1 idcard-index-tabs bg-body-tertiary p-1 rounded-3 shadow-sm" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center transition-opacity" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-panel" type="button" role="tab" aria-controls="active-panel" aria-selected="true">
                    Active
                    @if($activeRequests->count() > 0)
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis ms-2">{{ $activeRequests->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center" id="duplication-tab" data-bs-toggle="tab" data-bs-target="#duplication-panel" type="button" role="tab" aria-controls="duplication-panel" aria-selected="false">
                    Duplication
                    @if($duplicationRequests->count() > 0)
                        <span class="badge rounded-pill text-bg-warning ms-2">{{ $duplicationRequests->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center" id="extension-tab" data-bs-toggle="tab" data-bs-target="#extension-panel" type="button" role="tab" aria-controls="extension-panel" aria-selected="false">
                    Extension
                    @if($extensionRequests->count() > 0)
                        <span class="badge rounded-pill text-bg-info ms-2">{{ $extensionRequests->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-panel" type="button" role="tab" aria-controls="archive-panel" aria-selected="false">
                    Archive
                    @if($archivedRequests->count() > 0)
                        <span class="badge rounded-pill text-bg-secondary ms-2">{{ $archivedRequests->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-success-subtle border border-success-subtle text-success-emphasis dropdown-toggle d-flex align-items-center gap-2 px-4 py-2 rounded-3 shadow-sm focus-ring" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="material-icons material-symbols-rounded fs-5">download</i>
                    Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 py-2 dropdown-menu-lg-end" aria-labelledby="exportDropdown">
                    <li><h6 class="dropdown-header text-body-secondary small text-uppercase fw-semibold">Active Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'active', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success fs-6">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'active', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info fs-6">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'active', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger fs-6">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-body-secondary small text-uppercase fw-semibold">Archived Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'archive', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success fs-6">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'archive', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info fs-6">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'archive', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger fs-6">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-body-secondary small text-uppercase fw-semibold">All Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'all', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success fs-6">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'all', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info fs-6">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'all', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger fs-6">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('admin.employee_idcard.create') }}" class="btn btn-primary px-4 py-2 rounded-3 d-flex align-items-center gap-2 shadow-sm focus-ring">
                <i class="material-icons material-symbols-rounded fs-5">add</i>
                Generate New ID Card
            </a>
        </div>
    </div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle idcard-index-table" id="activeIdcardTable">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>ID Card</th>
                                    <th>Request date</th>
                                    <th>Employee Name</th>
                                    <th>Designation</th>
                                    <th>Card Type</th>
                                    <th>Request For</th>
                                    <th>Duplication</th>
                                    <th>Extension</th>
                                    <th>Valid Upto</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeRequests as $index => $request)
                                    <tr data-request-id="{{ $request->id }}" class="align-middle">
                                        <td class="fw-medium ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            @if($request->photo)
                                                <a href="{{ asset('storage/' . $request->photo) }}" target="_blank" class="d-inline-block rounded-2 overflow-hidden shadow-sm">
                                                    <img src="{{ asset('storage/' . $request->photo) }}" alt="ID Card" class="rounded-2 object-fit-cover" style="width:40px;height:50px;">
                                                </a>
                                            @else
                                                <span class="text-body-tertiary">--</span>
                                            @endif
                                        </td>
                                        <td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td>
                                        <td>{{ $request->name }}</td>
                                        <td>{{ $request->designation ?? '--' }}</td>
                                        <td>{{ $request->card_type ?? '--' }}</td>
                                        <td>{{ $request->request_for ?? '--' }}</td>
                                        <td>
                                            <a href="#" class="amend-dup-ext-btn text-decoration-none" data-request-id="{{ $request->id }}" data-type="duplication" data-name="{{ $request->name }}" data-designation="{{ $request->designation ?? '--' }}" data-duplication="{{ $request->duplication_reason ?? '' }}" data-extension="{{ $request->id_card_valid_upto ?? '' }}" data-valid-from="{{ $request->id_card_valid_from ?? '' }}" data-id-number="{{ $request->id_card_number ?? '' }}" data-request-for="{{ $request->request_for }}" data-created="{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}" data-status="{{ $request->status ?? '--' }}" data-show-url="{{ route('admin.employee_idcard.show', $request->id) }}">
                                                @if(in_array($request->request_for, ['Replacement', 'Duplication']) && $request->duplication_reason)
                                                    @php $dupBadge = match($request->duplication_reason ?? '') { 'Lost' => 'danger', 'Damage' => 'warning', 'Expired Card' => 'info', default => 'secondary' }; @endphp
                                                    <span class="badge bg-{{ $dupBadge }} text-dark">{{ $request->duplication_reason }}</span>
                                                @else
                                                    <span class="text-primary">Duplication</span>
                                                @endif
                                            </a>
                                        </td>
                                        <td>
                                            <a href="#" class="amend-dup-ext-btn text-decoration-none" data-request-id="{{ $request->id }}" data-type="extension" data-name="{{ $request->name }}" data-designation="{{ $request->designation ?? '--' }}" data-duplication="{{ $request->duplication_reason ?? '' }}" data-extension="{{ $request->id_card_valid_upto ?? '' }}" data-valid-from="{{ $request->id_card_valid_from ?? '' }}" data-id-number="{{ $request->id_card_number ?? '' }}" data-request-for="{{ $request->request_for }}" data-created="{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}" data-status="{{ $request->status ?? '--' }}" data-show-url="{{ route('admin.employee_idcard.show', $request->id) }}">
                                                @if($request->request_for == 'Extension' && $request->id_card_valid_upto)
                                                    <span class="badge bg-info">{{ $request->id_card_valid_upto }}</span>
                                                @else
                                                    <span class="text-primary">Extension</span>
                                                @endif
                                            </a>
                                        </td>
                                        <td>{{ $request->id_card_valid_upto ?? '--' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($request->status ?? '') {
                                                    'Pending' => 'warning',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Issued' => 'primary',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge rounded-pill bg-{{ $statusClass }}">{{ $request->status ?? '--' }}</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.employee_idcard.show', $request->id) }}" 
                                                   class="text-primary rounded-start-2 view-details-btn d-inline-flex align-items-center gap-1 px-2 py-1" title="View Details" data-request-id="{{ $request->id }}" data-name="{{ $request->name }}" data-designation="{{ $request->designation ?? '--' }}" data-request-for="{{ $request->request_for ?? '--' }}" data-duplication="{{ $request->duplication_reason ?? '--' }}" data-extension="{{ $request->id_card_valid_upto ?? '--' }}" data-valid-from="{{ $request->id_card_valid_from ?? '' }}" data-id-number="{{ $request->id_card_number ?? '' }}" data-valid-upto="{{ $request->id_card_valid_upto ?? '--' }}" data-status="{{ $request->status ?? '--' }}" data-created="{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}" data-show-url="{{ route('admin.employee_idcard.show', $request->id) }}">
                                                    <i class="material-icons material-symbols-rounded">visibility</i>
                                                </a>
                                                <a href="{{ route('admin.employee_idcard.edit', $request->id) }}" 
                                                   class="text-primary rounded-0 d-inline-flex align-items-center gap-1 px-2 py-1" title="Edit" data-bs-toggle="tooltip" data-bs-placement="top">
                                                    <i class="material-icons material-symbols-rounded">edit</i>
                                                </a>
                                                <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <a href="javascript:void(0)" class="text-primary rounded-end-2 px-2 py-1" title="Archive">
                                                        <i class="material-icons material-symbols-rounded">delete</i>
                                                    </a>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-5 table-empty-state">
                                            <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                                <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">inbox</i>
                                                <p class="mb-1 fw-semibold text-body-emphasis">No active ID card requests found.</p>
                                                <small class="text-body-secondary mb-4">Click "Generate New ID Card" to create one.</small>
                                                <a href="{{ route('admin.employee_idcard.create') }}" class="btn btn-primary rounded-3 px-4 py-2">
                                                    <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                                    Create Request
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="duplication-panel" role="tabpanel" aria-labelledby="duplication-tab">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-borderless mb-0 align-middle idcard-index-table" id="duplicationIdcardTable">
                            <thead class="table-light text-body-secondary border-bottom border-2">
                                <tr>
                                    <th class="text-nowrap py-3 ps-4">S.No.</th>
                                    <th class="py-3">ID Card</th>
                                    <th class="py-3">Request date</th>
                                    <th class="py-3">Employee Name</th>
                                    <th class="py-3">Designation</th>
                                    <th class="py-3">Duplication</th>
                                    <th class="py-3">Extension</th>
                                    <th class="py-3">Valid Upto</th>
                                    <th class="py-3">Status</th>
                                    <th class="text-end py-3 pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                @forelse($duplicationRequests as $index => $request)
                                    <tr data-request-id="{{ $request->id }}" class="align-middle">
                                        <td class="fw-medium ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            @if($request->photo)
                                                <a href="{{ asset('storage/' . $request->photo) }}" target="_blank" class="d-inline-block rounded-2 overflow-hidden shadow-sm">
                                                    <img src="{{ asset('storage/' . $request->photo) }}" alt="ID Card" class="rounded-2 object-fit-cover" style="width:40px;height:50px;">
                                                </a>
                                            @else
                                                <span class="text-body-tertiary">--</span>
                                            @endif
                                        </td>
                                        <td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td>
                                        <td class="fw-medium text-body-emphasis">{{ $request->name }}</td>
                                        <td class="text-body-secondary">{{ $request->designation ?? '--' }}</td>
                                        <td>
                                            @php $dupBadge = match($request->duplication_reason ?? '') { 'Lost' => 'danger', 'Damage' => 'warning', 'Expired Card' => 'info', default => 'secondary' }; @endphp
                                            <span class="badge bg-{{ $dupBadge }} text-dark">
                                                {{ $request->duplication_reason ?? '--' }}
                                            </span>
                                        </td>
                                        <td><span class="text-body-tertiary">--</span></td>
                                        <td>{{ $request->id_card_valid_upto ?? '--' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($request->status ?? '') {
                                                    'Pending' => 'warning',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Issued' => 'primary',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge rounded-pill bg-{{ $statusClass }}">{{ $request->status ?? '--' }}</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.employee_idcard.show', $request->id) }}" class="btn btn-outline-primary rounded-start-2 view-details-btn d-inline-flex align-items-center gap-1 px-2 py-1" title="View Details" data-name="{{ $request->name }}" data-designation="{{ $request->designation ?? '--' }}" data-request-for="{{ $request->request_for ?? '--' }}" data-duplication="{{ $request->duplication_reason ?? '--' }}" data-extension="{{ $request->request_for == 'Extension' ? ($request->id_card_valid_upto ?? '--') : '--' }}" data-valid-upto="{{ $request->id_card_valid_upto ?? '--' }}" data-status="{{ $request->status ?? '--' }}" data-created="{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}" data-show-url="{{ route('admin.employee_idcard.show', $request->id) }}">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                                </a>
                                                <a href="{{ route('admin.employee_idcard.edit', $request->id) }}" class="btn btn-outline-secondary rounded-0 d-inline-flex align-items-center gap-1 px-2 py-1" title="Edit">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i>
                                                </a>
                                                <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger rounded-end-2 px-2 py-1" title="Archive">
                                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                                <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">content_copy</i>
                                                <p class="mb-1 fw-semibold text-body-emphasis">No ID card duplication requests found.</p>
                                                <small class="text-body-secondary mb-4 text-center">Duplication requests (Lost/Damage) will appear here when request for is "Replacement" or "Duplication".</small>
                                                <a href="{{ route('admin.employee_idcard.create') }}" class="btn btn-warning rounded-3 px-4 py-2">
                                                    <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                                    Request Duplication
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="extension-panel" role="tabpanel" aria-labelledby="extension-tab">
                    <div class="table-responsive">
                        <table class="table text-nowrap mb-0 align-middle idcard-index-table table-striped" id="extensionIdcardTable">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>ID Card</th>
                                    <th>Request date</th>
                                    <th>Employee Name</th>
                                    <th>Designation</th>
                                    <th>Duplication</th>
                                    <th>Extension</th>
                                    <th>Valid Upto</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                @forelse($extensionRequests as $index => $request)
                                    <tr data-request-id="{{ $request->id }}" class="align-middle">
                                        <td class="fw-medium ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            @if($request->photo)
                                                <a href="{{ asset('storage/' . $request->photo) }}" target="_blank" class="d-inline-block rounded-2 overflow-hidden shadow-sm">
                                                    <img src="{{ asset('storage/' . $request->photo) }}" alt="ID Card" class="rounded-2 object-fit-cover" style="width:40px;height:50px;">
                                                </a>
                                            @else
                                                <span class="text-body-tertiary">--</span>
                                            @endif
                                        </td>
                                        <td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td>
                                        <td class="fw-medium text-body-emphasis">{{ $request->name }}</td>
                                        <td class="text-body-secondary">{{ $request->designation ?? '--' }}</td>
                                        <td><span class="text-body-tertiary">--</span></td>
                                        <td>
                                            <span class="badge bg-info">{{ $request->id_card_valid_upto ?? '--' }}</span>
                                        </td>
                                        <td>{{ $request->id_card_valid_upto ?? '--' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($request->status ?? '') {
                                                    'Pending' => 'warning',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Issued' => 'primary',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge rounded-pill bg-{{ $statusClass }}">{{ $request->status ?? '--' }}</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.employee_idcard.show', $request->id) }}" class="btn btn-outline-primary rounded-start-2 view-details-btn d-inline-flex align-items-center gap-1 px-2 py-1" title="View Details" data-name="{{ $request->name }}" data-designation="{{ $request->designation ?? '--' }}" data-request-for="{{ $request->request_for ?? '--' }}" data-duplication="--" data-extension="{{ $request->id_card_valid_upto ?? '--' }}" data-valid-upto="{{ $request->id_card_valid_upto ?? '--' }}" data-status="{{ $request->status ?? '--' }}" data-created="{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}" data-show-url="{{ route('admin.employee_idcard.show', $request->id) }}">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                                </a>
                                                <a href="{{ route('admin.employee_idcard.edit', $request->id) }}" class="btn btn-outline-secondary rounded-0 d-inline-flex align-items-center gap-1 px-2 py-1" title="Edit">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i>
                                                </a>
                                                <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger rounded-end-2 px-2 py-1" title="Archive">
                                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                                <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">schedule</i>
                                                <p class="mb-1 fw-semibold text-body-emphasis">No ID card extension requests found.</p>
                                                <small class="text-body-secondary mb-4">Extension requests will appear here when request for is "Extension".</small>
                                                <a href="{{ route('admin.employee_idcard.create') }}" class="btn btn-info rounded-3 px-4 py-2">
                                                    <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                                    Request Extension
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="table-responsive">
                        <table class="table text-nowrap mb-0 align-middle idcard-index-table table-striped" id="archiveIdcardTable">
                            <thead>
                                <tr>
                                    <th class="text-nowrap py-3 ps-4">S.No.</th>
                                    <th class="py-3">ID Card</th>
                                    <th class="py-3">Request date</th>
                                    <th class="py-3">Employee Name</th>
                                    <th class="py-3">Designation</th>
                                    <th class="py-3">Status</th>
                                    <th class="text-end py-3 pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider">
                                @forelse($archivedRequests as $index => $request)
                                    <tr data-request-id="{{ $request->id }}" class="align-middle">
                                        <td class="fw-medium ps-4">{{ $index + 1 }}</td>
                                        <td>
                                            @if($request->photo)
                                                <a href="{{ asset('storage/' . $request->photo) }}" target="_blank" class="d-inline-block rounded-2 overflow-hidden shadow-sm">
                                                    <img src="{{ asset('storage/' . $request->photo) }}" alt="ID Card" class="rounded-2 object-fit-cover" style="width:40px;height:50px;">
                                                </a>
                                            @else
                                                <span class="text-body-tertiary">--</span>
                                            @endif
                                        </td>
                                        <td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td>
                                        <td class="fw-medium text-body-emphasis">{{ $request->name }}</td>
                                        <td class="text-body-secondary">{{ $request->designation ?? '--' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($request->status) {
                                                    'Pending' => 'warning',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Issued' => 'primary',
                                                    default => 'secondary'
                                                };
                                                $statusIcon = match($request->status) {
                                                    'Pending' => 'schedule',
                                                    'Approved' => 'check_circle',
                                                    'Rejected' => 'cancel',
                                                    'Issued' => 'card_giftcard',
                                                    default => 'help'
                                                };
                                            @endphp
                                            <span class="badge rounded-pill bg-{{ $statusClass }}">
                                                <i class="material-icons material-symbols-rounded" style="font-size:12px;">{{ $statusIcon }}</i>
                                                {{ $request->status }}
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.employee_idcard.show', $request->id) }}" class="btn btn-outline-primary rounded-start-2 d-inline-flex align-items-center gap-1 px-2 py-1" title="View Details">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                                </a>
                                                <form action="{{ route('admin.employee_idcard.restore', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this request?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success rounded-0 px-2 py-1" title="Restore">
                                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">restore</i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.employee_idcard.forceDelete', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this request? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger rounded-end-2 px-2 py-1" title="Delete Permanently">
                                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete_forever</i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                                <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">archive</i>
                                                <p class="mb-1 fw-semibold text-body-emphasis">No archived ID card requests found.</p>
                                                <small class="text-body-secondary">Deleted records will appear here.</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Details + Amend Duplication/Extension Modal (Bootstrap 5.3 + GIGW compliant) -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="viewDetailsModalLabel" aria-describedby="viewDetailsModalDesc" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg idcard-view-modal rounded-4 overflow-hidden">
            <div class="modal-header idcard-modal-header py-3 px-4 border-0">
                <h2 class="modal-title h5 fw-bold mb-0" id="viewDetailsModalLabel">
                    <span class="material-icons material-symbols-rounded align-middle me-2" aria-hidden="true">badge</span>
                    ID Card Request Details
                </h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close modal"></button>
            </div>
            <div class="modal-body p-4 bg-body" id="viewDetailsModalDesc">
                <!-- View Section -->
                <section class="mb-4" aria-labelledby="viewDetailsHeading">
                    <h3 id="viewDetailsHeading" class="h6 fw-bold text-uppercase text-body-secondary mb-3 d-flex align-items-center">
                        <span class="material-icons material-symbols-rounded me-2" aria-hidden="true">info</span>
                        Request Summary
                    </h3>
                    <div class="card border border-body-secondary bg-body-tertiary rounded-4 overflow-hidden">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3 border-end border-bottom border-body-secondary border-bottom-md-0 border-end-md-0">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Employee Name</span>
                                        <span class="idcard-modal-value fw-semibold text-body-emphasis" id="modalName">--</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3 border-bottom border-body-secondary border-bottom-md-0">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Designation</span>
                                        <span class="idcard-modal-value fw-semibold text-body-emphasis" id="modalDesignation">--</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3 border-end border-bottom border-body-secondary border-bottom-md-0 border-end-md-0">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Request For</span>
                                        <span class="idcard-modal-value fw-semibold text-body-emphasis" id="modalRequestFor">--</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3 border-bottom border-body-secondary border-bottom-md-0">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Request Date</span>
                                        <span class="idcard-modal-value fw-semibold text-body-emphasis" id="modalCreated">--</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3 border-end border-bottom border-body-secondary border-bottom-md-0 border-end-md-0">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Duplication</span>
                                        <span class="idcard-modal-value fw-semibold text-body-emphasis" id="modalDuplication">--</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3 border-bottom border-body-secondary border-bottom-md-0">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Extension</span>
                                        <span class="idcard-modal-value fw-semibold text-body-emphasis" id="modalExtension">--</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3 border-end border-body-secondary border-end-md-0">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Valid Upto</span>
                                        <span class="idcard-modal-value fw-semibold text-body-emphasis" id="modalValidUpto">--</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="idcard-modal-item p-3">
                                        <span class="idcard-modal-label d-block small text-body-secondary mb-1">Status</span>
                                        <span class="idcard-modal-value" id="modalStatus">--</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Amend Duplication/Extension Section -->
                <section class="idcard-amend-section" aria-labelledby="amendSectionHeading">
                    <h3 id="amendSectionHeading" class="h6 fw-bold text-uppercase text-body-secondary mb-3 d-flex align-items-center">
                        <span class="material-icons material-symbols-rounded me-2" aria-hidden="true">edit_note</span>
                        Amend Duplication / Extension
                    </h3>
                    <div class="card border border-body-secondary rounded-4 shadow-sm">
                        <div class="card-body p-4">
                            <form id="amendDupExtForm" method="POST" enctype="multipart/form-data" novalidate>
                                @csrf
                                @method('PATCH')
                                <div class="row g-3">
                                    <div class="col-12 col-md-6" id="amendDupReasonField">
                                        <label for="amend_duplication_reason" class="form-label fw-medium text-body-emphasis">Reason for Duplicate Card</label>
                                        <select name="duplication_reason" id="amend_duplication_reason" class="form-select rounded-3" aria-describedby="amend_duplication_reason_help">
                                            <option value="">Select Reason</option>
                                            <option value="Expired Card">Expired Card</option>
                                            <option value="Lost">Card Lost</option>
                                            <option value="Damage">Card Damaged</option>
                                        </select>
                                        <span id="amend_duplication_reason_help" class="visually-hidden">Choose the reason for requesting a duplicate ID card</span>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="amend_id_card_number" class="form-label fw-medium text-body-emphasis">ID Card Number</label>
                                        <input type="text" name="id_card_number" id="amend_id_card_number" class="form-control rounded-3" placeholder="e.g. NOP00148" autocomplete="off" aria-describedby="amend_id_card_number_help">
                                        <span id="amend_id_card_number_help" class="visually-hidden">Enter the existing ID card number if known</span>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="amend_id_card_valid_from" class="form-label fw-medium text-body-emphasis">ID Card Valid From</label>
                                        <input type="text" name="id_card_valid_from" id="amend_id_card_valid_from" class="form-control rounded-3" placeholder="DD/MM/YYYY" autocomplete="off" aria-describedby="amend_id_card_valid_from_help">
                                        <span id="amend_id_card_valid_from_help" class="visually-hidden">Date format: DD/MM/YYYY</span>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="amend_id_card_valid_upto" class="form-label fw-medium text-body-emphasis">ID Card Valid Upto</label>
                                        <input type="text" name="id_card_valid_upto" id="amend_id_card_valid_upto" class="form-control rounded-3" placeholder="DD/MM/YYYY" autocomplete="off" aria-describedby="amend_id_card_valid_upto_help">
                                        <span id="amend_id_card_valid_upto_help" class="visually-hidden">New validity date for extension requests. Format: DD/MM/YYYY</span>
                                    </div>
                                    <div class="col-12 col-md-6" id="amendFirReceiptField" style="display:none;">
                                        <label for="amend_fir_receipt" class="form-label fw-medium text-body-emphasis">Upload FIR (First Information Report) <span class="text-danger" aria-hidden="true">*</span></label>
                                        <input type="file" name="fir_receipt" id="amend_fir_receipt" class="form-control rounded-3" accept=".pdf,.doc,.docx,image/*" aria-describedby="amend_fir_receipt_help">
                                        <span id="amend_fir_receipt_help" class="form-text text-body-secondary">Required when card is lost. Upload FIR filed against lost card.</span>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="amend_payment_receipt" class="form-label fw-medium text-body-emphasis">Upload Payment Receipt</label>
                                        <input type="file" name="payment_receipt" id="amend_payment_receipt" class="form-control rounded-3" accept=".pdf,.doc,.docx,image/*">
                                    </div>
                                </div>
                                <div id="amendDupExtError" class="alert alert-danger mt-3 d-none d-flex align-items-center rounded-3" role="alert" aria-live="assertive">
                                    <span class="material-icons material-symbols-rounded me-2" aria-hidden="true">error</span>
                                    <span id="amendDupExtErrorText"></span>
                                </div>
                                <div id="amendDupExtSuccess" class="alert alert-success mt-3 d-none d-flex align-items-center rounded-3" role="status" aria-live="polite">
                                    <span class="material-icons material-symbols-rounded me-2" aria-hidden="true">check_circle</span>
                                    <span id="amendDupExtSuccessText"></span>
                                </div>
                                <div class="mt-4 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary rounded-3 px-4 focus-ring" id="amendDupExtSubmitBtn">
                                        <span class="material-icons material-symbols-rounded align-middle me-1" aria-hidden="true" style="font-size:1.1rem;">save</span>
                                        Save Duplication/Extension
                                    </button>
                                    <a href="#" id="modalViewFullLink" class="btn btn-outline-primary rounded-3 text-decoration-none">
                                        <span class="material-icons material-symbols-rounded align-middle me-1" aria-hidden="true" style="font-size:1.1rem;">open_in_new</span>
                                        View Full Details
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure Active tab is shown on page load (default tab)
    var activeTabEl = document.getElementById('active-tab');
    if (activeTabEl && typeof bootstrap !== 'undefined') {
        var tabInstance = bootstrap.Tab.getOrCreateInstance(activeTabEl);
        tabInstance.show();
    }
    if (window.location.hash) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    // DataTables: init only when table has data rows (no empty-state colspan row)
    var dtDefaults = {
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)',
            paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' },
            emptyTable: 'No data available'
        },
        columnDefs: [
            { orderable: false, targets: [0, 1, -1] }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        autoWidth: false
    };
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        ['#activeIdcardTable', '#duplicationIdcardTable', '#extensionIdcardTable', '#archiveIdcardTable'].forEach(function(id) {
            var $t = $(id);
            if ($t.length && $t.find('tbody tr td[colspan]').length === 0) {
                if ($.fn.DataTable.isDataTable(id)) $t.DataTable().destroy();
                $t.DataTable(dtDefaults);
            }
        });
        // Adjust DataTables when tab is shown (hidden tables may have wrong width)
        var panelToTable = { '#active-panel': '#activeIdcardTable', '#duplication-panel': '#duplicationIdcardTable', '#extension-panel': '#extensionIdcardTable', '#archive-panel': '#archiveIdcardTable' };
        document.querySelectorAll('#active-tab, #duplication-tab, #extension-tab, #archive-tab').forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function() {
                var target = this.getAttribute('data-bs-target');
                var tableId = panelToTable[target];
                if (tableId && $(tableId).length && $.fn.DataTable.isDataTable(tableId)) {
                    $(tableId).DataTable().columns().adjust();
                }
            });
        });
    }

    function openViewAmendModal(btn) {
        const modal = document.getElementById('viewDetailsModal');
        document.getElementById('modalName').textContent = btn.dataset.name || '--';
        document.getElementById('modalDesignation').textContent = btn.dataset.designation || '--';
        document.getElementById('modalRequestFor').textContent = btn.dataset.requestFor || '--';
        document.getElementById('modalCreated').textContent = btn.dataset.created || '--';
        document.getElementById('modalDuplication').textContent = btn.dataset.duplication || '--';
        document.getElementById('modalExtension').textContent = btn.dataset.extension || '--';
        document.getElementById('modalValidUpto').textContent = btn.dataset.validUpto || btn.dataset.extension || '--';
        const status = btn.dataset.status || '--';
        const statusClass = { 'Pending': 'warning', 'Approved': 'success', 'Rejected': 'danger', 'Issued': 'primary' }[status] || 'secondary';
        document.getElementById('modalStatus').innerHTML = status !== '--' ? '<span class="badge bg-' + statusClass + '">' + status + '</span>' : '--';
        document.getElementById('modalViewFullLink').href = btn.dataset.showUrl || '#';
        const amendForm = document.getElementById('amendDupExtForm');
        const requestId = btn.dataset.requestId;
        amendForm.action = '{{ route("admin.employee_idcard.amendDuplicationExtension", ["id" => "__ID__"]) }}'.replace('__ID__', requestId);
        document.getElementById('amend_duplication_reason').value = btn.dataset.duplication || '';
        document.getElementById('amend_id_card_number').value = btn.dataset.idNumber || '';
        document.getElementById('amend_id_card_valid_from').value = btn.dataset.validFrom || '';
        document.getElementById('amend_id_card_valid_upto').value = btn.dataset.validUpto || btn.dataset.extension || '';
        document.getElementById('amend_fir_receipt').value = '';
        document.getElementById('amend_payment_receipt').value = '';
        document.getElementById('amendDupExtError').classList.add('d-none');
        document.getElementById('amendDupExtSuccess').classList.add('d-none');
        document.getElementById('amendDupReasonField').style.display = '';
        const dupReason = document.getElementById('amend_duplication_reason');
        dupReason.dispatchEvent(new Event('change'));
        new bootstrap.Modal(modal).show();
    }

    var currentAmendBtn = null;
    document.body.addEventListener('click', function(e) {
        var target = e.target.closest('a.view-details-btn');
        if (target) { e.preventDefault(); openViewAmendModal(target); }
    });
    document.body.addEventListener('click', function(e) {
        var target = e.target.closest('a.amend-dup-ext-btn');
        if (target) {
            e.preventDefault();
            currentAmendBtn = target;
            openViewAmendModal(target);
        }
    });

    const amendForm = document.getElementById('amendDupExtForm');
    const amendModal = document.getElementById('viewDetailsModal');

    document.getElementById('amend_duplication_reason').addEventListener('change', function() {
        const firField = document.getElementById('amendFirReceiptField');
        const firInput = document.getElementById('amend_fir_receipt');
        const isLost = this.value === 'Lost';
        firField.style.display = isLost ? '' : 'none';
        firInput.required = isLost;
    });

    if (amendForm) {
        amendForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('amendDupExtSubmitBtn');
            const errEl = document.getElementById('amendDupExtError');
            const successEl = document.getElementById('amendDupExtSuccess');
            errEl.classList.add('d-none');
            successEl.classList.add('d-none');
            submitBtn.disabled = true;
            const formData = new FormData(amendForm);
            fetch(amendForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
            .then(function(result) {
                submitBtn.disabled = false;
                if (result.ok && result.data && result.data.success) {
                    var successText = document.getElementById('amendDupExtSuccessText');
                    if (successText) successText.textContent = result.data.message || 'Updated successfully.';
                    successEl.classList.remove('d-none');
                    if (currentAmendBtn) {
                        const row = currentAmendBtn.closest('tr');
                        const allModalBtns = row ? row.querySelectorAll('a.view-details-btn, a.amend-dup-ext-btn') : [];
                        allModalBtns.forEach(function(btn) {
                            btn.dataset.duplication = result.data.data.duplication_reason || '';
                            btn.dataset.extension = result.data.data.id_card_valid_upto || '';
                            btn.dataset.validFrom = result.data.data.id_card_valid_from || '';
                            btn.dataset.idNumber = result.data.data.id_card_number || '';
                            btn.dataset.validUpto = result.data.data.id_card_valid_upto || '';
                        });
                        const dupExtBtns = row ? row.querySelectorAll('a.amend-dup-ext-btn') : [];
                        dupExtBtns.forEach(function(btn) {
                            btn.dataset.duplication = result.data.data.duplication_reason || '';
                            btn.dataset.extension = result.data.data.id_card_valid_upto || '';
                            btn.dataset.validFrom = result.data.data.id_card_valid_from || '';
                            btn.dataset.idNumber = result.data.data.id_card_number || '';
                            const dupBadge = { 'Lost': 'danger', 'Damage': 'warning', 'Expired Card': 'info' }[result.data.data.duplication_reason] || 'secondary';
                            if (btn.dataset.type === 'duplication') {
                                btn.innerHTML = result.data.data.duplication_reason
                                    ? '<span class="badge bg-' + dupBadge + ' text-dark">' + result.data.data.duplication_reason + '</span>'
                                    : '<span class="text-primary">Duplication</span>';
                            } else {
                                btn.innerHTML = result.data.data.id_card_valid_upto
                                    ? '<span class="badge bg-info">' + result.data.data.id_card_valid_upto + '</span>'
                                    : '<span class="text-primary">Extension</span>';
                            }
                        });
                        document.getElementById('modalDuplication').textContent = result.data.data.duplication_reason || '--';
                        document.getElementById('modalExtension').textContent = result.data.data.id_card_valid_upto || '--';
                        document.getElementById('modalValidUpto').textContent = result.data.data.id_card_valid_upto || '--';
                    }
                    setTimeout(function() { bootstrap.Modal.getInstance(amendModal).hide(); }, 800);
                } else {
                    let errMsg = 'Update failed.';
                    if (result.data) {
                        if (result.data.message) errMsg = result.data.message;
                        else if (result.data.errors) errMsg = Object.values(result.data.errors).flat().join(' ');
                    }
                    var errText = document.getElementById('amendDupExtErrorText');
                    if (errText) errText.textContent = errMsg;
                    errEl.classList.remove('d-none');
                }
            })
            .catch(function() {
                submitBtn.disabled = false;
                var errText = document.getElementById('amendDupExtErrorText');
                if (errText) errText.textContent = 'An error occurred. Please try again.';
                errEl.classList.remove('d-none');
            });
        });
    }
});
</script>
@endsection
