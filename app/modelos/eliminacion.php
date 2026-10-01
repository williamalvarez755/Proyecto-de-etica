<?php
/**
 * ELIMINAR UNA CUENTA DE VERDAD
 * =================================================================
 * La regla 4 dice: "borrar la cuenta debe borrar de verdad: registros
 * en base de datos y archivo de currículum del disco".
 *
 * Muchos sistemas marcan la cuenta como "eliminada" y dejan todo
 * adentro. Acá no: si alguien pide que borremos sus datos, se borran.
 * En un país sin ley de protección de datos, esto es de lo poco que
 * podemos garantizarle a una persona migrante, y si no lo cumplimos
 * de verdad no cumplimos nada.
 *
 * QUÉ SE BORRA (todo lo que la identifica):
 *   - Su fila en usuarios, y con ella en cascada: perfil, oficios,
 *     idiomas, países, ofertas guardadas, postulaciones,
 *     consentimientos y códigos de restablecimiento.
 *   - TODOS sus archivos de currículum del disco, incluidos los que
 *     ya había compartido con alguna oferta.
 *
 * QUÉ SE CONSERVA, y por qué:
 *   - Los reportes que hizo, SIN su identidad (usuario_id queda en
 *     NULL). El aviso de que una oferta puede ser una estafa le sirve
 *     a la institución para proteger a otras personas, y ya no apunta
 *     a nadie.
 *   - La bitácora, también sin su identidad. Es el registro de qué
 *     hizo el personal administrativo, no de la persona.
 *
 * Sobre borrar los archivos ya compartidos: contradice a propósito la
 * decisión D-031, que los conserva cuando la persona solo REEMPLAZA
 * su currículum. Al eliminar la cuenta manda la regla 4. Que un
 * empleador se quede sin un archivo que ya podía ver es un costo
 * menor que incumplirle a alguien el borrado de sus datos.
 */

/**
 * Todos los archivos de currículum que pertenecen a esta persona:
 * el que tiene en su perfil ahora y los que compartió antes.
 */
function archivos_de_cv_de(int $usuario_id): array
{
    $archivos = [];

    $perfil = buscar_perfil($usuario_id);
    if ($perfil !== null && $perfil['cv_archivo'] !== null) {
        $archivos[] = $perfil['cv_archivo'];
    }

    foreach (consultar_todas(
        'SELECT DISTINCT cv_archivo FROM consentimientos WHERE usuario_id = ?',
        [$usuario_id]
    ) as $fila) {
        if ($fila['cv_archivo'] !== null && $fila['cv_archivo'] !== 'perfil') {
            $archivos[] = $fila['cv_archivo'];
        }
    }

    return array_unique($archivos);
}


/**
 * Borra la cuenta y todo lo suyo. Devuelve cuántos archivos borró.
 *
 * El orden importa: PRIMERO se juntan los nombres de los archivos,
 * porque una vez borrada la fila de la base ya no habría manera de
 * saber qué archivos eran suyos y quedarían para siempre en el disco.
 */
function eliminar_cuenta(int $usuario_id): int
{
    // 1. Se anotan los archivos ANTES de borrar nada de la base.
    $archivos = archivos_de_cv_de($usuario_id);

    // 2. Se borra la base, todo junto o nada.
    //
    //    Las postulaciones se borran PRIMERO y a mano. En el esquema
    //    original, postulaciones.consentimiento_id no tenía ON DELETE
    //    CASCADE: al borrar la cuenta, MySQL intentaba borrar los
    //    consentimientos con la postulación todavía apuntándolos, y
    //    rechazaba todo. Resultado: quien se había postulado a algo NO
    //    podía borrar su cuenta (regla 4). Se corrigió también el
    //    esquema (sql/migracion_001.sql), pero esto no depende de que
    //    la migración se haya corrido en el servidor.
    //
    //    Después, la fila de usuarios. Las llaves foráneas hacen el
    //    resto: perfil, oficios, idiomas, países, guardadas,
    //    consentimientos y restablecimientos se van en cascada;
    //    reportes y bitácora quedan sin identidad.
    $conexion = bd();
    $conexion->beginTransaction();
    try {
        consultar('DELETE FROM postulaciones WHERE usuario_id = ?', [$usuario_id]);
        consultar('DELETE FROM usuarios WHERE id = ?', [$usuario_id]);
        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollBack();
        throw $e;
    }

    // 3. Recién ahora, los archivos del disco.
    $borrados = 0;
    foreach ($archivos as $archivo) {
        if (ruta_de_cv($archivo) !== null) {
            borrar_cv($archivo);
            $borrados++;
        }
    }

    return $borrados;
}


/**
 * Un resumen de lo que se va a borrar, para enseñárselo a la persona
 * ANTES de que confirme.
 *
 * Que alguien vea en números qué está a punto de perder es parte de
 * que la decisión sea informada. "¿Seguro?" no alcanza.
 */
function resumen_de_lo_que_se_borra(int $usuario_id): array
{
    return [
        'tiene_perfil'   => buscar_perfil($usuario_id) !== null,
        'archivos'       => count(archivos_de_cv_de($usuario_id)),
        'postulaciones'  => (int) consultar_valor(
            'SELECT COUNT(*) FROM postulaciones WHERE usuario_id = ?', [$usuario_id]
        ),
        'guardadas'      => (int) consultar_valor(
            'SELECT COUNT(*) FROM ofertas_guardadas WHERE usuario_id = ?', [$usuario_id]
        ),
        'reportes'       => (int) consultar_valor(
            'SELECT COUNT(*) FROM reportes WHERE usuario_id = ?', [$usuario_id]
        ),
    ];
}


// =================================================================
//  LIMPIEZA PERIÓDICA
//
//  El hosting no tiene tareas programadas (D-003), así que esto se
//  dispara desde el panel, a mano.
//
//  Se eligió el botón manual y NO ejecutarlo solo cuando entra un
//  administrador. Dos razones:
//
//   1. Un borrado que ocurre sin que nadie lo pida es un borrado que
//      nadie revisó. Si algún día la limpieza tiene un error, con el
//      botón hay una persona que apretó y una entrada en la bitácora
//      con su nombre; automático, no hay a quién preguntarle.
//
//   2. Colgarle trabajo pesado al inicio de sesión hace lento
//      justo el momento en que alguien está entrando a trabajar.
//
//  Para que no se olvide, el panel avisa cuando hay algo que limpiar.
// =================================================================

/** Cuánta basura hay acumulada, para que el panel pueda avisar. */
function pendientes_de_limpieza(): array
{
    return [
        'intentos' => (int) consultar_valor(
            'SELECT COUNT(*) FROM intentos_acceso WHERE creado_en < ?',
            [ahora_menos_dias(RETENCION_INTENTOS_DIAS)]
        ),
        'bitacora' => (int) consultar_valor(
            'SELECT COUNT(*) FROM bitacora_admin WHERE creado_en < ?',
            [ahora_menos_dias(RETENCION_BITACORA_DIAS)]
        ),
        'restablecimientos' => (int) consultar_valor(
            'SELECT COUNT(*) FROM restablecimientos WHERE creado_en < ? AND (usado_en IS NOT NULL OR expira_en < ?)',
            [ahora_menos_dias(RETENCION_INTENTOS_DIAS), ahora()]
        ),
    ];
}

/**
 * Borra los códigos de restablecimiento viejos ya usados o vencidos.
 * No sirven para nada y son hashes guardados de más.
 */
function limpiar_restablecimientos_viejos(): int
{
    $sentencia = consultar(
        'DELETE FROM restablecimientos
         WHERE creado_en < ? AND (usado_en IS NOT NULL OR expira_en < ?)',
        [ahora_menos_dias(RETENCION_INTENTOS_DIAS), ahora()]
    );
    return $sentencia->rowCount();
}

/**
 * Borra entradas de bitácora más viejas que la retención definida.
 *
 * Ojo: la bitácora se conserva dos años por algo. Esta limpieza está
 * para que la base no crezca sin límite en un hosting con espacio
 * contado, no para hacer desaparecer registros incómodos: por eso el
 * plazo está en la configuración y no se puede acortar desde la
 * interfaz.
 */
function limpiar_bitacora_vieja(): int
{
    $sentencia = consultar(
        'DELETE FROM bitacora_admin WHERE creado_en < ?',
        [ahora_menos_dias(RETENCION_BITACORA_DIAS)]
    );
    return $sentencia->rowCount();
}


/**
 * ESTADO DEL SISTEMA  (regla 11)
 * -----------------------------------------------------------------
 * Comprueba las cosas que pueden estar mal sin que nadie se entere, y
 * las muestra en el panel en palabras.
 *
 * La regla 11 dice que cuando algo falla, el administrador tiene que
 * recibir un aviso claro y el usuario final no tiene que ver un error
 * técnico. Esta función es la mitad "aviso claro al administrador".
 */
function revisar_estado_del_sistema(): array
{
    $revisiones = [];

    // ¿Se puede guardar un currículum?
    $carpeta_ok = is_dir(RUTA_CV) && is_writable(RUTA_CV);
    $revisiones[] = [
        'nombre' => 'Carpeta de currículums',
        'bien'   => $carpeta_ok,
        'texto'  => $carpeta_ok
            ? 'Se pueden guardar currículums.'
            : 'NO se puede escribir en la carpeta de currículums. Nadie puede subir su CV.',
    ];

    // ¿Se pueden registrar los errores?
    $logs_ok = is_dir(RUTA_LOGS) && is_writable(RUTA_LOGS);
    $revisiones[] = [
        'nombre' => 'Registro de errores',
        'bien'   => $logs_ok,
        'texto'  => $logs_ok
            ? 'Los errores quedan registrados para poder revisarlos.'
            : 'No se puede escribir el registro de errores. Si algo falla, no vamos a saberlo.',
    ];

    // ¿Se pueden leer los PDF?
    $pdf_ok = is_file(RAIZ_APP . '/../vendor/autoload.php');
    $revisiones[] = [
        'nombre' => 'Lectura de currículums en PDF',
        'bien'   => $pdf_ok,
        'texto'  => $pdf_ok
            ? 'Los PDF se pueden leer automáticamente.'
            : 'Falta la carpeta vendor. Los PDF se guardan igual, pero la persona tiene que '
            . 'escribir sus datos a mano. No es urgente, pero conviene resolverlo.',
    ];

    // ¿Se pueden leer los .docx?
    $zip_ok = class_exists('ZipArchive');
    $revisiones[] = [
        'nombre' => 'Lectura de currículums en Word',
        'bien'   => $zip_ok,
        'texto'  => $zip_ok
            ? 'Los archivos .docx se pueden leer.'
            : 'Falta la extensión zip de PHP. Los .docx no se pueden leer.',
    ];

    // ¿Hay reclutadores cargados? Sin ellos, el verificador no sirve.
    $registro = contar_reclutadores();
    $revisiones[] = [
        'nombre' => 'Registro de reclutadores autorizados',
        'bien'   => $registro > 0,
        'texto'  => $registro > 0
            ? $registro . ' reclutadores cargados. El verificador puede contestar.'
            : 'No hay ningún reclutador cargado. El verificador no le sirve a nadie todavía: '
            . 'es la función más importante de la plataforma.',
    ];

    // ¿Hay ofertas que el público pueda ver? Con la misma condición del
    // sitio: una "publicada" vencida no la ve nadie.
    $publicadas = contar_ofertas_publicas([]);
    $revisiones[] = [
        'nombre' => 'Ofertas publicadas',
        'bien'   => $publicadas > 0,
        'texto'  => $publicadas > 0
            ? $publicadas . ' ofertas visibles en el sitio.'
            : 'No hay ninguna oferta publicada. El buscador está vacío para quien entre.',
    ];

    return $revisiones;
}
