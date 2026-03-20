<nav class="sidebar-nav d-block simplebar-scrollable-y" id="menu-right-mini-1" data-simplebar="init">
    <div class="simplebar-wrapper" style="margin: 0px -20px -24px;">
        <div class="simplebar-height-auto-observer-wrapper">
            <div class="simplebar-height-auto-observer"></div>
        </div>
        <div class="simplebar-mask">
            <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content"
                    style="height: 100%; overflow: hidden scroll;">
                    <div class="simplebar-content" style="padding: 20px 0px 24px 20px;">
                        <ul class="sidebar-menu" id="sidebarnav">
                            {{-- GENERAL --}}

                            <!-- Dashboard Link -->
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Dashboard</span>
                                </a>
                            </li>
                            @if(hasRole('Admin') || hasRole('Training-Induction'))
                            <!-- Participant / Dashboard Statistics -->
                            <li class="sidebar-item">
                                <a class="sidebar-link {{ request()->routeIs('admin.dashboard-statistics.*') ? 'active' : '' }}"
                                    href="{{ route('admin.dashboard-statistics.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Batch Profile</span>
                                </a>
                            </li>
                                    @endif
                                      <!-- Notice Notification Route -->
                             <li class="sidebar-item"><a class="sidebar-link"
                                        href="{{ route('admin.notice.index') }}">
                                        <span class="hide-menu small small-sm-normal text-nowrap">Notice Notifications</span>
                                    </a></li>

                            <!-- Faculty Dashboard Route -->
                            @if(hasRole('Doctor'))
                            <li class="sidebar-item"><a class="sidebar-link"
                                    href="{{ route('student.medical.exemption.index') }}">
                                    <span class="hide-menu small small-sm-normal text-nowrap">Student Medical Exemption (Doctor)</span>
                                </a></li>
                            @endif



                            <ul class="sidebar-menu" id="sidebarnav">
                                <li class="sidebar-item" style="background: #4077ad;
                                border-radius: 30px 0px 0px 30px;
                                width: 100%;
                                box-shadow: -2px 3px rgba(251, 248, 248, 0.1);
                                min-width: 250px;">
                                    <a class="sidebar-link d-flex justify-content-between align-items-center"
                                        data-bs-toggle="collapse" href="#generalCollapse" role="button"
                                        aria-expanded="false" aria-controls="generalCollapse">
                                        <span class="hide-menu fw-bold small small-sm-normal text-nowrap">Quick Links</span>
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
                                    </a>
                                </li>
                                <ul class="collapse list-unstyled ps-3" id="generalCollapse">
                                    @php
                                        $quickLinks = \App\Models\QuickLink::query()
                                            ->active()
                                            ->orderBy('position')
                                            ->get(['id', 'label', 'url', 'target_blank']);
                                    @endphp

                                    @if ($quickLinks->isEmpty())
                                        {{-- Fallback (before migrations/seed): keeps the UI usable. --}}
                                        @php
                                            $quickLinks = collect([
                                                (object) ['id' => null, 'label' => 'E-Office', 'url' => 'https://eoffice.lbsnaa.gov.in/', 'target_blank' => true],
                                                (object) ['id' => null, 'label' => 'Medical Center', 'url' => 'http://cghs.lbsnaa.gov.in/', 'target_blank' => true],
                                                (object) ['id' => null, 'label' => 'Library', 'url' => 'https://idpbridge.myloft.xyz/simplesaml/module.php/core/loginuserpass?AuthState=_13df360546d97777e748e8ded7bf639c5c8c45d3d7%3Ahttps%3A%2F%2Fidpbridge.myloft.xyz%2Fsimplesaml%2Fmodule.php%2Fsaml%2Fidp%2FsingleSignOnService%3Fspentityid%3Dhttps%253A%252F%252Felibrarylbsnaa.myloft.xyz%26cookieTime%3D1688360911', 'target_blank' => true],
                                                (object) ['id' => null, 'label' => 'Photo Gallery', 'url' => 'https://rcentre.lbsnaa.gov.in/web/', 'target_blank' => true],
                                            ]);
                                        @endphp
                                    @endif

                                    @foreach ($quickLinks as $link)
                                        <li class="sidebar-item d-flex justify-content-between align-items-center">
                                            <a class="sidebar-link" href="{{ trim($link->url) }}"
                                                target="{{ $link->target_blank ? '_blank' : '_self' }}">
                                                <span class="hide-menu small small-sm-normal text-nowrap">{{ $link->label }}</span>
                                            </a>

                                            @if (($link->id ?? null) && (hasRole('Admin') || hasRole('Super Admin')))
                                                <form method="POST"
                                                    action="{{ route('admin.quick-links.destroy', $link->id) }}"
                                                    onsubmit="return confirm('Delete this quick link?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-link text-danger p-0 ms-2"
                                                        title="Delete quick link"
                                                        aria-label="Delete quick link">
                                                        <i class="material-icons" style="font-size: 18px;">delete</i>
                                                    </button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach

                                    @if (hasRole('Admin') || hasRole('Super Admin'))
                                        <li class="sidebar-item mt-2">
                                            <form method="POST" action="{{ route('admin.quick-links.store') }}">
                                                @csrf
                                                <input type="text" name="label" class="form-control form-control-sm mb-1"
                                                    placeholder="Label" required maxlength="255">
                                                <input type="url" name="url" class="form-control form-control-sm mb-1"
                                                    placeholder="URL (include https://)" required maxlength="2048">
                                                <select name="target_blank" class="form-select form-select-sm mb-1" required>
                                                    <option value="1" selected>Open in New Tab</option>
                                                    <option value="0">Open in Same Tab</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                                    Add Quick Link
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
    </div>
</nav>