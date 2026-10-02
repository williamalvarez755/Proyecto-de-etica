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
 *   5. ¿Con qué dirección IP ve el servidor a cada visita?
 *
 * La cuarta decide dónde se guardan los currículums. Si la respuesta
 * fuera "no", habría que cambiar el manejo de archivos completo, y es
 * mucho mejor saberlo hoy que en la Fase 3.
 *
 * No muestra contraseñas ni datos de nadie.
 */

// app/ vive DENTRO de htdocs porque el hosting no deja a PHP salir de
// esta carpeta (ver decisiones D-016 y D-044 en CLAUDE.md). Está protegida por
// .htaccess, y esta página comprueba que esa protección funcione.
$raiz_app = __DIR__ . '/app';

// -----------------------------------------------------------------
//  Se crean las carpetas internas y su protección, si faltan.
//
//  Esta página no solo revisa: arregla. Los programas de FTP no suben
//  los archivos que empiezan con punto salvo que se les pida, así que
//  los .htaccess de protección casi nunca llegan en la primera subida,
//  y lo que queda sin proteger son currículums de personas. Es más
//  seguro que el sistema los escriba a que dependan de que alguien se
//  acuerde.
// -----------------------------------------------------------------
$htaccess_privado = "# Generado por el sistema. NO BORRAR.\n"
    . "# Esta carpeta guarda datos de personas y no se sirve por internet.\n"
    . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
    . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
    . "<FilesMatch \".*\">\n"
    . "    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n"
    . "    <IfModule !mod_authz_core.c>\n        Order allow,deny\n        Deny from all\n    </IfModule>\n"
    . "</FilesMatch>\n"
    . "Options -Indexes\n";

$carpetas_internas = [
    $raiz_app,
    $raiz_app . '/almacen',
    $raiz_app . '/almacen/cv',
    $raiz_app . '/almacen/logs',
    $raiz_app . '/almacen/respaldos',
];

$arreglos = [];

foreach ($carpetas_internas as $carpeta) {
    if (!is_dir($carpeta)) {
        if (@mkdir($carpeta, 0750, true)) {
            $arreglos[] = 'Se creó la carpeta ' . basename($carpeta);
        }
    }
}

// El .htaccess va en app/ y en app/almacen/: las dos que guardan algo
// que nadie debe poder pedir por dirección web.
foreach ([$raiz_app, $raiz_app . '/almacen'] as $carpeta) {
    if (is_dir($carpeta) && !is_file($carpeta . '/.htaccess')) {
        if (@file_put_contents($carpeta . '/.htaccess', $htaccess_privado) !== false) {
            $arreglos[] = 'Se escribió la protección .htaccess en ' . basename($carpeta);
        }
    }
}

// -----------------------------------------------------------------
//  Prueba de escritura
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

  <?php if ($arreglos !== []): ?>
    <div class="aviso aviso--exito">
      <p><strong>Se arreglaron cosas que faltaban:</strong></p>
      <ul>
        <?php foreach ($arreglos as $arreglo): ?>
          <li><?= htmlspecialchars($arreglo) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="texto-menor">
        Recargá esta página para comprobar que ahora esté todo en verde.
      </p>
    </div>
  <?php endif; ?>

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
      fila('IP con la que el servidor te ve (REMOTE_ADDR)', $_SERVER['REMOTE_ADDR'] ?? 'no llegó');
      fila('Cabecera X-Forwarded-For', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'no llegó');
      ?>
    </tbody>
  </table>
  </div>

  <div class="aviso aviso--aviso pila">
    <p><strong>Pregunta 5: la IP.</strong> Abrí esta página desde tu teléfono con datos móviles
    y desde otra conexión (la de tu casa, la universidad). Compará la fila
    <em>REMOTE_ADDR</em> con lo que te dice un buscador si escribís "cuál es mi IP".</p>
    <p>Si en las dos conexiones sale <strong>la misma</strong> dirección, o una que empieza con
    10., 172. o 192.168., el servidor está viendo la IP de un intermediario del hosting y no
    la de la persona. En ese caso los límites "por conexión" (crear cuentas, verificador)
    se los reparten <strong>todas las personas del sitio juntas</strong>: tres cuentas nuevas
    al día y el registro queda cerrado para todos. Hay que avisar antes de abrir el sitio.</p>
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

  <h2 class="subtitulo">¿Puede arrancar el sitio?</h2>
  <p class="texto-guia">
    Esto revisa por qué las páginas dan error 500.
  </p>

  <?php
  // Los tres archivos sin los cuales ninguna página puede cargar.
  $archivos_clave = [
      'app/nucleo/inicio.php'  => $raiz_app . '/nucleo/inicio.php',
      'app/config/config.php'  => $raiz_app . '/config/config.php',
      'app/config/limites.php' => $raiz_app . '/config/limites.php',
  ];

  // La ruta que index.php usa para buscar el sistema. Si el archivo no
  // se sobrescribió al subirlo, va a seguir buscando una carpeta arriba
  // y ninguna página va a cargar.
  $ruta_en_index = 'no se pudo leer index.php';
  $index = __DIR__ . '/index.php';
  if (is_file($index)) {
      $contenido = (string) file_get_contents($index);
      if (preg_match("/require\s+__DIR__\s*\.\s*'([^']+)'/", $contenido, $coincidencia)) {
          $ruta_en_index = $coincidencia[1];
      }
  }
  $index_correcto = $ruta_en_index === '/app/nucleo/inicio.php';
  ?>

  <div class="tabla-desliza">
  <table class="tabla">
    <tbody>
      <?php foreach ($archivos_clave as $nombre => $ruta): ?>
        <?php fila($nombre, is_file($ruta) ? 'está' : 'FALTA', is_file($ruta)); ?>
      <?php endforeach; ?>
      <?php fila('index.php busca el sistema en', $ruta_en_index, $index_correcto); ?>
    </tbody>
  </table>
  </div>

  <h3 class="subtitulo">Qué hay realmente en las carpetas</h3>
  <p class="texto-guia">
    Esto es lo que ve PHP en el servidor, sin intermediarios.
  </p>

  <?php
  /** Lista el contenido de una carpeta, o dice por qué no puede. */
  function listar_carpeta(string $ruta, string $etiqueta): void
  {
      echo '<p><strong>' . htmlspecialchars($etiqueta) . '</strong><br>';
      echo '<span class="texto-menor">' . htmlspecialchars($ruta) . '</span></p>';

      if (!is_dir($ruta)) {
          echo '<p class="aviso aviso--error">Esta carpeta NO EXISTE.</p>';
          return;
      }

      $cosas = @scandir($ruta);
      if ($cosas === false) {
          echo '<p class="aviso aviso--error">No se puede leer el contenido.</p>';
          return;
      }

      $cosas = array_diff($cosas, ['.', '..']);
      if ($cosas === []) {
          echo '<p class="aviso aviso--error">Está VACÍA.</p>';
          return;
      }

      echo '<ul>';
      foreach ($cosas as $cosa) {
          $tipo = is_dir($ruta . '/' . $cosa) ? 'carpeta' : 'archivo';
          echo '<li>' . htmlspecialchars($cosa) . ' <span class="texto-menor">(' . $tipo . ')</span></li>';
      }
      echo '</ul>';
  }

  listar_carpeta(__DIR__, 'La carpeta pública (htdocs)');
  listar_carpeta($raiz_app, 'La carpeta app');
  listar_carpeta($raiz_app . '/nucleo', 'La carpeta app/nucleo');
  listar_carpeta($raiz_app . '/config', 'La carpeta app/config');
  ?>

  <?php if (!$index_correcto): ?>
    <p class="aviso aviso--error">
      <strong>index.php quedó con la ruta vieja.</strong>
      Tendría que decir <em>/app/nucleo/inicio.php</em> y dice <em><?= htmlspecialchars($ruta_en_index) ?></em>.
      Los archivos de htdocs no se sobrescribieron al subirlos: volvé a subirlos eligiendo
      «Sobrescribir».
    </p>
  <?php elseif (!is_file($raiz_app . '/config/config.php')): ?>
    <p class="aviso aviso--error">
      <strong>Falta app/config/config.php.</strong>
      Ese archivo no viaja con el resto porque tiene las credenciales de la base.
      Subilo a mano desde tu computadora, a la carpeta app/config del servidor.
    </p>
  <?php endif; ?>

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
