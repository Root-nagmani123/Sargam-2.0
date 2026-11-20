{{-- Customise: the login reorders and hides this dashboard's cards and panels, for itself only
     (DashboardLayoutController, user_dashboard_layouts). Lists every card its roles have here,
     hidden ones included, in its current order. Saving reloads the page in the new layout. --}}
@if($dashboardModule && ($customiseCards->isNotEmpty() || $customisePanels->isNotEmpty()))
<div class="modal fade" id="dashboardCustomiseModal" tabindex="-1" aria-labelledby="dashboardCustomiseTitle" aria-hidden="true"
    data-category="{{ $dashboardModule->id }}" data-save="{{ route('admin.dashboard.layout.store') }}" data-reset="{{ route('admin.dashboard.layout.destroy') }}">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashboardCustomiseTitle">Customise {{ $dashboardTitle ?? 'Dashboard' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-body-secondary mb-3">Choose what you see here and in what order. Only you see this layout; it changes nothing for anyone else.</p>
                @if($customiseCards->isNotEmpty())
                <h6 class="dashboard-card-group-title">Cards</h6>
                <ul class="list-unstyled mb-4 dashboard-customise-list" id="dashboardCustomiseCards">
                    @foreach($customiseCards as $card)
                    <li class="dashboard-customise-item" data-key="{{ $card['key'] }}">
                        <div class="form-check mb-0 flex-grow-1 min-w-0">
                            <input class="form-check-input" type="checkbox" id="dashboard-customise-{{ $card['key'] }}" @checked(! $card['hidden'])>
                            <label class="form-check-label" for="dashboard-customise-{{ $card['key'] }}">{{ $card['label'] }}</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" data-move="-1" aria-label="Move {{ $card['label'] }} up"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-sm btn-light border" data-move="1" aria-label="Move {{ $card['label'] }} down"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
                    </li>
                    @endforeach
                </ul>
                @endif
                @if($customisePanels->isNotEmpty())
                <h6 class="dashboard-card-group-title">Panels</h6>
                <ul class="list-unstyled mb-0 dashboard-customise-list" id="dashboardCustomisePanels">
                    @foreach($customisePanels as $panel)
                    <li class="dashboard-customise-item" data-key="{{ $panel['key'] }}">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="dashboard-customise-{{ $panel['key'] }}" @checked(! $panel['hidden'])>
                            <label class="form-check-label" for="dashboard-customise-{{ $panel['key'] }}">{{ $panel['label'] }}</label>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
                <p class="small text-danger mt-3 mb-0" id="dashboardCustomiseError" role="alert" hidden></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link text-danger me-auto px-0" id="dashboardCustomiseReset">Reset to default</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="dashboardCustomiseSave">Save</button>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        'use strict';
        var modal = document.getElementById('dashboardCustomiseModal');
        if (!modal) { return; }
        var body = modal.querySelector('.modal-body');
        var original = body.innerHTML;
        var error = function () { return document.getElementById('dashboardCustomiseError'); };
        var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '{{ csrf_token() }}';

        // Move a card up or down; focus stays on the button that moved it.
        body.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-move]');
            if (!btn) { return; }
            var item = btn.closest('li');
            var target = btn.getAttribute('data-move') === '-1' ? item.previousElementSibling : item.nextElementSibling;
            if (!target) { return; }
            if (btn.getAttribute('data-move') === '-1') { item.parentNode.insertBefore(item, target); } else { item.parentNode.insertBefore(target, item); }
            btn.focus();
        });

        // Cancel / close forgets unsaved changes.
        modal.addEventListener('hidden.bs.modal', function () { body.innerHTML = original; });

        var send = function (method, url, payload) {
            var buttons = modal.querySelectorAll('.modal-footer button');
            buttons.forEach(function (b) { b.disabled = true; });
            error().hidden = true;
            fetch(url, {
                method: method,
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            }).then(function (r) {
                if (!r.ok) { throw new Error(r.status); }
                window.location.reload();
            }).catch(function () {
                buttons.forEach(function (b) { b.disabled = false; });
                error().textContent = 'Could not save your layout. Please try again.';
                error().hidden = false;
            });
        };

        var category = Number(modal.getAttribute('data-category'));
        modal.querySelector('#dashboardCustomiseSave').addEventListener('click', function () {
            var keys = function (sel) { return Array.prototype.map.call(body.querySelectorAll(sel), function (li) { return li.getAttribute('data-key'); }); };
            var hidden = Array.prototype.filter.call(body.querySelectorAll('.dashboard-customise-item'), function (li) {
                return !li.querySelector('input[type="checkbox"]').checked;
            }).map(function (li) { return li.getAttribute('data-key'); });
            send('POST', modal.getAttribute('data-save'), { category: category, order: keys('#dashboardCustomiseCards > li'), hidden: hidden });
        });
        modal.querySelector('#dashboardCustomiseReset').addEventListener('click', function () {
            send('DELETE', modal.getAttribute('data-reset'), { category: category });
        });
    })();
</script>
@endif
