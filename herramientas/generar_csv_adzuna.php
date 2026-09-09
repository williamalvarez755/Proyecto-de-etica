<?php
/**
 * GENERADOR DE CSV DESDE ADZUNA
 * =================================================================
 * ESTE ARCHIVO NO SE SUBE AL SERVIDOR. Corre en TU COMPUTADORA.
 *
 * Por qué (decisión D-004): las conexiones salientes de InfinityFree
 * fallan seguido por DNS y restricciones del hosting. Una función que
 * dependa de que el servidor llame a una API externa no va a andar en
 * el servidor real. Entonces la llamada se hace acá, en local, y lo
 * que viaja al servidor es un archivo CSV que se sube por el panel.
 *
 * El día que el proyecto se mueva a un servidor propio, este mismo
 * script se puede automatizar sin reescribir una línea.
 *
 * -----------------------------------------------------------------
 * ANTES DE USARLO, LEÉ ESTO
 * -----------------------------------------------------------------
 *
 * 1. TÉRMINOS DE USO (regla 3). Adzuna ofrece una API pública para
 *    desarrolladores, pero sus condiciones pueden cambiar y tienen
 *    límites de uso. Entrá a https://developer.adzuna.com/, leé los
 *    términos y confirmá que el uso que le vas a dar está permitido.
 *    Este proyecto no scrapea sitios que lo prohíben, ni siquiera
 *    cuando técnicamente se pueda.
 *
 * 2. LO QUE ESTE SCRIPT GENERA ES UN BORRADOR, NO OFERTAS LISTAS.
 *    Dos datos NO vienen de la API y los tenés que revisar a mano:
 *
 *      - El OFICIO (rubro) se adivina por palabras del título. Se
 *        equivoca seguido.
 *      - La FECHA DE VENCIMIENTO no existe en Adzuna. El script pone
 *        una fecha estimada. Hay que corregirla con la que diga la
 *        publicación original.
 *
 *    Igual, nada de esto llega al público sin que una persona lo
 *    verifique: las ofertas importadas entran como 'pendiente' y para
 *    verificarlas hay que confirmar que los datos coinciden con la
 *    publicación original.
 *
 * -----------------------------------------------------------------
 * CÓMO SE USA
 * -----------------------------------------------------------------
 *
 *   php herramientas/generar_csv_adzuna.php --pais=ca --buscar="farm worker" --vence=2026-12-31
 *
 * Opciones:
 *   --pais     Código del país en Adzuna: ca, us, mx, es... (obligatorio)
 *   --buscar   Qué buscar (obligatorio)
 *   --vence    Fecha de vencimiento AAAA-MM-DD (recomendado)
 *   --cuantos  Cuántos resultados traer, hasta 50 (por defecto 20)
 *   --salida   Nombre del archivo a generar
 *
 * Las credenciales van en herramientas/adzuna_credenciales.php, que
 * NO se sube al repositorio. Copiá el bloque de abajo en ese archivo.
 * =================================================================
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script se corre desde la terminal de tu computadora, no desde el navegador.\n");
}

// -----------------------------------------------------------------
//  Credenciales
// -----------------------------------------------------------------
$ruta_credenciales = __DIR__ . '/adzuna_credenciales.php';

if (!is_file($ruta_credenciales)) {
    exit(
        "Falta el archivo de credenciales.\n\n"
        . "Creá  herramientas/adzuna_credenciales.php  con este contenido:\n\n"
        . "<?php\n"
        . "define('ADZUNA_APP_ID',  'tu-app-id');\n"
        . "define('ADZUNA_APP_KEY', 'tu-app-key');\n\n"
        . "Se sacan registrándose en https://developer.adzuna.com/\n"
        . "Ese archivo no se sube al repositorio ni al servidor.\n"
    );
}
require $ruta_credenciales;

// -----------------------------------------------------------------
//  Opciones de la línea de comandos
// -----------------------------------------------------------------
$opciones = getopt('', ['pais:', 'buscar:', 'vence::', 'cuantos::', 'salida::']);

$pais    = $opciones['pais']   ?? '';
$buscar  = $opciones['buscar'] ?? '';
$cuantos = (int) ($opciones['cuantos'] ?? 20);
$salida  = $opciones['salida'] ?? __DIR__ . '/ofertas_' . date('Y-m-d_His') . '.csv';

if ($pais === '' || $buscar === '') {
    exit("Faltan datos.\n\n  php generar_csv_adzuna.php --pais=ca --buscar=\"farm worker\" --vence=2026-12-31\n");
}

$cuantos = max(1, min($cuantos, 50));

// Si no se dio fecha de vencimiento, se estima. Se avisa fuerte.
$vence = $opciones['vence'] ?? '';
if ($vence === '') {
    $vence = date('Y-m-d', strtotime('+45 days'));
    echo "AVISO: no diste --vence. Se puso una fecha ESTIMADA ($vence).\n";
    echo "       Corregila en el CSV con la fecha real de cada oferta antes de subirlo.\n\n";
}

// Códigos de país de Adzuna traducidos a los nuestros.
$paises_nuestros = [
    'ca' => 'ca', 'us' => 'us', 'mx' => 'mx', 'es' => 'es',
];
$pais_nuestro = $paises_nuestros[$pais] ?? 'otro';

/**
 * Adivina el oficio por palabras del título.
 * Se equivoca: por eso el CSV es un borrador que una persona revisa.
 */
function adivinar_rubro(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');

    $pistas = [
        'agricultura'            => ['farm', 'harvest', 'agricultur', 'cosecha', 'campo', 'fruit', 'picker'],
        'construccion'           => ['construction', 'mason', 'albañil', 'obra', 'builder', 'concrete'],
        'carpinteria'            => ['carpenter', 'carpinter', 'woodwork'],
        'electricidad'           => ['electric', 'electricista'],
        'mecanica'               => ['mechanic', 'mecánic', 'mecanic', 'automotive'],
        'manufactura'            => ['factory', 'manufactur', 'production', 'maquila'],
        'empaque_bodega'         => ['warehouse', 'packag', 'packer', 'bodega', 'empaque'],
        'hoteleria_restaurantes' => ['hotel', 'restaurant', 'kitchen', 'cocina', 'server', 'waiter', 'cook'],
        'alimentos'              => ['bakery', 'panader', 'food process'],
        'limpieza'               => ['clean', 'janitor', 'limpieza', 'housekeep'],
        'jardineria'             => ['landscap', 'garden', 'jardin'],
        'cuidado_personas'       => ['caregiver', 'nurse', 'cuidado', 'elderly'],
        'transporte'             => ['driver', 'truck', 'transport', 'piloto', 'chofer'],
        'comercio'               => ['sales', 'retail', 'cashier', 'ventas'],
        'seguridad'              => ['security', 'guard', 'seguridad'],
    ];

    foreach ($pistas as $rubro => $palabras) {
        foreach ($palabras as $palabra) {
            if (str_contains($texto, $palabra)) {
                return $rubro;
            }
        }
    }
    return 'otros';
}

// -----------------------------------------------------------------
//  Llamada a la API
// -----------------------------------------------------------------
$url = sprintf(
    'https://api.adzuna.com/v1/api/jobs/%s/search/1?app_id=%s&app_key=%s&results_per_page=%d&what=%s&content-type=application/json',
    rawurlencode($pais),
    rawurlencode(ADZUNA_APP_ID),
    rawurlencode(ADZUNA_APP_KEY),
    $cuantos,
    rawurlencode($buscar)
);

echo "Consultando Adzuna...\n";

$contexto = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true]]);
$respuesta = @file_get_contents($url, false, $contexto);

if ($respuesta === false) {
    exit("No se pudo conectar con Adzuna. Revisá tu conexión a internet.\n");
}

$datos = json_decode($respuesta, true);

if (!is_array($datos) || !isset($datos['results'])) {
    echo "Adzuna respondió algo que no se pudo leer. Respuesta:\n";
    exit(substr($respuesta, 0, 500) . "\n");
}

if ($datos['results'] === []) {
    exit("La búsqueda no devolvió ninguna oferta. Probá con otras palabras.\n");
}

// -----------------------------------------------------------------
//  Generar el CSV con las columnas que espera el panel
// -----------------------------------------------------------------
$columnas = [
    'titulo', 'empleador', 'descripcion', 'pais_codigo', 'ciudad', 'rubro_codigo',
    'requisitos', 'experiencia_anios_min', 'estudios_min', 'disponibilidad_requerida',
    'salario_texto', 'url_original', 'fecha_publicacion', 'fecha_vencimiento', 'idiomas',
];

$archivo = fopen($salida, 'w');
fputcsv($archivo, $columnas);

$escritas  = 0;
$saltadas  = 0;

foreach ($datos['results'] as $oferta) {
    $titulo      = trim((string) ($oferta['title'] ?? ''));
    $empleador   = trim((string) ($oferta['company']['display_name'] ?? ''));
    $descripcion = trim((string) ($oferta['description'] ?? ''));

    // El importador exige estos tres. Si faltan, no tiene sentido
    // escribir la fila para que después la rechace.
    if (mb_strlen($titulo) < 5 || mb_strlen($empleador) < 2 || mb_strlen($descripcion) < 20) {
        $saltadas++;
        continue;
    }

    $publicada = isset($oferta['created'])
        ? date('Y-m-d', strtotime($oferta['created']))
        : date('Y-m-d');

    $salario = '';
    if (!empty($oferta['salary_min']) && !empty($oferta['salary_max'])) {
        $salario = 'Entre ' . round((float) $oferta['salary_min'])
                 . ' y ' . round((float) $oferta['salary_max']) . ' al año, según la publicación';
    }

    fputcsv($archivo, [
        mb_substr($titulo, 0, 200),
        mb_substr($empleador, 0, 150),
        mb_substr($descripcion, 0, 4000),
        $pais_nuestro,
        mb_substr(trim((string) ($oferta['location']['display_name'] ?? '')), 0, 100),
        adivinar_rubro($titulo . ' ' . $descripcion),
        '',
        '0',
        'ninguno',
        'a_convenir',
        $salario,
        (string) ($oferta['redirect_url'] ?? ''),
        $publicada,
        $vence,
        '',
    ]);

    $escritas++;
}

fclose($archivo);

echo "\nListo: $escritas ofertas escritas";
if ($saltadas > 0) {
    echo " ($saltadas se saltaron por venir incompletas de la API)";
}
echo "\nArchivo: $salida\n\n";
echo "ANTES DE SUBIRLO, revisá a mano:\n";
echo "  1. El oficio (rubro_codigo): se adivinó por el título y se equivoca.\n";
echo "  2. La fecha de vencimiento: no viene de Adzuna.\n";
echo "  3. Que la oferta no pida dinero ni documentos por adelantado.\n\n";
echo "Después subilo en el panel, en Importar ofertas. Van a entrar como pendientes.\n";
