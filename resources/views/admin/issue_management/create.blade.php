@extends('admin.layouts.master')

@section('title', 'Log New Issue - Sargam | Lal Bahadur')

@section('css')
<style>
.form-control, .form-select {
    background-color: #fff !important;
    color: #212529 !important;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Log New Issue" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <h4 class="mb-3">Log New Issue</h4>
                <hr>
                    <form action="{{ route('admin.issue-management.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="issue_category_master_pk" id="issue_category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                                        @endforeach
                                    </select>
                                    @error('issue_category_master_pk')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sub-Categories</label>
                                    <select name="sub_categories[]" id="sub_categories" class="form-select" multiple>
                                        <option value="">Select sub-categories</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Priority <span class="text-danger">*</span></label>
                                    <select name="issue_priority_master_pk" class="form-select" required>
                                        <option value="">Select Priority</option>
                                        @foreach($priorities as $priority)
                                            <option value="{{ $priority->pk }}">{{ $priority->priority }}</option>
                                        @endforeach
                                    </select>
                                    @error('issue_priority_master_pk')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Reproducibility <span class="text-danger">*</span></label>
                                    <select name="issue_reproducibility_master_pk" class="form-select" required>
                                        <option value="">Select Reproducibility</option>
                                        @foreach($reproducibilities as $reproducibility)
                                            <option value="{{ $reproducibility->pk }}">{{ $reproducibility->reproducibility_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('issue_reproducibility_master_pk')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Reported Behalf <span class="text-danger">*</span></label>
                                    <select name="behalf" class="form-select" required>
                                        <option value="1">MySelf</option>
                                        <option value="0">Centcom (On behalf of someone)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location Type <span class="text-danger">*</span></label>
                                    <select name="location_type" id="location_type" class="form-select" required>
                                        <option value="other">Other</option>
                                        <option value="building">Building</option>
                                        <option value="hostel">Hostel</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="building_section" style="display:none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Building</label>
                                        <select name="building_master_pk" class="form-select">
                                            <option value="">Select Building</option>
                                            @foreach($buildings as $building)
                                                <option value="{{ $building->pk }}">{{ $building->building_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Floor</label>
                                        <input type="text" name="floor_name" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Room</label>
                                        <input type="text" name="room_name" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="hostel_section" style="display:none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Hostel</label>
                                        <select name="hostel_building_master_pk" class="form-select">
                                            <option value="">Select Hostel</option>
                                            @foreach($hostels as $hostel)
                                                <option value="{{ $hostel->pk }}">{{ $hostel->hostel_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Floor</label>
                                        <input type="text" name="floor_name" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Room</label>
                                        <input type="text" name="room_name" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location Details</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attach Document (Optional)</label>
                            <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <small class="text-muted">Max size: 5MB. Allowed: PDF, JPG, PNG, DOC, DOCX</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attach Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
                            <small class="text-muted">Max size: 5MB. Allowed: JPG, PNG</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remark" class="form-control" rows="2">{{ old('remark') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.issue-management.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Log Issue</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Load sub-categories when category changes
    $('#issue_category').change(function() {
        var categoryId = $(this).val();
        if(categoryId) {
            $.ajax({
                url: '/admin/issue-management/sub-categories/' + categoryId,
                type: 'GET',
                success: function(data) {
                    $('#sub_categories').html('<option value="">Select sub-categories</option>');
                    $.each(data, function(key, value) {
                        $('#sub_categories').append('<option value="'+ value.pk +'">'+ value.issue_sub_category +'</option>');
                    });
                }
            });
        } else {
            $('#sub_categories').html('<option value="">Select sub-categories</option>');
        }
    });

    // Show/hide location sections
    $('#location_type').change(function() {
        var type = $(this).val();
        $('#building_section, #hostel_section').hide();
        if(type == 'building') {
            $('#building_section').show();
        } else if(type == 'hostel') {
            $('#hostel_section').show();
        }
    });
});
</script>
@endsection
