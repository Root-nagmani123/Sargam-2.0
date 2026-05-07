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

<div class="hn-sections d-flex flex-wrap align-items-start gap-1" role="menubar" aria-label="Home navigation sections">

    {{-- GENERAL --}}
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">apps</i>
            <span>General</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.general />
        </div>
    </div>

    {{-- ESTATE MANAGEMENT --}}
    @if(hasRole('Admin') || hasRole('Super Admin') || hasRole('Training-Induction') || hasRole('Training-MCTP') || hasRole('IST') || hasRole('Estate') || hasRole('HAC Person') || hasRole('Staff') || hasRole('Student-OT') || hasRole('Doctor') || hasRole('Guest Faculty') || hasRole('Internal Faculty'))
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">house</i>
            <span>Estate</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_estate_management />
        </div>
    </div>
    @endif

    {{-- MESS MANAGEMENT --}}
    @if(canSeeMessSelfServiceSetup())
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">restaurant_menu</i>
            <span>Mess</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_mess_management />
        </div>
    </div>
    @endif

    {{-- SECURITY --}}
    @if(! hasRole('Student-OT') && ! $isContractualEmployee)
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">shield</i>
            <span>Security</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_security_management />
        </div>
    </div>

    {{-- CENTCOM --}}
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">report_problem</i>
            <span>Centcom</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.setup_issue_management />
        </div>
    </div>
    @endif

</div>
