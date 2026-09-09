<?php
/**
 * OFERTAS GUARDADAS
 * -----------------------------------------------------------------
 * "Guardar para verlo después".
 *
 * Sirve para algo muy concreto de nuestra población: alguien que está
 * usando datos móviles contados, o un teléfono prestado, encuentra una
 * oferta y no puede quedarse a leerla ahí mismo. La guarda y la abre
 * cuando tenga wifi o cuando pueda sentarse.
 *
 * Guardar NO es postularse y no comparte ningún dato con nadie. La
 * postulación, con su consentimiento propio, es otra cosa y se
 * construye en la Fase 4.
 */

function esta_guardada(int $usuario_id, int $oferta_id): bool
{
    return consultar_valor(
        'SELECT 1 FROM ofertas_guardadas WHERE usuario_id = ? AND oferta_id = ?',
        [$usuario_id, $oferta_id]
    ) !== null;
}

function guardar_oferta(int $usuario_id, int $oferta_id): void
{
    if (esta_guardada($usuario_id, $oferta_id)) {
        return;
    }
    consultar(
        'INSERT INTO ofertas_guardadas (usuario_id, oferta_id, creado_en) VALUES (?, ?, ?)',
        [$usuario_id, $oferta_id, ahora()]
    );
}

function quitar_oferta_guardada(int $usuario_id, int $oferta_id): void
{
    consultar(
        'DELETE FROM ofertas_guardadas WHERE usuario_id = ? AND oferta_id = ?',
        [$usuario_id, $oferta_id]
    );
}

/**
 * Las ofertas que guardó y que TODAVÍA se pueden mostrar.
 *
 * Se usa la misma condición pública que en el buscador: si una oferta
 * guardada se venció o se retiró, deja de aparecer acá también. Sería
 * peor mostrarle a alguien una oferta que ya no existe que no
 * mostrarle nada.
 */
function listar_ofertas_guardadas(int $usuario_id): array
{
    return consultar_todas(
        SELECCION_OFERTA . '
         INNER JOIN ofertas_guardadas g ON g.oferta_id = o.id
         WHERE ' . CONDICION_OFERTA_PUBLICA . ' AND g.usuario_id = :usuario_id
         ORDER BY g.creado_en DESC',
        [':hoy' => hoy(), ':usuario_id' => $usuario_id]
    );
}

/** Cuántas guardó que ya no están disponibles, para poder avisarle. */
function contar_guardadas_no_disponibles(int $usuario_id): int
{
    $total = (int) consultar_valor(
        'SELECT COUNT(*) FROM ofertas_guardadas WHERE usuario_id = ?',
        [$usuario_id]
    );
    return $total - count(listar_ofertas_guardadas($usuario_id));
}
