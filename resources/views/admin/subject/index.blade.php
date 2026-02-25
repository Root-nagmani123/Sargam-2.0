@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')

<style>
    /* Responsive table styles */
    .subject-index .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .subject-index #zero_config {
        width: 100% !important;
        table-layout: auto;
    }

    /* Ensure proper column wrapping on smaller screens */
    .subject-index #zero_config td,
    .subject-index #zero_config th {
        white-space: normal;
        word-wrap: break-word;
        vertical-align: middle;
    }

    /* Only apply text-nowrap to specific columns */
    .subject-index #zero_config td.text-nowrap,
    .subject-index #zero_config th.text-nowrap {
        white-space: nowrap;
    }

    /* Make subject name and short name columns wrap on smaller screens */
    .subject-index #zero_config td:nth-child(2),
    .subject-index #zero_config td:nth-child(3) {
        max-width: 200px;
        word-break: break-word;
    }

    /* Ensure all columns are visible and wrap properly */
    .subject-index #zero_config th,
    .subject-index #zero_config td {
        min-width: 0;
    }

    /* Responsive child row styling - improve appearance */
    .subject-index .dtr-details {
        display: block;
        width: 100%;
        padding: 0.75rem;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        margin-top: 0.5rem;
    }

    .subject-index .dtr-details li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #dee2e6;
    }

    .subject-index .dtr-details li:last-child {
        border-bottom: none;
    }

    .subject-index .dtr-title {
        font-weight: 600;
        margin-right: 1rem;
        min-width: 140px;
        color: #495057;
    }

    .subject-index .dtr-data {
        text-align: right;
        flex: 1;
    }

    /* Hide responsive control button completely - multiple selectors to catch all cases */
    .subject-index #zero_config .dtr-control,
    .subject-index #zero_config th.dtr-control,
    .subject-index #zero_config td.dtr-control,
    .subject-index #zero_config thead th.dtr-control,
    .subject-index #zero_config tbody td.dtr-control,
    .subject-index #zero_config tbody tr td:first-child.dtr-control,
    .subject-index #zero_config thead tr th:first-child.dtr-control,
    .subject-index #zero_config tbody tr > td.dtr-control,
    .subject-index #zero_config thead tr > th.dtr-control {
        display: none !important;
        width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        visibility: hidden !important;
        position: absolute !important;
        left: -9999px !important;
    }
    
    /* Remove any responsive control column */
    .subject-index #zero_config colgroup col.dtr-control {
        display: none !important;
        width: 0 !important;
    }
    
    /* Hide control column if it's the first column */
    .subject-index #zero_config thead tr th:first-child.dtr-control,
    .subject-index #zero_config tbody tr td:first-child.dtr-control {
        display: none !important;
        width: 0 !important;
        min-width: 0 !important;
        max-width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
    }

    /* Ensure action buttons are visible and properly spaced */
    .subject-index #zero_config td .d-inline-flex {
        flex-wrap: nowrap;
        gap: 0.5rem;
    }

    /* Mobile optimizations */
    @media (max-width: 575px) {
        .subject-index .card-body {
            padding: 0.75rem;
        }

        .subject-index #zero_config {
            font-size: 0.875rem;
        }

        .subject-index #zero_config th,
        .subject-index #zero_config td {
            padding: 0.5rem 0.25rem;
        }

        .subject-index #zero_config th:nth-child(2),
        .subject-index #zero_config td:nth-child(2),
        .subject-index #zero_config th:nth-child(3),
        .subject-index #zero_config td:nth-child(3) {
            max-width: 120px;
        }

        .subject-index .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }

        .subject-index .d-flex.justify-content-between .btn {
            width: 100%;
            justify-content: center;
        }

        .subject-index .d-flex.justify-content-between h4 {
            font-size: 1.1rem;
        }
    }

    /* Small tablet optimizations */
    @media (min-width: 576px) and (max-width: 767px) {
        .subject-index #zero_config th,
        .subject-index #zero_config td {
            padding: 0.75rem 0.5rem;
            font-size: 0.9rem;
        }

        .subject-index #zero_config th:nth-child(2),
        .subject-index #zero_config td:nth-child(2),
        .subject-index #zero_config th:nth-child(3),
        .subject-index #zero_config td:nth-child(3) {
            max-width: 150px;
        }
    }

    /* Tablet optimizations */
    @media (min-width: 768px) and (max-width: 991px) {
        .subject-index #zero_config th,
        .subject-index #zero_config td {
            padding: 0.75rem 0.5rem;
        }

        .subject-index #zero_config th:nth-child(2),
        .subject-index #zero_config td:nth-child(2),
        .subject-index #zero_config th:nth-child(3),
        .subject-index #zero_config td:nth-child(3) {
            max-width: 180px;
        }
    }
</style>

<div class="container-fluid subject-index">
    <x-breadcrum title="Subject"></x-breadcrum>
    <x-session_message />
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 class="fw-semibold text-dark mb-0">Subject</h4>
                <div class="d-flex align-items-center gap-2">
                    <!-- Add New Button -->
                    <a href="{{ route('subject.create') }}" class="btn btn-primary px-3 py-2 rounded-1 shadow-sm">
                        <i class="material-icons menu-icon material-symbols-rounded"
                            style="font-size: 20px; vertical-align: middle;">add</i>
                        Add Subject
                    </a>
                </div>
            </div>
            <hr>

            <div id="zero_config_table">

                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="zero_config" style="width:100%" data-responsive="false">
                        <thead>
                            <tr>
                                <th class="text-nowrap">S.No.</th>
                                <th>Major Subject Name</th>
                                <th>Short Name</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subjects as $key => $subject)
                            <tr>
                                <td class="text-nowrap">{{ $subjects->firstItem() + $key }}</td>
                                <td>{{ $subject->subject_name }}</td>
                                <td>{{ $subject->sub_short_name }}</td>
                                <td class="text-nowrap">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="subject_master" data-column="active_inactive"
                                            data-id="{{ $subject->pk }}"
                                            {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <div class="d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Subject actions">

                                        <!-- Edit -->
                                        <a href="{{ route('subject.edit', $subject->pk) }}"
                                            class="text-primary d-flex align-items-center gap-1"
                                            aria-label="Edit subject">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;"
                                                aria-hidden="true">edit</i>
                                        </a>

                                        <!-- Delete -->
                                        @if ($subject->active_inactive == 1)
                                        <a href="javascript:void(0)"
                                            class="text-primary d-flex align-items-center gap-1"
                                            title="Cannot delete active subject">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;"
                                                aria-hidden="true">delete</i>
                                        </a>
                                        @else
                                        <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                            @csrf
                                            @method('DELETE')
                                            <a href="javascript:void(0)" onclick="event.preventDefault(); this.closest('form').submit();"
                                                class="text-primary d-flex align-items-center gap-1"
                                                aria-label="Delete subject">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">delete</i>
                                            </a>
                                        </form>
                                        @endif

                                    </div>

                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <p class="text-muted mb-0">No subjects found.</p>
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

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";

// Function to remove responsive control arrow
function removeResponsiveControl() {
    // Remove control column from header
    $('.subject-index #zero_config thead th.dtr-control').remove();
    // Remove control cells from body
    $('.subject-index #zero_config tbody td.dtr-control').remove();
    // Hide any remaining control elements
    $('.subject-index #zero_config .dtr-control').hide().remove();
    // Remove control column from colgroup if exists
    $('.subject-index #zero_config colgroup col.dtr-control').remove();
}

// Initialize DataTables without responsive mode to prevent arrow from showing
$(document).ready(function() {
    // Wait a bit to ensure global initialization has run, then override it
    setTimeout(function() {
        // First, remove any existing control columns
        removeResponsiveControl();
        
        // Destroy existing DataTable if already initialized
        if ($.fn.DataTable.isDataTable('#zero_config')) {
            $('#zero_config').DataTable().destroy();
        }
        
        // Initialize DataTable with responsive disabled
        $('#zero_config').DataTable({
            responsive: false,
            autoWidth: false,
            pageLength: 10,
            language: {
                emptyTable: "No subjects found."
            }
        });
        
        // Remove control elements after initialization
        removeResponsiveControl();
    }, 300);
    
    // Also run after window load to catch any late initializations
    $(window).on('load', function() {
        setTimeout(function() {
            removeResponsiveControl();
        }, 500);
    });
    
    // Adjust table on window resize
    $(window).on('resize', function() {
        if ($.fn.DataTable.isDataTable('#zero_config')) {
            $('#zero_config').DataTable().columns.adjust();
        }
        // Remove control if it appears again
        removeResponsiveControl();
    });
    
    // Monitor for any DataTables responsive events and remove control
    $(document).on('responsive-display', function() {
        removeResponsiveControl();
    });
});
</script>
@endsection

@section('scripts')
<script>
// Ensure this runs after all other scripts including datatable-basic.init.js
$(window).on('load', function() {
    setTimeout(function() {
        // Remove responsive control arrow completely
        function forceRemoveControl() {
            // Remove from DOM
            $('.subject-index #zero_config thead th.dtr-control').remove();
            $('.subject-index #zero_config tbody td.dtr-control').remove();
            $('.subject-index #zero_config .dtr-control').remove();
            $('.subject-index #zero_config colgroup col.dtr-control').remove();
            
            // Hide with CSS as backup
            $('.subject-index #zero_config .dtr-control').css({
                'display': 'none !important',
                'visibility': 'hidden !important',
                'width': '0 !important',
                'padding': '0 !important'
            });
        }
        
        // If table is already initialized, destroy and reinitialize without responsive
        if ($.fn.DataTable.isDataTable('#zero_config')) {
            var currentSettings = $('#zero_config').DataTable().settings()[0];
            $('#zero_config').DataTable().destroy();
            
            // Reinitialize without responsive
            $('#zero_config').DataTable({
                responsive: false,
                autoWidth: false,
                pageLength: currentSettings.oInit.pageLength || 10,
                language: {
                    emptyTable: "No subjects found."
                }
            });
        }
        
        // Remove control elements
        forceRemoveControl();
        
        // Set up observer to remove control if it appears again
        var observer = new MutationObserver(function(mutations) {
            forceRemoveControl();
        });
        
        var tableElement = document.querySelector('.subject-index #zero_config');
        if (tableElement) {
            observer.observe(tableElement, {
                childList: true,
                subtree: true
            });
        }
        
        // Also check periodically for a short time
        var checkInterval = setInterval(function() {
            forceRemoveControl();
        }, 100);
        
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 2000);
        
    }, 500);
});
</script>
@endsection