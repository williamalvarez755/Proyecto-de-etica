<?php
/**
 * ARRANQUE DEL SISTEMA
 * -----------------------------------------------------------------
 * TODA página del sitio empieza con una sola línea:
 *
 *     require __DIR__ . '/app/nucleo/inicio.php';      (desde htdocs/)
 *     require __DIR__ . '/../app/nucleo/inicio.php';   (desde htdocs/cuenta/ o htdocs/admin/)
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

// La carpeta app/. En InfinityFree vive DENTRO de htdocs (el hosting no
// deja a PHP salir de ahí) y la protegen tres .htaccess: ver D-044 en
// CLAUDE.md. Al migrar a un servidor propio vuelve afuera.
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

// El lema que acompaña al nombre ("Trabajo sin fronteras"). Se agregó
// después de que el sitio ya estaba instalado: si el config.php del
// servidor todavía no lo tiene, el sitio arranca igual, sin lema.
if (!defined('SITIO_LEMA')) {
    define('SITIO_LEMA', '');
}


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
require RAIZ_APP . '/nucleo/iconos.php';       // íconos SVG de la interfaz
require RAIZ_APP . '/nucleo/validacion.php';   // validación de entrada
require RAIZ_APP . '/nucleo/bd.php';           // conexión PDO
require RAIZ_APP . '/nucleo/sesion.php';       // sesión segura
require RAIZ_APP . '/nucleo/csrf.php';         // token de formularios
require RAIZ_APP . '/nucleo/codigos.php';      // códigos y contraseñas temporales
require RAIZ_APP . '/nucleo/bitacora.php';     // auditoría
require RAIZ_APP . '/nucleo/limites_uso.php';  // control de abuso
require RAIZ_APP . '/modelos/usuarios.php';    // consultas de cuentas
require RAIZ_APP . '/nucleo/autorizacion.php'; // rol y permiso (regla 5)

// Modelos del dominio: todas las consultas SQL, agrupadas por entidad.
require RAIZ_APP . '/modelos/rubros.php';
require RAIZ_APP . '/modelos/fuentes.php';
require RAIZ_APP . '/modelos/reclutadores.php';
require RAIZ_APP . '/modelos/ofertas.php';
require RAIZ_APP . '/modelos/perfiles.php';
require RAIZ_APP . '/modelos/guardadas.php';
require RAIZ_APP . '/modelos/restablecimientos.php';
require RAIZ_APP . '/modelos/postulaciones.php';
require RAIZ_APP . '/modelos/reportes.php';
require RAIZ_APP . '/modelos/eliminacion.php';

// Manejo de currículums: subida segura y lectura del archivo.
require RAIZ_APP . '/nucleo/archivos.php';
require RAIZ_APP . '/nucleo/extraccion.php';

// Emparejamiento explicable entre perfiles y ofertas (reglas 7 y 8).
require RAIZ_APP . '/nucleo/emparejamiento.php';


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

// Freno para quien pide páginas más rápido de lo que podría una
// persona. Va acá, después de la sesión y antes de cualquier consulta
// pesada, para que corte lo antes posible.
revisar_ritmo();


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
    // Caso especial: cuando alguien sube un archivo más grande de lo
    // que PHP acepta, PHP descarta TODO el formulario, incluido el
    // token. Sin esto, la persona vería "no pudimos completar esa
    // acción" y no entendería nunca que el problema era el tamaño.
    $largo = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($_POST === [] && $largo > 0) {
        abortar(
            413,
            'El archivo pesa demasiado',
            'El archivo que intentaste subir es más grande de lo que el servidor acepta. '
            . 'Probá con uno más liviano.'
        );
    }

    validar_csrf();
}
