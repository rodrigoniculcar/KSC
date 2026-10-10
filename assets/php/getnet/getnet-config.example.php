<?php
// Configuración de Getnet Chile (Web Checkout) para la matrícula.
//
// CÓMO USARLO
// 1. Copia este archivo con el nombre  getnet-config.php
// 2. Lo más seguro es dejarlo FUERA de public_html, en:
//        /home/ksconsultores/getnet-config.php
//    (también funciona en esta misma carpeta: assets/php/getnet/, donde
//    el .htaccess impide abrirlo desde el navegador).
// 3. Completa login y secret_key con los datos que entrega Getnet.
//
// Mientras no esté completo, el formulario de matrícula sigue funcionando
// como antes: solo envía los datos por correo, sin cobrar.
//
// No subas el archivo con las credenciales reales a GitHub.

return [
    // 'pruebas' (sandbox, sin dinero real) o 'produccion' (cobros reales).
    // Las credenciales de pruebas y de producción son distintas.
    'ambiente' => 'pruebas',

    'login' => 'TU_LOGIN_AQUI',
    'secret_key' => 'TU_SECRET_KEY_AQUI',

    // Valor de la matrícula en pesos chilenos, sin puntos ni decimales.
    'monto' => 0,
    'descripcion' => 'Matrícula Programa de Formación Profesional de Coaches — Kairos School of Coaching',

    // Opcional: URL pública del sitio. Si se deja vacía se usa el dominio
    // desde el que se abrió el formulario.
    'sitio_url' => 'https://ksconsultores.cl',
];
