// KSC Coaching — simulated form submission (no backend connected yet)

function simulateSubmit(formId, panelId, opts) {
  const form = document.getElementById(formId);
  const successPanel = document.getElementById(panelId);
  if (!form || !successPanel) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!form.reportValidity()) return;

    if (opts && typeof opts.beforeSubmit === 'function') {
      const ok = opts.beforeSubmit(form);
      if (ok === false) return;
    }

    form.classList.add('hide');
    successPanel.classList.add('show');

    if (opts && typeof opts.onSuccess === 'function') {
      opts.onSuccess(form, successPanel);
    }

    successPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
}

function initSlotPicker(gridSelector, hiddenInputSelector) {
  const grid = document.querySelector(gridSelector);
  const hidden = document.querySelector(hiddenInputSelector);
  if (!grid || !hidden) return;

  grid.querySelectorAll('.slot').forEach((slot) => {
    slot.addEventListener('click', () => {
      grid.querySelectorAll('.slot').forEach((s) => s.classList.remove('selected'));
      slot.classList.add('selected');
      hidden.value = slot.dataset.value || slot.textContent.trim();
    });
  });
}
