@extends('admin.layouts.master')

@section('title', 'Log New Issue - Sargam | Lal Bahadur')

@section('css')
<style>
.form-control, .form-select {
    background-color: #fff !important;
    color: #212529 !important;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
.form-control:focus, .form-select:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
}
.complaint-card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.08);
    overflow: hidden;
}
.complaint-card .card-body {
    padding: 1.75rem 2rem;
}
.complaint-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.complaint-header .back-link {
    color: #004a93;
    text-decoration: none;
    font-size: 1.25rem;
    line-height: 1;
    padding: 0.25rem;
    border-radius: 0.375rem;
    transition: background-color 0.2s, color 0.2s;
}
.complaint-header .back-link:hover {
    color: #003366;
    background-color: rgba(0, 74, 147, 0.08);
}
.complaint-header .page-title {
    font-weight: 600;
    color: #1a365d;
    font-size: 1.35rem;
    margin: 0;
}
.form-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
}
.form-select, .form-control {
    padding: 0.5rem 0.75rem;
}
.btn-submit-complaint {
    background-color: #004a93;
    border-color: #004a93;
    color: #fff;
    padding: 0.5rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 500;
}
.btn-submit-complaint:hover {
    background-color: #003366;
    border-color: #003366;
    color: #fff;
}
.char-counter {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 0.25rem;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid py-3">
    <x-breadcrum title="Log New Issue" />
    <div class="datatables">
        <div class="card complaint-card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <form action="{{ route('admin.issue-management.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
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
                                    <div class="text-danger small mt-1">{{ $message }}</div>
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

                    <div class="row g-3">
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
                                    <div class="text-danger small mt-1">{{ $message }}</div>
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
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
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

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" rows="5" maxlength="1000" placeholder="Type your message here......" required>{{ old('description') }}</textarea>
                        <div class="char-counter"><span id="char-count">0</span>/1000 Character</div>
                        @error('description')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="building_section" class="d-none">
                        <div class="row g-3">
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
                                    <input type="text" name="floor_name" class="form-control" placeholder="Select Floor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Room</label>
                                    <input type="text" name="room_name" class="form-control" placeholder="Room">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="hostel_section" class="d-none">
                        <div class="row g-3">
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
                                    <input type="text" name="floor_name" class="form-control" placeholder="Select Floor">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Room</label>
                                    <input type="text" name="room_name" class="form-control" placeholder="Room">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location Details</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="Select Location">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Attach Document (Optional)</label>
                                <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <small class="text-muted">Max size: 5MB. Allowed: PDF, JPG, PNG, DOC, DOCX</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Attach Image (Optional)</label>
                                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max size: 5MB. Allowed: JPG, PNG</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Remarks</label>
                        <textarea name="remark" class="form-control" rows="2" placeholder="Type your message here......">{{ old('remark') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <a href="{{ route('admin.issue-management.index') }}" class="btn btn-secondary rounded">Cancel</a>
                        <button type="submit" class="btn btn-submit-complaint">Log Issue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Character counter for description (max 1000)
    function updateCharCount() {
        var len = $('#description').val().length;
        $('#char-count').text(len);
    }
    $('#description').on('input keyup', updateCharCount);
    updateCharCount();

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
        $('#building_section, #hostel_section').addClass('d-none');
        if(type == 'building') {
            $('#building_section').removeClass('d-none');
        } else if(type == 'hostel') {
            $('#hostel_section').removeClass('d-none');
        }
    });
});
</script>
@endsection
