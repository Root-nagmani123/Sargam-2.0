{{-- UX4G Design System 3.3.2, self-hosted (scripts/vendor-ux4g.mjs). Must load BEFORE Bootstrap/theme CSS:
     ux4g-* classes still win on specificity, while legacy rules keep winning ties, so unmigrated pages are unchanged.
     SARGAM brand tokens (LBSNAA blue primary scale) are applied in css/sargam-app.css. See docs/UX4G-DESIGN-TOKENS.md. --}}
<link rel="stylesheet" href="{{ asset('vendor/ux4g/3.3.2/ux4g.css') }}">
