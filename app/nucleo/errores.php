<?php
/**
 * MANEJO DE ERRORES
 * -----------------------------------------------------------------
 * Regla: el usuario NUNCA ve un error técnico.
 *
 * Un mensaje de PHP en pantalla le regala a quien esté atacando la ruta
 * completa de los archivos, el nombre de las tablas y a veces hasta la
 * consulta con los datos. Además, a la persona que vino a buscar
 * trabajo no le sirve de nada y le hace ver el sitio como poco serio,
 * que es exactamente lo que no podemos permitirnos (regla 11).
 *
 * Entonces: todo error se guarda en app/almacen/logs/ y en pantalla
 * sale una página de disculpa en español, con salida.
 */

// -----------------------------------------------------------------
//  Configuración de PHP según el entorno
// -----------------------------------------------------------------
if (defined('ENTORNO') && ENTORNO === 'desarrollo') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
error_reporting(E_ALL);


/**
 * Guarda una línea en el archivo de errores del día.
 *
 * Nunca escribe contraseñas, códigos ni contenido de formularios:
 * solo qué pasó, dónde y cuándo.
 */
function registrar_error(string $mensaje, string $archivo = '', int $linea = 0): void
{
    $ruta = RUTA_LOGS . '/errores-' . date('Y-m') . '.log';

    $linea_log = sprintf(
        "[%s] %s | %s:%d | %s%s",
        date('Y-m-d H:i:s'),
        $mensaje,
        $archivo,
        $linea,
        $_SERVER['REQUEST_URI'] ?? 'cli',
        PHP_EOL
    );

    // El @ es a propósito: si no se puede escribir el log (permisos,
    // disco lleno), no queremos que el intento de registrar el error
    // provoque otro error y deje la página en blanco.
    @file_put_contents($ruta, $linea_log, FILE_APPEND | LOCK_EX);
}


/**
 * Convierte los avisos de PHP (notice, warning) en excepciones, para
 * que todo pase por el mismo camino y nada se muestre a medias.
 */
function manejar_error_php(int $nivel, string $mensaje, string $archivo = '', int $linea = 0): bool
{
    if (!(error_reporting() & $nivel)) {
        return false;
    }
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
}


/**
 * Último recurso: cualquier excepción que nadie atrapó llega acá.
 */
function manejar_excepcion(Throwable $e): void
{
    registrar_error(
        get_class($e) . ': ' . $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );

    if (defined('ENTORNO') && ENTORNO === 'desarrollo') {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "ERROR (solo visible en desarrollo)\n\n";
        echo $e->getMessage() . "\n\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();
        exit;
    }

    mostrar_pagina_error();
}


/**
 * Errores fatales que ni siquiera llegan al manejador de excepciones
 * (por ejemplo, quedarse sin memoria).
 */
function manejar_apagado(): void
{
    $error = error_get_last();
    if ($error === null) {
        return;
    }
    if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    registrar_error('FATAL: ' . $error['message'], $error['file'], $error['line']);

    if (!(defined('ENTORNO') && ENTORNO === 'desarrollo')) {
        mostrar_pagina_error();
    }
}


/**
 * Página de disculpa. Sin detalles técnicos y con una salida clara:
 * ninguna pantalla del sitio deja a la persona sin a dónde ir.
 */
function mostrar_pagina_error(): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Algo salió mal</title>';
    echo '<link rel="stylesheet" href="/recursos/estilo.css"></head><body>';
    echo '<main class="contenedor contenedor--angosto pila-grande">';
    echo '<div class="aviso aviso--error">';
    echo '<h1 class="titulo-pagina">Algo salió mal de nuestro lado</h1>';
    echo '<p>No pudimos mostrar esta página. El problema quedó registrado y lo vamos a revisar.</p>';
    echo '<p>Tus datos no se vieron afectados.</p>';
    echo '</div>';
    echo '<p><a class="boton boton--principal" href="/">Volver al inicio</a></p>';
    echo '</main></body></html>';
    exit;
}


// -----------------------------------------------------------------
//  Se activan los tres manejadores
// -----------------------------------------------------------------
set_error_handler('manejar_error_php');
set_exception_handler('manejar_excepcion');
register_shutdown_function('manejar_apagado');
