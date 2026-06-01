@extends('admin.layouts.master')

@section('title', 'City')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/city-master-admin.css') }}?v={{ @filemtime(public_path('css/city-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid cty-master-page py-4">
    <x-breadcrum title="City List">
        <a href="{{ route('master.city.create') }}" id="openCreateCity"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-2 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add City</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="card cty-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="ctyDtSearch" class="programme-dt-search ms-lg-auto">
                    <div class="dataTables_filter">
                        <label>
                            <input type="search"
                                id="ctyListSearch"
                                class="form-control"
                                placeholder="Search"
                                aria-label="Search cities">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel cty-dt-panel">
                <div class="table-responsive cty-dt-scroll">
                    <table id="city-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">City Name</th>
                                <th scope="col">District Name</th>
                                <th scope="col">State Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cities as $key => $city)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-city-name="{{ $city->city_name ?? '' }}"
                                data-cty-district="{{ $city->district?->district_name ?? '' }}"
                                data-cty-state="{{ $city->state?->state_name ?? '' }}">
                                <td>{{ $cities->firstItem() + $key }}</td>
                                <td>{{ $city->city_name }}</td>
                                <td>{{ $city->district?->district_name ?? 'N/A' }}</td>
                                <td>{{ optional($city->state)->state_name ?? 'N/A' }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block cty-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="city_master" data-column="active_inactive"
                                            data-id="{{ $city->pk }}"
                                            {{ $city->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="cty-city-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="City actions">
                                        <a href="{{ route('master.city.edit', $city->pk) }}"
                                            id="actionMenu{{ $city->pk }}"
                                            class="cty-edit-link"
                                            aria-label="Edit city">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        <form action="{{ route('master.city.delete', $city->pk) }}" method="POST"
                                            class="d-inline cty-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                class="cty-delete-btn"
                                                aria-label="Delete city"
                                                title="Delete city"
                                                onclick="event.preventDefault(); if({{ $city->active_inactive }} == 1) return; if(confirm('Are you sure you want to delete this?')) { this.closest('form').submit(); }"
                                                {{ $city->active_inactive == 1 ? 'disabled' : '' }}>
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="cty-empty-row">
                                <td colspan="6" class="cty-empty-state text-center">
                                    <i class="bi bi-buildings display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Cities Found</h5>
                                    <p class="text-secondary mb-0">Add a city to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($cities->hasPages() || $cities->total() > 0)
                <div class="programme-dt-footer cty-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                    <nav aria-label="City pagination" class="cty-pagination">
                        {{ $cities->withQueryString()->links('vendor.pagination.custom') }}
                    </nav>
                    <div class="cty-dt-info">
                        @if($cities->total() > 0)
                            Showing {{ $cities->firstItem() }}–{{ $cities->lastItem() }} of {{ $cities->total() }} items
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

<div class="modal fade" id="cityModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered cty-city-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add City</h5>
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
    var tableSelector = '#city-master-table';
    var modalEl = document.getElementById('cityModal');
    var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
    var modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var statesUrl = "{{ route('master.city.getStates') }}";
    var districtsUrl = "{{ route('master.city.getDistricts') }}";

    if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    function bindCityFormCascades(form) {
        if (!form || typeof jQuery === 'undefined') {
            return;
        }

        var $form = jQuery(form);
        var $country = $form.find('#country_master_pk');
        var $state = $form.find('#state_master_pk');
        var $district = $form.find('#district_master_pk');
        var selectedState = $form.data('selected-state') || $state.val();
        var selectedDistrict = $form.data('selected-district') || $district.val();

        function populateDistricts(stateId, keepDistrictId) {
            $district.html('<option value="">Loading...</option>');

            if (!stateId) {
                $district.html('<option value="">Select District</option>');
                return;
            }

            jQuery.post(districtsUrl, {
                state_id: stateId,
                _token: csrfToken
            })
                .done(function (districts) {
                    $district.html('<option value="">Select District</option>');
                    jQuery.each(districts || [], function (i, item) {
                        var $opt = jQuery('<option>', {
                            value: item.pk,
                            text: item.district_name
                        });
                        if (keepDistrictId && String(keepDistrictId) === String(item.pk)) {
                            $opt.prop('selected', true);
                        }
                        $district.append($opt);
                    });
                })
                .fail(function () {
                    $district.html('<option value="">Select District</option>');
                });
        }

        function populateStates(countryId, keepStateId, thenDistricts) {
            $state.html('<option value="">Loading...</option>');

            if (!countryId) {
                $state.html('<option value="">Select State</option>');
                $district.html('<option value="">Select District</option>');
                return;
            }

            jQuery.post(statesUrl, {
                country_id: countryId,
                _token: csrfToken
            })
                .done(function (states) {
                    $state.html('<option value="">Select State</option>');
                    jQuery.each(states || [], function (i, item) {
                        var $opt = jQuery('<option>', {
                            value: item.pk,
                            text: item.state_name
                        });
                        if (keepStateId && String(keepStateId) === String(item.pk)) {
                            $opt.prop('selected', true);
                        }
                        $state.append($opt);
                    });

                    if (thenDistricts && keepStateId) {
                        populateDistricts(keepStateId, selectedDistrict);
                    }
                })
                .fail(function () {
                    $state.html('<option value="">Select State</option>');
                });
        }

        $country.off('change.ctyCascade').on('change.ctyCascade', function () {
            populateStates(jQuery(this).val(), '', false);
            $district.html('<option value="">Select District</option>');
        });

        $state.off('change.ctyCascade').on('change.ctyCascade', function () {
            populateDistricts(jQuery(this).val(), '');
        });

        if ($country.val()) {
            populateStates($country.val(), selectedState, true);
        }
    }

    function loadCityForm(url, title) {
        if (!modalEl || !modalBody || !modalTitle) {
            return;
        }

        modalTitle.textContent = title || 'Add City';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                modalBody.innerHTML = html;
                var form = modalBody.querySelector('#cityForm');
                if (form) {
                    bindCityFormCascades(form);
                }
            })
            .catch(function () {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0 rounded-3">Failed to load form.</div>';
            });

        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function clearCtyFormErrors(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.cty-field-error').forEach(function (el) {
            el.textContent = '';
            el.classList.add('d-none');
        });
    }

    function showCtyFormErrors(form, errors) {
        if (!form || !errors) {
            return;
        }
        Object.entries(errors).forEach(function (entry) {
            var field = entry[0];
            var messages = entry[1];
            var input = form.querySelector('[name="' + field + '"]');
            var errorEl = form.querySelector('.cty-field-error[data-field="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorEl && messages && messages[0]) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('d-none');
            }
        });
    }

    function bindCityModalHandlers() {
        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('#openCreateCity');
            if (createBtn) {
                e.preventDefault();
                loadCityForm(createBtn.getAttribute('href'), 'Add City');
                return;
            }

            var editLink = e.target.closest('.cty-edit-link');
            if (editLink) {
                e.preventDefault();
                loadCityForm(editLink.getAttribute('href'), 'Edit City');
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('#cityForm');
            if (!form) {
                return;
            }

            e.preventDefault();
            clearCtyFormErrors(form);

            var submitBtn = form.querySelector('#saveCityForm');
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
                            showCtyFormErrors(form, data?.errors || {});
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

        @if (request('open_cty_modal') === 'add')
            loadCityForm("{{ route('master.city.create') }}", 'Add City');
        @elseif (request('open_cty_modal') === 'edit' && request('cty_id'))
            loadCityForm("{{ route('master.city.edit', request('cty_id')) }}", 'Edit City');
        @endif
    }

    function styleCtyEditLink($link) {
        $link.removeClass('dropdown-item d-flex align-items-center gap-2');
        $link.addClass('cty-action-btn cty-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleCtyDeleteBtn($btn) {
        $btn.removeClass('dropdown-item d-flex align-items-center gap-2 text-danger');
        $btn.addClass('cty-action-btn cty-action-delete');
        $btn.empty().append('<i class="bi bi-trash" aria-hidden="true"></i>');
    }

    function updateCtyStatusBadge($toggle, isActive) {
        var $badge = $toggle.closest('tr').find('.cty-status-badge');
        if (!$badge.length) {
            return;
        }
        $badge
            .removeClass('programme-status-badge--active programme-status-badge--inactive')
            .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
            .text(isActive ? 'Active' : 'Inactive');
    }

    function decorateCtyRows() {
        jQuery(tableSelector + ' tbody tr').not('.cty-empty-row').each(function () {
            var $row = jQuery(this);

            if ($row.hasClass('cty-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if ($cells.length < 6) {
                return;
            }

            var $statusCell = $cells.eq(4);
            var $actionCell = $cells.eq(5);
            var $toggleWrap = $statusCell.find('.cty-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.cty-city-actions-source').first();

            if ($toggle.length) {
                var isActive = $toggle.is(':checked');
                var badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';
                var label = isActive ? 'Active' : 'Inactive';

                $toggleWrap.detach();
                $statusCell.empty().append(
                    jQuery('<span>', {
                        class: 'badge rounded-pill programme-status-badge cty-status-badge ' + badgeClass,
                        text: label
                    })
                );

                var $group = jQuery('<div>', {
                    class: 'cty-city-actions',
                    role: 'group',
                    'aria-label': 'City actions'
                });

                var $editLink = $sourceActions.find('.cty-edit-link').first();
                if ($editLink.length) {
                    styleCtyEditLink($editLink);
                    $group.append($editLink);
                }

                $toggleWrap.addClass('cty-action-switch-wrap mb-0');
                $group.append($toggleWrap);

                var $deleteBtn = $sourceActions.find('.cty-delete-btn').first();
                if ($deleteBtn.length) {
                    styleCtyDeleteBtn($deleteBtn);
                    var $form = $deleteBtn.closest('.cty-delete-form');
                    $group.append($form.length ? $form : $deleteBtn);
                }

                $actionCell.empty().append($group);
            }

            $row.addClass('cty-row-decorated');
        });
    }

    function bindCtyListSearch() {
        jQuery('#ctyListSearch').on('input', function () {
            var query = jQuery(this).val().toLowerCase().trim();
            jQuery(tableSelector + ' tbody tr').not('.cty-empty-row').each(function () {
                var $row = jQuery(this);
                var text = (
                    ($row.data('city-name') || '') + ' ' +
                    ($row.data('cty-district') || '') + ' ' +
                    ($row.data('cty-state') || '')
                ).toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function initCtyPage() {
        bindCityModalHandlers();

        if (typeof jQuery === 'undefined') {
            return;
        }

        decorateCtyRows();
        bindCtyListSearch();

        jQuery(document).on('change', tableSelector + ' .status-toggle', function () {
            var $toggle = jQuery(this);
            window.setTimeout(function () {
                updateCtyStatusBadge($toggle, $toggle.is(':checked'));
            }, 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCtyPage);
    } else {
        initCtyPage();
    }
})();
</script>
@endpush
