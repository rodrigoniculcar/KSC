// Kairos School of Coaching — envía el formulario a assets/php/enviar.php,
// que reenvía los datos por correo a kscontacto@ksconsultores.cl.
// Requiere hosting con PHP; en un preview sin PHP el envío fallará y se
// mostrará un aviso pidiendo escribir directamente al correo.

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

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalLabel = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Enviando…';
    }

    fetch('../assets/php/enviar.php', {
      method: 'POST',
      body: new FormData(form),
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data || !data.ok) throw new Error((data && data.error) || 'send_failed');

        form.classList.add('hide');
        successPanel.classList.add('show');

        if (opts && typeof opts.onSuccess === 'function') {
          opts.onSuccess(form, successPanel);
        }

        successPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
      })
      .catch(() => {
        alert('No pudimos enviar tu solicitud. Por favor escríbenos directamente a kscontacto@ksconsultores.cl.');
      })
      .finally(() => {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalLabel;
        }
      });
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
