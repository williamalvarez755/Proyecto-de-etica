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


/**
 * Arma las condiciones de los filtros de la bitácora.
 *
 * Una bitácora sin filtros es una bitácora que nadie lee: con miles de
 * líneas, encontrar "quién descargó currículums la semana pasada" a
 * ojo es imposible, y entonces la auditoría no sirve para auditar.
 *
 * Como siempre: lo que se pega al texto son nombres de columna
 * escritos por nosotros; los valores van como parámetros.
 */
function filtros_de_bitacora(array $filtros): array
{
    $condiciones = [];
    $parametros  = [];

    if (!empty($filtros['accion'])) {
        $condiciones[] = 'b.accion = :accion';
        $parametros[':accion'] = $filtros['accion'];
    }
    if (!empty($filtros['usuario_id'])) {
        $condiciones[] = 'b.usuario_id = :usuario_id';
        $parametros[':usuario_id'] = (int) $filtros['usuario_id'];
    }
    if (!empty($filtros['desde'])) {
        $condiciones[] = 'b.creado_en >= :desde';
        $parametros[':desde'] = $filtros['desde'] . ' 00:00:00';
    }
    if (!empty($filtros['hasta'])) {
        $condiciones[] = 'b.creado_en <= :hasta';
        $parametros[':hasta'] = $filtros['hasta'] . ' 23:59:59';
    }

    $donde = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

    return [$donde, $parametros];
}

/** Últimas acciones registradas, para la pantalla de bitácora del panel. */
function leer_bitacora(array $filtros, int $limite, int $desde = 0): array
{
    [$donde, $parametros] = filtros_de_bitacora($filtros);

    return consultar_paginado(
        'SELECT b.id, b.accion, b.entidad, b.entidad_id, b.detalle, b.ip, b.creado_en,
                u.correo AS correo_usuario, u.nombre AS nombre_usuario
         FROM bitacora_admin b
         LEFT JOIN usuarios u ON u.id = b.usuario_id' . $donde . '
         ORDER BY b.id DESC',
        $parametros,
        min($limite, 200),
        $desde
    );
}

/** Cuántas entradas hay con esos filtros (para la paginación). */
function contar_bitacora(array $filtros = []): int
{
    [$donde, $parametros] = filtros_de_bitacora($filtros);

    $sentencia = bd()->prepare(
        'SELECT COUNT(*) FROM bitacora_admin b' . $donde
    );
    $sentencia->execute($parametros);

    return (int) $sentencia->fetchColumn();
}

/** Las acciones que realmente aparecen en la bitácora, para el filtro. */
function acciones_registradas(): array
{
    $acciones = [];
    foreach (consultar_todas(
        'SELECT DISTINCT accion FROM bitacora_admin ORDER BY accion ASC'
    ) as $fila) {
        $acciones[] = $fila['accion'];
    }
    return $acciones;
}
