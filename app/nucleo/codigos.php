<?php
/**
 * CÓDIGOS ALEATORIOS
 * -----------------------------------------------------------------
 * Un solo lugar para generar contraseñas temporales y códigos de
 * restablecimiento.
 *
 * Se usa random_int() y no rand(): random_int usa el generador
 * criptográfico del sistema operativo. Con rand() los valores son
 * predecibles, y un código de restablecimiento predecible es una
 * llave maestra regalada.
 */

/**
 * Alfabeto sin caracteres que se confunden al leerlos o dictarlos:
 * sin O ni 0, sin I ni l ni 1.
 *
 * Importa porque estos códigos se van a dictar por teléfono o copiar
 * de una pantalla a otra, muchas veces por alguien apurado.
 */
const ALFABETO_CODIGOS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

function generar_codigo(int $largo): string
{
    $largo  = max(6, min($largo, 64));
    $maximo = strlen(ALFABETO_CODIGOS) - 1;
    $codigo = '';

    for ($i = 0; $i < $largo; $i++) {
        $codigo .= ALFABETO_CODIGOS[random_int(0, $maximo)];
    }

    return $codigo;
}

/**
 * Contraseña temporal para una cuenta administrativa nueva o para un
 * restablecimiento asistido. Se muestra UNA sola vez en pantalla y
 * nunca se guarda en texto plano en ningún lado.
 *
 * Se parte en bloques de cuatro para poder leerla sin perderse.
 */
function generar_contrasena_temporal(): string
{
    return generar_codigo(4) . '-' . generar_codigo(4) . '-' . generar_codigo(4);
}
