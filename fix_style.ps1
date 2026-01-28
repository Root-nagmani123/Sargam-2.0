$file = 'c:\xampp\htdocs\Sargam-2.0\resources\views\admin\course-repository\index.blade.php'
$content = Get-Content $file -Raw -Encoding UTF8

# Find the style section
$styleStart = $content.IndexOf('<style>')
$styleEnd = $content.IndexOf('</style>') + '</style>'.Length

if ($styleStart -ge 0 -and $styleEnd -gt $styleStart) {
    $before = $content.Substring(0, $styleStart)
    $after = $content.Substring($styleEnd)
    
    $newStyle = @'
<style>
    /* Chevron-style divider */
    .breadcrumb-divider-chevron {
        --bs-breadcrumb-divider: "›";
    }

    /* Accessible link styling */
    .breadcrumb-link {
        color: #495057;
        text-decoration: none;
        font-weight: 500;
    }

    .breadcrumb-link:hover,
    .breadcrumb-link:focus {
        color: #0d6efd;
        text-decoration: underline;
    }

    /* Ensure wrapping on small screens */
    .breadcrumb {
        flex-wrap: wrap;
        row-gap: 0.25rem;
    }

    /* Active item emphasis */
    .breadcrumb-item.active {
        color: #0d6efd;
    }

    /* Modal Header - Blue Gradient */
    .upload-modal-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        padding: 1.5rem !important;
    }

    .upload-modal-header .header-icon-circle {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 50%;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
    }

    .upload-modal-header .header-icon-circle .material-icons,
    .upload-modal-header .header-icon-circle .material-symbols-rounded {
        color: #0d6efd;
        font-size: 1.3rem !important;
    }

    .upload-modal-header .modal-title {
        color: #fff;
        font-weight: 600;
        font-size: 1.25rem;
        margin: 0;
    }

    .upload-modal-header .btn-close-white {
        opacity: 0.9;
    }

    /* Upload Zone */
    .upload-zone-ref {
        display: block;
        border: 2px dashed #b6d4fe;
        border-radius: 12px;
        background-color: #f8fbff;
        cursor: pointer;
        transition: border-color 0.2s, background-color 0.2s;
        min-height: 180px;
        padding: 0;
    }

    .upload-zone-ref:hover,
    .upload-zone-ref:focus-within {
        border-color: #0d6efd;
        background-color: #eef5ff;
    }

    .upload-zone-ref.upload-dragover {
        border-color: #0d6efd;
        background-color: #eef5ff;
    }

    .upload-zone-inner {
        cursor: pointer;
        height: 100%;
    }

    .upload-icon-ref {
        font-size: 48px;
        display: block;
        color: #0d6efd;
    }

    .upload-zone-ref .upload-cta {
        color: #0d6efd;
    }

    /* Form Controls */
    .form-control-lg {
        border-color: #e0e0e0;
        border-radius: 0.5rem;
    }

    .form-control-lg:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Buttons */
    .btn-cancel-ref {
        background-color: #fff;
        border: 1px solid #dc3545;
        color: #dc3545;
        border-radius: 0.5rem;
        font-weight: 500;
    }

    .btn-cancel-ref:hover {
        background-color: #fff5f5;
        border-color: #dc3545;
        color: #b02a37;
    }
</style>
'@
    
    $newContent = $before + $newStyle + $after
    Set-Content $file -Value $newContent -Encoding UTF8 -NoNewline
    Write-Output "Style section updated successfully"
} else {
    Write-Output "Could not find style section"
}
