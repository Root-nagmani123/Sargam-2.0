@php($fmt = \Modules\GuestTaClaim\Support\Format::class)
@php($desigCol = config('guest-ta-claim.user_designation_column'))

<section class="gtc-card">
    @if ($selected)
        <div class="d-flex align-items-center gap-3">
            <span class="gtc-avatar">{{ $fmt::initials($selected->{$nameCol}) }}</span>
            <div class="min-w-0">
                <h2 class="mb-1 text-break">{{ mb_strtoupper($selected->first_name.' '.$selected->last_name) }}</h2>
                @if ($desigCol && $selected->{$desigCol})
                    <p class="gtc-sub">{{ mb_strtoupper((string) $selected->{$desigCol}) }}</p>
                @endif
            </div>
        </div>

        @if (app(\Modules\GuestTaClaim\Services\PermissionService::class)->isSuperAdmin($selected))
            <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                This user is a super admin and already has every Guest TA Claim permission.
            </div>
        @endif

        <div class="gtc-section">
            <h2 class="mb-1">Module permissions</h2>
            <p class="gtc-sub">Choose which Guest TA Claim actions this employee can access.</p>
        </div>

        <form id="permission-update-form" method="POST" action="{{ route('guest-ta-claim.permissions.update', $selected->getKey()) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="dept" value="{{ $dept }}">
            <input type="hidden" name="q" value="{{ request('q') }}">

            @foreach (\Modules\GuestTaClaim\Enums\Permission::groups() as $group => $permissions)
                <section class="gtc-permbox">
                    <h3>{{ $group }}</h3>
                    <div class="row row-cols-1 row-cols-md-2 g-2">
                        @foreach ($permissions as $permission)
                            <div class="col">
                                <div class="form-check gtc-permission-option border rounded-2 bg-white p-3 h-100">
                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                           value="{{ $permission->value }}"
                                           id="perm_{{ str_replace('.', '_', $permission->value) }}"
                                           {{ in_array($permission->value, $held, true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="perm_{{ str_replace('.', '_', $permission->value) }}">
                                        {{ $permission->label() }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="gtc-actions no-print">
                <a href="{{ url()->current().'?'.http_build_query(request()->query()) }}"
                   class="btn btn-outline-secondary" data-permissions-cancel>Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Save changes
                </button>
            </div>
        </form>
    @else
        <div class="gtc-empty">
            <i class="bi bi-person-lock fs-3 d-block mb-2" aria-hidden="true"></i>
            Choose an employee to see their permissions.
        </div>
    @endif
</section>
