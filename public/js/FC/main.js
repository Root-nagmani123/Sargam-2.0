/**
 * LBSNAARP FC Registration – Main JS
 * Mirrors: allfunction.js / regestration.js / logindeatils.js from original Spring Boot app
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Auto-dismiss flash alerts after 5 seconds ──────────────────
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ── IFSC code auto-uppercase ────────────────────────────────────
    const ifscInput = document.querySelector('input[name="ifsc_code"]');
    if (ifscInput) {
        ifscInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
        // Basic IFSC validation on blur
        ifscInput.addEventListener('blur', function () {
            const regex = /^[A-Z]{4}0[A-Z0-9]{6}$/;
            if (this.value && !regex.test(this.value)) {
                this.setCustomValidity('Invalid IFSC code format (e.g. SBIN0001234)');
                this.reportValidity();
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // ── Mobile number – digits only ─────────────────────────────────
    document.querySelectorAll('input[name="mobile_no"], input[name="emergency_contact_mobile"]').forEach(function (el) {
        el.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    });

    // ── Account number – confirm match live check ───────────────────
    const accNo        = document.querySelector('input[name="account_no"]');
    const accNoConfirm = document.querySelector('input[name="account_no_confirm"]');
    if (accNo && accNoConfirm) {
        function checkAccountMatch() {
            if (accNoConfirm.value && accNo.value !== accNoConfirm.value) {
                accNoConfirm.setCustomValidity('Account numbers do not match');
                accNoConfirm.classList.add('is-invalid');
            } else {
                accNoConfirm.setCustomValidity('');
                accNoConfirm.classList.remove('is-invalid');
            }
        }
        accNo.addEventListener('input', checkAccountMatch);
        accNoConfirm.addEventListener('input', checkAccountMatch);
    }

    // ── Pincode – digits only, max 6 ───────────────────────────────
    document.querySelectorAll('input[name$="pincode"]').forEach(function (el) {
        el.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    });

    // ── Year fields – 4 digit only ──────────────────────────────────
    document.querySelectorAll('input[name$="year_of_passing"], input[name$="[year]"]').forEach(function (el) {
        el.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
        });
    });

    // ── Tab persistence via localStorage ────────────────────────────
    const step3Tabs = document.getElementById('step3Tabs');
    if (step3Tabs) {
        const savedTab = localStorage.getItem('lbs_step3_tab');
        if (savedTab) {
            const tabEl = step3Tabs.querySelector(`a[href="${savedTab}"]`);
            if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
        }
        step3Tabs.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (e) {
                localStorage.setItem('lbs_step3_tab', e.target.getAttribute('href'));
            });
        });
    }

    // ── Form submit – disable button to prevent double submission ───
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.dataset.noDisable) {
                setTimeout(function () {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';
                }, 50);
            }
        });
    });

    // ── File upload size check ───────────────────────────────────────
    document.querySelectorAll('input[type="file"]').forEach(function (el) {
        el.addEventListener('change', function () {
            const maxMB   = parseFloat(this.dataset.maxMb || 5);
            const maxBytes= maxMB * 1024 * 1024;
            for (const file of this.files) {
                if (file.size > maxBytes) {
                    alert(`File "${file.name}" exceeds the ${maxMB}MB size limit. Please choose a smaller file.`);
                    this.value = '';
                    break;
                }
            }
        });
    });

    // ── Tooltip init ─────────────────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        bootstrap.Tooltip.getOrCreateInstance(el);
    });

});

// ── Dynamic row helper (used by Step 3 partials) ─────────────────────────
function addRow(type) {
    const tmplId = {
        'qual':    'tmpl-qual',
        'higher':  'tmpl-higher',
        'employ':  'tmpl-employ',
        'lang':    'tmpl-lang',
        'distc':   'tmpl-distc',
        'sportsp': 'tmpl-sportsp',
    }[type];

    const containerId = {
        'qual':    'qualContainer',
        'higher':  'higherContainer',
        'employ':  'employContainer',
        'lang':    'langContainer',
        'distc':   'disinctContainer',
        'sportsp': 'sportsPlayedContainer',
    }[type];

    const tmpl      = document.getElementById(tmplId);
    const container = document.getElementById(containerId);
    if (!tmpl || !container) return;

    const idx  = container.querySelectorAll('.dynamic-row').length;
    const html = tmpl.innerHTML.replace(/__INDEX__/g, idx);
    container.insertAdjacentHTML('beforeend', html);
}

function removeRow(btn) {
    const row = btn.closest('.dynamic-row');
    if (row) row.remove();
}

// ── "Same as Permanent" address toggle ─────────────────────────────────
function togglePresentAddress(checkbox) {
    const block = document.getElementById('presentAddressBlock');
    if (!block) return;
    block.style.opacity  = checkbox.checked ? '0.4' : '1';
    block.querySelectorAll('input, select').forEach(function (el) {
        el.disabled = checkbox.checked;
    });
}

// ── Show/hide password toggle ──────────────────────────────────────────
function togglePassword(inputId, iconId) {
    const pw   = document.getElementById(inputId || 'password');
    const icon = document.getElementById(iconId  || 'eyeIcon');
    if (!pw) return;
    if (pw.type === 'password') {
        pw.type = 'text';
        if (icon) { icon.className = 'bi bi-eye-slash'; }
    } else {
        pw.type = 'password';
        if (icon) { icon.className = 'bi bi-eye'; }
    }
}
