/* Guest TA Claim: session edit modal, live claim totals, action modals */
(function () {
  'use strict';
  const fmtMoney = n => '₹' + Math.round(+n || 0).toLocaleString('en-IN');
  const fmtDate = iso => iso ? iso.split('-').reverse().join('-') : '';
  const escapeHtml = value => String(value == null ? '' : value).replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  }[char]));

  const confirmModalEl = document.getElementById('gtcSessionConfirmModal');
  let pendingConfirmationForm = null;
  let pendingConfirmationSubmitter = null;

  function renderSessionStatus(row, confirmed) {
    const status = row.querySelector('[data-field="status"]');
    const taken = status && status.value === 'taken';
    const chip = row.querySelector('[data-show="status"]');
    row.dataset.sessionConfirmed = confirmed ? '1' : '0';
    row.classList.toggle('is-cancelled', confirmed && !taken);
    if (chip) {
      chip.textContent = confirmed ? (taken ? 'Taken' : 'Cancelled') : 'Awaiting confirmation';
      chip.className = 'gtc-chip ' + (confirmed ? (taken ? 't-green' : 't-grey') : 't-amber');
    }
  }

  function markSessionsUnconfirmed(form) {
    const confirmed = form && form.querySelector('[name="sessions_confirmed"]');
    if (!confirmed) return;
    confirmed.value = '0';
    form.querySelectorAll('tr[data-session-confirmed]').forEach(row => renderSessionStatus(row, false));
  }

  function openSessionConfirmation(form, submitter) {
    if (!confirmModalEl || !form) return;
    pendingConfirmationForm = form;
    pendingConfirmationSubmitter = submitter || null;
    const context = confirmModalEl.querySelector('[data-gtc-confirm-context]');
    const message = confirmModalEl.querySelector('[data-gtc-confirm-message]');
    if (form.dataset.sessionConfirmation === 'prepare') {
      const name = form.dataset.facultyName || 'this guest speaker';
      const count = form.dataset.sessionCount || 'the listed';
      context.textContent = 'Confirm sessions for ' + name + '.';
      message.textContent = 'You are confirming ' + count + ' scheduled sessions. They will be marked taken unless you marked them cancelled.';
    } else {
      context.textContent = 'Confirm the attendance details before continuing.';
      message.textContent = 'This confirmation is required before saving or sending the claim.';
    }
    if (window.bootstrap) {
      bootstrap.Modal.getOrCreateInstance(confirmModalEl).show();
    } else if (window.confirm(message.textContent)) {
      confirmModalEl.querySelector('[data-gtc-confirm-sessions]').click();
    }
  }

  function confirmSessions(form) {
    const confirmation = form.querySelector('[name="sessions_confirmed"]');
    if (!confirmation) return;
    form.querySelectorAll('[data-field="status"]').forEach(status => {
      if (!status.value) status.value = 'taken';
      const row = status.closest('tr[data-session-confirmed]');
      if (row) renderSessionStatus(row, true);
    });
    confirmation.value = '1';

    if (window.bootstrap && confirmModalEl) {
      bootstrap.Modal.getOrCreateInstance(confirmModalEl).hide();
    }

    if (form.dataset.sessionConfirmation === 'prepare') {
      form.requestSubmit();
    } else if (form.requestSubmit) {
      form.requestSubmit(pendingConfirmationSubmitter || undefined);
    }
  }

  if (confirmModalEl) {
    document.addEventListener('click', e => {
      const confirmButton = e.target.closest('[data-gtc-confirm-sessions]');
      if (confirmButton && pendingConfirmationForm) {
        confirmSessions(pendingConfirmationForm);
      }
    });
    document.addEventListener('click', e => {
      const openButton = e.target.closest('[data-gtc-open-session-confirmation]');
      if (!openButton) return;
      const form = openButton.closest('form');
      const confirmation = form && form.querySelector('[name="sessions_confirmed"]');
      if (confirmation && confirmation.value === '1') {
        form.requestSubmit();
      } else {
        openSessionConfirmation(form, null);
      }
    });
  }

  /* ---- Session edit modal (timetable results + claim form) ---- */
  const modalEl = document.getElementById('gtcSessionModal');
  if (modalEl && window.bootstrap) {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const f = name => modalEl.querySelector('[name="m_' + name + '"]');
    let row = null;

    document.addEventListener('click', e => {
      const btn = e.target.closest('[data-gtc-edit-session]');
      if (!btn) return;
      row = btn.closest('tr');
      ['topic', 'session_date', 'start_time', 'end_time', 'venue', 'status'].forEach(k => {
        const value = row.querySelector('[data-field="' + k + '"]').value;
        f(k).value = k === 'status' && !value ? 'taken' : value;
      });
      modalEl.querySelector('.gtc-modal-error').classList.add('d-none');
      modal.show();
    });

    modalEl.querySelector('[data-gtc-save-session]').addEventListener('click', () => {
      if (!row) return;
      const err = modalEl.querySelector('.gtc-modal-error');
      if (!f('topic').value.trim() || !f('session_date').value || !f('start_time').value || !f('end_time').value) {
        err.textContent = 'Fill the topic, date and times.'; err.classList.remove('d-none'); return;
      }
      if (f('end_time').value <= f('start_time').value) {
        err.textContent = 'End time must be after the start time.'; err.classList.remove('d-none'); return;
      }
      ['topic', 'session_date', 'start_time', 'end_time', 'venue', 'status'].forEach(k => {
        row.querySelector('[data-field="' + k + '"]').value = f(k).value.trim();
      });
      row.querySelector('[data-show="date"]').textContent = fmtDate(f('session_date').value);
      row.querySelector('[data-show="time"]').textContent = f('start_time').value + '–' + f('end_time').value;
      row.querySelector('[data-show="topic"]').textContent = f('topic').value.trim();
      row.querySelector('[data-show="venue"]').textContent = f('venue').value.trim();
      markSessionsUnconfirmed(row.closest('form'));
      renderSessionStatus(row, row.dataset.sessionConfirmed === '1');
      modal.hide();
      recalc();
    });
  }

  /* ---- Live totals on the claim form ---- */
  const form = document.getElementById('gtcClaimForm');
  function recalc() {
    if (!form) return;
    const v = n => parseFloat((form.querySelector('[name="' + n + '"]') || {}).value) || 0;
    const taken = [...form.querySelectorAll('[data-field="status"]')].filter(i => i.value === 'taken').length;
    const travelRows = [...form.querySelectorAll('[data-travel-row]')];
    const hasTravelData = travelRows.some(row => [...row.querySelectorAll('input,select')].some(input => input.value !== ''));
    const legacy = form.querySelector('.gtc-calc');
    const sum = (field, fallback) => hasTravelData
      ? travelRows.reduce((total, row) => total + (parseFloat((row.querySelector('[name$="[' + field + ']"]') || {}).value) || 0), 0)
      : (parseFloat(legacy ? legacy.dataset[fallback] : 0) || 0);
    const fare = sum('fare_admissible', 'legacyFare');
    const accommodation = sum('accommodation', 'legacyAccommodation');
    const rate = v('honorarium_rate'), hon = taken * rate;
    const days = v('da_journey_days') + v('da_stay_days');
    const percentage = v('da_percentage') || 100;
    const da = days * v('da_rate') * percentage / 100;
    const total = hon + fare + accommodation + da;
    const set = (k, t) => { const el = form.querySelector('[data-calc="' + k + '"]'); if (el) el.textContent = t; };
    set('hon-label', 'Honorarium (' + taken + ' × ' + fmtMoney(rate) + ')');
    set('hon', fmtMoney(hon)); set('fare', fmtMoney(fare)); set('accommodation', fmtMoney(accommodation));
    set('da-label', 'DA (' + days + ' × ' + fmtMoney(v('da_rate')) + ' × ' + percentage + '%)');
    set('da', fmtMoney(da)); set('total', fmtMoney(total));
  }
  if (form) {
    form.addEventListener('input', recalc);
    form.addEventListener('change', recalc);
    const travelRows = document.getElementById('gtcTravelRows');
    const travelTemplate = document.getElementById('gtcTravelRowTemplate');
    const addTravelRow = () => {
      if (!travelRows || !travelTemplate) return;
      const index = Number(travelRows.dataset.nextIndex || 0);
      travelRows.dataset.nextIndex = String(index + 1);
      const html = travelTemplate.innerHTML.replace(/__INDEX__/g, String(index));
      const holder = document.createElement('template');
      holder.innerHTML = html.trim();
      travelRows.appendChild(holder.content.firstElementChild);
      recalc();
    };
    document.addEventListener('click', e => {
      if (e.target.closest('[data-gtc-add-travel-row]')) {
        addTravelRow();
        return;
      }
      const remove = e.target.closest('[data-gtc-remove-travel-row]');
      if (remove) {
        remove.closest('[data-travel-row]').remove();
        if (travelRows && !travelRows.children.length) addTravelRow();
        recalc();
      }
    });
    const cat = form.querySelector('[name="faculty_category"]');
    if (cat) cat.addEventListener('change', () => {
      const rate = cat.selectedOptions[0].dataset.rate;
      if (rate) form.querySelector('[name="honorarium_rate"]').value = rate;
      recalc();
    });
    form.addEventListener('submit', e => {
      const confirmed = form.querySelector('[name="sessions_confirmed"]');
      if (confirmed && confirmed.value !== '1') {
        e.preventDefault();
        openSessionConfirmation(form, e.submitter);
        return;
      }
      form.querySelectorAll('button[type="submit"]').forEach(b => setTimeout(() => b.disabled = true, 0));
    });
    recalc();
  }

  /* ---- Approve / return / pay modals: point the form at the clicked claim ---- */
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-gtc-action]');
    if (!btn) return;
    const m = document.getElementById(btn.dataset.gtcAction);
    if (!m) return;
    const form = m.querySelector('form');
    form.action = btn.dataset.url;
    form.reset();
    m.querySelectorAll('[data-fill]').forEach(el => { el.textContent = btn.dataset[el.dataset.fill] || ''; });
    const amount = m.querySelector('[name="amount"]');
    if (amount) amount.placeholder = btn.dataset.total || '';
    bootstrap.Modal.getOrCreateInstance(m).show();
  });

  /* ---- Clickable table rows ---- */
  document.addEventListener('click', e => {
    const tr = e.target.closest('tr[data-href]');
    if (!tr || e.target.closest('a,button,form,input,select')) return;
    window.location = tr.dataset.href;
  });

  /* ---- Print buttons ---- */
  document.addEventListener('click', e => { if (e.target.closest('[data-gtc-print]')) window.print(); });
})();
