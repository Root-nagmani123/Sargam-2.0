@extends('admin.layouts.master')

@section('title', $drive->election_drive_name . ' — Result')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid cs-master-page ed-nom-page">
    <x-breadcrum :title="$drive->election_drive_name . ' — Result'"
                 :items="[
                     ['label' => 'Home', 'url' => route('admin.dashboard')],
                     ['label' => 'Communications', 'url' => null],
                     ['label' => 'Club/ Society', 'url' => null],
                     ['label' => $drive->election_drive_name, 'url' => route('master.election.drive.index')],
                     ['label' => 'Result', 'url' => null],
                 ]"
                 :show-back="true" />

    <x-session_message />

    {{-- No design was supplied for View Result. This is the plainest defensible
         reading: per society and post, accepted nominees ranked by how many
         people nominated them, with the top N (N = posts available) elected. --}}
    <div class="alert alert-info d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>
        <div>
            Nominees are ranked by the number of distinct people who nominated them.
            The top entries, up to the number of posts available for that role, are marked
            <span class="badge rounded-1 programme-status-badge programme-status-badge--active">Elected</span>.
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            @forelse ($groups as $groupKey => $rows)
                @php [$societyName, $postName] = explode('|', $groupKey); @endphp
                <div class="ed-post-block mb-4">
                    <div class="ed-post-band d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="fw-semibold">{{ $societyName }} — Post : {{ $postName }}</span>
                        <span class="text-body-secondary small">
                            Posts available : {{ (int) ($rows->first()->seats ?? 0) }}
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 programme-dt-table">
                            <thead>
                                <tr>
                                    <th style="width:6rem;">Rank</th>
                                    <th>Nominee</th>
                                    <th style="width:10rem;" class="text-center">OT Code</th>
                                    <th style="width:11rem;" class="text-center">Nominated By</th>
                                    <th style="width:10rem;" class="text-center">Outcome</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $i => $row)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $row->nominee_name ?: '-' }}</td>
                                        <td class="text-center">{{ $row->ot_code ?: '-' }}</td>
                                        <td class="text-center">{{ $row->votes }}</td>
                                        <td class="text-center">
                                            @if ($row->elected)
                                                <span class="badge rounded-1 programme-status-badge programme-status-badge--active">Elected</span>
                                            @else
                                                <span class="badge rounded-1 programme-status-badge ed-status-pending">Not elected</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center text-body-secondary py-5">
                    No accepted nominations, so there is no result to show yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
