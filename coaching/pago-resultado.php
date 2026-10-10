<?php
// Kairos School of Coaching — página a la que Getnet devuelve al alumno
// después de pagar (o cancelar). Consulta el estado real del pago.

require __DIR__ . '/../assets/php/getnet/getnet.php';

$ref = $_GET['ref'] ?? '';
$matricula = matriculaLeer($ref);
$estado = null;

if ($matricula && getnetConfigurado()) {
    $estado = in_array($matricula['estado'], ['APPROVED', 'REJECTED'], true)
        ? $matricula['estado']
        : matriculaActualizarEstado($matricula);
}

if ($estado === 'APPROVED') {
    $tipo = 'ok';
    $titulo = '¡Tu matrícula está pagada!';
    $texto = 'Recibimos tu pago correctamente. Un asesor de Kairos School of Coaching se contactará contigo dentro de las próximas 48 horas hábiles para darte la bienvenida y tu acceso al Aula Virtual.';
} elseif ($estado === 'REJECTED') {
    $tipo = 'error';
    $titulo = 'El pago no se completó';
    $texto = 'El pago fue rechazado o cancelado y no se realizó ningún cobro. Puedes intentarlo nuevamente o escribirnos si necesitas ayuda.';
} elseif ($matricula) {
    $tipo = 'pendiente';
    $titulo = 'Tu pago está en proceso';
    $texto = 'Getnet aún está confirmando tu pago. Te avisaremos por correo cuando quede aprobado; no es necesario volver a pagar.';
} else {
    $tipo = 'error';
    $titulo = 'No encontramos tu matrícula';
    $texto = 'El enlace no es válido o ya expiró. Si realizaste un pago, escríbenos y lo revisamos.';
}

function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Resultado del pago — Kairos School of Coaching</title>
  <meta name="robots" content="noindex" />
  <link rel="icon" href="../assets/img/logo-kairos-icon.png" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/base.css" />
  <link rel="stylesheet" href="../assets/css/coaching.css" />
  <link rel="stylesheet" href="../assets/css/coaching-site.css" />
</head>
<body>

  <header class="site-nav">
    <div class="wrap">
      <a class="brand" href="index.html">
        <img src="../assets/img/logo-kairos.png" alt="Kairos School of Coaching" />
      </a>
      <button class="nav-toggle" aria-label="Abrir menú"><span></span></button>
      <ul class="nav-links">
        <li><a href="index.html" data-nav="index.html">Inicio</a></li>
        <li><a href="nosotros.html" data-nav="nosotros.html">Nosotros</a></li>
        <li><a href="malla.html" data-nav="malla.html">Malla</a></li>
        <li><a href="agenda.html" data-nav="agenda.html">Agenda una reunión</a></li>
        <li><a href="aula-virtual.html" data-nav="aula-virtual.html">Aula Virtual</a></li>
      </ul>
      <div class="nav-cta">
        <a class="btn btn-primary btn-sm" href="matricula.html">Matricúlate</a>
      </div>
    </div>
  </header>

  <section class="section">
    <div class="wrap" style="max-width:760px;">
      <div class="form-card">
        <div class="success-panel show pago-<?= e($tipo) ?>">
          <div class="check">
            <?php if ($tipo === 'ok'): ?>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <?php elseif ($tipo === 'pendiente'): ?>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
            <?php else: ?>
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            <?php endif; ?>
          </div>
          <h3><?= e($titulo) ?></h3>
          <p><?= e($texto) ?></p>
          <?php if ($matricula): ?>
          <p style="font-size:0.82rem;">Referencia de tu matrícula: <strong><?= e($matricula['referencia']) ?></strong></p>
          <?php endif; ?>
          <div class="hero-actions" style="justify-content:center;">
            <?php if ($tipo === 'error'): ?>
            <a class="btn btn-primary" href="matricula.html">Intentar nuevamente</a>
            <a class="btn btn-outline" href="mailto:kscontacto@ksconsultores.cl">Escríbenos</a>
            <?php else: ?>
            <a class="btn btn-primary" href="index.html">Volver al inicio</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="site-footer">
    <div class="wrap">
      <p>&copy; <span id="year"></span> Kairos School of Coaching — Programa de Formación Profesional de Coaches.</p>
      <p>Consultas: <a href="mailto:ltapia@ksconsultores.cl">ltapia@ksconsultores.cl</a> · <a href="mailto:eruiz@ksconsultores.cl">eruiz@ksconsultores.cl</a></p>
      <p><a href="../index.html">&larr; Volver al portal KSC</a></p>
    </div>
  </footer>

  <script src="../assets/js/site.js"></script>
</body>
</html>
