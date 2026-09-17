<?php
// Kairos School of Coaching — manejador de formularios (matrícula / agenda)
// Envía los datos del formulario por correo a kscontacto@ksconsultores.cl
// usando SMTP autenticado (PHPMailer) en vez de mail() nativo, porque en
// este hosting mail() no entrega los correos de forma confiable.

// Evita que un aviso/warning de PHP se imprima antes del JSON y rompa la
// respuesta que espera el navegador.
error_reporting(E_ALL);
ini_set('display_errors', '0');

$logFile = __DIR__ . '/mail-error.log';

function registrarError($mensaje) {
    global $logFile;
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . "\n", FILE_APPEND);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        registrarError('Error fatal: ' . $error['message'] . ' en ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'server_error']);
    }
});

header('Content-Type: application/json; charset=utf-8');

$destinatario = 'kscontacto@ksconsultores.cl';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Honeypot anti-spam: campo oculto que un visitante real nunca completa.
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$tipoFormulario = isset($_POST['form_type']) ? trim($_POST['form_type']) : 'Formulario del sitio';

$etiquetas = [
    'nombres' => 'Nombres',
    'apellidos' => 'Apellidos',
    'nombre' => 'Nombre',
    'apellido' => 'Apellido',
    'email' => 'Correo electrónico',
    'telefono' => 'Teléfono',
    'ciudad' => 'Ciudad / comuna',
    'ocupacion' => 'Ocupación actual',
    'conociste' => '¿Cómo se enteró?',
    'comentario' => 'Comentario',
    'motivo' => 'Motivo de la reunión',
    'fecha' => 'Fecha preferida',
    'horario' => 'Horario preferido',
];

$lineas = [];
foreach ($_POST as $campo => $valor) {
    if (in_array($campo, ['form_type', 'website'], true)) {
        continue;
    }
    $valor = is_string($valor) ? trim($valor) : $valor;
    if ($valor === '') {
        continue;
    }
    $etiqueta = isset($etiquetas[$campo]) ? $etiquetas[$campo] : ucfirst(str_replace('_', ' ', $campo));
    $lineas[] = $etiqueta . ': ' . $valor;
}

if (empty($lineas)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty_form']);
    exit;
}

$cuerpo = implode("\n", $lineas) . "\n";
$asunto = 'Kairos School of Coaching — ' . $tipoFormulario;

$correoRespuesta = $destinatario;
if (!empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $correoRespuesta = $_POST['email'];
}

$configPath = __DIR__ . '/smtp-config.php';
if (!file_exists($configPath)) {
    registrarError('Falta el archivo assets/php/smtp-config.php con los datos SMTP.');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'smtp_config_missing']);
    exit;
}

$config = require $configPath;

if (empty($config['password']) || $config['password'] === 'TU_CONTRASEÑA_AQUI') {
    registrarError('Falta completar la contraseña real en assets/php/smtp-config.php.');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'smtp_password_missing']);
    exit;
}

require_once __DIR__ . '/PHPMailer/class.phpmailer.php';
require_once __DIR__ . '/PHPMailer/class.smtp.php';

$mail = new PHPMailer();

try {
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->Port = $config['port'];
    $mail->SMTPSecure = $config['secure'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($config['username'], 'Kairos School of Coaching');
    $mail->addAddress($destinatario);
    $mail->addReplyTo($correoRespuesta);

    $mail->Subject = $asunto;
    $mail->Body = $cuerpo;

    $enviado = $mail->send();
} catch (Exception $e) {
    $enviado = false;
}

if ($enviado) {
    echo json_encode(['ok' => true]);
} else {
    registrarError('PHPMailer no pudo enviar: ' . $mail->ErrorInfo);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'mail_failed']);
}
