<?php
/**
 * PANEL — RESPALDO DE LA BASE DE DATOS
 * =================================================================
 * Genera un archivo .sql con todo el contenido de la base y lo
 * descarga.
 *
 * Por qué se genera con PHP y no con mysqldump: en el hosting no hay
 * terminal ni forma de ejecutar programas. Se arma leyendo cada tabla
 * y escribiendo las instrucciones INSERT, que es exactamente lo que
 * haría mysqldump, solo que más lento.
 *
 * -----------------------------------------------------------------
 * ESTE ARCHIVO ES SENSIBLE Y HAY QUE TRATARLO COMO TAL
 * -----------------------------------------------------------------
 * El respaldo trae los correos de todas las personas registradas y
 * sus perfiles laborales. NO trae los archivos de currículum (esos
 * están en app/almacen/cv y se bajan aparte por FTP), pero igual es
 * el archivo más delicado que produce el sistema.
 *
 * Por eso: solo con permiso de mantenimiento, queda registrado en la
 * bitácora quién lo descargó y cuándo, y la pantalla lo dice con
 * todas las letras antes de que alguien lo baje.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('mantenimiento.ejecutar');

/** Las tablas del sistema, en orden de dependencias para poder restaurar. */
const TABLAS_RESPALDO = [
    'roles', 'permisos', 'roles_permisos', 'usuarios', 'restablecimientos',
    'intentos_acceso', 'bitacora_admin', 'rubros', 'fuentes',
    'reclutadores_autorizados', 'reclutadores_alias', 'ofertas', 'ofertas_idiomas',
    'perfiles', 'perfiles_rubros', 'perfiles_idiomas', 'perfiles_paises',
    'ofertas_guardadas', 'consentimientos', 'postulaciones', 'reportes',
];

/**
 * Devuelve el nombre de tabla listo para pegar en una consulta, o corta
 * la ejecución.
 *
 * Este es el ÚNICO lugar del proyecto donde algo que no es un parámetro
 * entra al texto de una consulta, y hay una razón: **PDO no permite
 * pasar nombres de tabla como parámetro**. Solo se pueden parametrizar
 * los valores, nunca los identificadores.
 *
 * Entonces la protección es la lista blanca, y por eso se comprueba
 * explícitamente acá en vez de confiar en que quien llame la función
 * use la constante. Dos candados:
 *
 *   1. El nombre tiene que estar en TABLAS_RESPALDO.
 *   2. Y además tiene que ser solo letras minúsculas y guiones bajos.
 *
 * Si alguna vez alguien arma esta lista desde otro lado, sigue sin
 * poder colar nada.
 */
function tabla_permitida(string $tabla): string
{
    if (!in_array($tabla, TABLAS_RESPALDO, true) || !preg_match('/^[a-z_]+$/', $tabla)) {
        registrar_error('Se intentó respaldar una tabla fuera de la lista: ' . $tabla, __FILE__, __LINE__);
        abortar(400, 'No se pudo generar el respaldo', 'Hubo un problema al preparar la copia.');
    }
    return '`' . $tabla . '`';
}

if (es_post() && campo('accion') === 'descargar') {

    registrar_accion('respaldo_descargado', 'sistema', null, 'Respaldo completo de la base');

    $nombre = 'respaldo-' . date('Y-m-d_His') . '.sql';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Cache-Control: private, no-store');

    $conexion = bd();

    echo "-- Respaldo de " . SITIO_NOMBRE . "\n";
    echo "-- Generado el " . ahora() . "\n";
    echo "-- \n";
    echo "-- CÓMO RESTAURAR:\n";
    echo "--   phpMyAdmin -> Importar -> elegir este archivo -> Continuar.\n";
    echo "--   Ojo: esto BORRA lo que haya y lo reemplaza por esta copia.\n";
    echo "-- \n";
    echo "-- Este archivo NO contiene los archivos de currículum.\n";
    echo "-- Esos están en app/almacen/cv y se copian por FTP.\n\n";

    echo "SET NAMES utf8mb4;\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach (TABLAS_RESPALDO as $tabla) {
        $nombre_seguro = tabla_permitida($tabla);

        echo "-- ---------------------------------------------------\n";
        echo "-- Tabla: {$tabla}\n";
        echo "-- ---------------------------------------------------\n";
        echo 'DELETE FROM ' . $nombre_seguro . ";\n";

        // Se lee de a poco y se escribe de a poco: si la tabla es
        // grande, no se junta todo en memoria antes de mandarlo.
        $sentencia = $conexion->query('SELECT * FROM ' . $nombre_seguro);

        while ($fila = $sentencia->fetch(PDO::FETCH_ASSOC)) {
            $columnas = [];
            $valores  = [];

            foreach ($fila as $columna => $valor) {
                $columnas[] = '`' . $columna . '`';
                $valores[]  = $valor === null ? 'NULL' : $conexion->quote((string) $valor);
            }

            echo 'INSERT INTO ' . $nombre_seguro . ' (' . implode(', ', $columnas) . ') VALUES ('
               . implode(', ', $valores) . ");\n";
        }

        echo "\n";
        flush();
    }

    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    exit;
}

// Cuántas filas hay en cada tabla, para que se vea qué se está bajando.
$conteos = [];
$total_filas = 0;
foreach (TABLAS_RESPALDO as $tabla) {
    $cuantas = (int) bd()->query('SELECT COUNT(*) FROM ' . tabla_permitida($tabla))->fetchColumn();
    $conteos[$tabla] = $cuantas;
    $total_filas += $cuantas;
}

$cv_guardados = is_dir(RUTA_CV) ? count(glob(RUTA_CV . '/*.{pdf,docx}', GLOB_BRACE) ?: []) : 0;

$titulo_pagina = 'Respaldo';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Respaldo de la base de datos</h1>
    <p class="texto-guia">
      Una copia de todo, por si algo pasa. Guardala en un lugar seguro.
    </p>
  </section>

  <section class="aviso aviso--error">
    <p><strong>Este archivo tiene datos de personas.</strong></p>
    <p>
      Trae los correos de todas las personas registradas y sus perfiles laborales.
      No lo dejes en la carpeta de descargas, no lo mandes por WhatsApp y no lo subas a ningún
      lado. Guardalo donde guardarías un documento con datos de gente que confió en vos.
    </p>
    <p class="texto-menor">
      Queda registrado en la bitácora quién lo descargó y cuándo.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Qué se lleva este archivo</h2>
    <div class="tabla-desliza">
      <table class="tabla">
        <thead><tr><th>Tabla</th><th>Filas</th></tr></thead>
        <tbody>
          <?php foreach ($conteos as $tabla => $cuantas): ?>
            <tr>
              <td><?= escapar($tabla) ?></td>
              <td><?= $cuantas ?></td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td><strong>Total</strong></td>
            <td><strong><?= $total_filas ?></strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- data-descarga: la página no cambia al descargar, así que app.js
         no deja el botón "trabajando" para siempre. -->
    <form method="post" action="/admin/respaldo.php" data-descarga>
      <?php campo_csrf(); ?>
      <input type="hidden" name="accion" value="descargar">
      <button class="boton boton--principal" type="submit">Descargar el respaldo</button>
    </form>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Los currículums van aparte</h2>
    <p>
      El respaldo <strong>no incluye los archivos de currículum</strong>: son archivos, no filas
      de la base. Hay <?= (int) $cv_guardados ?> guardado<?= $cv_guardados === 1 ? '' : 's' ?>
      en la carpeta <em>app/almacen/cv</em>.
    </p>
    <p>
      Para respaldarlos hay que copiar esa carpeta por FTP. Un respaldo completo son las dos
      cosas: este archivo <strong>y</strong> esa carpeta.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Cada cuánto y dónde guardarlo</h2>
    <ul class="pila">
      <li><strong>Una vez por semana</strong> si se están cargando ofertas seguido; una vez al mes si está tranquilo.</li>
      <li><strong>Siempre antes</strong> de una importación grande por CSV o de cualquier cambio importante.</li>
      <li>Guardá <strong>las últimas tres copias</strong>, no solo la última: si el problema viene de hace días, la copia de ayer ya lo trae adentro.</li>
      <li>En una computadora de la institución, no en la personal de quien lo baja.</li>
      <li><strong>Probá una restauración al menos una vez</strong>, en una base de prueba. Un respaldo que nunca se restauró no es un respaldo: es un archivo.</li>
    </ul>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Cómo restaurar</h2>
    <ol class="pila">
      <li>Entrar a phpMyAdmin desde el panel del hosting.</li>
      <li>Elegir la base de datos de la plataforma.</li>
      <li>Pestaña <strong>Importar</strong> → elegir el archivo .sql → Continuar.</li>
      <li>Copiar por FTP la carpeta <em>app/almacen/cv</em> que corresponda a esa misma fecha.</li>
    </ol>
    <p class="aviso aviso--aviso">
      Restaurar <strong>borra lo que haya</strong> y lo reemplaza por la copia. Todo lo que haya
      pasado después de la fecha del respaldo se pierde.
    </p>
  </section>

  <p><a href="/admin/mantenimiento.php">Volver a mantenimiento</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
