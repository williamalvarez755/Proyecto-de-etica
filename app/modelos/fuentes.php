<?php
/**
 * FUENTES DE LAS OFERTAS
 * -----------------------------------------------------------------
 * De dónde salió cada oferta. Es la base de la regla 1: ninguna oferta
 * se muestra sin fuente verificable, y la fuente se ve en pantalla.
 *
 * Una fuente desactivada no desaparece: las ofertas que ya entraron por
 * ella siguen mostrando de dónde vinieron. Solo deja de ofrecerse para
 * cargar ofertas nuevas.
 */

function listar_fuentes(bool $solo_activas = false): array
{
    $sql = 'SELECT id, nombre, tipo, url, descripcion, activa, notas, creado_en
            FROM fuentes';
    if ($solo_activas) {
        $sql .= ' WHERE activa = 1';
    }
    $sql .= ' ORDER BY activa DESC, nombre ASC';

    return consultar_todas($sql);
}

function buscar_fuente(int $id): ?array
{
    return consultar_una('SELECT * FROM fuentes WHERE id = ?', [$id]);
}

function existe_nombre_de_fuente(string $nombre, ?int $excepto_id = null): bool
{
    if ($excepto_id === null) {
        return consultar_valor('SELECT 1 FROM fuentes WHERE nombre = ?', [$nombre]) !== null;
    }
    return consultar_valor(
        'SELECT 1 FROM fuentes WHERE nombre = ? AND id <> ?',
        [$nombre, $excepto_id]
    ) !== null;
}

function crear_fuente(string $nombre, string $tipo, string $url, string $descripcion, string $notas): int
{
    consultar(
        'INSERT INTO fuentes (nombre, tipo, url, descripcion, activa, notas, creado_en)
         VALUES (?, ?, ?, ?, 1, ?, ?)',
        [$nombre, $tipo, $url ?: null, $descripcion ?: null, $notas ?: null, ahora()]
    );
    return (int) bd()->lastInsertId();
}

function actualizar_fuente(int $id, string $nombre, string $tipo, string $url, string $descripcion, string $notas): void
{
    consultar(
        'UPDATE fuentes
         SET nombre = ?, tipo = ?, url = ?, descripcion = ?, notas = ?, actualizado_en = ?
         WHERE id = ?',
        [$nombre, $tipo, $url ?: null, $descripcion ?: null, $notas ?: null, ahora(), $id]
    );
}

/**
 * Activar o desactivar. No se borra nunca: hay ofertas que apuntan acá
 * y tienen que poder seguir diciendo de dónde vinieron.
 */
function cambiar_estado_fuente(int $id, bool $activa): void
{
    consultar(
        'UPDATE fuentes SET activa = ?, actualizado_en = ? WHERE id = ?',
        [$activa ? 1 : 0, ahora(), $id]
    );
}

/** Cuántas ofertas entraron por esta fuente (para no desactivar a ciegas). */
function contar_ofertas_de_fuente(int $id): int
{
    return (int) consultar_valor('SELECT COUNT(*) FROM ofertas WHERE fuente_id = ?', [$id]);
}
