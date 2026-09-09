<?php
/**
 * REPORTES DE USUARIOS
 * =================================================================
 * Cuando alguien ve algo raro en una oferta, lo avisa desde acá.
 *
 * La regla más importante de este archivo, y está escrita en el
 * código y no solo en la documentación: **una oferta NUNCA se retira
 * automáticamente por acumular reportes**. No existe ninguna función
 * acá que cambie el estado de una oferta. La decisión es siempre de
 * un administrador, en la pantalla de ofertas.
 *
 * Si fuera automático, cualquiera podría tumbar ofertas legítimas
 * reportándolas en masa — con una cuenta por correo desechable, un
 * competidor o un reclutador irregular podría sacar del aire a los
 * que sí cumplen. El costo de revisar a mano es mucho menor que ese.
 *
 * Sobre la identidad de quien reporta: se guarda, porque el
 * administrador necesita poder distinguir un reporte serio de una
 * campaña, pero NO se muestra en ninguna pantalla pública.
 */

function crear_reporte(int $oferta_id, ?int $usuario_id, string $motivo, string $descripcion): int
{
    consultar(
        'INSERT INTO reportes (oferta_id, usuario_id, motivo, descripcion, estado, creado_en)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$oferta_id, $usuario_id, $motivo, $descripcion ?: null, 'pendiente', ahora()]
    );

    return (int) bd()->lastInsertId();
}

/** ¿Esta persona ya reportó esta oferta? Para no contar el mismo dos veces. */
function ya_reporto(int $usuario_id, int $oferta_id): bool
{
    return consultar_valor(
        'SELECT 1 FROM reportes WHERE usuario_id = ? AND oferta_id = ?',
        [$usuario_id, $oferta_id]
    ) !== null;
}

function contar_reportes_de_oferta(int $oferta_id): int
{
    return (int) consultar_valor(
        'SELECT COUNT(*) FROM reportes WHERE oferta_id = ?',
        [$oferta_id]
    );
}

/** Cuántos reportes hay en cada estado, para las pestañas del panel. */
function contar_reportes_por_estado(): array
{
    $conteo = [];
    foreach (consultar_todas('SELECT estado, COUNT(*) AS total FROM reportes GROUP BY estado') as $fila) {
        $conteo[$fila['estado']] = (int) $fila['total'];
    }
    return $conteo;
}

function listar_reportes(string $estado, int $limite, int $desde): array
{
    $sql = 'SELECT r.*, o.titulo AS oferta_titulo, o.empleador, o.estado AS oferta_estado,
                   u.nombre AS reporta_nombre, u.correo AS reporta_correo,
                   a.nombre AS revisor_nombre
            FROM reportes r
            INNER JOIN ofertas o ON o.id = r.oferta_id
            LEFT JOIN usuarios u ON u.id = r.usuario_id
            LEFT JOIN usuarios a ON a.id = r.revisado_por';

    $parametros = [];
    if ($estado !== '') {
        $sql .= ' WHERE r.estado = :estado';
        $parametros[':estado'] = $estado;
    }
    $sql .= ' ORDER BY r.id DESC';

    return consultar_paginado($sql, $parametros, $limite, $desde);
}

function contar_reportes(string $estado): int
{
    if ($estado === '') {
        return (int) consultar_valor('SELECT COUNT(*) FROM reportes');
    }
    return (int) consultar_valor('SELECT COUNT(*) FROM reportes WHERE estado = ?', [$estado]);
}

function buscar_reporte(int $id): ?array
{
    return consultar_una(
        'SELECT r.*, o.titulo AS oferta_titulo, o.estado AS oferta_estado
         FROM reportes r
         INNER JOIN ofertas o ON o.id = r.oferta_id
         WHERE r.id = ?',
        [$id]
    );
}

/**
 * Cambia el estado del reporte y deja anotado quién lo revisó.
 *
 * Ojo: esto cambia el estado del REPORTE, no el de la oferta. Retirar
 * o poner en revisión una oferta es otra acción, en otra pantalla, y
 * la decide una persona.
 */
function resolver_reporte(int $id, string $estado, string $resolucion, int $admin_id): void
{
    consultar(
        'UPDATE reportes SET estado = ?, resolucion = ?, revisado_por = ?, revisado_en = ?
         WHERE id = ?',
        [$estado, $resolucion ?: null, $admin_id, ahora(), $id]
    );
}

/** Cuántos reportes están esperando que alguien los mire. */
function reportes_pendientes(): int
{
    return (int) consultar_valor(
        'SELECT COUNT(*) FROM reportes WHERE estado IN (?, ?)',
        ['pendiente', 'en_revision']
    );
}

/**
 * Las ofertas publicadas que tienen reportes sin resolver, con cuántos.
 * Sirve para que el panel avise, sin que nada se mueva solo.
 */
function ofertas_con_reportes_pendientes(): array
{
    return consultar_todas(
        'SELECT o.id, o.titulo, o.empleador, o.estado, COUNT(r.id) AS cuantos
         FROM reportes r
         INNER JOIN ofertas o ON o.id = r.oferta_id
         WHERE r.estado IN (?, ?)
         GROUP BY o.id, o.titulo, o.empleador, o.estado
         ORDER BY cuantos DESC',
        ['pendiente', 'en_revision']
    );
}
