@extends('admin.layouts.master')

@section('title', 'Category Wise Material Report - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Category Wise Material Report" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <h4 class="mb-4">Category Wise Material Report</h4>
                <hr>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Category</th>
                            <th>Total Items</th>
                            <th>Total Stock</th>
                            <th>Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryData as $key => $data)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><strong>{{ $data['category']->category_name }}</strong></td>
                            <td>{{ $data['total_items'] }}</td>
                            <td>{{ number_format($data['total_stock'], 2) }}</td>
                            <td>₹{{ number_format($data['total_value'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
