<?php
/**
 * BITÁCORA DE AUDITORÍA
 * -----------------------------------------------------------------
 * Deja registro de quién hizo qué y cuándo.
 *
 * Se empieza a escribir desde la Fase 1, no en la Fase 6, porque una
 * bitácora que se agrega al final siempre queda con huecos: nadie se
 * acuerda de todas las acciones que ya existían.
 *
 * LO QUE NUNCA VA EN LA BITÁCORA:
 *   contraseñas, códigos de restablecimiento, tokens, contenido de un
 *   currículum, ni datos personales que no hagan falta para auditar.
 *
 * El campo "detalle" es para contexto corto y legible ("cambió el
 * estado a publicada"), no para volcar formularios completos.
 */

function registrar_accion(
    string $accion,
    ?string $entidad = null,
    ?int $entidad_id = null,
    ?string $detalle = null,
    ?int $usuario_id = null
): void {
    // Si no se dice de quién es la acción, se toma de la sesión.
    if ($usuario_id === null) {
        $usuario_id = $_SESSION['usuario_id'] ?? null;
    }

    // El detalle se recorta: la columna mide 255 y no queremos que un
    // texto largo tumbe la operación que se está auditando.
    if ($detalle !== null) {
        $detalle = mb_substr(limpiar_texto($detalle), 0, 255, 'UTF-8');
    }

    try {
        consultar(
            'INSERT INTO bitacora_admin (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$usuario_id, $accion, $entidad, $entidad_id, $detalle, ip_cliente(), ahora()]
        );
    } catch (Throwable $e) {
        // Que no se pueda escribir la bitácora no debe impedir que la
        // persona termine lo que estaba haciendo. Queda en el log de
        // errores, que es el otro lugar donde vamos a mirar.
        registrar_error('No se pudo escribir en la bitácora: ' . $e->getMessage(), __FILE__, __LINE__);
    }
}


/** Últimas acciones registradas, para la pantalla de bitácora del panel. */
function leer_bitacora(int $limite, int $desde = 0): array
{
    return consultar_paginado(
        'SELECT b.id, b.accion, b.entidad, b.entidad_id, b.detalle, b.ip, b.creado_en,
                u.correo AS correo_usuario, u.nombre AS nombre_usuario
         FROM bitacora_admin b
         LEFT JOIN usuarios u ON u.id = b.usuario_id
         ORDER BY b.id DESC',
        [],
        min($limite, 200),
        $desde
    );
}

/** Cuántas entradas hay en total (para la paginación). */
function contar_bitacora(): int
{
    return (int) consultar_valor('SELECT COUNT(*) FROM bitacora_admin');
}
