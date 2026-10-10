@extends('admin.layouts.master')

@section('title', 'Marks Deducted in Discipline')

@section('setup_content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container-fluid">
    <x-breadcrum title="Marks Deducted in Discipline" :showBack="true" :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Marks Deducted in Discipline'],
    ]" />
    <x-session_message />

    {{-- The two sources, and the figure the dashboard card shows. --}}
    <div class="row g-3 g-lg-4 mb-4">
        @php
            $tiles = [
                ['label' => 'Discipline Memo', 'value' => $disciplineTotal, 'class' => 'text-danger'],
                ['label' => 'Memo / Notice', 'value' => $memoNoticeTotal, 'class' => 'text-warning'],
                ['label' => 'Total Deducted', 'value' => $total, 'class' => 'text-dark'],
            ];
        @endphp
        @foreach($tiles as $tile)
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">{{ $tile['label'] }}</p>
                    <p class="h3 fw-bold mb-0 {{ $tile['class'] }}">
                        {{ rtrim(rtrim(number_format($tile['value'], 2, '.', ''), '0'), '.') ?: '0' }}
                    </p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="text-muted small mb-3">
                Every closed case against you, from both the Discipline Memo register and the
                Memo / Notice register. A case still open carries no deduction yet, so it is not listed.
            </p>

            <div class="table-responsive">
                <table class="table align-middle mb-0 text-nowrap">
                    <thead>
                        <tr>
                            <th>S. No.</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Program Name</th>
                            <th>Details</th>
                            <th>Conclusion Remark</th>
                            <th class="text-center">Marks Deducted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $row)
                        <tr>
                            <td class="fw-semibold text-muted">{{ $index + 1 }}</td>
                            <td class="text-muted">
                                {{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d M Y') : '—' }}
                            </td>
                            <td>
                                @if($row['type'] === 'Discipline Memo')
                                <span class="badge bg-danger-subtle text-danger">Discipline Memo</span>
                                @elseif($row['type'] === 'Memo')
                                <span class="badge bg-warning-subtle text-warning">Memo</span>
                                @else
                                <span class="badge bg-info-subtle text-info">Notice</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $row['course'] }}</td>
                            <td class="text-muted text-wrap" style="max-width:320px">
                                {{ $row['detail'] }}
                                @if(!empty($row['category']))
                                <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $row['category'] }}</span>
                                @endif
                            </td>
                            <td class="text-muted text-wrap" style="max-width:280px">{{ $row['remark'] ?: '—' }}</td>
                            <td class="text-center fw-semibold text-danger">
                                {{ rtrim(rtrim(number_format($row['marks'], 2, '.', ''), '0'), '.') ?: '0' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-clipboard-check d-block fs-1 opacity-50 mb-2" aria-hidden="true"></i>
                                No marks have been deducted.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="6" class="text-end">Total</td>
                            <td class="text-center text-danger">
                                {{ rtrim(rtrim(number_format($total, 2, '.', ''), '0'), '.') ?: '0' }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
