@extends('admin.layouts.master')

@section('title', 'Guest Faculty - Sargam | Lal Bahadur')

@section('content')
<style>
/* Guest Faculty page - modern UI (Bootstrap 5 compatible) */
.guest-faculty-card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075), 0 0.5rem 1rem rgba(0, 74, 147, 0.08);
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}
.guest-faculty-card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1), 0 0.75rem 1.5rem rgba(0, 74, 147, 0.1);
}
.guest-faculty-card .card-header {
    background: linear-gradient(135deg, #004a93 0%, #003a75 100%);
    color: #fff;
    font-weight: 600;
    padding: 1rem 1.25rem;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.guest-faculty-card .card-header .card-header-icon {
    font-size: 1.35rem;
    opacity: 0.95;
    line-height: 1;
}
#guess_faculty { margin-bottom: 0; }
#guess_faculty thead th {
    font-weight: 600;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #374151;
    background-color: #af2910;
    border-bottom: 2px solid #e2e8f0;
    padding: 0.875rem 1rem;
    white-space: nowrap;
}
#guess_faculty tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom-color: #f1f5f9;
}
#guess_faculty tbody tr { transition: background-color 0.15s ease; }
.badge-guest { font-weight: 500; padding: 0.35em 0.65em; font-size: 0.75rem; }
.badge-sector-gov { background-color: rgba(0, 74, 147, 0.12); color: #004a93; }
.badge-sector-private { background-color: rgba(30, 64, 175, 0.12); color: #1e40af; }
.badge-sector-other { background-color: rgba(100, 116, 139, 0.15); color: #475569; }
</style>
<div class="container-fluid">
    <x-breadcrum title="Guest Faculty"></x-breadcrum>
    <div class="card guest-faculty-card">
        <div class="card-header">
            <span class="material-symbols-rounded card-header-icon">badge</span>
            <span>Guest Faculty</span>
        </div>
        <div class="card-body p-0">
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table align-middle" id="guess_faculty">
                        <thead>
                            <tr>
                                <th scope="col">Sl. No.</th>
                                <th scope="col">Faculty Type</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Mobile Number</th>
                                <th scope="col">Current Sector</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($guest_faculty as $index => $faculty)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><span class="badge rounded-pill badge-guest bg-success-subtle text-success">Guest</span></td>
                                <td class="fw-medium">{{ $faculty->full_name }}</td>
                                <td>{{ $faculty->mobile_no }}</td>
                                <td>
                                    @if($faculty->faculty_sector == 1)
                                        <span class="badge rounded-pill badge-sector-gov">Government</span>
                                    @elseif($faculty->faculty_sector == 2)
                                        <span class="badge rounded-pill badge-sector-private">Private</span>
                                    @else
                                        <span class="badge rounded-pill badge-sector-other">Other</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>

<script>
$(document).ready(function (){
    $('#guess_faculty').DataTable();
});
</script>
@endpush