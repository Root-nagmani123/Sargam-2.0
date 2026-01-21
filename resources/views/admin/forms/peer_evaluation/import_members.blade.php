@extends('admin.layouts.master')
@section('title', 'Import Members to Peer Evaluation Group - ' . $group->group_name)
@section('setup_content')

<style>
    /* ============================================
       Modern Import Page Styles
       ============================================ */
    
    /* Focus Indicators - WCAG 2.4.7 */
    *:focus-visible {
        outline: 3px solid #004a93;
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(0, 74, 147, 0.2);
    }
    
    /* Modern Card Styles */
    .import-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        height: 100%;
    }
    
    .import-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }
    
    .import-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1.5rem;
        font-weight: 600;
    }
    
    .import-card.template-card .card-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    /* Drag and Drop File Upload */
    .file-upload-area {
        border: 3px dashed #dee2e6;
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    
    .file-upload-area:hover {
        border-color: #004a93;
        background: #e7f3ff;
    }
    
    .file-upload-area.dragover {
        border-color: #198754;
        background: #d1e7dd;
        transform: scale(1.02);
    }
    
    .file-upload-area.has-file {
        border-color: #198754;
        background: #d1e7dd;
    }
    
    .file-upload-icon {
        font-size: 4rem;
        color: #6c757d;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .file-upload-area:hover .file-upload-icon,
    .file-upload-area.dragover .file-upload-icon {
        color: #004a93;
        transform: scale(1.1);
    }
    
    .file-upload-text {
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .file-upload-hint {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    /* File Input Styling */
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    
    .file-input-wrapper input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        z-index: 2;
    }
    
    .file-input-label {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background: #004a93;
        color: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        margin-top: 1rem;
    }
    
    .file-input-label:hover {
        background: #003d7a;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 74, 147, 0.3);
    }
    
    /* File Preview */
    .file-preview {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        display: none;
    }
    
    .file-preview.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .file-preview-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: white;
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }
    
    .file-preview-icon {
        font-size: 2rem;
        color: #198754;
    }
    
    .file-preview-info {
        flex: 1;
    }
    
    .file-preview-name {
        font-weight: 600;
        color: #212529;
        margin-bottom: 0.25rem;
    }
    
    .file-preview-size {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .file-preview-remove {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .file-preview-remove:hover {
        background: #bb2d3b;
        transform: scale(1.1);
    }
    
    /* Progress Bar */
    .upload-progress {
        display: none;
        margin-top: 1rem;
    }
    
    .upload-progress.show {
        display: block;
    }
    
    .progress {
        height: 8px;
        border-radius: 10px;
        overflow: hidden;
        background: #e9ecef;
    }
    
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #198754, #20c997);
        transition: width 0.3s ease;
        border-radius: 10px;
    }
    
    /* Template Info */
    .template-info {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .template-columns {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .template-column-item {
        background: white;
        padding: 0.75rem;
        border-radius: 8px;
        border-left: 4px solid #004a93;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    /* Alert Enhancements */
    .alert {
        border: none;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d1e7dd 0%, #a3d9b3 100%);
        color: #0f5132;
        border-left: 4px solid #198754;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%);
        color: #842029;
        border-left: 4px solid #dc3545;
    }
    
    /* Button Enhancements */
    .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
        min-height: 44px;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .btn:active {
        transform: translateY(0);
    }
    
    /* Loading State */
    .btn-loading {
        position: relative;
        pointer-events: none;
        opacity: 0.7;
    }
    
    .btn-loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 3px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .file-upload-area {
            padding: 2rem 1rem;
        }
        
        .file-upload-icon {
            font-size: 3rem;
        }
        
        .template-columns {
            grid-template-columns: 1fr;
        }
        
        .import-card {
            margin-bottom: 1.5rem;
        }
    }
    
    /* Info Badge */
    .info-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #e7f3ff;
        color: #004a93;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        margin-top: 1rem;
    }
    
    /* Group Info Card */
    .group-info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .group-info-icon {
        font-size: 2.5rem;
        opacity: 0.9;
    }
    
    /* Step Indicator */
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e9ecef;
        z-index: 0;
    }
    
    .step-item {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin: 0 auto 0.5rem;
        transition: all 0.3s ease;
    }
    
    .step-item.active .step-number {
        background: #004a93;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 74, 147, 0.3);
    }
    
    .step-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #6c757d;
    }
    
    .step-item.active .step-label {
        color: #004a93;
        font-weight: 600;
    }
</style>

<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <x-breadcrum title="Import Members - {{ $group->group_name }}" />
    
    <!-- Group Info Card -->
    <div class="group-info-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <div class="group-info-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold">Import Members</h4>
                    <p class="mb-0 opacity-90">
                        <i class="fas fa-layer-group me-2"></i>
                        Group: <strong>{{ $group->group_name }}</strong>
                    </p>
                    <p class="mb-0 opacity-90 small mt-1">
                        <i class="fas fa-book me-2"></i>
                        Course: {{ $group->course->course_name ?? 'N/A' }} | 
                        <i class="fas fa-calendar-alt me-2"></i>
                        Event: {{ $group->event->event_name ?? 'N/A' }}
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.peer.group.members', $group->id) }}" 
               class="btn btn-light btn-lg">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Members
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Success!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step-item active">
            <div class="step-number">1</div>
            <div class="step-label">Prepare File</div>
        </div>
        <div class="step-item">
            <div class="step-number">2</div>
            <div class="step-label">Upload File</div>
        </div>
        <div class="step-item">
            <div class="step-number">3</div>
            <div class="step-label">Import Complete</div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Import from Excel Card -->
        <div class="col-12 col-lg-6">
            <div class="card import-card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-excel me-2"></i>
                        Import from Excel
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" 
                          action="{{ route('admin.peer.group.import-excel', $group->id) }}"
                          enctype="multipart/form-data" 
                          id="importForm"
                          class="d-flex flex-column h-100">
                        @csrf
                        
                        <!-- Drag and Drop Area -->
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">
                                Drag & Drop your file here
                            </div>
                            <div class="file-upload-hint">
                                or click to browse
                            </div>
                            <div class="file-input-wrapper">
                                <input type="file" 
                                       class="form-control" 
                                       id="excel_file" 
                                       name="excel_file"
                                       accept=".xlsx,.xls,.csv" 
                                       required
                                       aria-label="Select Excel file">
                                <label for="excel_file" class="file-input-label">
                                    <i class="fas fa-folder-open me-2"></i>
                                    Choose File
                                </label>
                            </div>
                            <div class="info-badge">
                                <i class="fas fa-info-circle"></i>
                                Supported: .xlsx, .xls, .csv
                            </div>
                        </div>

                        <!-- File Preview -->
                        <div class="file-preview" id="filePreview">
                            <div class="file-preview-item">
                                <div class="file-preview-icon">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <div class="file-preview-info">
                                    <div class="file-preview-name" id="fileName"></div>
                                    <div class="file-preview-size" id="fileSize"></div>
                                </div>
                                <button type="button" 
                                        class="file-preview-remove" 
                                        id="removeFile"
                                        aria-label="Remove file">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Upload Progress -->
                        <div class="upload-progress" id="uploadProgress">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small fw-medium">Uploading...</span>
                                <span class="small" id="progressPercent">0%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" 
                                     id="progressBar" 
                                     role="progressbar" 
                                     style="width: 0%"
                                     aria-valuenow="0" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                                class="btn btn-success btn-lg w-100 mt-4" 
                                id="submitBtn"
                                disabled>
                            <i class="fas fa-upload me-2"></i>
                            Import Excel File
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Excel Template Card -->
        <div class="col-12 col-lg-6">
            <div class="card import-card template-card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-download me-2"></i>
                        Excel Template
                    </h5>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <div class="flex-fill">
                        <p class="mb-3">
                            Download the template file to ensure your Excel file has the correct format for importing members.
                        </p>
                        
                        <div class="template-info">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-list-check me-2"></i>
                                Required Columns:
                            </h6>
                            <div class="template-columns">
                                <div class="template-column-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Course Name
                                </div>
                                <div class="template-column-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Event Name
                                </div>
                                <div class="template-column-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    User ID
                                </div>
                                <div class="template-column-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    User Name
                                </div>
                                <div class="template-column-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    OT Code
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Tip:</strong> Make sure your Excel file matches the template format exactly to avoid import errors.
                        </div>
                    </div>

                    <a href="{{ route('admin.peer.download-template') }}" 
                       class="btn btn-primary btn-lg w-100 mt-4"
                       id="downloadTemplateBtn">
                        <i class="fas fa-download me-2"></i>
                        Download Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="card mt-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3">
                <i class="fas fa-question-circle text-primary me-2"></i>
                Import Instructions
            </h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-start gap-2">
                        <div class="badge bg-primary rounded-circle p-2">
                            <i class="fas fa-1"></i>
                        </div>
                        <div>
                            <strong>Step 1:</strong> Download the template file
                            <p class="small text-muted mb-0">Use the template to ensure correct format</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start gap-2">
                        <div class="badge bg-success rounded-circle p-2">
                            <i class="fas fa-2"></i>
                        </div>
                        <div>
                            <strong>Step 2:</strong> Fill in member data
                            <p class="small text-muted mb-0">Add all required columns with member information</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start gap-2">
                        <div class="badge bg-info rounded-circle p-2">
                            <i class="fas fa-3"></i>
                        </div>
                        <div>
                            <strong>Step 3:</strong> Upload and import
                            <p class="small text-muted mb-0">Upload your file and click import</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const fileInput = $('#excel_file');
    const fileUploadArea = $('#fileUploadArea');
    const filePreview = $('#filePreview');
    const submitBtn = $('#submitBtn');
    const importForm = $('#importForm');
    const uploadProgress = $('#uploadProgress');
    const progressBar = $('#progressBar');
    const progressPercent = $('#progressPercent');
    const removeFileBtn = $('#removeFile');
    
    let selectedFile = null;
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Show file preview
    function showFilePreview(file) {
        selectedFile = file;
        $('#fileName').text(file.name);
        $('#fileSize').text(formatFileSize(file.size));
        filePreview.addClass('show');
        fileUploadArea.addClass('has-file');
        submitBtn.prop('disabled', false);
        
        // Update step indicator
        $('.step-item').eq(0).removeClass('active');
        $('.step-item').eq(1).addClass('active');
    }
    
    // Remove file
    function removeFile() {
        selectedFile = null;
        fileInput.val('');
        filePreview.removeClass('show');
        fileUploadArea.removeClass('has-file');
        submitBtn.prop('disabled', true);
        
        // Reset step indicator
        $('.step-item').eq(1).removeClass('active');
        $('.step-item').eq(0).addClass('active');
    }
    
    // File input change
    fileInput.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                               'application/vnd.ms-excel',
                               'text/csv',
                               'application/csv'];
            const validExtensions = ['xlsx', 'xls', 'csv'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!validExtensions.includes(fileExtension)) {
                alert('Please select a valid Excel file (.xlsx, .xls, or .csv)');
                fileInput.val('');
                return;
            }
            
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                fileInput.val('');
                return;
            }
            
            showFilePreview(file);
        }
    });
    
    // Drag and drop handlers
    fileUploadArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    fileUploadArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    fileUploadArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            
            // Validate file type
            const validExtensions = ['xlsx', 'xls', 'csv'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!validExtensions.includes(fileExtension)) {
                alert('Please drop a valid Excel file (.xlsx, .xls, or .csv)');
                return;
            }
            
            // Validate file size
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                return;
            }
            
            // Set file to input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput[0].files = dataTransfer.files;
            
            showFilePreview(file);
        }
    });
    
    // Click on upload area
    fileUploadArea.on('click', function(e) {
        if (!$(e.target).is('input') && !$(e.target).is('label') && !$(e.target).closest('label').length) {
            fileInput.click();
        }
    });
    
    // Remove file button
    removeFileBtn.on('click', function(e) {
        e.stopPropagation();
        removeFile();
    });
    
    // Form submission
    importForm.on('submit', function(e) {
        if (!selectedFile) {
            e.preventDefault();
            alert('Please select a file to import');
            return;
        }
        
        // Show progress
        submitBtn.prop('disabled', true).addClass('btn-loading').html('<i class="fas fa-spinner fa-spin me-2"></i>Importing...');
        uploadProgress.addClass('show');
        
        // Simulate progress (since we can't track actual upload progress without XMLHttpRequest)
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
            progressPercent.text(Math.round(progress) + '%');
        }, 200);
        
        // Note: For real progress tracking, you would need to use XMLHttpRequest instead of form submission
        // This is a visual enhancement for better UX
    });
    
    // Download template button
    $('#downloadTemplateBtn').on('click', function() {
        $(this).addClass('btn-loading').prop('disabled', true);
    });
    
    // Keyboard accessibility
    fileUploadArea.attr('tabindex', '0');
    fileUploadArea.on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInput.click();
        }
    });
});
</script>

@endsection
