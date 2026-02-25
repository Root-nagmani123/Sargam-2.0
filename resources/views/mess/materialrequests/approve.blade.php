@extends('admin.layouts.master')
@section('title', 'Approve Material Request')
@section('setup_content')
<div class="container-fluid">
    <h4>Approve/Reject Material Request</h4>
    <form method="POST" action="{{ route('admin.mess.materialrequests.processApproval', $materialRequest->id) }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <p><strong>Request Number:</strong> {{ $materialRequest->request_number }}</p>
                <p><strong>Requested By:</strong> {{ $materialRequest->requester->name ?? 'N/A' }}</p>
                <p><strong>Purpose:</strong> {{ $materialRequest->purpose }}</p>
                
                <div class="mb-3">
                    <label>Decision *</label>
                    <select name="status" class="form-select select2" required id="decision">
                        <option value="">Select Decision</option>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                
                <div class="mb-3" id="rejectionReason" style="display:none;">
                    <label>Rejection Reason *</label>
                    <textarea name="rejection_reason" class="form-control" rows="3"></textarea>
                </div>
                
                <div id="itemsSection" style="display:none;">
                    <h5>Items (You can modify approved quantities)</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Requested Qty</th>
                                <th>Approved Qty</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materialRequest->items as $item)
                                <tr>
                                    <td>{{ $item->inventory->item_name ?? 'N/A' }}</td>
                                    <td>{{ $item->requested_quantity }}</td>
                                    <td>
                                        <input type="number" name="items[{{ $item->id }}][approved_quantity]" 
                                               class="form-control" value="{{ $item->requested_quantity }}" step="0.01">
                                    </td>
                                    <td>{{ $item->unit ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('admin.mess.materialrequests.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('decision').addEventListener('change', function() {
    if (this.value === 'rejected') {
        document.getElementById('rejectionReason').style.display = 'block';
        document.getElementById('itemsSection').style.display = 'none';
    } else if (this.value === 'approved') {
        document.getElementById('rejectionReason').style.display = 'none';
        document.getElementById('itemsSection').style.display = 'block';
    } else {
        document.getElementById('rejectionReason').style.display = 'none';
        document.getElementById('itemsSection').style.display = 'none';
    }
});
</script>
@endsection
