UX4G itself is vendored as compiled CSS at
public/admin_assets/libs/ux4g/css/ux4g-min.css (pinned, byte-exact — never edited).
It is wired into the `ux4g` cascade layer via a <link> in Phase 2, not @imported
here, so the pinned file stays untouched. This dir holds only SCSS that must be
inlined into the build (currently none).
