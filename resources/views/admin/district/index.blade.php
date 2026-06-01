@extends('admin.layouts.master')

@section('title', 'District')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/district-master-admin.css') }}?v={{ @filemtime(public_path('css/district-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid dst-master-page py-4">
    <x-breadcrum title="District List">
        <a href="{{ route('master.district.create') }}" id="openCreateDistrict"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add District</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card dst-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="dstDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="dstListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search districts">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel dst-dt-panel">
                <div class="table-responsive dst-dt-scroll">
                    <table id="district-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">District Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($districts as $key => $district)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-dst-name="{{ $district->district_name ?? '' }}">
                                <td>{{ $districts->firstItem() + $key }}</td>
                                <td class="sorting_1">{{ $district->district_name }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block dst-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="state_district_mapping" data-column="active_inactive"
                                            data-id="{{ $district->pk }}"
                                            {{ $district->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="dst-district-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="District actions">
                                        <a href="{{ route('master.district.edit', $district->pk) }}"
                                            id="actionMenu{{ $district->pk }}"
                                            class="dst-edit-link"
                                            aria-label="Edit district">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        <form action="{{ route('master.district.delete', $district->pk) }}"
                                            method="POST"
                                            class="d-inline dst-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                class="dst-delete-btn"
                                                aria-label="Delete district"
                                                title="Delete district"
                                                onclick="event.preventDefault(); if({{ $district->active_inactive }} == 1) return; if(confirm('Are you sure you want to delete this?')) { this.closest('form').submit(); }"
                                                {{ $district->active_inactive == 1 ? 'disabled' : '' }}>
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="dst-empty-row">
                                <td colspan="4" class="dst-empty-state text-center">
                                    <i class="bi bi-geo-alt display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Districts Found</h5>
                                    <p class="text-secondary mb-0">Add a district to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($districts->hasPages() || $districts->total() > 0)
                <div class="programme-dt-footer dst-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                    <nav aria-label="District pagination" class="dst-pagination">
                        {{ $districts->withQueryString()->links('vendor.pagination.custom') }}
                    </nav>
                    <div class="dst-dt-info">
                        @if($districts->total() > 0)
                            Showing {{ $districts->firstItem() }}–{{ $districts->lastItem() }} of {{ $districts->total() }} items
                        @else
                            Showing 0 of 0 items
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="districtModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered dst-district-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add District</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-4 placeholder-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tableSelector = '#district-master-table';
    var modalEl = document.getElementById('districtModal');
    var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
    var modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var statesByCountryUrl = "{{ route('master.country.get.state.by.country') }}";

    if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    function bindDistrictFormCascades(form) {
        if (!form || typeof jQuery === 'undefined') {
            return;
        }

        var $form = jQuery(form);
        var $country = $form.find('#country_master_pk');
        var $state = $form.find('#state');
        var selectedState = $form.data('selected-state') || $state.val();

        function populateStates(countryId, keepStateId) {
            $state.html('<option value="">Loading...</option>');

            if (!countryId) {
                $state.html('<option value="">Select State</option>');
                return;
            }

            jQuery.post(statesByCountryUrl, {
                country_id: countryId,
                _token: csrfToken
            })
                .done(function (res) {
                    $state.html('<option value="">Select State</option>');
                    if (res && res.states) {
                        jQuery.each(res.states, function (i, item) {
                            var $opt = jQuery('<option>', {
                                value: item.pk,
                                text: item.state_name
                            });
                            if (keepStateId && String(keepStateId) === String(item.pk)) {
                                $opt.prop('selected', true);
                            }
                            $state.append($opt);
                        });
                    }
                })
                .fail(function () {
                    $state.html('<option value="">Select State</option>');
                });
        }

        $country.off('change.dstCascade').on('change.dstCascade', function () {
            populateStates(jQuery(this).val(), '');
        });

        if ($country.val()) {
            populateStates($country.val(), selectedState);
        }
    }

    function loadDistrictForm(url, title) {
        if (!modalEl || !modalBody || !modalTitle) {
            return;
        }

        modalTitle.textContent = title || 'Add District';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                modalBody.innerHTML = html;
                var form = modalBody.querySelector('#districtForm');
                if (form) {
                    bindDistrictFormCascades(form);
                }
            })
            .catch(function () {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0 rounded-3">Failed to load form.</div>';
            });

        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function clearDstFormErrors(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.dst-field-error').forEach(function (el) {
            el.textContent = '';
            el.classList.add('d-none');
        });
    }

    function showDstFormErrors(form, errors) {
        if (!form || !errors) {
            return;
        }
        Object.entries(errors).forEach(function (entry) {
            var field = entry[0];
            var messages = entry[1];
            var fieldKey = field;
            if (field.indexOf('state_master_pk') === 0) {
                fieldKey = 'state_master_pk';
            }
            var input = form.querySelector('[name="' + fieldKey + '"]');
            var errorEl = form.querySelector('.dst-field-error[data-field="' + fieldKey + '"]');
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorEl && messages && messages[0]) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('d-none');
            }
        });
    }

    function bindDistrictModalHandlers() {
        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('#openCreateDistrict');
            if (createBtn) {
                e.preventDefault();
                loadDistrictForm(createBtn.getAttribute('href'), 'Add District');
                return;
            }

            var editLink = e.target.closest('.dst-edit-link');
            if (editLink) {
                e.preventDefault();
                loadDistrictForm(editLink.getAttribute('href'), 'Edit District');
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('#districtForm');
            if (!form) {
                return;
            }

            e.preventDefault();
            clearDstFormErrors(form);

            var submitBtn = form.querySelector('#saveDistrictForm');
            var originalSubmitText = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
            }

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(function (res) {
                    if (res.ok) {
                        if (window.bootstrap && bootstrap.Modal && modalEl) {
                            var modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }
                        window.location.reload();
                        return null;
                    }

                    if (res.status === 422) {
                        return res.json().then(function (data) {
                            showDstFormErrors(form, data?.errors || {});
                        });
                    }

                    throw new Error('Save failed. Please try again.');
                })
                .catch(function (err) {
                    alert(err.message || 'Save failed. Please try again.');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalSubmitText;
                    }
                });
        });

        @if (request('open_dst_modal') === 'add')
            loadDistrictForm("{{ route('master.district.create') }}", 'Add District');
        @elseif (request('open_dst_modal') === 'edit' && request('dst_id'))
            loadDistrictForm("{{ route('master.district.edit', request('dst_id')) }}", 'Edit District');
        @endif
    }

    function styleDstEditLink($link) {
        $link.removeClass('dropdown-item d-flex align-items-center gap-2');
        $link.addClass('dst-action-btn dst-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleDstDeleteBtn($btn) {
        $btn.removeClass('dropdown-item d-flex align-items-center gap-2 text-danger');
        $btn.addClass('dst-action-btn dst-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateDstStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.dst-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateDstRows() {
        jQuery(tableSelector + ' tbody tr').not('.dst-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('dst-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 4) {
                return;
            }

            var $statusCell = $cells.eq(2);
            var $actionCell = $cells.eq(3);
            var $toggleWrap = $statusCell.find('.dst-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.dst-district-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge dst-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'dst-district-actions',
                    role: 'group',
                    'aria-label': 'District actions'
                });

                var $editLink = $sourceActions.find('.dst-edit-link').first();
                if ($editLink.length) {
                    styleDstEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('dst-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.dst-delete-btn').first();
                if ($deleteBtn.length) {
                    styleDstDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.dst-delete-form');
                    $group.append($form.length ? $form : $deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('dst-row-decorated');
        });
    }

    function bindDstListSearch() {
        jQuery('#dstListSearch').on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.dst-empty-row').each(function () {
                var $row = jQuery(this);
                var text = ($row.data('dst-name') || '').toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function initDstPage() {
        bindDistrictModalHandlers();

        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateDstRows();
        bindDstListSearch();

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateDstStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDstPage);
    } else {
        initDstPage();
    }
})();
</script>
@endpush
