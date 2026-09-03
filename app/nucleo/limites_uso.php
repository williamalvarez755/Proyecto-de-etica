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
