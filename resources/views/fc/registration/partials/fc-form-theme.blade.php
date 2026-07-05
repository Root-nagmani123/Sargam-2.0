{{-- Premium government-portal theme for the dynamic FC forms (visual only). --}}
@push('styles')
<style>
:root{
    --fc-navy:#003366; --fc-blue:#004a93; --fc-blue-2:#1565c0;
    --fc-gold:#c79a3b; --fc-green:#1a7f4b; --fc-ink:#1f2937;
    --fc-line:#e3e8ef; --fc-soft:#f4f8fd;
}

/* ---- Page shell: wide, centered, fully responsive (minimal side gutters) ---- */
.fc-form-page{ padding:0 0 3rem; }
.fc-shell{ width:96%; max-width:1640px; margin:0 auto; padding:1.25rem 0 0; }
@media (max-width:575.98px){ .fc-shell{ width:100%; padding:1rem .75rem 0; } }

/* ---- Title band ---- */
.fc-band{
    background:linear-gradient(120deg,var(--fc-navy),var(--fc-blue) 55%,var(--fc-blue-2));
    color:#fff; border-radius:16px; padding:1.4rem 1.6rem;
    box-shadow:0 10px 30px rgba(0,40,90,.18); position:relative; overflow:hidden;
    margin-bottom:1.25rem;
}
.fc-band::after{ content:""; position:absolute; right:-40px; top:-40px; width:180px; height:180px;
    background:radial-gradient(circle,rgba(255,255,255,.14),transparent 70%); }
.fc-band__row{ display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
.fc-band__ico{ flex:0 0 auto; width:54px; height:54px; border-radius:14px;
    background:rgba(255,255,255,.16); display:flex; align-items:center; justify-content:center;
    font-size:1.5rem; backdrop-filter:blur(2px); }
.fc-band h1,.fc-band h4{ font-weight:700; letter-spacing:.2px; margin:0; font-size:1.4rem; }
.fc-band p{ margin:.15rem 0 0; color:rgba(255,255,255,.82); font-size:.9rem; }
.fc-band__meta{ margin-left:auto; text-align:right; }
.fc-band .fc-prog{ height:8px; border-radius:6px; background:rgba(255,255,255,.25); width:220px; max-width:40vw; overflow:hidden; }
.fc-band .fc-prog>span{ display:block; height:100%; background:linear-gradient(90deg,#7ed957,#1a7f4b); border-radius:6px; }
.fc-band__meta small{ color:rgba(255,255,255,.85); font-size:.78rem; display:block; margin-bottom:.35rem; }

/* ---- Horizontal stepper ---- */
.fc-stepper{ display:flex; gap:0; overflow-x:auto; background:#fff; border:1px solid var(--fc-line);
    border-radius:14px; padding:.85rem 1rem; margin-bottom:1.25rem; box-shadow:0 4px 16px rgba(0,40,90,.05);
    scrollbar-width:thin; }
.fc-stp{ flex:1 1 0; min-width:128px; position:relative; display:flex; flex-direction:column;
    align-items:center; text-decoration:none; padding:.25rem .4rem; }
.fc-stp::before,.fc-stp::after{ content:""; position:absolute; top:19px; height:3px; background:var(--fc-line); z-index:0; }
.fc-stp::before{ left:0; right:50%; }
.fc-stp::after{ left:50%; right:0; }
.fc-stp:first-child::before,.fc-stp:last-child::after{ display:none; }
.fc-stp__dot{ position:relative; z-index:1; width:38px; height:38px; border-radius:50%;
    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.95rem;
    background:#fff; border:2px solid var(--fc-line); color:#9aa6b5; transition:.2s; }
.fc-stp__lbl{ margin-top:.45rem; font-size:.78rem; line-height:1.2; text-align:center; color:#7a8699;
    font-weight:600; max-width:140px; }
/* states */
.fc-stp--done .fc-stp__dot{ background:var(--fc-green); border-color:var(--fc-green); color:#fff; }
.fc-stp--done::before,.fc-stp--done::after{ background:var(--fc-green); }
.fc-stp--active .fc-stp__dot{ background:var(--fc-blue); border-color:var(--fc-blue); color:#fff;
    box-shadow:0 0 0 5px rgba(0,74,147,.15); }
.fc-stp--active::before{ background:var(--fc-green); }
.fc-stp--active .fc-stp__lbl{ color:var(--fc-blue); }
.fc-stp--done .fc-stp__lbl{ color:var(--fc-ink); }
.fc-stp:hover .fc-stp__dot{ transform:translateY(-1px); }

/* ---- Card ---- */
.fc-card{ border:1px solid var(--fc-line) !important; border-radius:16px !important;
    box-shadow:0 8px 30px rgba(0,40,90,.07) !important; overflow:hidden; background:#fff; }
.fc-card>.card-header{ background:linear-gradient(180deg,#fbfdff,#f3f7fc); border-bottom:1px solid var(--fc-line);
    padding:1.1rem 1.5rem; }
.fc-card>.card-header h5{ font-weight:700; color:var(--fc-navy); margin:0; }
.fc-card>.card-body{ padding:1.6rem 1.6rem 1.4rem; }

/* ---- Section headings: full-width themed band (like the legacy form bars) ---- */
.fc-form-page h6.text-uppercase{
    display:flex; align-items:center;
    background:linear-gradient(95deg,var(--fc-navy),var(--fc-blue) 85%);
    color:#fff !important;
    border:0 !important;
    border-left:4px solid var(--fc-gold) !important;
    border-radius:8px;
    padding:.6rem .95rem !important;
    margin:1.5rem 0 1.1rem !important;
    font-size:.8rem !important; font-weight:700 !important; letter-spacing:.7px !important;
    box-shadow:0 3px 10px rgba(0,40,90,.14);
}
.fc-form-page h6.text-uppercase:first-of-type{ margin-top:.2rem !important; }
.fc-form-page h6.text-uppercase .bi{ color:#fff; }
.fc-form-page h6.text-uppercase .text-success{ color:#7ef0a8 !important; }

/* ---- Form controls ---- */
.fc-form-page .form-label,.fc-form-page label.form-label{ font-weight:600; color:#37465b; font-size:.86rem; margin-bottom:.35rem; }
.fc-form-page .form-control,.fc-form-page .form-select{ border:1px solid #d8e0ea; border-radius:10px;
    padding:.55rem .8rem; font-size:.92rem; background:#fdfefe; transition:.15s; }
.fc-form-page .form-control:focus,.fc-form-page .form-select:focus{ border-color:var(--fc-blue);
    box-shadow:0 0 0 .2rem rgba(0,74,147,.13); background:#fff; }
.fc-form-page .form-control-sm,.fc-form-page .form-select-sm{ border-radius:8px; }
.fc-form-page textarea.form-control{ min-height:90px; }
.fc-form-page .text-danger{ color:#c0392b !important; }
.fc-form-page .repeatable-row{ background:#fbfcfe !important; border:1px solid var(--fc-line) !important;
    border-radius:12px !important; }

/* ---- Buttons ---- */
.fc-form-page .btn-primary{ background:linear-gradient(180deg,var(--fc-blue),var(--fc-navy));
    border:none; border-radius:10px; padding:.55rem 1.3rem; font-weight:600;
    box-shadow:0 4px 12px rgba(0,74,147,.25); }
.fc-form-page .btn-primary:hover{ filter:brightness(1.07); }
.fc-form-page .btn-outline-secondary{ border-radius:10px; font-weight:600; }
.fc-form-page .btn-outline-primary{ border-radius:10px; font-weight:600; border-color:var(--fc-blue); color:var(--fc-blue); }
.fc-form-page .btn-outline-success{ border-radius:10px; font-weight:600; }

/* ---- Dashboard cards ---- */
.fc-form-page .card.h-100{ border:1px solid var(--fc-line) !important; border-radius:16px !important;
    box-shadow:0 6px 22px rgba(0,40,90,.06) !important; transition:.18s; }
.fc-form-page .card.h-100:hover{ transform:translateY(-3px); box-shadow:0 14px 34px rgba(0,40,90,.12) !important; }

/* ---- Align the shared FC header/footer with the wide form shell ----
   (scoped: this stylesheet only loads on the dynamic form pages, so the
   login & other FC pages keep their default header width.) */
.top-header > .container,
.header > .container,
footer .container,
.footer .container{
    width:96% !important; max-width:1640px !important;
    padding-left:0 !important; padding-right:0 !important;
}

@media (max-width:991.98px){
    .fc-band__meta{ display:none; }
    .fc-stp__lbl{ font-size:.7rem; }
}
@media (max-width:575.98px){
    .top-header > .container,
    .header > .container,
    footer .container,
    .footer .container{ width:100% !important; padding-left:.75rem !important; padding-right:.75rem !important; }
}
</style>
@endpush
