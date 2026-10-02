<?php
/**
 * POSTULACIONES Y CONSENTIMIENTOS
 * =================================================================
 * Acá vive la regla 6: el consentimiento es por oferta y no se
 * reutiliza nunca.
 *
 * Cómo se hace cumplir, y por qué así:
 *
 *  - El consentimiento se crea PRIMERO y la postulación apunta a él.
 *    La columna postulaciones.consentimiento_id es NOT NULL, así que
 *    a nivel de base de datos NO EXISTE una postulación sin su propio
 *    consentimiento. No depende de que el programador se acuerde.
 *
 *  - Cada consentimiento guarda a qué oferta corresponde, qué se
 *    compartió exactamente y en qué momento. No hay ninguna consulta
 *    en todo el proyecto que busque "un consentimiento de este
 *    usuario" sin decir de qué oferta.
 *
 *  - Se guarda también la versión del texto que la persona aceptó.
 *    Si mañana cambiamos la redacción, lo que aceptó ayer sigue
 *    diciendo lo que decía ayer. Cambiar el texto no reescribe la
 *    historia.
 */

/** ¿Ya se postuló a esta oferta? */
function ya_se_postulo(int $usuario_id, int $oferta_id): bool
{
    return consultar_valor(
        'SELECT 1 FROM postulaciones WHERE usuario_id = ? AND oferta_id = ?',
        [$usuario_id, $oferta_id]
    ) !== null;
}

/** Cuántas veces se postuló hoy (para el límite de uso). */
function contar_postulaciones_del_dia(int $usuario_id): int
{
    return (int) consultar_valor(
        'SELECT COUNT(*) FROM postulaciones WHERE usuario_id = ? AND creado_en >= ?',
        [$usuario_id, ahora_menos_dias(1)]
    );
}


/**
 * Registra el consentimiento y crea la postulación.
 *
 * Las dos cosas van juntas o no va ninguna: si algo falla en el medio,
 * se deshace todo. Una postulación sin consentimiento sería compartir
 * el currículum de alguien sin permiso, y un consentimiento sin
 * postulación sería un permiso guardado que nadie pidió.
 *
 * @param string $que_se_comparte  El nombre del archivo de currículum,
 *                                 o 'perfil' si la persona no tiene
 *                                 archivo y comparte sus datos.
 */
function crear_postulacion(int $usuario_id, int $oferta_id, string $que_se_comparte): int
{
    $conexion = bd();
    $conexion->beginTransaction();

    try {
        consultar(
            'INSERT INTO consentimientos (usuario_id, oferta_id, texto_version, cv_archivo, otorgado_en)
             VALUES (?, ?, ?, ?, ?)',
            [$usuario_id, $oferta_id, CONSENTIMIENTO_VERSION, $que_se_comparte, ahora()]
        );

        $consentimiento_id = (int) $conexion->lastInsertId();

        consultar(
            'INSERT INTO postulaciones (usuario_id, oferta_id, consentimiento_id, estado, creado_en)
             VALUES (?, ?, ?, ?, ?)',
            [$usuario_id, $oferta_id, $consentimiento_id, 'enviada', ahora()]
        );

        $postulacion_id = (int) $conexion->lastInsertId();

        $conexion->commit();

        return $postulacion_id;

    } catch (Throwable $e) {
        $conexion->rollBack();
        throw $e;
    }
}


/**
 * Las postulaciones de una persona, con la oferta y la fecha en que
 * autorizó compartir su currículum.
 *
 * Se usa LEFT JOIN con las ofertas porque una oferta puede haberse
 * retirado: la persona igual tiene derecho a saber que autorizó
 * compartir su currículum con ella y cuándo.
 */
function listar_postulaciones_de(int $usuario_id): array
{
    return consultar_todas(
        'SELECT p.id, p.estado, p.creado_en, p.actualizado_en,
                c.otorgado_en, c.cv_archivo, c.texto_version,
                o.id AS oferta_id, o.titulo, o.empleador, o.pais_codigo, o.estado AS oferta_estado,
                f.nombre AS fuente_nombre,
                rec.nombre AS reclutador_nombre
         FROM postulaciones p
         INNER JOIN consentimientos c ON c.id = p.consentimiento_id
         INNER JOIN ofertas o ON o.id = p.oferta_id
         INNER JOIN fuentes f ON f.id = o.fuente_id
         LEFT JOIN reclutadores_autorizados rec ON rec.id = o.reclutador_id
         WHERE p.usuario_id = ?
         ORDER BY p.id DESC',
        [$usuario_id]
    );
}

function buscar_postulacion(int $id): ?array
{
    return consultar_una(
        'SELECT p.*, c.otorgado_en, c.cv_archivo, c.texto_version
         FROM postulaciones p
         INNER JOIN consentimientos c ON c.id = p.consentimiento_id
         WHERE p.id = ?',
        [$id]
    );
}

/**
 * Retirar una postulación.
 *
 * No borra nada: marca el estado. El consentimiento que se dio queda
 * registrado igual, porque es cierto que se dio, y la persona tiene
 * que poder consultarlo después.
 */
function retirar_postulacion(int $postulacion_id, int $usuario_id): bool
{
    $sentencia = consultar(
        'UPDATE postulaciones SET estado = ?, actualizado_en = ?
         WHERE id = ? AND usuario_id = ? AND estado = ?',
        ['retirada', ahora(), $postulacion_id, $usuario_id, 'enviada']
    );

    return $sentencia->rowCount() > 0;
}


// =================================================================
//  Del lado del panel
// =================================================================

/** Quiénes se postularon a una oferta. */
function listar_postulaciones_de_oferta(int $oferta_id): array
{
    return consultar_todas(
        'SELECT p.id, p.estado, p.creado_en, p.actualizado_en,
                c.otorgado_en, c.cv_archivo,
                u.id AS usuario_id, u.nombre, u.correo,
                pe.anios_experiencia, pe.nivel_estudios, pe.disponibilidad, pe.cv_extension
         FROM postulaciones p
         INNER JOIN consentimientos c ON c.id = p.consentimiento_id
         INNER JOIN usuarios u ON u.id = p.usuario_id
         LEFT JOIN perfiles pe ON pe.usuario_id = u.id
         WHERE p.oferta_id = ?
         ORDER BY p.id DESC',
        [$oferta_id]
    );
}

/** Cuántas postulaciones tiene cada oferta, para el listado del panel. */
function contar_postulaciones_por_oferta(): array
{
    $conteo = [];
    foreach (consultar_todas(
        'SELECT oferta_id, COUNT(*) AS total FROM postulaciones GROUP BY oferta_id'
    ) as $fila) {
        $conteo[(int) $fila['oferta_id']] = (int) $fila['total'];
    }
    return $conteo;
}

/**
 * ¿Este archivo de currículum está comprometido con alguna oferta?
 *
 * Sirve para un caso concreto: si la persona sube un currículum nuevo,
 * el anterior normalmente se borra. Pero si ya lo había compartido con
 * una oferta, borrarlo dejaría el registro mintiendo: el
 * consentimiento diría "se compartió este archivo" y el archivo no
 * existiría. Entonces ese se conserva y se borran solo los que nadie
 * autorizó compartir con nadie.
 */
function archivo_esta_en_algun_consentimiento(?string $archivo): bool
{
    if ($archivo === null || $archivo === '' || $archivo === 'perfil') {
        return false;
    }
    return consultar_valor(
        'SELECT 1 FROM consentimientos WHERE cv_archivo = ?',
        [$archivo]
    ) !== null;
}


/**
 * Comprueba que exista un consentimiento vigente de esa persona PARA
 * ESA OFERTA antes de dejar que el panel vea su currículum.
 *
 * Esta función es la que hace que la regla 6 signifique algo del lado
 * administrativo: no alcanza con que la persona haya subido un
 * currículum, ni con que se haya postulado a otra oferta. Tiene que
 * haber autorizado compartirlo con esta.
 */
function hay_consentimiento_para(int $usuario_id, int $oferta_id): bool
{
    return consultar_valor(
        'SELECT 1 FROM consentimientos WHERE usuario_id = ? AND oferta_id = ?',
        [$usuario_id, $oferta_id]
    ) !== null;
}
