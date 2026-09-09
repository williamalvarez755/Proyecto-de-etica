<?php
/**
 * LECTURA DE CURRÍCULUMS
 * -----------------------------------------------------------------
 * Saca el texto del archivo y trata de entender qué sabe hacer la
 * persona.
 *
 * REGLA 9, y es la más importante de este archivo: lo que sale de acá
 * es una PROPUESTA. La persona la revisa y la corrige antes de que el
 * sistema le muestre una sola oferta. La extracción automática se
 * equivoca seguido —los currículums vienen de mil formas— y quien
 * tiene la última palabra sobre su propia experiencia es ella.
 *
 * Por eso nada de lo que se extrae se guarda hasta que la persona lo
 * confirma en pantalla.
 *
 * Sobre los formatos:
 *
 *  - .docx se lee con ZipArchive, que viene con PHP. Un .docx es un
 *    ZIP con XML adentro: se abre, se saca word/document.xml y se le
 *    quitan las etiquetas.
 *
 *  - .pdf necesita smalot/pdfparser, que se instala con Composer en la
 *    computadora local y se sube por FTP. Si no está instalado, el
 *    sistema NO se cae: le dice a la persona que escriba sus datos a
 *    mano y sigue funcionando igual (regla 11).
 */

/** Deja el texto en minúsculas y sin tildes, para poder buscar en él. */
function texto_comparable(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = str_replace(
        ['á','é','í','ó','ú','ü','ñ','à','è','ì','ò','ù','â','ê','î','ô','û'],
        ['a','e','i','o','u','u','n','a','e','i','o','u','a','e','i','o','u'],
        $texto
    );
    return preg_replace('/\s+/', ' ', $texto) ?? $texto;
}


/**
 * ¿Se puede leer este formato en este servidor ahora mismo?
 * El .docx siempre; el .pdf solo si pdfparser está instalado.
 */
function se_puede_leer(string $extension): bool
{
    if ($extension === 'docx') {
        return class_exists('ZipArchive');
    }
    if ($extension === 'pdf') {
        return is_file(RAIZ_APP . '/../vendor/autoload.php');
    }
    return false;
}


/** Saca el texto del archivo. Devuelve null si no se pudo. */
function leer_texto_de_cv(string $ruta, string $extension): ?string
{
    if ($extension === 'docx') {
        return leer_docx($ruta);
    }
    if ($extension === 'pdf') {
        return leer_pdf($ruta);
    }
    return null;
}


/**
 * Un .docx es un ZIP. Adentro, word/document.xml tiene el texto
 * rodeado de etiquetas XML. Se sacan las etiquetas y queda el texto.
 */
function leer_docx(string $ruta): ?string
{
    if (!class_exists('ZipArchive')) {
        return null;
    }

    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) {
        return null;
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if ($xml === false) {
        return null;
    }

    // Los saltos de párrafo y de línea se vuelven espacios, para que
    // dos palabras de renglones distintos no queden pegadas.
    $xml = str_replace(['</w:p>', '<w:br/>', '<w:tab/>'], ' ', $xml);
    $texto = strip_tags($xml);
    $texto = html_entity_decode($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');

    return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
}


/**
 * El PDF necesita la única dependencia externa del proyecto.
 * Si no está, se devuelve null y quien llama muestra el camino manual.
 */
function leer_pdf(string $ruta): ?string
{
    $autoload = RAIZ_APP . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        return null;
    }

    require_once $autoload;

    if (!class_exists('Smalot\PdfParser\Parser')) {
        return null;
    }

    try {
        $parser = new Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($ruta);
        $texto  = $pdf->getText();
    } catch (Throwable $e) {
        // Un PDF hecho de fotos escaneadas no tiene texto adentro, y
        // eso no es un error del sistema: es un caso normal.
        registrar_error('No se pudo leer un PDF: ' . $e->getMessage(), __FILE__, __LINE__);
        return null;
    }

    return trim(preg_replace('/\s+/', ' ', $texto) ?? '');
}


// =================================================================
//  Qué se busca en el texto
// =================================================================

/**
 * Palabras que delatan cada oficio.
 * Están en español y sin tildes, porque el texto se compara
 * normalizado. Es una lista honesta: acierta con currículums escritos
 * de forma corriente y falla con los raros. Por eso la persona
 * confirma.
 */
const PISTAS_RUBRO = [
    'agricultura'            => ['agricultura', 'agricola', 'cosecha', 'cultivo', 'campo', 'finca', 'cafe', 'milpa', 'siembra', 'jornalero'],
    'construccion'           => ['construccion', 'albanil', 'albanileria', 'obra', 'block', 'repello', 'mezcla', 'armador', 'fundicion'],
    'carpinteria'            => ['carpinteria', 'carpintero', 'madera', 'ebanisteria', 'mueble'],
    'electricidad'           => ['electricista', 'electrico', 'instalacion electrica', 'cableado'],
    'mecanica'               => ['mecanico', 'mecanica', 'automotriz', 'taller', 'motor', 'enderezado'],
    'manufactura'            => ['maquila', 'fabrica', 'produccion', 'ensamble', 'costura', 'operario'],
    'empaque_bodega'         => ['bodega', 'empaque', 'almacen', 'inventario', 'montacargas', 'carga y descarga'],
    'hoteleria_restaurantes' => ['restaurante', 'hotel', 'cocina', 'mesero', 'camarero', 'cocinero', 'bar'],
    'alimentos'              => ['panaderia', 'panadero', 'reposteria', 'tortilleria', 'procesamiento de alimentos'],
    'limpieza'               => ['limpieza', 'conserje', 'mantenimiento', 'aseo'],
    'jardineria'             => ['jardineria', 'jardinero', 'areas verdes', 'poda'],
    'cuidado_personas'       => ['cuidado de ninos', 'ninera', 'cuidadora', 'enfermeria', 'adulto mayor', 'cuidado de personas'],
    'transporte'             => ['piloto', 'chofer', 'conductor', 'licencia de conducir', 'transporte', 'camion', 'reparto'],
    'comercio'               => ['ventas', 'vendedor', 'cajero', 'atencion al cliente', 'tienda', 'comercio'],
    'seguridad'              => ['seguridad', 'guardia', 'vigilancia', 'agente de seguridad'],
];

/** Palabras que delatan el nivel de estudios, del más alto al más bajo. */
const PISTAS_ESTUDIOS = [
    'universitario' => ['universidad', 'universitario', 'licenciatura', 'ingenieria', 'profesorado'],
    'tecnico'       => ['tecnico', 'diplomado', 'intecap', 'carrera tecnica'],
    'diversificado' => ['diversificado', 'bachillerato', 'bachiller', 'perito', 'secretariado', 'magisterio'],
    'basicos'       => ['basicos', 'basico', 'tercero basico'],
    'primaria'      => ['primaria', 'sexto primaria'],
];

/** Palabras que delatan un idioma. */
const PISTAS_IDIOMA = [
    'espanol'   => ['espanol', 'castellano'],
    'ingles'    => ['ingles', 'english'],
    'frances'   => ['frances', 'french'],
    'kiche'     => ['kiche', "k'iche", 'quiche'],
    'qeqchi'    => ['qeqchi', "q'eqchi", 'kekchi'],
    'mam'       => ['idioma mam', 'habla mam', 'mam '],
    'kaqchikel' => ['kaqchikel', 'cakchiquel'],
];


/**
 * Lee el texto y propone qué entendió.
 *
 * Nada de esto se guarda: se muestra en la pantalla de confirmación
 * para que la persona lo corrija (regla 9).
 *
 * La ubicación NO se extrae, y es a propósito: en esta plataforma
 * "ubicación" significa a dónde quiere ir la persona (decisión D-008),
 * y eso un currículum no lo dice. Se le pregunta directamente.
 */
function extraer_datos_del_cv(string $texto): array
{
    $t = texto_comparable($texto);

    // --- Oficios --------------------------------------------------
    $rubros = [];
    foreach (PISTAS_RUBRO as $codigo => $palabras) {
        foreach ($palabras as $palabra) {
            if (str_contains($t, $palabra)) {
                $rubros[] = $codigo;
                break;
            }
        }
    }

    // --- Años de experiencia --------------------------------------
    // Se busca "N años" y se toma el número más alto que aparezca,
    // que suele ser el total de la trayectoria.
    $anios = 0;
    if (preg_match_all('/(\d{1,2})\s*(?:anos|ano)\b/', $t, $coincidencias)) {
        foreach ($coincidencias[1] as $numero) {
            $numero = (int) $numero;
            if ($numero <= 50 && $numero > $anios) {
                $anios = $numero;
            }
        }
    }

    // --- Estudios -------------------------------------------------
    // Se recorre de mayor a menor y se queda con el primero: si dice
    // universidad y primaria, lo que importa es el nivel más alto.
    $estudios = null;
    foreach (PISTAS_ESTUDIOS as $codigo => $palabras) {
        foreach ($palabras as $palabra) {
            if (str_contains($t, $palabra)) {
                $estudios = $codigo;
                break 2;
            }
        }
    }

    // --- Idiomas --------------------------------------------------
    $idiomas = [];
    foreach (PISTAS_IDIOMA as $codigo => $palabras) {
        foreach ($palabras as $palabra) {
            if (str_contains($t, $palabra)) {
                $idiomas[] = $codigo;
                break;
            }
        }
    }

    return [
        'rubros'            => array_slice($rubros, 0, 5),
        'anios_experiencia' => $anios,
        'nivel_estudios'    => $estudios,
        'idiomas'           => $idiomas,
    ];
}
