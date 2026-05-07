@php
    $isContractualEmployee = false;
    $authUser = \Illuminate\Support\Facades\Auth::user();
    if ($authUser && \Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'payroll')) {
        $userId = $authUser->user_id ?? $authUser->pk ?? null;
        if ($userId) {
            $emp = \Illuminate\Support\Facades\DB::table('employee_master')
                ->where('pk', $userId)
                ->orWhere('pk_old', $userId)
                ->first(['payroll']);
            $isContractualEmployee = $emp && (int) ($emp->payroll ?? 0) !== 0;
        }
    }
@endphp

<div class="hn-sections d-flex flex-wrap align-items-start gap-1" role="menubar" aria-label="Setup navigation sections">

    {{-- ACADEMIC --}}
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">dashboard_customize</i>
            <span>Academic</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_academic />
        </div>
    </div>

    {{-- TIME TABLE --}}
    @if(hasRole('Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST'))
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">calendar_month</i>
            <span>Time Table</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_general />
        </div>
    </div>

    {{-- USERS --}}
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">user_attributes</i>
            <span>Users</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_activities />
        </div>
    </div>
    @endif

    {{-- MASTER --}}
    @if(hasRole('Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST'))
    @if(! hasRole('Training-MCTP') && ! hasRole('IST'))
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">menu_open</i>
            <span>Master</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_mappings />
        </div>
    </div>

    {{-- FC FORMS --}}
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">note_add</i>
            <span>FC Forms</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.fc-sidebar />
        </div>
    </div>
    @endif
    @endif

</div>
