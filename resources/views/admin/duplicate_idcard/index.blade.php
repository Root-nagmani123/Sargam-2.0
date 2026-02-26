@extends('admin.layouts.master')
@section('title', 'Request For Duplicate ID Card - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Request For Duplicate ID Card"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="text-muted small">Show</label>
                        <select name="per_page" class="form-select form-select-sm" style="width:90px" onchange="this.form.submit()">
                            @foreach([10,25,50,100] as $n)
                                <option value="{{ $n }}" {{ (int)request('per_page',10)===$n ? 'selected':'' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span class="text-muted small">entries</span>
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                    </form>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="per_page" value="{{ request('per_page',10) }}">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search with in table:" style="width:220px">
                        <button class="btn btn-sm btn-primary">Search</button>
                    </form>
                    <a href="{{ route('admin.duplicate_idcard.create') }}" class="btn btn-sm btn-success">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">add</i>
                        Add
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-primary text-white" style="background:#0d6efd;">
                        <tr>
                            <th style="width:60px">S. No.</th>
                            <th>Employee Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>ID Card No</th>
                            <th>Date Of Birth</th>
                            <th>Blood Group</th>
                            <th>Contact No.</th>
                            <th>Reason</th>
                            <th>Employee Type</th>
                            <th>Employee Photo</th>
                            <th>Document (If Any)</th>
                            <th>Valid From</th>
                            <th>Valid To</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th class="text-center" style="width:120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $idx => $r)
                            <tr>
                                <td>{{ ($requests->currentPage()-1)*$requests->perPage() + $idx + 1 }}</td>
                                <td>{{ $r->employee_name }}</td>
                                <td>{{ $r->designation }}</td>
                                <td>{{ $r->department }}</td>
                                <td>{{ $r->id_card_no }}</td>
                                <td>{{ $r->employee_dob ? \Carbon\Carbon::parse($r->employee_dob)->format('d-m-Y') : '--' }}</td>
                                <td>{{ $r->blood_group }}</td>
                                <td>{{ $r->mobile_no }}</td>
                                <td>{{ $r->card_reason }}</td>
                                <td>{{ $r->employee_type }}</td>
                                <td>
                                    @php
                                        $p = $r->photo_path;
                                        if ($p && strpos($p,'/') === false) { $p = 'idcard/photos/'.$p; }
                                        $photoExists = $p && \Storage::disk('public')->exists($p);
                                        $photoUrl = $photoExists ? asset('storage/'.$p) : asset('images/dummypic.jpeg');
                                    @endphp
                                    <a href="{{ $photoUrl }}" target="_blank">Download</a>
                                </td>
                                <td>
                                    @php
                                        $d = $r->doc_path;
                                        if ($d && strpos($d,'/') === false) { $d = 'idcard/dup_docs/'.$d; }
                                    @endphp
                                    @if($d)
                                        <a href="{{ asset('storage/'.$d) }}" target="_blank">Download</a>
                                    @else
                                        --
                                    @endif
                                </td>
                                <td>{{ $r->valid_from ? \Carbon\Carbon::parse($r->valid_from)->format('d-m-Y') : '--' }}</td>
                                <td>{{ $r->valid_to ? \Carbon\Carbon::parse($r->valid_to)->format('d-m-Y') : '--' }}</td>
                                <td>{{ $r->status_label }}</td>
                                <td>{{ $r->request_date ? \Carbon\Carbon::parse($r->request_date)->format('d-m-Y') : '--' }}</td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <a href="{{ route('admin.duplicate_idcard.edit', $r->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="material-icons material-symbols-rounded" style="font-size:16px;">edit</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="17" class="text-center text-muted py-4">No requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} entries
                </div>
                <div>
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

