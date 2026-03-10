@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Low Stock Report"></x-breadcrum>

    <!-- Filters -->
    <div class="card mb-4 border-0 shadow-sm no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold text-dark">Filter Low Stock Items</h5>
                <span class="text-muted small">Items where available stock is at or below alert quantity</span>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.low-stock') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Till Date</label>
                        <input type="date" name="till_date" class="form-control"
                               value="{{ $tillDate }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Store</label>
                        <select name="store_id" class="form-select choices-select" data-placeholder="All Stores">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ ($storeId ?? null) == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">filter_list</span>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.low-stock') }}" class="btn btn-outline-secondary d-inline-flex align-items-center">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">refresh</span>
                        Reset
                    </a>
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center" onclick="window.print()" title="Print report or save as PDF">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">print</span>
                        Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-body">
            <div class="report-header text-center mb-4">
                <h4 class="fw-bold text-uppercase mb-1">Low Stock Report</h4>
                <p class="mb-1 text-muted">
                    <span class="badge bg-light text-dark fw-normal px-3 py-2">
                        Till: {{ date('d-F-Y', strtotime($tillDate)) }}
                    </span>
                </p>
                <p class="mb-0">
                    <span class="badge bg-primary-subtle text-primary-emphasis fw-normal px-3 py-2">
                        <strong>Store:</strong>
                        {{ $selectedStoreName ?? "All Stores" }}
                    </span>
                </p>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold text-dark">Items at or below minimum stock</span>
                    <span class="text-muted small">
                        Total items: {{ is_array($items) ? count($items) : 0 }}
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table text-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px;">Sr. No.</th>
                                <th style="min-width: 180px;">Item Name</th>
                                <th class="text-center" style="min-width: 90px;">Unit</th>
                                <th class="text-end" style="min-width: 110px;">Available Qty</th>
                                <th class="text-end" style="min-width: 120px;">Alert Qty</th>
                                <th class="text-center" style="min-width: 140px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $index => $row)
                                @php
                                    $remaining = $row['remaining_quantity'] ?? 0;
                                    $alert = $row['alert_quantity'] ?? 0;
                                @endphp
                                <tr class="{{ $remaining < $alert ? 'table-danger' : '' }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $row['item_name'] ?? '—' }}</td>
                                    <td class="text-center">{{ $row['unit'] ?? 'Unit' }}</td>
                                    <td class="text-end">{{ number_format($remaining, 2) }}</td>
                                    <td class="text-end">{{ number_format($alert, 2) }}</td>
                                    <td class="text-center">
                                        @if($remaining <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif($remaining <= $alert)
                                            <span class="badge bg-warning text-dark">Below Minimum</span>
                                        @else
                                            <span class="badge bg-success">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No items are currently below their minimum stock level for the selected filters.
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

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            font-size: 12px;
        }
        table {
            font-size: 11px;
            page-break-inside: auto;
        }
        table thead {
            display: table-header-group;
        }
        th, td {
            padding: 6px !important;
        }
        @page {
            margin: 1cm;
            size: A4 portrait;
        }
    }
</style>

{{-- Choices.js (enhanced dropdowns) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.Choices === 'undefined') return;

            document
                .querySelectorAll('.low-stock-report select.choices-select, select.choices-select')
                .forEach(function (el) {
                    if (el.dataset.choicesInitialized === 'true') return;

                    var placeholder = el.getAttribute('data-placeholder') || 'Select';

                    new Choices(el, {
                        shouldSort: false,
                        placeholder: true,
                        placeholderValue: placeholder,
                        searchPlaceholderValue: 'Search...',
                    });

                    el.dataset.choicesInitialized = 'true';
                });
        });
    })();
</script>
@endsection

