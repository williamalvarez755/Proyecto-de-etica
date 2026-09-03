<?php
/**
 * ARRANQUE DEL SISTEMA
 * -----------------------------------------------------------------
 * TODA página del sitio empieza con una sola línea:
 *
 *     require __DIR__ . '/../app/nucleo/inicio.php';
 *
 * Desde ese momento ya están puestas la configuración, la sesión
 * segura, las cabeceras de seguridad, el manejo de errores, la
 * validación del token CSRF y las funciones de permisos.
 *
 * Está hecho así justamente para que no se pueda olvidar nada: si
 * cada página tuviera que acordarse de incluir la protección CSRF o
 * de comprobar la sesión, tarde o temprano una se queda sin ella, y
 * ese es el agujero clásico.
 */

// La carpeta app/, que está FUERA de htdocs y no es accesible por web.
define('RAIZ_APP', dirname(__DIR__));


// -----------------------------------------------------------------
//  1. Configuración
// -----------------------------------------------------------------
$ruta_config = RAIZ_APP . '/config/config.php';

if (!is_file($ruta_config)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Falta configurar</title>';
    echo '<h1>Falta el archivo de configuración</h1>';
    echo '<p>Copiá <code>app/config/config.ejemplo.php</code> como '
       . '<code>app/config/config.php</code> y llenalo con los datos de la base de datos.</p>';
    exit;
}

require $ruta_config;                       // credenciales y entorno
require RAIZ_APP . '/config/limites.php';   // todos los números del sistema
require RAIZ_APP . '/config/catalogos.php'; // listas de valores válidos


// -----------------------------------------------------------------
//  2. Un solo reloj para todo el sistema
// -----------------------------------------------------------------
date_default_timezone_set(ZONA_HORARIA);


// -----------------------------------------------------------------
//  3. Manejo de errores (antes que nada más, para que atrape todo)
// -----------------------------------------------------------------
require RAIZ_APP . '/nucleo/errores.php';


// -----------------------------------------------------------------
//  4. Las primitivas compartidas
//     El orden importa: cada una usa las anteriores.
// -----------------------------------------------------------------
require RAIZ_APP . '/nucleo/peticion.php';     // ip_cliente, redirigir, abortar
require RAIZ_APP . '/nucleo/salida.php';       // escapar, fechas, mensajes
require RAIZ_APP . '/nucleo/validacion.php';   // validación de entrada
require RAIZ_APP . '/nucleo/bd.php';           // conexión PDO
require RAIZ_APP . '/nucleo/sesion.php';       // sesión segura
require RAIZ_APP . '/nucleo/csrf.php';         // token de formularios
require RAIZ_APP . '/nucleo/codigos.php';      // códigos y contraseñas temporales
require RAIZ_APP . '/nucleo/bitacora.php';     // auditoría
require RAIZ_APP . '/nucleo/limites_uso.php';  // control de abuso
require RAIZ_APP . '/modelos/usuarios.php';    // consultas de cuentas
require RAIZ_APP . '/nucleo/autorizacion.php'; // rol y permiso (regla 5)


// -----------------------------------------------------------------
//  5. Cabeceras de seguridad
//
//  Van acá, en PHP, y no solo en el .htaccess, porque así viajan
//  aunque el .htaccess falle o el hosting lo ignore. Se ponen en toda
//  respuesta del sitio.
// -----------------------------------------------------------------
if (!headers_sent()) {
    // El navegador no adivina el tipo de archivo: respeta lo que decimos.
    // Evita que un archivo subido se interprete como algo que no es.
    header('X-Content-Type-Options: nosniff');

    // Nadie puede meter el sitio dentro de un marco en otra página
    // para engañar a la persona sobre dónde está haciendo clic.
    header('X-Frame-Options: DENY');

    // No le contamos a otros sitios desde qué página del nuestro
    // llegó la persona.
    header('Referrer-Policy: same-origin');

    // Sin cámara, micrófono ni ubicación. La plataforma no los usa.
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // De dónde puede cargar cosas la página: solo de nosotros mismos.
    // Sin scripts de otros sitios, sin estilos externos, y el sitio no
    // se puede incrustar en ningún lado. Es la segunda línea de defensa
    // contra XSS, después de escapar() en toda la salida.
    header(
        "Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; "
        . "frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; "
        . "style-src 'self'; script-src 'self'"
    );

    // Si la visita ya llegó por HTTPS, se le dice al navegador que de
    // ahora en adelante nunca use http para este sitio.
    if (es_https()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
}


// -----------------------------------------------------------------
//  6. Sesión
// -----------------------------------------------------------------
iniciar_sesion();


// -----------------------------------------------------------------
//  7. Protección CSRF automática
//
//  Toda petición POST del sitio pasa por acá. Si el formulario no trae
//  el token de esta sesión, no se ejecuta nada.
//
//  Es automático a propósito: en la Fase 4 o en la 5, cuando se agregue
//  un formulario nuevo, va a estar protegido aunque a nadie se le
//  ocurra acordarse de esto. Lo único que hay que poner en el
//  formulario es campo_csrf().
// -----------------------------------------------------------------
if (es_post()) {
    validar_csrf();
}
