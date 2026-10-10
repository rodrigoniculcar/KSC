<?php
// Kairos School of Coaching — URL de notificación de Getnet.
// Getnet llama aquí cuando cambia el estado de un pago, aunque el alumno
// cierre el navegador antes de volver al sitio. No se confía en el cuerpo
// recibido: se verifica la firma y luego se consulta el estado a Getnet.

require __DIR__ . '/getnet.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !getnetConfigurado()) {
    http_response_code(405);
    exit;
}

$aviso = json_decode(file_get_contents('php://input'), true);
$requestId = (string) ($aviso['requestId'] ?? '');
$referencia = $aviso['reference'] ?? '';
$estado = $aviso['status']['status'] ?? '';
$fecha = $aviso['status']['date'] ?? '';
$firma = (string) ($aviso['signature'] ?? '');

$secret = getnetConfig()['secret_key'];
$base = $requestId . $estado . $fecha . $secret;
$firmaValida = strpos($firma, 'sha256:') === 0
    ? hash_equals(hash('sha256', $base), substr($firma, 7))
    : hash_equals(sha1($base), $firma);

$matricula = matriculaLeer($referencia);
if (!$firmaValida || !$matricula || ($matricula['request_id'] ?? '') !== $requestId) {
    getnetLog('Notificación rechazada: ' . json_encode($aviso));
    http_response_code(400);
    exit;
}

matriculaActualizarEstado($matricula);
http_response_code(200);
echo 'OK';
