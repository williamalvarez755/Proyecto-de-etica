<?php
/**
 * RESTABLECIMIENTO ASISTIDO DE CONTRASEÑA
 * -----------------------------------------------------------------
 * No hay recuperación por correo porque el hosting tiene el envío
 * deshabilitado (decisión D-005). Se resuelve así:
 *
 *   1. La persona avisa a la institución que no puede entrar.
 *   2. Un administrador con permiso genera un código.
 *   3. El sistema guarda SOLO EL HASH del código, con una hora de
 *      vida, e invalida cualquier código anterior de esa cuenta.
 *   4. El código se muestra UNA sola vez en pantalla y se dicta.
 *   5. La persona lo usa junto con su correo para poner una
 *      contraseña nueva.
 *   6. En cuanto se usa, queda marcado y no sirve más.
 *
 * El código se guarda con password_hash, igual que una contraseña.
 * Si alguien se llevara la base de datos, no podría usar los códigos
 * pendientes. Y en la bitácora queda que se generó uno, nunca cuál.
 */

/**
 * Genera un código nuevo para esa cuenta y devuelve el código EN CLARO.
 * Es la única vez que existe legible: quien llama tiene que mostrarlo
 * en ese momento y no guardarlo en ningún lado.
 */
function crear_restablecimiento(int $usuario_id, int $admin_id): string
{
    // Cualquier código anterior deja de servir en este momento.
    invalidar_restablecimientos($usuario_id);

    $codigo = generar_codigo(RESTABLECIMIENTO_LARGO_CODIGO);

    consultar(
        'INSERT INTO restablecimientos (usuario_id, codigo_hash, creado_por, creado_en, expira_en)
         VALUES (?, ?, ?, ?, ?)',
        [
            $usuario_id,
            password_hash($codigo, PASSWORD_DEFAULT),
            $admin_id,
            ahora(),
            ahora_mas_minutos(RESTABLECIMIENTO_VALIDEZ_MINUTOS),
        ]
    );

    return $codigo;
}

/** Deja sin efecto todos los códigos pendientes de esa cuenta. */
function invalidar_restablecimientos(int $usuario_id): void
{
    consultar(
        'UPDATE restablecimientos SET invalidado_en = ?
         WHERE usuario_id = ? AND usado_en IS NULL AND invalidado_en IS NULL',
        [ahora(), $usuario_id]
    );
}

/**
 * Busca el código vigente de esa cuenta y comprueba que coincida.
 * Devuelve la fila si sirve, o null.
 *
 * Se busca por usuario y no por código: los códigos se guardan con
 * hash y un hash no se puede buscar directamente, igual que pasa con
 * las contraseñas.
 */
function validar_restablecimiento(int $usuario_id, string $codigo): ?array
{
    $fila = consultar_una(
        'SELECT * FROM restablecimientos
         WHERE usuario_id = ? AND usado_en IS NULL AND invalidado_en IS NULL AND expira_en >= ?
         ORDER BY id DESC LIMIT 1',
        [$usuario_id, ahora()]
    );

    if ($fila === null) {
        return null;
    }

    if (!password_verify($codigo, $fila['codigo_hash'])) {
        return null;
    }

    return $fila;
}

/** Lo marca como usado. A partir de acá no sirve más, ni aunque lo repitan. */
function marcar_restablecimiento_usado(int $id): void
{
    consultar(
        'UPDATE restablecimientos SET usado_en = ? WHERE id = ?',
        [ahora(), $id]
    );
}

/**
 * Los códigos que están vigentes ahora mismo, para que el panel muestre
 * si ya se le generó uno a alguien y no se anden generando de a tres.
 */
function restablecimientos_vigentes(): array
{
    return consultar_todas(
        'SELECT r.id, r.creado_en, r.expira_en, u.correo, u.nombre,
                a.nombre AS generado_por
         FROM restablecimientos r
         INNER JOIN usuarios u ON u.id = r.usuario_id
         LEFT JOIN usuarios a ON a.id = r.creado_por
         WHERE r.usado_en IS NULL AND r.invalidado_en IS NULL AND r.expira_en >= ?
         ORDER BY r.id DESC',
        [ahora()]
    );
}
