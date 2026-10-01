<?php
/**
 * PANEL — IMPORTAR OFERTAS DESDE UN CSV
 * -----------------------------------------------------------------
 * El servidor NUNCA llama a una API externa (decisión D-004): las
 * conexiones salientes de InfinityFree no son confiables. En vez de
 * eso, el archivo CSV se genera en la computadora del administrador
 * (ver herramientas/generar_csv_adzuna.php) y se sube acá.
 *
 * Tres cosas que hace a propósito:
 *
 *  1. TODAS las ofertas importadas entran como 'pendiente'. No hay
 *     ninguna forma en el sistema de importar algo ya verificado. Si
 *     la hubiera, bastaría con subir un CSV para publicar cualquier
 *     cosa con el sello de verificada.
 *
 *  2. El archivo se lee y se descarta. No se guarda en el servidor:
 *     un archivo menos que cuidar y que cuenta contra el límite de
 *     archivos del hosting.
 *
 *  3. Informa fila por fila qué entró y qué se rechazó, con el motivo.
 *     Importar 200 filas y decir solo "listo" es como no informar.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('importacion.csv');

/** Las columnas que tiene que traer el archivo, en este orden. */
const COLUMNAS_CSV = [
    'titulo', 'empleador', 'descripcion', 'pais_codigo', 'ciudad', 'rubro_codigo',
    'requisitos', 'experiencia_anios_min', 'estudios_min', 'disponibilidad_requerida',
    'salario_texto', 'url_original', 'fecha_publicacion', 'fecha_vencimiento', 'idiomas',
];

$resultado = null;
$errores   = [];
$fuentes   = listar_fuentes(true);

if (es_post()) {
    $fuente_id = id_valido(campo('fuente_id'));
    $fuente    = $fuente_id === null ? null : buscar_fuente($fuente_id);

    if ($fuente === null) {
        $errores['fuente_id'] = 'Elegí de qué fuente vienen estas ofertas.';
    }

    $archivo = $_FILES['archivo'] ?? null;

    if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errores['archivo'] = 'Elegí el archivo CSV.';
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores['archivo'] = 'El archivo no se subió completo. Probá de nuevo.';
    } elseif ($archivo['size'] > CSV_TAMANO_MAXIMO_BYTES) {
        $errores['archivo'] = 'El archivo pesa más de 1 MB. Partilo en varios.';
    } elseif (!is_uploaded_file($archivo['tmp_name'])) {
        $errores['archivo'] = 'No se pudo leer el archivo.';
    } else {
        // Se comprueba el contenido real, no la extensión. Un CSV es
        // texto: si el archivo es otra cosa, se rechaza.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipo  = (string) $finfo->file($archivo['tmp_name']);

        if (!in_array($tipo, ['text/plain', 'text/csv', 'application/csv', 'inode/x-empty'], true)) {
            $errores['archivo'] = 'Ese archivo no es un CSV de texto.';
        }
    }

    // --- Procesar -------------------------------------------------
    if ($errores === []) {
        $manejador = fopen($archivo['tmp_name'], 'r');

        $encabezado = fgetcsv($manejador);
        if ($encabezado !== false && isset($encabezado[0])) {
            // Quita la marca invisible que Excel pone al principio.
            $encabezado[0] = preg_replace('/^\xEF\xBB\xBF/', '', $encabezado[0]);
        }
        $encabezado = array_map(static fn($c) => trim((string) $c), $encabezado ?: []);

        if ($encabezado !== COLUMNAS_CSV) {
            $errores['archivo'] = 'El archivo no tiene las columnas esperadas. '
                                . 'Usá la plantilla: ' . implode(', ', COLUMNAS_CSV);
            fclose($manejador);
        } else {
            $importadas = 0;
            $repetidas  = 0;
            $rechazadas = [];
            $fila_num   = 1;

            while (($fila = fgetcsv($manejador)) !== false) {
                $fila_num++;

                if ($fila_num > CSV_MAXIMO_FILAS + 1) {
                    $rechazadas[] = ['fila' => $fila_num, 'motivo' => 'Se pasó del límite de ' . CSV_MAXIMO_FILAS . ' filas.'];
                    break;
                }

                // Fila en blanco: se salta sin hacer ruido.
                if (count($fila) === 1 && trim((string) $fila[0]) === '') {
                    continue;
                }

                if (count($fila) !== count(COLUMNAS_CSV)) {
                    $rechazadas[] = ['fila' => $fila_num, 'motivo' => 'Tiene ' . count($fila) . ' columnas y deberían ser ' . count(COLUMNAS_CSV) . '.'];
                    continue;
                }

                $d = array_combine(COLUMNAS_CSV, array_map(static fn($v) => limpiar_texto((string) $v), $fila));

                // --- Validación de la fila ------------------------
                $motivo = null;
                $rubro_id = id_de_rubro($d['rubro_codigo']);
                $experiencia = entero_en_rango($d['experiencia_anios_min'], 0, 50);

                if (!largo_valido($d['titulo'], 5, 200)) {
                    $motivo = 'El título tiene que tener entre 5 y 200 caracteres.';
                } elseif (!largo_valido($d['empleador'], 2, 150)) {
                    $motivo = 'Falta el empleador.';
                } elseif (!largo_valido($d['descripcion'], 20, 5000)) {
                    $motivo = 'La descripción tiene menos de 20 caracteres.';
                } elseif (!en_catalogo($d['pais_codigo'], PAISES)) {
                    $motivo = 'El país "' . $d['pais_codigo'] . '" no está en la lista.';
                } elseif ($rubro_id === null) {
                    $motivo = 'El oficio "' . $d['rubro_codigo'] . '" no existe.';
                } elseif ($experiencia === null) {
                    $motivo = 'Los años de experiencia tienen que ser un número entre 0 y 50.';
                } elseif (!en_catalogo($d['estudios_min'], NIVELES_ESTUDIO)) {
                    $motivo = 'El nivel de estudios "' . $d['estudios_min'] . '" no está en la lista.';
                } elseif (!en_catalogo($d['disponibilidad_requerida'], DISPONIBILIDAD)) {
                    $motivo = 'La disponibilidad "' . $d['disponibilidad_requerida'] . '" no está en la lista.';
                } elseif (!es_fecha_valida($d['fecha_publicacion'])) {
                    $motivo = 'La fecha de publicación no tiene la forma AAAA-MM-DD.';
                } elseif (!es_fecha_valida($d['fecha_vencimiento'])) {
                    $motivo = 'La fecha de vencimiento no tiene la forma AAAA-MM-DD.';
                } elseif ($d['fecha_vencimiento'] < $d['fecha_publicacion']) {
                    $motivo = 'El vencimiento es anterior a la publicación.';
                } elseif ($d['url_original'] !== '' && !url_segura($d['url_original'])) {
                    $motivo = 'La dirección no empieza con http:// o https://';
                } elseif (!largo_valido($d['ciudad'], 0, 100)) {
                    $motivo = 'La ciudad tiene más de 100 caracteres.';
                } elseif (!largo_valido($d['salario_texto'], 0, 120)) {
                    $motivo = 'El pago tiene más de 120 caracteres.';
                } elseif (!largo_valido($d['requisitos'], 0, 5000)) {
                    $motivo = 'Los requisitos tienen más de 5000 caracteres.';
                } elseif (!largo_valido($d['url_original'], 0, 255)) {
                    $motivo = 'La dirección tiene más de 255 caracteres.';
                }

                if ($motivo !== null) {
                    $rechazadas[] = ['fila' => $fila_num, 'motivo' => $motivo];
                    continue;
                }

                if (existe_oferta_igual($d['titulo'], $d['empleador'], (int) $fuente['id'])) {
                    $repetidas++;
                    continue;
                }

                // --- Se carga, siempre como pendiente -------------
                $nueva_id = crear_oferta([
                    'titulo'                   => $d['titulo'],
                    'descripcion'              => $d['descripcion'],
                    'empleador'                => $d['empleador'],
                    'reclutador_id'            => null,
                    'fuente_id'                => (int) $fuente['id'],
                    'pais_codigo'              => $d['pais_codigo'],
                    'ciudad'                   => $d['ciudad'],
                    'rubro_id'                 => $rubro_id,
                    'requisitos'               => $d['requisitos'],
                    'experiencia_anios_min'    => $experiencia,
                    'estudios_min'             => $d['estudios_min'],
                    'disponibilidad_requerida' => $d['disponibilidad_requerida'],
                    'salario_texto'            => $d['salario_texto'],
                    'url_original'             => $d['url_original'],
                    'fecha_publicacion'        => $d['fecha_publicacion'],
                    'fecha_vencimiento'        => $d['fecha_vencimiento'],
                ], id_usuario_actual());

                if ($d['idiomas'] !== '') {
                    guardar_idiomas_oferta($nueva_id, array_map('trim', explode(';', $d['idiomas'])));
                }

                $importadas++;
            }

            fclose($manejador);

            registrar_accion(
                'importacion_csv',
                'fuente',
                (int) $fuente['id'],
                $importadas . ' importadas, ' . $repetidas . ' repetidas, ' . count($rechazadas) . ' rechazadas'
            );

            $resultado = [
                'importadas' => $importadas,
                'repetidas'  => $repetidas,
                'rechazadas' => $rechazadas,
                'fuente'     => $fuente['nombre'],
            ];
        }
    }
}

$titulo_pagina = 'Importar ofertas';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Importar ofertas desde un archivo</h1>
    <p class="texto-guia">
      Las ofertas entran <strong>siempre como pendientes</strong>, sin importar de dónde vengan.
      Hay que verificarlas una por una antes de poder publicarlas.
    </p>
  </section>

  <?php if ($resultado !== null): ?>
    <section class="pila">
      <h2 class="subtitulo">Cómo salió la importación</h2>

      <p class="aviso aviso--exito">
        Se cargaron <strong><?= (int) $resultado['importadas'] ?></strong> ofertas nuevas
        desde <?= escapar($resultado['fuente']) ?>, todas en estado pendiente.
      </p>

      <?php if ($resultado['repetidas'] > 0): ?>
        <p class="aviso aviso--aviso">
          <?= (int) $resultado['repetidas'] ?> ya estaban cargadas de esta misma fuente y se saltaron.
        </p>
      <?php endif; ?>

      <?php if ($resultado['rechazadas'] !== []): ?>
        <div class="aviso aviso--error">
          <p><strong><?= count($resultado['rechazadas']) ?> filas no se pudieron cargar:</strong></p>
        </div>
        <div class="tabla-desliza">
          <table class="tabla">
            <thead><tr><th>Fila</th><th>Por qué</th></tr></thead>
            <tbody>
              <?php foreach ($resultado['rechazadas'] as $rechazada): ?>
                <tr>
                  <td><?= (int) $rechazada['fila'] ?></td>
                  <td><?= escapar($rechazada['motivo']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div class="acciones">
        <a class="boton boton--principal" href="/admin/ofertas.php?estado=pendiente">Ver las ofertas pendientes</a>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($fuentes === []): ?>

    <div class="vacio pila">
      <p><strong>Antes hay que crear al menos una fuente activa.</strong></p>
      <p><a class="boton boton--principal" href="/admin/fuentes.php">Crear una fuente</a></p>
    </div>

  <?php else: ?>

    <form method="post" action="/admin/importar_csv.php" enctype="multipart/form-data" class="tarjeta">
      <?php campo_csrf(); ?>

      <div class="campo">
        <label class="etiqueta" for="fuente_id">¿De qué fuente vienen estas ofertas?</label>
        <span class="ayuda" id="ayuda-fuente">
          La fuente no viene en el archivo: la elegís vos, y es tu responsabilidad.
        </span>
        <select class="entrada <?= isset($errores['fuente_id']) ? 'entrada--error' : '' ?>"
                id="fuente_id" name="fuente_id" aria-describedby="ayuda-fuente" required>
          <option value="">Elegí la fuente</option>
          <?php foreach ($fuentes as $fuente): ?>
            <option value="<?= (int) $fuente['id'] ?>"><?= escapar($fuente['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['fuente_id'])): ?><span class="error-campo"><?= escapar($errores['fuente_id']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="archivo">El archivo CSV</label>
        <span class="ayuda" id="ayuda-archivo">
          Hasta 1 MB y <?= (int) CSV_MAXIMO_FILAS ?> filas.
        </span>
        <input class="entrada <?= isset($errores['archivo']) ? 'entrada--error' : '' ?>" type="file"
               id="archivo" name="archivo" accept=".csv,text/csv" aria-describedby="ayuda-archivo" required>
        <?php if (isset($errores['archivo'])): ?><span class="error-campo"><?= escapar($errores['archivo']) ?></span><?php endif; ?>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal" type="submit">Importar</button>
      </div>
    </form>

    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Cómo tiene que ser el archivo</h2>
      <p>La primera fila son los nombres de las columnas, exactamente en este orden:</p>
      <div class="tabla-desliza">
        <p class="codigo"><?= escapar(implode(',', COLUMNAS_CSV)) ?></p>
      </div>
      <ul class="pila">
        <li><strong>pais_codigo</strong>: <?= escapar(implode(', ', array_keys(PAISES))) ?></li>
        <li><strong>estudios_min</strong>: <?= escapar(implode(', ', array_keys(NIVELES_ESTUDIO))) ?></li>
        <li><strong>disponibilidad_requerida</strong>: <?= escapar(implode(', ', array_keys(DISPONIBILIDAD))) ?></li>
        <li><strong>idiomas</strong>: separados por punto y coma. Por ejemplo: <em>espanol;ingles</em></li>
        <li><strong>Las fechas</strong> van como AAAA-MM-DD. Por ejemplo: <em>2026-09-15</em></li>
      </ul>
      <p class="texto-menor">
        En la carpeta <em>herramientas/</em> del proyecto hay un archivo de ejemplo y el script que
        genera el CSV desde Adzuna en tu computadora.
      </p>
    </section>

  <?php endif; ?>

  <p><a href="/admin/ofertas.php">Volver a las ofertas</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
