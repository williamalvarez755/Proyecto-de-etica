<?php
/**
 * PLANTILLA DE CONFIGURACIÓN
 * -----------------------------------------------------------------
 * Este archivo SÍ va al repositorio. Nunca tiene credenciales reales.
 *
 * QUÉ HACER CON ÉL:
 *   1. Copialo y ponele de nombre  config.php  (en esta misma carpeta).
 *   2. Llená los datos que te da el panel de InfinityFree.
 *   3. Subí solamente config.php por FTP. Nunca lo subas a GitHub:
 *      ya está excluido en el .gitignore.
 *
 * Si config.php no existe, el sitio muestra un mensaje claro y no
 * arranca. Eso es a propósito: preferimos que no funcione a que
 * funcione con datos equivocados.
 */

// -----------------------------------------------------------------
//  Base de datos (panel de InfinityFree -> MySQL Databases)
// -----------------------------------------------------------------
define('BD_SERVIDOR', 'sqlXXX.infinityfree.com');
define('BD_NOMBRE',   'ifXXXXXXX_plataforma');
define('BD_USUARIO',  'ifXXXXXXX_usuario');
define('BD_CLAVE',    'la-contrasena-de-la-base');

// -----------------------------------------------------------------
//  Sitio
// -----------------------------------------------------------------

// Dirección pública, sin barra al final. Se usa para las redirecciones.
define('SITIO_URL', 'https://tudominio.infinityfreeapp.com');

// Nombre visible de la plataforma.
define('SITIO_NOMBRE', 'Trabajo Verificado');

// En 'produccion' los errores se guardan en el archivo de bitácora y el
// usuario ve una página de disculpa. En 'desarrollo' se muestran en
// pantalla. NUNCA dejar 'desarrollo' en el servidor público.
define('ENTORNO', 'produccion');
