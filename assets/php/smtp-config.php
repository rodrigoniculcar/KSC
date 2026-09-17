<?php
// Configuración SMTP para el envío de los formularios del sitio.
//
// Reemplaza 'TU_CONTRASEÑA_AQUI' por la contraseña real del correo
// kscontacto@ksconsultores.cl (la misma que usas para entrar al Webmail).
//
// Si el host/puerto no funcionan, en cPanel ve a "Cuentas de correo" →
// busca kscontacto@ksconsultores.cl → "Conectar dispositivos" (Connect
// Devices) y copia ahí los datos exactos de servidor SMTP, puerto y
// tipo de seguridad para esta casilla.
//
// Este archivo queda solo en el servidor: no lo compartas ni lo subas
// a un repositorio público con la contraseña real puesta.

return [
    'host' => 'mail.ksconsultores.cl',
    'port' => 465,
    'secure' => 'ssl', // 'tls' para el puerto 587, 'ssl' para el puerto 465
    'username' => 'noreply@ksconsultores.cl',
    'password' => 'TU_CONTRASEÑA_AQUI',
];
