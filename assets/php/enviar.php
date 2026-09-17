<?php
// Kairos School of Coaching — manejador de formularios (matrícula / agenda)
// Envía los datos del formulario por correo a kscontacto@ksconsultores.cl
// usando la función mail() nativa de PHP. Requiere hosting con PHP (cPanel).

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
    $etiqueta = $etiquetas[$campo] ?? ucfirst(str_replace('_', ' ', $campo));
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

$headers = "From: Kairos School of Coaching <{$destinatario}>\r\n";
$headers .= "Reply-To: {$correoRespuesta}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$enviado = mail($destinatario, $asunto, $cuerpo, $headers);

if ($enviado) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'mail_failed']);
}
