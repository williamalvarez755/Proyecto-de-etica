<?php
/**
 * PROTECCIÓN CSRF
 * -----------------------------------------------------------------
 * CSRF es este ataque: la persona tiene la sesión abierta en nuestro
 * sitio y visita otra página cualquiera. Esa otra página manda, sin que
 * se dé cuenta, un formulario a nuestro sitio. Como el navegador
 * adjunta la cookie de sesión, para nosotros parece una acción legítima
 * de esa persona: borrar su cuenta, postularse, lo que sea.
 *
 * La defensa: cada formulario lleva un valor secreto que solo está en
 * la sesión. La página del atacante no puede leerlo, así que no puede
 * incluirlo, y el envío se rechaza.
 *
 * En este proyecto la comprobación es AUTOMÁTICA: app/nucleo/inicio.php
 * valida el token en TODA petición POST. No hay forma de olvidarlo en
 * una página nueva, que es exactamente como aparecen estos agujeros.
 * Lo único que hay que acordarse es de poner campo_csrf() en el
 * formulario.
 */

/** Devuelve el token de esta sesión, creándolo la primera vez. */
function token_csrf(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}


/** Imprime el campo oculto. Va dentro de TODO formulario con method="post". */
function campo_csrf(): void
{
    echo '<input type="hidden" name="token_csrf" value="' . escapar(token_csrf()) . '">';
}


/**
 * Comprueba el token. Si no cuadra, la petición se corta acá.
 *
 * hash_equals compara los dos valores en tiempo constante: no responde
 * más rápido cuando los primeros caracteres coinciden, así no se puede
 * ir adivinando el token letra por letra.
 */
function validar_csrf(): void
{
    $enviado  = $_POST['token_csrf'] ?? '';
    $guardado = $_SESSION['token_csrf'] ?? '';

    if (!is_string($enviado) || $guardado === '' || !hash_equals($guardado, $enviado)) {
        abortar(
            400,
            'No pudimos completar esa acción',
            'La página estuvo abierta demasiado tiempo o se envió desde otro lugar. '
            . 'Volvé a abrir la página y hacelo de nuevo. Tus datos no cambiaron.'
        );
    }
}
