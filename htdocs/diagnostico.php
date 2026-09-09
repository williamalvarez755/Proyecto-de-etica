<?php
/**
 * DIAGNÓSTICO DEL SERVIDOR
 * -----------------------------------------------------------------
 * ESTE ARCHIVO SE SUBE, SE MIRA UNA VEZ Y SE BORRA POR FTP.
 *
 * No forma parte del sistema. Sirve para contestar, antes de construir
 * nada encima, las cuatro preguntas que pueden cambiar el diseño:
 *
 *   1. ¿Qué versión de PHP y de MySQL hay de verdad en el servidor?
 *   2. ¿Están las extensiones que necesitamos?
 *   3. ¿Cuánto pesa el archivo más grande que se puede subir?
 *   4. ¿Puede PHP leer y escribir FUERA de htdocs?  <-- la importante
 *
 * La cuarta decide dónde se guardan los currículums. Si la respuesta
 * fuera "no", habría que cambiar el manejo de archivos completo, y es
 * mucho mejor saberlo hoy que en la Fase 3.
 *
 * No muestra contraseñas ni datos de nadie.
 */

// app/ vive DENTRO de htdocs porque el hosting no deja a PHP salir de
// esta carpeta (ver decisión D-016 en CLAUDE.md). Está protegida por
// .htaccess, y esta página comprueba que esa protección funcione.
$raiz_app = __DIR__ . '/app';

// -----------------------------------------------------------------
//  Prueba de escritura fuera de htdocs
// -----------------------------------------------------------------
$carpeta_prueba = $raiz_app . '/almacen/logs';
$archivo_prueba = $carpeta_prueba . '/prueba-escritura.txt';

$prueba = ['existe' => false, 'escribe' => false, 'lee' => false, 'borra' => false];

$prueba['existe'] = is_dir($carpeta_prueba);

if ($prueba['existe']) {
    $prueba['escribe'] = @file_put_contents($archivo_prueba, 'prueba ' . date('c')) !== false;
    if ($prueba['escribe']) {
        $prueba['lee']   = @file_get_contents($archivo_prueba) !== false;
        $prueba['borra'] = @unlink($archivo_prueba);
    }
}

// -----------------------------------------------------------------
//  Versión de MySQL (solo si ya existe config.php)
// -----------------------------------------------------------------
$version_mysql = 'no se pudo consultar (todavía no existe app/config/config.php)';
$ruta_config   = $raiz_app . '/config/config.php';

if (is_file($ruta_config)) {
    require $ruta_config;
    try {
        $pdo = new PDO(
            'mysql:host=' . BD_SERVIDOR . ';dbname=' . BD_NOMBRE . ';charset=utf8mb4',
            BD_USUARIO,
            BD_CLAVE,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $version_mysql = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    } catch (Throwable $e) {
        $version_mysql = 'ERROR al conectar: ' . $e->getMessage();
    }
}

// -----------------------------------------------------------------
//  Extensiones que el proyecto necesita
// -----------------------------------------------------------------
$extensiones = [
    'pdo_mysql' => 'Conexión a la base de datos. Sin esto no funciona nada.',
    'mbstring'  => 'Manejo correcto de tildes y ñ.',
    'fileinfo'  => 'Comprobar el tipo REAL de un archivo subido (Fase 3).',
    'zip'       => 'Leer currículums .docx (Fase 3).',
    'openssl'   => 'Generar códigos y tokens aleatorios seguros.',
    'session'   => 'Sesiones de usuario.',
];

function fila(string $etiqueta, string $valor, ?bool $bien = null): void
{
    $marca = $bien === null ? '' : ($bien ? ' ✔' : ' ✘');
    $clase = $bien === null ? '' : ($bien ? 'bien' : 'mal');
    echo '<tr><th>' . htmlspecialchars($etiqueta) . '</th>';
    echo '<td class="' . $clase . '">' . htmlspecialchars($valor) . $marca . '</td></tr>';
}

$php_bien = version_compare(PHP_VERSION, '8.0.0', '>=');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico del servidor</title>
<link rel="stylesheet" href="/recursos/estilo.css">
</head>
<body>
<main class="contenido">
<div class="contenedor pila">

  <h1 class="titulo-pagina">Diagnóstico del servidor</h1>

  <p class="aviso aviso--aviso">
    <strong>Borrá este archivo por FTP en cuanto termines de leerlo.</strong>
    No es parte de la plataforma y le cuenta a cualquiera qué versiones corre el servidor.
  </p>

  <div class="tabla-desliza">
  <table class="tabla">
    <tbody>
      <?php
      fila('Versión de PHP', PHP_VERSION, $php_bien);
      fila('Versión de MySQL / MariaDB', $version_mysql);
      fila('Tamaño máximo de subida', ini_get('upload_max_filesize'));
      fila('Tamaño máximo del formulario', ini_get('post_max_size'));
      fila('Memoria disponible', ini_get('memory_limit'));
      fila('Tiempo máximo de ejecución', ini_get('max_execution_time') . ' s');
      fila('open_basedir', ini_get('open_basedir') ?: 'sin restricción');
      fila('¿PHP ve la conexión como HTTPS?', (!empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'sí' : 'no');
      ?>
    </tbody>
  </table>
  </div>

  <h2 class="subtitulo">Extensiones</h2>
  <div class="tabla-desliza">
  <table class="tabla">
    <tbody>
      <?php foreach ($extensiones as $nombre => $para_que): ?>
        <?php fila($nombre . ' — ' . $para_que, extension_loaded($nombre) ? 'disponible' : 'NO DISPONIBLE', extension_loaded($nombre)); ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <h2 class="subtitulo">Guardado de currículums</h2>
  <p class="texto-guia">
    La carpeta está dentro de htdocs porque este hosting no deja a PHP salir de ahí, y por eso
    la protege un archivo .htaccess. Más abajo se comprueba que esa protección esté puesta.
  </p>
  <div class="tabla-desliza">
  <table class="tabla">
    <tbody>
      <?php
      fila('La carpeta app/almacen/logs existe', $prueba['existe'] ? 'sí' : 'no', $prueba['existe']);
      fila('PHP puede escribir ahí',            $prueba['escribe'] ? 'sí' : 'no', $prueba['escribe']);
      fila('PHP puede leer lo que escribió',    $prueba['lee'] ? 'sí' : 'no', $prueba['lee']);
      fila('PHP puede borrar el archivo',       $prueba['borra'] ? 'sí' : 'no', $prueba['borra']);
      ?>
    </tbody>
  </table>
  </div>

  <?php
  // La protección de los currículums depende de estos dos .htaccess.
  // Si faltan, los archivos quedarían descargables por dirección web.
  $htaccess_app     = is_file($raiz_app . '/.htaccess');
  $htaccess_almacen = is_file($raiz_app . '/almacen/.htaccess');
  ?>

  <h2 class="subtitulo">Protección de la carpeta</h2>
  <div class="tabla-desliza">
  <table class="tabla">
    <tbody>
      <?php
      fila('Existe app/.htaccess',         $htaccess_app ? 'sí' : 'NO', $htaccess_app);
      fila('Existe app/almacen/.htaccess', $htaccess_almacen ? 'sí' : 'NO', $htaccess_almacen);
      ?>
    </tbody>
  </table>
  </div>

  <?php if ($prueba['escribe'] && $prueba['lee'] && $prueba['borra'] && $htaccess_app && $htaccess_almacen): ?>
    <p class="aviso aviso--exito">
      Todo bien: los currículums se pueden guardar y la carpeta está protegida.
    </p>
    <p class="aviso aviso--aviso">
      <strong>Falta una comprobación que no se puede hacer desde acá.</strong>
      Abrí en el navegador la dirección <em>/app/config/config.php</em> de este sitio.
      Tiene que dar error 403 o 404. Si te muestra texto o te descarga algo, la protección
      no está funcionando y hay que resolverlo antes de que nadie suba un currículum.
    </p>
  <?php elseif (!$htaccess_app || !$htaccess_almacen): ?>
    <p class="aviso aviso--error">
      <strong>Falta un archivo .htaccess de protección.</strong>
      Sin él, los currículums de las personas se podrían descargar escribiendo una dirección.
      Subilo antes de seguir.
    </p>
  <?php else: ?>
    <p class="aviso aviso--error">
      No se puede escribir en la carpeta de currículums. Nadie va a poder subir su CV.
      Copiá esta pantalla y avisá.
    </p>
  <?php endif; ?>

</div>
</main>
</body>
</html>
