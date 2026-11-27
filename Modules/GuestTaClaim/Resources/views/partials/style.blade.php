<style>
    /* Guest TA Claim: Sargam 2.0 look on top of Bootstrap 5 */
:root{
  --gtc-gov:#0e1b33; --gtc-bg:#eef1f5; --gtc-surface:#fff; --gtc-surface-2:#f6f8fb; --gtc-border:#e3e7ee;
  --gtc-text:#1c2333; --gtc-muted:#677085; --gtc-primary:#0b3b78; --gtc-primary-soft:#e8effa; --gtc-nav-bg:#e9edf3;
  --gtc-amber:#8a5200; --gtc-amber-bg:#fff3dc; --gtc-green:#17703f; --gtc-green-bg:#e3f4ea; --gtc-red:#b42318; --gtc-red-bg:#fdeceb;
  --gtc-blue:#1f4fb8; --gtc-blue-bg:#e9f0ff; --gtc-grey:#555e70; --gtc-grey-bg:#eef0f4;
  --bs-primary:#0b3b78; --bs-primary-rgb:11,59,120; --bs-link-color:#0b3b78; --bs-link-color-rgb:11,59,120;
  --bs-body-font-family:"Noto Sans",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;
}
.gtc-body{background:var(--gtc-bg);color:var(--gtc-text);font-size:.92rem}
.btn-primary{--bs-btn-bg:#0b3b78;--bs-btn-border-color:#0b3b78;--bs-btn-hover-bg:#0a3268;--bs-btn-hover-border-color:#0a3268;--bs-btn-active-bg:#082a57}
.btn-success{--bs-btn-bg:#17703f;--bs-btn-border-color:#17703f;--bs-btn-hover-bg:#125d34;--bs-btn-hover-border-color:#125d34}
.btn{font-weight:600}
.form-label{font-size:.82rem;color:var(--gtc-muted);font-weight:500;margin-bottom:.35rem}
.form-label.req::after{content:" *";color:var(--gtc-red)}

/* chrome */
.gtc-gov{background:var(--gtc-gov);color:#fff;min-height:36px;font-size:13px}
.gtc-flag{display:inline-block;width:20px;height:13px;vertical-align:-2px;background:linear-gradient(#ff9933 33%,#fff 33% 66%,#138808 66%)}
.gtc-header{background:var(--gtc-surface);border-bottom:1px solid var(--gtc-border)}
.gtc-brand{font-weight:700;color:var(--gtc-primary);font-size:1.35rem;text-decoration:none}
.gtc-topnav{background:var(--gtc-nav-bg);border-radius:10px;padding:5px;gap:4px}
.gtc-topnav .nav-link{color:var(--gtc-text);border-radius:7px;padding:.45rem 1.1rem}
.gtc-topnav .nav-link.active{background:var(--gtc-primary);color:#fff;font-weight:600}
.gtc-layout{display:flex;min-height:calc(100vh - 110px)}
.gtc-rail{width:96px;flex:none;background:var(--gtc-surface);border-right:1px solid var(--gtc-border);padding:18px 0}
.gtc-rail a{display:flex;flex-direction:column;align-items:center;gap:4px;font-size:11.5px;text-align:center;color:var(--gtc-text);text-decoration:none;margin-bottom:14px;line-height:1.25}
.gtc-rail a i{width:44px;height:44px;border-radius:10px;display:grid;place-items:center;font-size:1.15rem}
.gtc-rail a.active{color:var(--gtc-primary);font-weight:600}.gtc-rail a.active i{background:var(--gtc-primary-soft)}
.gtc-shell{display:flex;flex:1;min-width:0}
.gtc-side{width:250px;flex:none;background:var(--gtc-surface);border-right:1px solid var(--gtc-border);padding:20px 14px}
.gtc-side h6{font-size:13px;color:var(--gtc-muted);font-weight:600;margin:0 14px 10px}
.gtc-side .nav-link{display:flex;align-items:center;gap:12px;color:var(--gtc-text);padding:.6rem .9rem;border-radius:8px;margin-bottom:2px}
.gtc-side .nav-link:hover{background:var(--gtc-surface-2)}
.gtc-side .nav-link.active{background:var(--gtc-primary-soft);color:var(--gtc-primary);font-weight:600}
.gtc-side .nav-link .badge{margin-left:auto}
.gtc-main{flex:1;min-width:0;padding:22px}

/* building blocks */
.gtc-card{background:var(--gtc-surface);border:1px solid var(--gtc-border);border-radius:12px;padding:20px}
.gtc-card + .gtc-card{margin-top:16px}
.gtc-pagehead{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:16px}
.gtc-crumb{color:var(--gtc-muted);font-size:14px}.gtc-crumb b{color:var(--gtc-text);font-weight:500}
.gtc-pagehead h1{font-size:1.55rem;font-weight:700;margin:2px 0 0}
.gtc-card h2{font-size:1.05rem;font-weight:700;margin:0 0 2px}
.gtc-sub{color:var(--gtc-muted);font-size:.85rem;margin:0}
.gtc-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(225px,1fr));gap:16px;margin-bottom:16px}
.gtc-stat{display:flex;gap:16px;align-items:center;background:var(--gtc-surface);border:1px solid var(--gtc-border);border-radius:12px;padding:18px;color:inherit;text-decoration:none}
.gtc-stat:hover{border-color:var(--gtc-primary)}
.gtc-tile{width:54px;height:54px;border-radius:10px;display:grid;place-items:center;flex:none;font-size:1.35rem}
.gtc-stat .lbl{color:var(--gtc-muted);font-size:14px;line-height:1.3}.gtc-stat .val{font-size:1.75rem;font-weight:700;line-height:1.15}
.t-blue{background:var(--gtc-blue-bg);color:var(--gtc-blue)}.t-amber{background:var(--gtc-amber-bg);color:var(--gtc-amber)}
.t-green{background:var(--gtc-green-bg);color:var(--gtc-green)}.t-red{background:var(--gtc-red-bg);color:var(--gtc-red)}.t-grey{background:var(--gtc-grey-bg);color:var(--gtc-grey)}
.gtc-chip{display:inline-flex;align-items:center;padding:2px 10px;border-radius:999px;font-size:12.5px;font-weight:600;white-space:nowrap}
.gtc-avatar{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:14px;background:var(--gtc-primary-soft);color:var(--gtc-primary);flex:none}
.gtc-avatar.sm{width:34px;height:34px;font-size:12.5px}
.gtc-table-wrap{overflow-x:auto;border:1px solid var(--gtc-border);border-radius:10px;margin-top:14px}
.gtc-table{margin:0;font-size:.88rem}
.gtc-table th{color:var(--gtc-muted);font-weight:600;font-size:.8rem;background:var(--gtc-surface-2);white-space:nowrap;padding:.65rem .75rem}
.gtc-table td{padding:.7rem .75rem;vertical-align:middle}
.gtc-table tr.gtc-click{cursor:pointer}.gtc-table tr.gtc-click:hover td{background:var(--gtc-surface-2)}
.gtc-table tr.is-cancelled td:not(.keep){color:var(--gtc-muted);text-decoration:line-through}
.gtc-tabs{border-bottom:1px solid var(--gtc-border);gap:4px;flex-wrap:nowrap;overflow-x:auto}
.gtc-tabs .nav-link{border:0;border-bottom:2px solid transparent;border-radius:0;color:var(--gtc-muted);font-weight:600;white-space:nowrap;padding:.6rem .9rem}
.gtc-tabs .nav-link.active{color:var(--gtc-primary);border-color:var(--gtc-primary);background:none}
.gtc-tabs .badge{background:var(--gtc-grey-bg);color:var(--gtc-grey);font-weight:600}
.gtc-fac{border:1px solid var(--gtc-border);border-radius:12px;margin-top:16px;overflow:hidden;background:var(--gtc-surface)}
.gtc-fac-head{display:flex;align-items:center;gap:14px;padding:14px 18px;background:var(--gtc-surface-2);flex-wrap:wrap}
.gtc-fac .gtc-table-wrap{margin:0;border:0;border-radius:0;border-top:1px solid var(--gtc-border)}
.gtc-fac-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 18px;border-top:1px solid var(--gtc-border);flex-wrap:wrap}
.gtc-section{border-top:1px solid var(--gtc-border);padding-top:18px;margin-top:18px}
.gtc-travel-item{background:var(--gtc-surface-2);border-color:var(--gtc-border)!important}
.gtc-travel-item-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.gtc-claim-actions{align-items:center}
.gtc-claim-actions form{display:flex;align-items:center;margin-bottom:0}
.gtc-claim-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;min-height:44px;white-space:nowrap}
.gtc-claim-upload .btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;width:100%}
.gtc-calc{background:var(--gtc-surface-2);border:1px solid var(--gtc-border);border-radius:10px;padding:14px 16px;min-width:270px}
.gtc-calc div{display:flex;justify-content:space-between;gap:16px;padding:3px 0;font-variant-numeric:tabular-nums}
.gtc-calc .tot{border-top:1px solid var(--gtc-border);margin-top:6px;padding-top:10px;font-weight:700;font-size:1.05rem}
.gtc-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;border-top:1px solid var(--gtc-border);padding-top:16px;margin-top:20px}
.gtc-kv{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px 24px}
.gtc-kv span{display:block;color:var(--gtc-muted);font-size:12.5px}.gtc-kv b{font-weight:600}
.gtc-steps{display:flex;margin:18px 0 4px}
.gtc-step{flex:1;position:relative;text-align:center;font-size:13px;color:var(--gtc-muted)}
.gtc-step .dot{width:30px;height:30px;border-radius:50%;margin:0 auto 6px;display:grid;place-items:center;font-weight:700;font-size:13px;background:var(--gtc-grey-bg);color:var(--gtc-grey);position:relative;z-index:1}
.gtc-step::before{content:"";position:absolute;top:15px;left:-50%;width:100%;height:2px;background:var(--gtc-border)}
.gtc-step:first-child::before{display:none}
.gtc-step.done{color:var(--gtc-text)}.gtc-step.done .dot{background:var(--gtc-green);color:#fff}.gtc-step.done::before{background:var(--gtc-green)}
.gtc-step.cur{color:var(--gtc-primary);font-weight:600}.gtc-step.cur .dot{background:var(--gtc-primary);color:#fff}
.gtc-step.ret{color:var(--gtc-red);font-weight:600}.gtc-step.ret .dot{background:var(--gtc-red);color:#fff}
.gtc-hist{list-style:none;margin:10px 0 0;padding:0}
.gtc-hist li{display:flex;gap:12px;padding:10px 0;border-bottom:1px dashed var(--gtc-border)}
.gtc-hist li:last-child{border:0}
.gtc-hist .when{color:var(--gtc-muted);font-size:12.5px;white-space:nowrap;min-width:120px}
.gtc-flow{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-top:14px}
.gtc-flow div{border:1px solid var(--gtc-border);border-radius:10px;padding:12px 14px;background:var(--gtc-surface-2)}
.gtc-flow b{display:flex;align-items:center;gap:8px}
.gtc-flow b i{font-style:normal;width:22px;height:22px;border-radius:50%;background:var(--gtc-primary);color:#fff;display:grid;place-items:center;font-size:12px}
.gtc-flow p{margin:6px 0 0;font-size:13px;color:var(--gtc-muted)}
.gtc-map{display:grid;grid-template-columns:minmax(260px,460px) minmax(300px,540px);gap:18px;align-items:start}
.gtc-emp{display:flex;gap:12px;align-items:center;padding:11px 16px;border-left:3px solid transparent;color:inherit;text-decoration:none}
.gtc-emp:hover{background:var(--gtc-surface-2)}
.gtc-emp.active{background:var(--gtc-primary-soft);border-left-color:var(--gtc-primary)}
.gtc-emp b{display:block;font-size:13.5px}.gtc-emp small{color:var(--gtc-muted);font-size:12px}
.gtc-permbox{border:1px solid var(--gtc-border);border-radius:10px;padding:14px 16px;margin-top:12px;background:var(--gtc-surface-2)}
.gtc-permbox h3{margin:0 0 6px;font-size:14px;font-weight:700;color:var(--gtc-primary)}
.gtc-empty{text-align:center;padding:36px 16px;color:var(--gtc-muted)}

@media (max-width: 991.98px){
  .gtc-rail{display:none}
  .gtc-shell{flex-direction:column}
  .gtc-side{width:auto;border-right:0;border-bottom:1px solid var(--gtc-border);padding:8px 10px}
  .gtc-side h6{display:none}
  .gtc-side .nav{flex-direction:row!important;flex-wrap:nowrap;overflow-x:auto}
  .gtc-side .nav-link{white-space:nowrap;margin:0}
  .gtc-map{grid-template-columns:1fr}
  .gtc-main{padding:14px}
}
@media (max-width: 575.98px){
  .gtc-claim-actions>*,.gtc-claim-actions form{width:100%}
  .gtc-claim-actions .btn{width:100%}
}
@media print{
  .gtc-gov,.gtc-header,.gtc-rail,.gtc-side,.no-print,.alert{display:none!important}
  .gtc-body{background:#fff}
  .gtc-main{padding:0}
  .gtc-card{border:0;padding:0}
  .gtc-table-wrap{overflow:visible}
}

</style>