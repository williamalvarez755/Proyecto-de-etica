<?php
/**
 * LA PETICIÓN HTTP
 * -----------------------------------------------------------------
 * Todo lo que tiene que ver con lo que llegó del navegador.
 * Se junta acá para que ningún archivo ande tocando $_POST ni
 * $_SERVER por su cuenta y cada uno lo interprete distinto.
 */

/** ¿El formulario se está enviando (POST) o solo se está viendo (GET)? */
function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}


/**
 * Lee un campo del formulario enviado por POST.
 * Siempre devuelve texto, siempre sin espacios sobrantes a los lados.
 * Nunca devuelve null: así ningún archivo tiene que andar preguntando
 * si el campo venía o no.
 */
function campo(string $nombre, string $por_defecto = ''): string
{
    if (!isset($_POST[$nombre]) || !is_string($_POST[$nombre])) {
        return $por_defecto;
    }
    return trim($_POST[$nombre]);
}

/**
 * Lee un campo TAL CUAL vino, sin quitarle los espacios.
 * Se usa solo para las contraseñas: si alguien eligió a propósito una
 * contraseña que empieza o termina con un espacio, no somos nosotros
 * quienes se lo cambiamos por detrás.
 */
function campo_crudo(string $nombre): string
{
    if (!isset($_POST[$nombre]) || !is_string($_POST[$nombre])) {
        return '';
    }
    return $_POST[$nombre];
}

/** Lo mismo, para los parámetros de la dirección (?pagina=2). */
function parametro(string $nombre, string $por_defecto = ''): string
{
    if (!isset($_GET[$nombre]) || !is_string($_GET[$nombre])) {
        return $por_defecto;
    }
    return trim($_GET[$nombre]);
}

/** Lee una lista de valores marcados (casillas de verificación). */
function campo_lista(string $nombre): array
{
    if (!isset($_POST[$nombre]) || !is_array($_POST[$nombre])) {
        return [];
    }
    $limpios = [];
    foreach ($_POST[$nombre] as $valor) {
        if (is_string($valor)) {
            $limpios[] = trim($valor);
        }
    }
    return $limpios;
}


/**
 * Dirección IP de quien está visitando.
 *
 * A propósito se usa SOLO $_SERVER['REMOTE_ADDR'] y no las cabeceras
 * X-Forwarded-For: esas las puede escribir cualquiera en su petición.
 * Si confiáramos en ellas, saltarse el bloqueo por intentos fallidos
 * sería tan fácil como mandar una cabecera distinta cada vez.
 */
function ip_cliente(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}


/** ¿La visita llegó por HTTPS? */
function es_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    // InfinityFree entrega el sitio detrás de un proxy: cuando la
    // conexión del visitante fue segura, lo avisa con esta cabecera.
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        return true;
    }
    return ($_SERVER['SERVER_PORT'] ?? '') === '443';
}


/**
 * Manda al navegador a otra página del sitio y corta la ejecución.
 *
 * Solo acepta rutas internas que empiecen con una sola barra. Es para
 * evitar el "redirección abierta": que alguien arme un enlace a nuestro
 * sitio que termine mandando a la persona a una página de estafa. En
 * este proyecto esa protección no es teórica, es el ataque más obvio.
 */
function redirigir(string $ruta): void
{
    if ($ruta === '' || $ruta[0] !== '/' || str_starts_with($ruta, '//') || str_contains($ruta, "\\")) {
        $ruta = '/';
    }

    header('Location: ' . $ruta, true, 302);
    exit;
}


/**
 * Responde con un código de error y una página entendible.
 * Se usa cuando alguien pide algo que no existe o que no le toca.
 */
function abortar(int $codigo, string $titulo, string $mensaje): void
{
    http_response_code($codigo);

    $titulo_pagina = $titulo;
    $texto = $mensaje;
    require RAIZ_APP . '/vistas/pagina_aviso.php';
    exit;
}
