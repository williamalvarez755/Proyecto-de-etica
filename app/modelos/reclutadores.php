<?php
/**
 * RECLUTADORES AUTORIZADOS
 * -----------------------------------------------------------------
 * El registro público de reclutadores autorizados del Ministerio de
 * Trabajo, cargado a mano por la institución.
 *
 * Es el corazón del proyecto (decisión D-006). En la Fase 5 este mismo
 * registro alimenta el verificador público, donde cualquiera puede
 * escribir el nombre de quien lo contactó por WhatsApp y saber si
 * está autorizado o no.
 *
 * Por eso se guarda dos veces el nombre: como lo escribió la
 * institución, y "normalizado" (mayúsculas, sin tildes ni puntuación)
 * para poder encontrarlo aunque la persona lo escriba distinto.
 */

function listar_reclutadores(string $busqueda = ''): array
{
    if ($busqueda === '') {
        return consultar_todas(
            'SELECT * FROM reclutadores_autorizados ORDER BY nombre ASC'
        );
    }

    $normalizada = normalizar_nombre($busqueda);

    return consultar_todas(
        'SELECT DISTINCT r.*
         FROM reclutadores_autorizados r
         LEFT JOIN reclutadores_alias a ON a.reclutador_id = r.id
         WHERE r.nombre_normalizado LIKE ?
            OR a.alias_normalizado LIKE ?
            OR r.numero_registro = ?
         ORDER BY r.nombre ASC',
        ['%' . $normalizada . '%', '%' . $normalizada . '%', trim($busqueda)]
    );
}

/** Solo los que están vigentes, para elegir al cargar una oferta. */
function listar_reclutadores_vigentes(): array
{
    return consultar_todas(
        'SELECT id, nombre, numero_registro, vigencia_hasta
         FROM reclutadores_autorizados
         WHERE estado = ?
         ORDER BY nombre ASC',
        ['vigente']
    );
}

function buscar_reclutador(int $id): ?array
{
    return consultar_una('SELECT * FROM reclutadores_autorizados WHERE id = ?', [$id]);
}

function crear_reclutador(array $datos, int $admin_id): int
{
    consultar(
        'INSERT INTO reclutadores_autorizados
            (nombre, nombre_normalizado, numero_registro, estado, vigencia_desde,
             vigencia_hasta, fuente_registro, verificado_en, notas, creado_por, creado_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $datos['nombre'],
            normalizar_nombre($datos['nombre']),
            $datos['numero_registro'] ?: null,
            $datos['estado'],
            $datos['vigencia_desde'] ?: null,
            $datos['vigencia_hasta'] ?: null,
            $datos['fuente_registro'],
            ahora(),
            $datos['notas'] ?: null,
            $admin_id,
            ahora(),
        ]
    );
    return (int) bd()->lastInsertId();
}

function actualizar_reclutador(int $id, array $datos): void
{
    consultar(
        'UPDATE reclutadores_autorizados
         SET nombre = ?, nombre_normalizado = ?, numero_registro = ?, estado = ?,
             vigencia_desde = ?, vigencia_hasta = ?, fuente_registro = ?,
             verificado_en = ?, notas = ?, actualizado_en = ?
         WHERE id = ?',
        [
            $datos['nombre'],
            normalizar_nombre($datos['nombre']),
            $datos['numero_registro'] ?: null,
            $datos['estado'],
            $datos['vigencia_desde'] ?: null,
            $datos['vigencia_hasta'] ?: null,
            $datos['fuente_registro'],
            ahora(),
            $datos['notas'] ?: null,
            ahora(),
            $id,
        ]
    );
}

/**
 * ¿Está vigente hoy?
 *
 * No basta con que el estado diga "vigente": si la autorización se
 * venció ayer, ya no lo está. Se comprueban las dos cosas, porque el
 * estado lo escribe una persona y la fecha no miente.
 */
function reclutador_vigente_hoy(array $reclutador): bool
{
    if ($reclutador['estado'] !== 'vigente') {
        return false;
    }
    if ($reclutador['vigencia_hasta'] !== null && $reclutador['vigencia_hasta'] < hoy()) {
        return false;
    }
    return true;
}

// -----------------------------------------------------------------
//  Otros nombres con los que opera un mismo reclutador
// -----------------------------------------------------------------

function listar_alias(int $reclutador_id): array
{
    return consultar_todas(
        'SELECT id, alias FROM reclutadores_alias WHERE reclutador_id = ? ORDER BY alias ASC',
        [$reclutador_id]
    );
}

function agregar_alias(int $reclutador_id, string $alias): void
{
    consultar(
        'INSERT INTO reclutadores_alias (reclutador_id, alias, alias_normalizado, creado_en)
         VALUES (?, ?, ?, ?)',
        [$reclutador_id, $alias, normalizar_nombre($alias), ahora()]
    );
}

function borrar_alias(int $alias_id, int $reclutador_id): void
{
    consultar(
        'DELETE FROM reclutadores_alias WHERE id = ? AND reclutador_id = ?',
        [$alias_id, $reclutador_id]
    );
}

/**
 * BÚSQUEDA DEL VERIFICADOR PÚBLICO  (Fase 5)
 * -----------------------------------------------------------------
 * La persona escribe el nombre de quien la contactó por WhatsApp y
 * esto busca en el registro.
 *
 * Se devuelven por separado las coincidencias exactas y las parecidas,
 * porque significan cosas distintas y no se pueden mostrar igual:
 * una exacta permite decir "sí, está en el registro"; una parecida
 * solo permite decir "hay uno con un nombre parecido, fijate bien si
 * es el mismo". Confundir las dos sería peligroso en las dos
 * direcciones.
 *
 * Todo se resuelve contra nuestra propia base: ni una llamada externa,
 * así que funciona bien en este hosting y no depende de nadie.
 */
function buscar_en_registro(string $texto): array
{
    $normalizado = normalizar_nombre($texto);

    if (mb_strlen($normalizado) < 3) {
        return ['exactas' => [], 'parecidas' => []];
    }

    $exactas = consultar_todas(
        'SELECT DISTINCT r.*
         FROM reclutadores_autorizados r
         LEFT JOIN reclutadores_alias a ON a.reclutador_id = r.id
         WHERE r.nombre_normalizado = ? OR a.alias_normalizado = ?',
        [$normalizado, $normalizado]
    );

    $ids_exactas = [];
    foreach ($exactas as $uno) {
        $ids_exactas[] = (int) $uno['id'];
    }

    $parecidas = [];
    foreach (consultar_todas(
        'SELECT DISTINCT r.*
         FROM reclutadores_autorizados r
         LEFT JOIN reclutadores_alias a ON a.reclutador_id = r.id
         WHERE r.nombre_normalizado LIKE ? OR a.alias_normalizado LIKE ?
         ORDER BY r.nombre ASC',
        ['%' . $normalizado . '%', '%' . $normalizado . '%']
    ) as $uno) {
        if (!in_array((int) $uno['id'], $ids_exactas, true)) {
            $parecidas[] = $uno;
        }
    }

    return ['exactas' => $exactas, 'parecidas' => $parecidas];
}

/** Cuántos reclutadores hay cargados en el registro. */
function contar_reclutadores(): int
{
    return (int) consultar_valor('SELECT COUNT(*) FROM reclutadores_autorizados');
}

/**
 * Cuándo se actualizó el registro por última vez.
 *
 * Se muestra en el verificador a propósito: la persona tiene derecho a
 * saber si lo que le estamos diciendo se revisó la semana pasada o
 * hace ocho meses. Un dato viejo presentado como actual es una forma
 * de engañar aunque no se mienta.
 */
function fecha_actualizacion_registro(): ?string
{
    $fecha = consultar_valor(
        'SELECT MAX(fecha) FROM (
            SELECT MAX(creado_en) AS fecha FROM reclutadores_autorizados
            UNION ALL
            SELECT MAX(actualizado_en) AS fecha FROM reclutadores_autorizados
         ) AS fechas'
    );
    return $fecha === null ? null : (string) $fecha;
}

/** Cuántas ofertas están asociadas a este reclutador. */
function contar_ofertas_de_reclutador(int $id): int
{
    return (int) consultar_valor('SELECT COUNT(*) FROM ofertas WHERE reclutador_id = ?', [$id]);
}
