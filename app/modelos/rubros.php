<?php
/**
 * RUBROS (OFICIOS)
 * -----------------------------------------------------------------
 * Están en la base y no en un archivo de configuración para que la
 * institución pueda agregar un oficio sin tocar el FTP.
 *
 * Por ahora solo se leen: la pantalla para crearlos y editarlos no se
 * construyó todavía (los 16 iniciales cubren el trabajo que se ofrece
 * en los programas de trabajo temporal). Queda anotado como pendiente.
 */

/** Todos los rubros activos, en el orden en que se muestran. */
function listar_rubros(bool $solo_activos = true): array
{
    static $cache = [];
    $clave = $solo_activos ? 'activos' : 'todos';

    if (isset($cache[$clave])) {
        return $cache[$clave];
    }

    $sql = 'SELECT id, codigo, nombre, activo FROM rubros';
    if ($solo_activos) {
        $sql .= ' WHERE activo = 1';
    }
    $sql .= ' ORDER BY orden ASC, nombre ASC';

    $cache[$clave] = consultar_todas($sql);
    return $cache[$clave];
}

/** El nombre de un rubro a partir de su id, para mostrarlo en pantalla. */
function nombre_de_rubro(?int $rubro_id): string
{
    if ($rubro_id === null) {
        return 'Sin oficio';
    }
    foreach (listar_rubros(false) as $rubro) {
        if ((int) $rubro['id'] === $rubro_id) {
            return $rubro['nombre'];
        }
    }
    return 'Sin oficio';
}

/** ¿Existe y está activo? Se usa para validar lo que llega del formulario. */
function rubro_valido(int $rubro_id): bool
{
    foreach (listar_rubros() as $rubro) {
        if ((int) $rubro['id'] === $rubro_id) {
            return true;
        }
    }
    return false;
}

/** El id de un rubro a partir de su código. Lo usa la importación CSV. */
function id_de_rubro(string $codigo): ?int
{
    foreach (listar_rubros() as $rubro) {
        if ($rubro['codigo'] === $codigo) {
            return (int) $rubro['id'];
        }
    }
    return null;
}
