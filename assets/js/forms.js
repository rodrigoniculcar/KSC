// Kairos School of Coaching — envía el formulario a assets/php/enviar.php,
// que reenvía los datos por correo a kscontacto@ksconsultores.cl.
// Requiere hosting con PHP; en un preview sin PHP el envío fallará y se
// mostrará un aviso pidiendo escribir directamente al correo.
//
// Con opts.pago = true (matrícula) primero se intenta crear el pago en
// Getnet (assets/php/getnet/crear-pago.php) y se redirige a su página de
// pago. Si Getnet aún no está configurado en el servidor, se usa el envío
// por correo de siempre.

// Teléfono con bandera y código de país. El campo visible muestra el número
// nacional; al enviar, el campo oculto "telefono" lleva el número completo
// en formato internacional (+56912345678) y "pais_telefono" el país (cl).
function initPhoneField(inputId) {
  const input = document.getElementById(inputId);
  if (!input || typeof window.intlTelInput !== 'function') return null;

  const iti = window.intlTelInput(input, {
    initialCountry: 'cl',
    countryOrder: ['cl', 'ar', 'pe', 'co', 'mx', 'es', 'us'],
    separateDialCode: true,
    strictMode: true,
    i18n: window.intlTelInputI18nEs || {},
    hiddenInput: () => ({ phone: 'telefono', country: 'pais_telefono' }),
  });

  const error = document.createElement('small');
  error.className = 'field-error';
  error.setAttribute('aria-live', 'polite');
  input.closest('.field').appendChild(error);

  const limpiar = () => {
    error.textContent = '';
    input.setCustomValidity('');
  };
  input.addEventListener('input', limpiar);
  input.addEventListener('countrychange', limpiar);

  input.validarTelefono = () => {
    if (!input.value.trim() || iti.isValidNumber()) {
      limpiar();
      return true;
    }
    error.textContent = 'Revisa el número: no es válido para el país seleccionado.';
    input.setCustomValidity(error.textContent);
    return false;
  };
  return iti;
}

function simulateSubmit(formId, panelId, opts) {
  const form = document.getElementById(formId);
  const successPanel = document.getElementById(panelId);
  if (!form || !successPanel) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    form.querySelectorAll('input[type="tel"]').forEach((tel) => {
      if (typeof tel.validarTelefono === 'function') tel.validarTelefono();
    });
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

    const enviarPorCorreo = () =>
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
        });

    const iniciarPago = () =>
      fetch('../assets/php/getnet/crear-pago.php', {
        method: 'POST',
        body: new FormData(form),
      })
        .then((res) => res.json().catch(() => null))
        .then((data) => {
          if (data && data.ok && data.processUrl) {
            if (submitBtn) submitBtn.textContent = 'Redirigiendo a Getnet…';
            window.location.href = data.processUrl;
            return new Promise(() => {}); // la página se va; no restaurar el botón
          }
          if (data && data.error === 'getnet_no_configurado') return enviarPorCorreo();
          throw new Error((data && data.error) || 'payment_failed');
        });

    (opts && opts.pago ? iniciarPago() : enviarPorCorreo())
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
