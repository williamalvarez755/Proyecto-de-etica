<?php
/**
 * SESIONES
 * -----------------------------------------------------------------
 * La sesión es lo que mantiene a alguien identificado entre una página
 * y la siguiente. Si se la roban, se roban la cuenta. Por eso:
 *
 *   httponly  -> el JavaScript de la página no puede leer la cookie,
 *                así un XSS no se lleva la sesión.
 *   secure    -> la cookie solo viaja por HTTPS, nunca en claro.
 *   samesite  -> otro sitio no puede hacer que el navegador mande
 *                nuestra cookie (base de la protección contra CSRF).
 *   strict_mode -> PHP no acepta un identificador de sesión inventado
 *                por el visitante (evita la "fijación de sesión").
 *
 * Además la sesión se cierra sola por inactividad y tiene una duración
 * máxima aunque la persona siga usándola.
 */

function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Que PHP no acepte identificadores de sesión que no creó él.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name(SESION_NOMBRE);

    session_set_cookie_params([
        'lifetime' => 0,               // la cookie muere al cerrar el navegador
        'path'     => '/',
        'domain'   => '',
        'secure'   => (ENTORNO === 'produccion') ? true : es_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    revisar_vida_de_sesion();
    revisar_huella();
}


/**
 * Cierra la sesión sola si pasó demasiado tiempo.
 *
 * Importa especialmente acá: mucha gente va a entrar desde un celular
 * prestado o desde una computadora de un café internet.
 */
function revisar_vida_de_sesion(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }

    $ahora = time();

    $inactividad = $ahora - ($_SESSION['ultima_actividad'] ?? $ahora);
    if ($inactividad > SESION_INACTIVIDAD_MINUTOS * 60) {
        cerrar_sesion();
        guardar_mensaje('aviso', 'Cerramos tu sesión porque pasó un rato sin actividad. Volvé a entrar.');
        return;
    }

    $duracion = $ahora - ($_SESSION['sesion_iniciada_en'] ?? $ahora);
    if ($duracion > SESION_DURACION_MAXIMA_MINUTOS * 60) {
        cerrar_sesion();
        guardar_mensaje('aviso', 'Por seguridad cerramos tu sesión. Volvé a entrar.');
        return;
    }

    $_SESSION['ultima_actividad'] = $ahora;
}


/**
 * Comprueba que la sesión la siga usando el mismo navegador.
 *
 * Se usa solo el navegador y NO la dirección IP a propósito: en datos
 * móviles la IP cambia sola todo el tiempo, y estaríamos sacando de la
 * sesión a la gente cada cinco minutos, que es justo nuestra población.
 */
function revisar_huella(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }

    $huella_actual = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido');

    if (!isset($_SESSION['huella'])) {
        $_SESSION['huella'] = $huella_actual;
        return;
    }

    if (!hash_equals($_SESSION['huella'], $huella_actual)) {
        cerrar_sesion();
        guardar_mensaje('aviso', 'Cerramos tu sesión por seguridad. Volvé a entrar.');
    }
}


/**
 * Deja registrado que esta persona inició sesión.
 *
 * session_regenerate_id es obligatorio acá: cambia el identificador de
 * la sesión al momento de entrar. Sin esto, alguien que hubiera logrado
 * fijar un identificador antes del login se quedaría con la sesión ya
 * autenticada.
 */
function iniciar_sesion_de_usuario(int $usuario_id): void
{
    session_regenerate_id(true);

    $_SESSION['usuario_id']        = $usuario_id;
    $_SESSION['sesion_iniciada_en']= time();
    $_SESSION['ultima_actividad']  = time();
    $_SESSION['huella']            = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido');

    renovar_sello_de_clave($usuario_id);
}


/**
 * SELLO DE LA CONTRASEÑA
 * -----------------------------------------------------------------
 * Cambiar la contraseña tiene que sacar a TODAS las otras sesiones
 * abiertas de esa cuenta, no solo renovar la propia. Es justo el caso
 * de nuestra población: alguien entró desde un café internet o un
 * teléfono prestado, se olvidó de salir, y después cambia la
 * contraseña (o pide un restablecimiento) para cortar ese acceso.
 *
 * session_regenerate_id() solo cambia el identificador de la sesión
 * de quien hace el cambio. La sesión olvidada en el café seguía viva
 * hasta 8 horas más.
 *
 * Cómo se resuelve sin agregar columnas: la sesión guarda una huella
 * del hash de la contraseña con la que se abrió. usuario_actual(), que
 * ya consulta la base en cada petición (D-014), compara. Si la
 * contraseña cambió desde cualquier lado, la huella no coincide y la
 * sesión se cierra.
 *
 * Lo que se guarda en la sesión es un SHA-256 del hash, no el hash:
 * aunque alguien leyera el archivo de sesión, no se lleva nada que
 * sirva para probar contraseñas.
 */
function sello_de_clave(string $contrasena_hash): string
{
    return hash('sha256', $contrasena_hash);
}

/** Guarda en la sesión el sello de la contraseña actual de esa cuenta. */
function renovar_sello_de_clave(int $usuario_id): void
{
    $hash = consultar_valor('SELECT contrasena_hash FROM usuarios WHERE id = ?', [$usuario_id]);
    $_SESSION['sello_clave'] = $hash === null ? '' : sello_de_clave((string) $hash);
}


/** Cierra la sesión y borra la cookie de verdad. */
function cerrar_sesion(): void
{
    $_SESSION = [];

    // Si la página ya empezó a imprimirse, tocar las cookies dispara un
    // aviso de PHP que acabaría en una pantalla de error. En ese caso
    // se limpia igual la sesión del servidor, que es lo que importa.
    if (headers_sent()) {
        session_destroy();
        return;
    }

    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 42000,
                'path'     => $parametros['path'],
                'domain'   => $parametros['domain'],
                'secure'   => $parametros['secure'],
                'httponly' => $parametros['httponly'],
                'samesite' => 'Lax',
            ]
        );
    }

    session_destroy();
    session_start();   // sesión limpia, para poder mostrar el mensaje de salida
}
