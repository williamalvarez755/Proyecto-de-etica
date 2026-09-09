<?php
/**
 * DESCARGAR MI PROPIO CURRÍCULUM
 * -----------------------------------------------------------------
 * Este es el ÚNICO camino que existe hacia un archivo de currículum.
 * No hay dirección web que llegue a la carpeta donde están guardados.
 *
 * Fijate que el archivo no se pide por su nombre: se busca el que
 * corresponde a la sesión que está abierta. Así no hay ningún número
 * ni nombre que alguien pueda cambiar en la dirección para pedir el
 * currículum de otra persona, que es el error clásico de estas
 * pantallas.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_rol_usuario();

$perfil = buscar_perfil((int) id_usuario_actual());

if ($perfil === null || $perfil['cv_archivo'] === null) {
    abortar(
        404,
        'Todavía no tenés un currículum guardado',
        'Subí tu currículum desde tu cuenta para poder descargarlo después.'
    );
}

$ruta = ruta_de_cv($perfil['cv_archivo']);

if ($ruta === null) {
    // La fila dice que hay archivo pero en el disco no está. Se
    // registra para poder revisarlo, y a la persona se le explica en
    // palabras normales qué hacer.
    registrar_error(
        'Falta en el disco un currículum que sí está en la base: ' . $perfil['cv_archivo'],
        __FILE__,
        __LINE__
    );
    abortar(
        404,
        'No encontramos tu archivo',
        'Hubo un problema con el archivo guardado. Volvé a subir tu currículum.'
    );
}

entregar_cv($ruta, 'mi-curriculum.' . ($perfil['cv_extension'] ?? 'pdf'));
