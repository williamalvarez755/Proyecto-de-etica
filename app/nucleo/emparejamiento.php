<?php
/**
 * EMPAREJAMIENTO ENTRE UN PERFIL Y UNA OFERTA
 * =================================================================
 * Acá viven las reglas 7 y 8, y este archivo está escrito para que se
 * puedan comprobar leyéndolo.
 *
 * REGLA 7 — no puede discriminar.
 * Fijate en la firma de evaluar_coincidencia(): recibe un arreglo con
 * SEIS claves y ninguna más. No recibe el usuario completo, así que no
 * hay forma de que use la edad, el sexo, el apellido, el idioma
 * materno ni el departamento de origen aunque alguien quisiera. Esos
 * datos, además, no existen en la base.
 *
 * Los únicos criterios permitidos, y los únicos que se usan:
 *   oficio/rubro · años de experiencia · estudios · idiomas ·
 *   ubicación (a dónde quiere ir) · disponibilidad
 *
 * REGLA 8 — tiene que ser explicable.
 * La función devuelve razones y advertencias EN PALABRAS. El puntaje
 * existe solo para ordenar la lista y NUNCA se muestra en pantalla:
 * un "87 % de coincidencia" no le dice nada a nadie y además da una
 * falsa sensación de precisión. Lo que la persona necesita para
 * decidir es "coincide porque sabés albañilería y pide 3 años, y vos
 * tenés 5", más "pero pide inglés y tu perfil no lo indica".
 */

/**
 * Cuánto pesa cada criterio al ordenar.
 * Están acá arriba y con nombre para que se vea de un vistazo qué se
 * está premiando. El oficio pesa más que todo lo demás junto, porque
 * es lo que realmente determina si alguien puede hacer ese trabajo.
 */
const PESO_OFICIO         = 50;
const PESO_EXPERIENCIA    = 20;
const PESO_ESTUDIOS       = 10;
const PESO_IDIOMAS        = 10;
const PESO_DISPONIBILIDAD = 10;

/** Qué tan pronto puede empezar cada opción. Menor es más pronto. */
const ORDEN_DISPONIBILIDAD = [
    'inmediata'  => 0,
    'un_mes'     => 1,
    'tres_meses' => 2,
    'a_convenir' => 0,   // flexible: nunca es un impedimento
];


/**
 * Compara un perfil con una oferta.
 *
 * @param array $perfil  SOLO estas seis claves:
 *                       rubros (ids), anios, estudios, idiomas,
 *                       paises, disponibilidad
 * @param array $oferta  La fila de la oferta
 * @param array $idiomas_oferta  Códigos de idioma que pide la oferta
 *
 * @return array ['sirve' => bool, 'puntaje' => int,
 *                'razones' => string[], 'advertencias' => string[]]
 */
function evaluar_coincidencia(array $perfil, array $oferta, array $idiomas_oferta): array
{
    $puntaje      = 0;
    $razones      = [];
    $advertencias = [];

    // -------------------------------------------------------------
    //  1. Oficio
    //  Es la condición de fondo: si la persona no sabe hacer ese
    //  trabajo, no tiene sentido recomendárselo por más que todo lo
    //  demás coincida.
    // -------------------------------------------------------------
    $oficio_coincide = in_array((int) $oferta['rubro_id'], array_map('intval', $perfil['rubros']), true);

    if (!$oficio_coincide) {
        return ['sirve' => false, 'puntaje' => 0, 'razones' => [], 'advertencias' => []];
    }

    $puntaje  += PESO_OFICIO;
    $razones[] = 'Sabés ' . mb_strtolower(nombre_de_rubro((int) $oferta['rubro_id']), 'UTF-8');

    // -------------------------------------------------------------
    //  2. Ubicación: dónde dijo que podría trabajar
    //     (el país del trabajo; de la persona no se guarda de dónde es)
    // -------------------------------------------------------------
    if (!in_array($oferta['pais_codigo'], $perfil['paises'], true)) {
        return ['sirve' => false, 'puntaje' => 0, 'razones' => [], 'advertencias' => []];
    }

    $razones[] = $oferta['pais_codigo'] === 'gt'
        ? 'Es en Guatemala, donde dijiste que podés trabajar'
        : 'Dijiste que irías a ' . (PAISES[$oferta['pais_codigo']] ?? $oferta['pais_codigo']);

    // -------------------------------------------------------------
    //  3. Experiencia
    // -------------------------------------------------------------
    $pide_anios = (int) $oferta['experiencia_anios_min'];
    $tiene      = (int) $perfil['anios'];

    if ($pide_anios === 0) {
        $puntaje  += PESO_EXPERIENCIA;
        $razones[] = 'No piden experiencia previa';
    } elseif ($tiene >= $pide_anios) {
        $puntaje  += PESO_EXPERIENCIA;
        $razones[] = 'Piden ' . $pide_anios . ' año' . ($pide_anios === 1 ? '' : 's')
                   . ' de experiencia y vos tenés ' . $tiene;
    } else {
        // No se descarta: se avisa. La persona decide si igual quiere
        // intentarlo, que muchas veces vale la pena.
        $advertencias[] = 'Piden ' . $pide_anios . ' año' . ($pide_anios === 1 ? '' : 's')
                        . ' de experiencia y tu perfil dice ' . $tiene;
    }

    // -------------------------------------------------------------
    //  4. Estudios
    // -------------------------------------------------------------
    $pide_estudios = ORDEN_ESTUDIOS[$oferta['estudios_min']] ?? 0;
    $tiene_estudios = ORDEN_ESTUDIOS[$perfil['estudios']] ?? 0;

    if ($pide_estudios === 0) {
        $puntaje += PESO_ESTUDIOS;
    } elseif ($tiene_estudios >= $pide_estudios) {
        $puntaje  += PESO_ESTUDIOS;
        $razones[] = 'Tus estudios alcanzan lo que piden ('
                   . mb_strtolower(NIVELES_ESTUDIO[$oferta['estudios_min']] ?? '', 'UTF-8') . ')';
    } else {
        $advertencias[] = 'Piden ' . mb_strtolower(NIVELES_ESTUDIO[$oferta['estudios_min']] ?? '', 'UTF-8')
                        . ' y tu perfil dice '
                        . mb_strtolower(NIVELES_ESTUDIO[$perfil['estudios']] ?? 'sin especificar', 'UTF-8');
    }

    // -------------------------------------------------------------
    //  5. Idiomas
    // -------------------------------------------------------------
    if ($idiomas_oferta === []) {
        $puntaje += PESO_IDIOMAS;
    } else {
        $faltan = [];
        foreach ($idiomas_oferta as $codigo) {
            if (!in_array($codigo, $perfil['idiomas'], true)) {
                $faltan[] = IDIOMAS[$codigo] ?? $codigo;
            }
        }

        if ($faltan === []) {
            $puntaje  += PESO_IDIOMAS;
            $nombres = [];
            foreach ($idiomas_oferta as $codigo) {
                $nombres[] = IDIOMAS[$codigo] ?? $codigo;
            }
            $razones[] = 'Hablás el idioma que piden (' . implode(', ', $nombres) . ')';
        } else {
            $advertencias[] = count($faltan) === 1
                ? 'Piden ' . $faltan[0] . ' y tu perfil no indica ese idioma'
                : 'Piden estos idiomas que tu perfil no indica: ' . implode(', ', $faltan);
        }
    }

    // -------------------------------------------------------------
    //  6. Disponibilidad
    // -------------------------------------------------------------
    $pide_cuando  = ORDEN_DISPONIBILIDAD[$oferta['disponibilidad_requerida']] ?? 0;
    $puede_cuando = ORDEN_DISPONIBILIDAD[$perfil['disponibilidad']] ?? 0;

    if ($puede_cuando <= $pide_cuando) {
        $puntaje  += PESO_DISPONIBILIDAD;
        $razones[] = 'Podrías empezar cuando lo necesitan';
    } else {
        $advertencias[] = 'Necesitan a alguien que pueda empezar '
                        . mb_strtolower(DISPONIBILIDAD[$oferta['disponibilidad_requerida']] ?? '', 'UTF-8')
                        . ', y vos dijiste "'
                        . mb_strtolower(DISPONIBILIDAD[$perfil['disponibilidad']] ?? '', 'UTF-8') . '"';
    }

    return [
        'sirve'        => true,
        'puntaje'      => $puntaje,
        'razones'      => $razones,
        'advertencias' => $advertencias,
    ];
}


/**
 * Arma el arreglo de seis claves que recibe el emparejamiento.
 *
 * Está separado a propósito: es el único lugar donde se decide qué
 * datos de la persona entran al algoritmo, y se ve de un vistazo que
 * son exactamente los seis que permite la regla 7.
 */
function datos_para_emparejar(int $usuario_id): ?array
{
    $perfil = buscar_perfil($usuario_id);

    if ($perfil === null || $perfil['confirmado_en'] === null) {
        return null;
    }

    return [
        'rubros'         => rubros_de_perfil($usuario_id),
        'anios'          => (int) $perfil['anios_experiencia'],
        'estudios'       => (string) $perfil['nivel_estudios'],
        'idiomas'        => idiomas_de_perfil($usuario_id),
        'paises'         => paises_de_perfil($usuario_id),
        'disponibilidad' => (string) $perfil['disponibilidad'],
    ];
}


/**
 * Las ofertas que le sirven a esta persona, ordenadas de la que más
 * coincide a la que menos.
 *
 * Devuelve cada oferta con sus razones y sus advertencias ya armadas.
 */
function ofertas_recomendadas(int $usuario_id): array
{
    $perfil = datos_para_emparejar($usuario_id);

    if ($perfil === null || $perfil['rubros'] === [] || $perfil['paises'] === []) {
        return [];
    }

    // Se traen solo las ofertas que podrían servir (mismo oficio y
    // país que le interesa), en vez de traerlas todas y descartarlas
    // en PHP. En un hosting con tope de peticiones y una base
    // compartida, eso importa.
    $candidatas = ofertas_para_perfil($perfil['rubros'], $perfil['paises']);

    $resultado = [];

    foreach ($candidatas as $oferta) {
        $idiomas = idiomas_de_oferta((int) $oferta['id']);
        $evaluacion = evaluar_coincidencia($perfil, $oferta, $idiomas);

        if (!$evaluacion['sirve']) {
            continue;
        }

        $oferta['razones']      = $evaluacion['razones'];
        $oferta['advertencias'] = $evaluacion['advertencias'];
        $oferta['puntaje']      = $evaluacion['puntaje'];

        $resultado[] = $oferta;
    }

    // De mayor a menor coincidencia. Si empatan, primero la más
    // reciente. El puntaje solo ordena: no se muestra nunca.
    usort($resultado, static function (array $a, array $b): int {
        if ($a['puntaje'] === $b['puntaje']) {
            return strcmp((string) $b['fecha_publicacion'], (string) $a['fecha_publicacion']);
        }
        return $b['puntaje'] <=> $a['puntaje'];
    });

    return $resultado;
}
