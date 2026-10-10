<?php
// Kairos School of Coaching — funciones compartidas para el pago con
// Getnet Chile (Web Checkout). Las usan crear-pago.php, notificacion.php
// y coaching/pago-resultado.php.
//
// Flujo:
//   1. crear-pago.php crea una sesión en Getnet y devuelve su processUrl.
//   2. El navegador redirige al alumno a Getnet para pagar.
//   3. Getnet devuelve al alumno a coaching/pago-resultado.php y, además,
//      avisa a notificacion.php. En ambos casos se consulta el estado real
//      de la sesión a Getnet antes de dar el pago por aprobado.

error_reporting(E_ALL);
ini_set('display_errors', '0');

date_default_timezone_set('America/Santiago');

define('GETNET_DIR', __DIR__);
define('GETNET_DATA_DIR', __DIR__ . '/data');

const GETNET_URLS = [
    'pruebas' => 'https://checkout.test.getnet.cl',
    'produccion' => 'https://checkout.getnet.cl',
];

function getnetLog($mensaje) {
    @file_put_contents(GETNET_DIR . '/getnet-error.log', '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . "\n", FILE_APPEND);
}

// Busca la configuración primero FUERA de public_html (más seguro) y, si no
// está, junto a este archivo (protegido por .htaccess).
function getnetConfig() {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $candidatos = [];
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $candidatos[] = dirname($_SERVER['DOCUMENT_ROOT']) . '/getnet-config.php';
    }
    $candidatos[] = GETNET_DIR . '/getnet-config.php';

    foreach ($candidatos as $ruta) {
        if (is_file($ruta)) {
            $config = require $ruta;
            return $config;
        }
    }

    $config = [];
    return $config;
}

function getnetConfigurado() {
    $c = getnetConfig();
    return !empty($c['login']) && !empty($c['secret_key'])
        && $c['login'] !== 'TU_LOGIN_AQUI' && $c['secret_key'] !== 'TU_SECRET_KEY_AQUI'
        && !empty($c['monto']) && isset(GETNET_URLS[$c['ambiente'] ?? '']);
}

// URL pública del sitio (ej. https://ksconsultores.cl). Se puede fijar en
// la configuración; si no, se deduce de la petición actual.
function sitioUrl() {
    $c = getnetConfig();
    if (!empty($c['sitio_url'])) {
        return rtrim($c['sitio_url'], '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
}

// Autenticación de Getnet: tranKey = Base64(SHA-256(nonce + seed + secretKey)),
// con el nonce enviado en Base64 y el seed como fecha ISO 8601.
function getnetAuth() {
    $c = getnetConfig();
    $nonce = random_bytes(16);
    $seed = date('c');
    return [
        'login' => $c['login'],
        'tranKey' => base64_encode(hash('sha256', $nonce . $seed . $c['secret_key'], true)),
        'nonce' => base64_encode($nonce),
        'seed' => $seed,
    ];
}

function getnetPost($ruta, array $cuerpo) {
    $c = getnetConfig();
    $cuerpo['auth'] = getnetAuth();

    $ch = curl_init(GETNET_URLS[$c['ambiente']] . $ruta);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($cuerpo),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) {
        getnetLog("Error de conexión con Getnet ($ruta): $error");
        return null;
    }
    $datos = json_decode($respuesta, true);
    if (!is_array($datos)) {
        getnetLog("Respuesta no válida de Getnet ($ruta): " . substr($respuesta, 0, 500));
        return null;
    }
    return $datos;
}

function getnetCrearSesion(array $sesion) {
    return getnetPost('/api/session', $sesion);
}

function getnetConsultarSesion($requestId) {
    return getnetPost('/api/session/' . rawurlencode($requestId), []);
}

// --- Registro de matrículas (un archivo JSON por referencia) -------------

function referenciaValida($ref) {
    return is_string($ref) && preg_match('/^KAI-[0-9]{14}-[a-f0-9]{6}$/', $ref) === 1;
}

function matriculaRuta($ref) {
    return GETNET_DATA_DIR . '/' . $ref . '.json';
}

function matriculaLeer($ref) {
    if (!referenciaValida($ref) || !is_file(matriculaRuta($ref))) {
        return null;
    }
    return json_decode(file_get_contents(matriculaRuta($ref)), true);
}

function matriculaGuardar(array $m) {
    if (!is_dir(GETNET_DATA_DIR)) {
        mkdir(GETNET_DATA_DIR, 0750, true);
    }
    $m['actualizado'] = date('c');
    file_put_contents(matriculaRuta($m['referencia']), json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Consulta el estado real en Getnet, lo guarda y, la primera vez que el
// pago queda aprobado, avisa por correo al equipo Kairos.
// Devuelve 'APPROVED', 'REJECTED', 'PENDING' (u otro estado de Getnet) o null.
function matriculaActualizarEstado(array $m) {
    if (empty($m['request_id'])) {
        return null;
    }
    $info = getnetConsultarSesion($m['request_id']);
    $estado = $info['status']['status'] ?? null;
    if (!$estado) {
        getnetLog('No se pudo consultar la sesión ' . $m['request_id'] . ': ' . json_encode($info));
        return null;
    }

    $m['estado'] = $estado;
    $m['estado_mensaje'] = $info['status']['message'] ?? '';
    if (!empty($info['payment'][0]['authorization'])) {
        $m['autorizacion'] = $info['payment'][0]['authorization'];
    }

    if ($estado === 'APPROVED' && empty($m['aviso_aprobado'])) {
        $m['aviso_aprobado'] = enviarCorreoKairos(
            'Kairos School of Coaching — Matrícula PAGADA (' . $m['referencia'] . ')',
            "Se recibió el pago de una matrícula a través de Getnet.\n\n" . matriculaResumen($m),
            $m['datos']['email'] ?? null
        );
    }

    matriculaGuardar($m);
    return $estado;
}

function matriculaResumen(array $m) {
    $etiquetas = [
        'nombres' => 'Nombres',
        'apellidos' => 'Apellidos',
        'email' => 'Correo electrónico',
        'telefono' => 'Teléfono',
        'pais_telefono' => 'País del teléfono',
        'ciudad' => 'Ciudad / comuna',
        'ocupacion' => 'Ocupación actual',
        'conociste' => '¿Cómo se enteró?',
        'comentario' => 'Comentario',
    ];
    $lineas = [
        'Referencia: ' . $m['referencia'],
        'Monto: $' . number_format($m['monto'], 0, ',', '.') . ' CLP',
        'Estado del pago: ' . ($m['estado'] ?? 'CREADO'),
    ];
    if (!empty($m['autorizacion'])) {
        $lineas[] = 'Código de autorización: ' . $m['autorizacion'];
    }
    $lineas[] = '';
    foreach ($etiquetas as $campo => $etiqueta) {
        if (!empty($m['datos'][$campo])) {
            $lineas[] = $etiqueta . ': ' . $m['datos'][$campo];
        }
    }
    return implode("\n", $lineas) . "\n";
}

// Envía un aviso a kscontacto@ksconsultores.cl con la misma cuenta SMTP
// que usa assets/php/enviar.php. Devuelve true si se envió.
function enviarCorreoKairos($asunto, $cuerpo, $responderA = null) {
    $configPath = dirname(GETNET_DIR) . '/smtp-config.php';
    if (!is_file($configPath)) {
        getnetLog('Falta assets/php/smtp-config.php; no se envió: ' . $asunto);
        return false;
    }
    $smtp = require $configPath;

    require_once dirname(GETNET_DIR) . '/PHPMailer/class.phpmailer.php';
    require_once dirname(GETNET_DIR) . '/PHPMailer/class.smtp.php';

    $mail = new PHPMailer();
    try {
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->Port = $smtp['port'];
        $mail->SMTPSecure = $smtp['secure'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($smtp['username'], 'Kairos School of Coaching');
        $mail->addAddress('kscontacto@ksconsultores.cl');
        if ($responderA && filter_var($responderA, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($responderA);
        }
        $mail->Subject = $asunto;
        $mail->Body = $cuerpo;
        $enviado = $mail->send();
    } catch (Exception $e) {
        $enviado = false;
    }
    if (!$enviado) {
        getnetLog('PHPMailer no pudo enviar "' . $asunto . '": ' . $mail->ErrorInfo);
    }
    return (bool) $enviado;
}
