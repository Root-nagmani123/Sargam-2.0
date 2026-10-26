@php
    $userRoleOptions = App\Models\UserRoleMaster::getUserRoleList();
    $memberRoles = optional($member->employeeRoleMapping())->toArray();
    $selectedRoles = old('userrole', $memberRoles);
@endphp
<div class="row">
    <div class="col-12">
        <div class="mb-3">
            <label class="form-label" for="role">Role Option <span class="text-danger">*</span></label>
            <div class="controls">
                <div class="mbrw-role-grid">
                    @foreach ($userRoleOptions as $key => $value)
                        <label class="mbrw-role-chip" for="userrole{{ $key }}">
                            <input class="form-check-input" type="checkbox" name="userrole[]"
                                id="userrole{{ $key }}" value="{{ $key }}"
                                {{ in_array($key, $selectedRoles) ? 'checked' : '' }}>
                            <span>{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
                {{-- Options this member holds that are not offered above, shown as text only:
                     they are not posted back, and update() keeps their mappings (PR #343 review, F-003). --}}
                @php
                    $heldNotOffered = App\Models\UserRoleMaster::whereIn(
                            'pk',
                            collect($memberRoles)->diff($userRoleOptions->keys())->all()
                        )
                        ->orderBy('pk')
                        ->pluck('user_role_display_name');
                @endphp
                @if ($heldNotOffered->isNotEmpty())
                    <div class="form-text mt-2">
                        Also assigned, not managed here (kept when you save): {{ $heldNotOffered->implode(', ') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
