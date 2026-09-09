<?php
/**
 * PERFIL LABORAL
 * -----------------------------------------------------------------
 * Lo que la persona sabe hacer. Es lo único que después entra al
 * emparejamiento.
 *
 * Los campos son exactamente los que permite la regla 7 y ninguno más:
 * oficios, años de experiencia, estudios, idiomas, países a los que
 * quiere ir y disponibilidad.
 *
 * NO existen, ni como columna ni como formulario: edad, sexo,
 * departamento de origen, idioma materno ni apellido. No es que no se
 * muestren: no se recolectan, para que la regla se pueda comprobar
 * leyendo el esquema.
 */

function buscar_perfil(int $usuario_id): ?array
{
    return consultar_una('SELECT * FROM perfiles WHERE usuario_id = ?', [$usuario_id]);
}

/** ¿Ya revisó y confirmó sus datos? Hasta que no lo haga, no hay emparejamiento. */
function perfil_confirmado(int $usuario_id): bool
{
    $perfil = buscar_perfil($usuario_id);
    return $perfil !== null && $perfil['confirmado_en'] !== null;
}

/** Crea la fila del perfil si todavía no existe. */
function asegurar_perfil(int $usuario_id): void
{
    if (buscar_perfil($usuario_id) !== null) {
        return;
    }
    consultar(
        'INSERT INTO perfiles (usuario_id, anios_experiencia, actualizado_en) VALUES (?, 0, ?)',
        [$usuario_id, ahora()]
    );
}

/**
 * Guarda los datos que la persona confirmó.
 * A partir de acá el perfil queda marcado como confirmado (regla 9).
 */
function guardar_perfil(int $usuario_id, array $datos): void
{
    asegurar_perfil($usuario_id);

    consultar(
        'UPDATE perfiles
         SET anios_experiencia = ?, nivel_estudios = ?, disponibilidad = ?,
             disponible_desde = ?, confirmado_en = ?, actualizado_en = ?
         WHERE usuario_id = ?',
        [
            $datos['anios_experiencia'],
            $datos['nivel_estudios'] ?: null,
            $datos['disponibilidad'] ?: null,
            $datos['disponible_desde'] ?: null,
            ahora(),
            ahora(),
            $usuario_id,
        ]
    );
}

/** Deja anotado el archivo de currículum que subió. */
function guardar_cv_en_perfil(int $usuario_id, string $archivo, string $extension, int $tamano, string $origen): void
{
    asegurar_perfil($usuario_id);

    consultar(
        'UPDATE perfiles
         SET cv_archivo = ?, cv_extension = ?, cv_tamano = ?, cv_origen = ?,
             cv_subido_en = ?, actualizado_en = ?
         WHERE usuario_id = ?',
        [$archivo, $extension, $tamano, $origen, ahora(), ahora(), $usuario_id]
    );
}

/** Quita el currículum del perfil (el archivo se borra aparte, con borrar_cv). */
function quitar_cv_del_perfil(int $usuario_id): void
{
    consultar(
        'UPDATE perfiles
         SET cv_archivo = NULL, cv_extension = NULL, cv_tamano = NULL,
             cv_origen = NULL, cv_subido_en = NULL, actualizado_en = ?
         WHERE usuario_id = ?',
        [ahora(), $usuario_id]
    );
}


// -----------------------------------------------------------------
//  Las listas del perfil
//  Se guardan borrando y volviendo a escribir: son pocas filas y así
//  el código es directo y no quedan restos de una edición anterior.
// -----------------------------------------------------------------

function rubros_de_perfil(int $usuario_id): array
{
    $ids = [];
    foreach (consultar_todas('SELECT rubro_id FROM perfiles_rubros WHERE usuario_id = ?', [$usuario_id]) as $fila) {
        $ids[] = (int) $fila['rubro_id'];
    }
    return $ids;
}

function guardar_rubros_de_perfil(int $usuario_id, array $rubro_ids): void
{
    consultar('DELETE FROM perfiles_rubros WHERE usuario_id = ?', [$usuario_id]);

    foreach (array_unique($rubro_ids) as $rubro_id) {
        if (!rubro_valido((int) $rubro_id)) {
            continue;
        }
        consultar(
            'INSERT INTO perfiles_rubros (usuario_id, rubro_id) VALUES (?, ?)',
            [$usuario_id, (int) $rubro_id]
        );
    }
}

function idiomas_de_perfil(int $usuario_id): array
{
    $codigos = [];
    foreach (consultar_todas('SELECT idioma_codigo FROM perfiles_idiomas WHERE usuario_id = ?', [$usuario_id]) as $fila) {
        $codigos[] = $fila['idioma_codigo'];
    }
    return $codigos;
}

function guardar_idiomas_de_perfil(int $usuario_id, array $codigos): void
{
    consultar('DELETE FROM perfiles_idiomas WHERE usuario_id = ?', [$usuario_id]);

    foreach (array_unique($codigos) as $codigo) {
        if (!en_catalogo($codigo, IDIOMAS)) {
            continue;
        }
        consultar(
            'INSERT INTO perfiles_idiomas (usuario_id, idioma_codigo) VALUES (?, ?)',
            [$usuario_id, $codigo]
        );
    }
}

/** Los países a los que la persona está dispuesta a ir (decisión D-008). */
function paises_de_perfil(int $usuario_id): array
{
    $codigos = [];
    foreach (consultar_todas('SELECT pais_codigo FROM perfiles_paises WHERE usuario_id = ?', [$usuario_id]) as $fila) {
        $codigos[] = $fila['pais_codigo'];
    }
    return $codigos;
}

function guardar_paises_de_perfil(int $usuario_id, array $codigos): void
{
    consultar('DELETE FROM perfiles_paises WHERE usuario_id = ?', [$usuario_id]);

    foreach (array_unique($codigos) as $codigo) {
        if (!en_catalogo($codigo, PAISES)) {
            continue;
        }
        consultar(
            'INSERT INTO perfiles_paises (usuario_id, pais_codigo) VALUES (?, ?)',
            [$usuario_id, $codigo]
        );
    }
}
