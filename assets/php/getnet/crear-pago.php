<?php
// Kairos School of Coaching — recibe el formulario de matrícula, lo registra
// y crea la sesión de pago en Getnet. Responde JSON:
//   { ok: true, processUrl: "https://checkout..." }  → el navegador redirige
//   { ok: false, error: "getnet_no_configurado" }     → aún sin credenciales

require __DIR__ . '/getnet.php';

header('Content-Type: application/json; charset=utf-8');

function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

if (!getnetConfigurado()) {
    responder(['ok' => false, 'error' => 'getnet_no_configurado'], 503);
}

// Honeypot anti-spam.
if (!empty($_POST['website'])) {
    responder(['ok' => false, 'error' => 'spam'], 400);
}

$campos = ['nombres', 'apellidos', 'email', 'telefono', 'pais_telefono', 'ciudad', 'ocupacion', 'conociste', 'comentario'];
$datos = [];
foreach ($campos as $campo) {
    $datos[$campo] = isset($_POST[$campo]) && is_string($_POST[$campo]) ? trim($_POST[$campo]) : '';
}

if ($datos['nombres'] === '' || $datos['apellidos'] === '' || $datos['telefono'] === ''
    || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL) || empty($_POST['acepto'])) {
    responder(['ok' => false, 'error' => 'datos_incompletos'], 400);
}

$config = getnetConfig();
$referencia = 'KAI-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
$monto = (int) $config['monto'];

$matricula = [
    'referencia' => $referencia,
    'monto' => $monto,
    'estado' => 'CREADO',
    'creado' => date('c'),
    'datos' => $datos,
];
matriculaGuardar($matricula);

$sitio = sitioUrl();
$retorno = $sitio . '/coaching/pago-resultado.php?ref=' . $referencia;

$comprador = [
    'name' => $datos['nombres'],
    'surname' => $datos['apellidos'],
    'email' => $datos['email'],
];
// Getnet espera el móvil en formato internacional (+569...).
if (preg_match('/^\+[0-9]{7,15}$/', $datos['telefono'])) {
    $comprador['mobile'] = $datos['telefono'];
}

$respuesta = getnetCrearSesion([
    'locale' => 'es_CL',
    'buyer' => $comprador,
    'payment' => [
        'reference' => $referencia,
        'description' => $config['descripcion'] ?? 'Matrícula Kairos School of Coaching',
        'amount' => ['currency' => 'CLP', 'total' => $monto],
    ],
    'expiration' => date('c', strtotime('+30 minutes')),
    'returnUrl' => $retorno,
    'cancelUrl' => $retorno,
    'notificationUrl' => $sitio . '/assets/php/getnet/notificacion.php',
    'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    'userAgent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'desconocido', 0, 255),
]);

if (($respuesta['status']['status'] ?? '') !== 'OK' || empty($respuesta['processUrl'])) {
    getnetLog("No se pudo crear la sesión para $referencia: " . json_encode($respuesta));
    $matricula['estado'] = 'ERROR_SESION';
    $matricula['estado_mensaje'] = $respuesta['status']['message'] ?? 'sin respuesta';
    matriculaGuardar($matricula);
    responder(['ok' => false, 'error' => 'getnet_error'], 502);
}

$matricula['request_id'] = (string) $respuesta['requestId'];
$matricula['estado'] = 'PENDING';
matriculaGuardar($matricula);

// Aviso al equipo: así quedan registrados también quienes no terminan de pagar.
enviarCorreoKairos(
    'Kairos School of Coaching — Matrícula iniciada, pago pendiente (' . $referencia . ')',
    "Una persona completó el formulario de matrícula y fue enviada a Getnet para pagar.\n"
    . "Recibirás otro correo cuando el pago quede aprobado.\n\n" . matriculaResumen($matricula),
    $datos['email']
);

responder(['ok' => true, 'processUrl' => $respuesta['processUrl']]);
