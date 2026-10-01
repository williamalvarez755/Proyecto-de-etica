<?php
/**
 * PANEL — DESCARGAR EL CURRÍCULUM DE UNA POSTULACIÓN
 * -----------------------------------------------------------------
 * El único camino por el que alguien del panel puede llegar a un
 * currículum. Tiene cuatro cerrojos, y los cuatro se comprueban acá
 * en el servidor:
 *
 *   1. Ser cuenta administrativa y tener el permiso cv.descargar.
 *   2. Que la postulación exista.
 *   3. Que exista un consentimiento de esa persona PARA ESA OFERTA.
 *      Este es el que hace valer la regla 6: no alcanza con que la
 *      persona tenga currículum subido ni con que se haya postulado
 *      a otra cosa.
 *   4. Que la persona NO haya retirado la postulación. Retirarla
 *      revoca el permiso desde ese momento: lo que ya se descargó no
 *      se puede deshacer, pero no se vuelve a entregar.
 *   5. Que el archivo que se entrega sea EXACTAMENTE el que dice el
 *      consentimiento, no el que la persona tenga hoy. Si cambió su
 *      currículum después, lo que se autorizó compartir fue el otro.
 *
 * Y antes de entregar nada, queda registrado en la bitácora quién lo
 * descargó, de quién y para qué oferta.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('cv.descargar');

$postulacion_id = id_valido(parametro('postulacion'));
$postulacion    = $postulacion_id === null ? null : buscar_postulacion($postulacion_id);

if ($postulacion === null) {
    abortar(404, 'Esa postulación no existe', 'Puede que el enlace esté incompleto.');
}

// El consentimiento tiene que ser para esta oferta. No hay otra forma
// de llegar acá, pero se comprueba igual: la regla 5 dice que se
// verifica en el servidor aunque la interfaz ya lo impidiera.
if (!hay_consentimiento_para((int) $postulacion['usuario_id'], (int) $postulacion['oferta_id'])) {
    registrar_accion(
        'acceso_denegado',
        'postulacion',
        $postulacion_id,
        'Intento de descargar un currículum sin consentimiento para esa oferta'
    );
    abortar(
        403,
        'No hay autorización para ver ese currículum',
        'Esa persona no autorizó compartir su currículum con esta oferta.'
    );
}

// Un consentimiento que la persona retiró ya no autoriza nada nuevo.
// Antes esto no se miraba: el panel seguía mostrando el botón y el
// archivo se entregaba igual después de que la persona se retiraba.
if ($postulacion['estado'] === 'retirada') {
    registrar_accion(
        'acceso_denegado',
        'postulacion',
        $postulacion_id,
        'Intento de descargar el currículum de una postulación retirada'
    );
    abortar(
        403,
        'Esta persona retiró su postulación',
        'Desde que la retiró, su currículum ya no se puede descargar para esta oferta.'
    );
}

$archivo = (string) $postulacion['cv_archivo'];

if ($archivo === 'perfil') {
    abortar(
        404,
        'Esta persona no compartió un archivo',
        'Autorizó compartir los datos de su perfil, que se ven en la lista de postulaciones. '
        . 'No subió ningún archivo de currículum.'
    );
}

$ruta = ruta_de_cv($archivo);

if ($ruta === null) {
    registrar_error(
        'Falta en el disco un currículum consentido: ' . $archivo,
        __FILE__,
        __LINE__
    );
    abortar(
        404,
        'No encontramos el archivo',
        'El archivo que se autorizó compartir ya no está disponible en el sistema.'
    );
}

// Se registra ANTES de entregar: si el registro fallara, preferimos no
// entregar el archivo a entregarlo sin dejar huella.
registrar_accion(
    'cv_descargado',
    'postulacion',
    $postulacion_id,
    'Currículum de la persona ' . (int) $postulacion['usuario_id']
    . ' para la oferta ' . (int) $postulacion['oferta_id']
);

entregar_cv($ruta, 'curriculum-postulacion-' . $postulacion_id . '.' . pathinfo($ruta, PATHINFO_EXTENSION));
