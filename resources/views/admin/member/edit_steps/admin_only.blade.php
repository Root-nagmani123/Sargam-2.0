{{-- PR #319 re-review F-072: shown in place of Step 3 / Step 6 to an actor who cannot manage
     members. Those values are only saved for an administrator, so no inputs are offered. --}}
<div class="mbrw-section">
    <h6 class="mbrw-section-title">{{ $step === 3 ? 'Role Assignment' : 'Employee Grade Pay' }}</h6>
</div>
<div class="row">
    <div class="col-12">
        <div class="alert alert-info mb-0" role="note">
            {{ $step === 3 ? 'Roles' : 'Grade pay, basic pay and bank details' }} can only be changed by an administrator.
            The rest of your details are saved as usual.
        </div>
    </div>
</div>
