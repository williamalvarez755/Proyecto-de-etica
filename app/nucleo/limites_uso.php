<?php
/**
 * LÍMITES DE USO
 * -----------------------------------------------------------------
 * Cuenta los intentos de las acciones sensibles y bloquea cuando se
 * pasan del límite.
 *
 * Sirve para dos cosas al mismo tiempo:
 *
 *   1. Que nadie pueda estar probando contraseñas hasta acertar.
 *   2. Que un abuso no se coma las 30 000 peticiones diarias que da
 *      InfinityFree. Si eso pasa, el sitio se cae para todos, incluida
 *      la persona que iba a revisar si el reclutador que la contactó
 *      es real. Por eso acá el límite de uso es seguridad, no cortesía.
 *
 * Todos los números salen de app/config/limites.php.
 */

/**
 * Deja registrado un intento (haya salido bien o mal).
 *
 * El identificador es lo que se está intentando: normalmente el correo.
 * También se guarda la IP, para poder frenar a alguien que esté
 * probando muchos correos distintos.
 */
function registrar_intento(string $tipo, string $identificador, bool $exito): void
{
    if (!en_lista($tipo, TIPOS_INTENTO)) {
        registrar_error('Tipo de intento desconocido: ' . $tipo, __FILE__, __LINE__);
        return;
    }

    consultar(
        'INSERT INTO intentos_acceso (tipo, identificador, ip, exito, creado_en)
         VALUES (?, ?, ?, ?, ?)',
        [
            $tipo,
            mb_substr(mb_strtolower($identificador, 'UTF-8'), 0, 191, 'UTF-8'),
            ip_cliente(),
            $exito ? 1 : 0,
            ahora(),
        ]
    );
}


/** Cuántos intentos FALLIDOS lleva ese identificador en los últimos minutos. */
function contar_fallos(string $tipo, string $identificador, int $minutos): int
{
    return (int) consultar_valor(
        'SELECT COUNT(*) FROM intentos_acceso
         WHERE tipo = ? AND identificador = ? AND exito = 0 AND creado_en >= ?',
        [$tipo, mb_strtolower($identificador, 'UTF-8'), ahora_menos_minutos($minutos)]
    );
}


/**
 * Cuántos intentos hizo este identificador, hayan salido bien o mal.
 *
 * Se cuenta por identificador y no por IP cuando ya sabemos quién es
 * la persona (por ejemplo, al subir un currículum). En un café
 * internet o con datos móviles, mucha gente comparte la misma IP:
 * limitar por IP haría que unos le consuman el cupo a otros.
 */
function contar_intentos(string $tipo, string $identificador, int $minutos): int
{
    return (int) consultar_valor(
        'SELECT COUNT(*) FROM intentos_acceso
         WHERE tipo = ? AND identificador = ? AND creado_en >= ?',
        [$tipo, mb_strtolower($identificador, 'UTF-8'), ahora_menos_minutos($minutos)]
    );
}

/** Cuántos intentos hizo esta dirección IP, hayan salido bien o mal. */
function contar_intentos_por_ip(string $tipo, int $minutos): int
{
    return (int) consultar_valor(
        'SELECT COUNT(*) FROM intentos_acceso
         WHERE tipo = ? AND ip = ? AND creado_en >= ?',
        [$tipo, ip_cliente(), ahora_menos_minutos($minutos)]
    );
}


/**
 * ¿Está bloqueado ahora mismo?
 *
 * Se cuenta dentro de la ventana de bloqueo: si en los últimos
 * LOGIN_BLOQUEO_MINUTOS hubo más fallos que los permitidos, se bloquea.
 * El bloqueo se suelta solo cuando esos intentos quedan viejos: no hace
 * falta que nadie lo desbloquee a mano.
 */
function esta_bloqueado(string $tipo, string $identificador, int $maximo, int $minutos_bloqueo): bool
{
    return contar_fallos($tipo, $identificador, $minutos_bloqueo) >= $maximo;
}


/**
 * Cuántos minutos le faltan para poder volver a intentar.
 * Se usa solo para escribir un mensaje entendible en pantalla.
 */
function minutos_para_reintentar(string $tipo, string $identificador, int $minutos_bloqueo): int
{
    $ultimo = consultar_valor(
        'SELECT creado_en FROM intentos_acceso
         WHERE tipo = ? AND identificador = ? AND exito = 0
         ORDER BY id DESC LIMIT 1',
        [$tipo, mb_strtolower($identificador, 'UTF-8')]
    );

    if ($ultimo === null) {
        return 0;
    }

    $libre_en = strtotime($ultimo) + ($minutos_bloqueo * 60);
    $faltan   = (int) ceil(($libre_en - time()) / 60);

    return max(0, $faltan);
}


/**
 * Borra los fallos de ese identificador. Se llama cuando la persona
 * por fin entra bien: no tiene sentido que siga arrastrando el bloqueo.
 */
function limpiar_fallos(string $tipo, string $identificador): void
{
    consultar(
        'DELETE FROM intentos_acceso
         WHERE tipo = ? AND identificador = ? AND exito = 0',
        [$tipo, mb_strtolower($identificador, 'UTF-8')]
    );
}


/**
 * RITMO DE NAVEGACIÓN
 * -----------------------------------------------------------------
 * Frena a quien pide páginas a una velocidad que ninguna persona
 * podría. Importa doble acá: el hosting tiene tope de 30 000
 * peticiones diarias, y si alguien se las come, el sitio se cae para
 * todos — incluida la persona que iba a entrar a comprobar si el
 * reclutador que la contactó es real.
 *
 * Se cuenta DENTRO DE LA SESIÓN, sin tocar la base de datos: agregarle
 * una consulta a cada visita sería empeorar el problema que queremos
 * resolver.
 *
 * Honestamente: esto frena programas que mantienen la sesión y el
 * ruido de fondo. A alguien decidido que pida páginas sin cookies no
 * lo para, y eso no se puede resolver desde PHP en un hosting
 * compartido. Para eso están los límites por acción, que sí son
 * infranqueables porque tocan la base.
 */
function revisar_ritmo(): void
{
    $ahora  = time();
    $inicio = $_SESSION['ritmo_inicio'] ?? $ahora;

    // La ventana se reinicia cada minuto.
    if ($ahora - $inicio >= 60) {
        $_SESSION['ritmo_inicio'] = $ahora;
        $_SESSION['ritmo_cuenta'] = 1;
        return;
    }

    $_SESSION['ritmo_cuenta'] = ($_SESSION['ritmo_cuenta'] ?? 0) + 1;

    if ($_SESSION['ritmo_cuenta'] > PETICIONES_MAX_POR_MINUTO) {
        http_response_code(429);
        header('Retry-After: 60');
        abortar(
            429,
            'Esperá un momento',
            'Se pidieron demasiadas páginas muy rápido desde este navegador. '
            . 'Esperá un minuto y volvé a intentar.'
        );
    }
}


/**
 * Borra los intentos viejos. Se dispara desde el panel (Fase 6), porque
 * el hosting no tiene tareas programadas (decisión D-003).
 */
function limpiar_intentos_viejos(): int
{
    $sentencia = consultar(
        'DELETE FROM intentos_acceso WHERE creado_en < ?',
        [ahora_menos_dias(RETENCION_INTENTOS_DIAS)]
    );
    return $sentencia->rowCount();
}
