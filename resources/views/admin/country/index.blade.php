@extends('admin.layouts.master')

@section('title', 'Country List')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/country-master-admin.css') }}?v={{ @filemtime(public_path('css/country-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid cty-master-page py-4">
    <x-breadcrum title="Country List">
        <a href="{{ route('master.country.create') }}" id="openCreateCountry"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Country</span>
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
                                aria-label="Search countries">
                        </label>
                    </div>
                </div>
            </div>

            <div class="programme-dt-panel cty-dt-panel">
                <div class="table-responsive cty-dt-scroll">
                    <table id="country-master-table"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table border-0">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Country Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($countries as $index => $country)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}"
                                data-cty-name="{{ $country->country_name ?? '' }}">
                                <td>{{ $countries->firstItem() + $index }}</td>
                                <td>{{ $country->country_name }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-block cty-status-toggle-source">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="country_master" data-column="active_inactive"
                                            data-id="{{ $country->pk }}"
                                            {{ $country->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="cty-country-actions-source d-inline-flex align-items-center gap-2" role="group"
                                        aria-label="Country actions">
                                        <a href="{{ route('master.country.edit', $country->pk) }}"
                                            class="cty-edit-link"
                                            aria-label="Edit country">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">edit</i>
                                        </a>

                                        @if($country->active_inactive == 1)
                                        <button type="button"
                                            class="cty-delete-btn"
                                            disabled
                                            aria-disabled="true"
                                            title="Cannot delete active country">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                        </button>
                                        @else
                                        <form action="{{ route('master.country.delete', $country->pk) }}" method="POST"
                                            class="d-inline cty-delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="cty-delete-btn"
                                                aria-label="Delete country"
                                                title="Delete country">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">delete</i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="cty-empty-row">
                                <td colspan="4" class="cty-empty-state text-center">
                                    <i class="bi bi-globe2 display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Countries Found</h5>
                                    <p class="text-secondary mb-0">Add a country to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($countries->hasPages() || $countries->total() > 0)
                <div class="programme-dt-footer cty-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                    <nav aria-label="Country pagination" class="cty-pagination">
                        {{ $countries->withQueryString()->links('vendor.pagination.custom') }}
                    </nav>
                    <div class="cty-dt-info">
                        @if($countries->total() > 0)
                            Showing {{ $countries->firstItem() }}–{{ $countries->lastItem() }} of {{ $countries->total() }} items
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

<div class="modal fade" id="countryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered cty-country-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <h5 class="modal-title mb-0 fw-bold">Add Country</h5>
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
    var tableSelector = '#country-master-table';
    var modalEl = document.getElementById('countryModal');
    var modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
    var modalTitle = modalEl ? modalEl.querySelector('.modal-title') : null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    function loadCountryForm(url, title) {
        if (!modalEl || !modalBody || !modalTitle) {
            return;
        }

        modalTitle.textContent = title || 'Add Country';
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (html) { modalBody.innerHTML = html; })
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
            var fieldKey = field.indexOf('country_name') === 0 ? 'country_name' : field;
            var input = form.querySelector('[name="' + fieldKey + '"]');
            var errorEl = form.querySelector('.cty-field-error[data-field="' + fieldKey + '"]');
            if (input) {
                input.classList.add('is-invalid');
            }
            if (errorEl && messages && messages[0]) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('d-none');
            }
        });
    }

    function bindCountryModalHandlers() {
        document.addEventListener('click', function (e) {
            var createBtn = e.target.closest('#openCreateCountry');
            if (createBtn) {
                e.preventDefault();
                loadCountryForm(createBtn.getAttribute('href'), 'Add Country');
                return;
            }

            var editLink = e.target.closest('.cty-edit-link');
            if (editLink) {
                e.preventDefault();
                loadCountryForm(editLink.getAttribute('href'), 'Edit Country');
            }
        });

        document.addEventListener('submit', function (e) {
            var form = e.target.closest('#countryForm');
            if (!form) {
                return;
            }

            e.preventDefault();
            clearCtyFormErrors(form);

            var submitBtn = form.querySelector('#saveCountryForm');
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
            loadCountryForm("{{ route('master.country.create') }}", 'Add Country');
        @elseif (request('open_cty_modal') === 'edit' && request('cty_id'))
            loadCountryForm("{{ route('master.country.edit', request('cty_id')) }}", 'Edit Country');
        @endif
    }

    function styleCtyEditLink($link) {
        $link.removeClass('btn btn-sm btn-outline-primary d-flex align-items-center gap-1');
        $link.addClass('cty-action-btn cty-action-edit');
        $link.empty().append('<i class="bi bi-pencil" aria-hidden="true"></i>');
    }

    function styleCtyDeleteBtn($btn) {
        $btn.removeClass('btn btn-sm btn-outline-danger btn-outline-secondary d-flex align-items-center gap-1');
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
            if ($cells.length < 4) {
                return;
            }

            var $statusCell = $cells.eq(2);
            var $actionCell = $cells.eq(3);
            var $toggleWrap = $statusCell.find('.cty-status-toggle-source').first();
            var $toggle = $toggleWrap.find('.status-toggle').first();
            var $sourceActions = $actionCell.find('.cty-country-actions-source').first();

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
                    class: 'cty-country-actions',
                    role: 'group',
                    'aria-label': 'Country actions'
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
                var text = ($row.data('cty-name') || '').toLowerCase();
                $row.toggle(!query || text.indexOf(query) !== -1);
            });
        });
    }

    function initCtyPage() {
        bindCountryModalHandlers();

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
