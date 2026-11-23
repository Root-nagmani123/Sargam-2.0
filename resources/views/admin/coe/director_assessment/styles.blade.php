{{-- Shared by Evaluation & Marks (table) and the OT profile (review): Figma "Examination Drive for Training Section". --}}
<style>
.coe-page {
    --da-ink: #101828;
    --da-muted: #667085;
    --da-tint: #e8eefa;
    --da-band: #e3e9fb;
    --da-line: #eaecf0;
}
.da-avatar { flex: 0 0 auto; border-radius: 50%; object-fit: cover; background: #d0d5dd; }
.da-avatar--initials { display: inline-flex; align-items: center; justify-content: center; background: #dbe4f6; color: var(--coe-brand); font-weight: 600; }
.da-seg { display: inline-flex; gap: 4px; }
.da-seg__btn { padding: 6px 12px; border-radius: 4px; background: var(--da-tint); color: var(--coe-brand); font-size: .8125rem; font-weight: 600; text-decoration: none; white-space: nowrap; }
.da-seg__btn:hover { color: var(--coe-brand); background: #d9e3f5; }
.da-seg__btn.is-active { background: var(--coe-brand); color: #fff; }
.da-seg__btn--plain { background: transparent; }
.da-seg__btn.is-disabled { opacity: .5; }
.coe-page .da-remove-filter { border: 1px solid var(--coe-danger); color: var(--coe-danger); background: #fff; border-radius: 8px; }
.coe-page .da-remove-filter:hover { background: var(--coe-danger-soft); color: var(--coe-danger); }
.da-chip { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: .75rem; font-weight: 600; line-height: 1.4; background: var(--da-tint); color: #344054; white-space: nowrap; }
.da-chip--green { background: var(--coe-success-soft); color: #067647; }
.da-chip--amber { background: var(--coe-warn-soft); color: #93370d; }
.da-chip--grey { background: #eaecf0; color: #344054; }
</style>
