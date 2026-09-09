<?php
/**
 * MI CURRÍCULUM — SUBIRLO
 * -----------------------------------------------------------------
 * Lo más delicado del lado de la persona.
 *
 * El archivo se guarda FUERA de la carpeta pública, con un nombre
 * aleatorio, y solo se puede descargar por un script que comprueba
 * que quien lo pide sea su dueño. No hay ninguna dirección web que
 * llegue al archivo.
 *
 * Se comprueba el contenido real con finfo: un archivo con la
 * extensión cambiada se rechaza acá.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();
$perfil     = buscar_perfil($usuario_id);
$error      = null;

if (es_post()) {
    $accion = campo('accion');

    // --- Quitar el currículum -------------------------------------
    if ($accion === 'quitar') {
        if ($perfil !== null && $perfil['cv_archivo'] !== null) {
            $archivo = $perfil['cv_archivo'];

            quitar_cv_del_perfil($usuario_id);

            // El archivo se borra del disco, salvo que la persona ya lo
            // haya compartido con alguna oferta: en ese caso el
            // consentimiento dice "se compartió este archivo" y borrarlo
            // dejaría el registro mintiendo.
            if (archivo_esta_en_algun_consentimiento($archivo)) {
                guardar_mensaje(
                    'exito',
                    'Quitamos tu currículum de tu perfil. El archivo se conserva únicamente '
                    . 'como respaldo de las ofertas con las que ya lo compartiste, y no se '
                    . 'usa para nada más.'
                );
            } else {
                borrar_cv($archivo);
                guardar_mensaje('exito', 'Tu currículum se borró del sistema.');
            }
        }
        redirigir('/cuenta/cv.php');
    }

    // --- Subir --------------------------------------------------
    if ($accion === 'subir') {
        // Límite de subidas por día: sin esto, alguien podría llenar
        // el disco del hosting subiendo archivos sin parar.
        if (contar_intentos('subida_cv', (string) $usuario_id, 24 * 60) >= CV_SUBIDAS_MAX_POR_DIA) {
            $error = 'Subiste varios archivos hoy. Probá mañana.';
        } else {
            $archivo = $_FILES['cv'] ?? null;
            $error   = revisar_cv_subido($archivo);

            if ($error === null) {
                $anterior = $perfil['cv_archivo'] ?? null;
                $nombre   = guardar_cv($archivo);

                if ($nombre === null) {
                    $error = 'No pudimos guardar el archivo. Probá otra vez en un rato.';
                    registrar_intento('subida_cv', (string) $usuario_id, false);
                } else {
                    // El anterior se borra recién ahora, cuando el
                    // nuevo ya está guardado: si algo falla, la
                    // persona no se queda sin ninguno. Y no se borra
                    // si ya lo compartió con alguna oferta.
                    if (!archivo_esta_en_algun_consentimiento($anterior)) {
                        borrar_cv($anterior);
                    }

                    guardar_cv_en_perfil(
                        $usuario_id,
                        $nombre,
                        (string) extension_real(RUTA_CV . '/' . $nombre),
                        (int) $archivo['size'],
                        'subido'
                    );

                    registrar_intento('subida_cv', (string) $usuario_id, true);

                    guardar_mensaje('exito', 'Recibimos tu currículum. Ahora revisá lo que entendimos.');
                    redirigir('/cuenta/confirmar_perfil.php');
                }
            } else {
                registrar_intento('subida_cv', (string) $usuario_id, false);
            }
        }
    }
}

$titulo_pagina = 'Mi currículum';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Mi currículum</h1>
    <p class="texto-guia">
      Lo usamos para entender qué sabés hacer y mostrarte ofertas que coincidan.
      Solo se comparte con una oferta si vos lo autorizás, una por una.
    </p>
  </section>

  <?php if ($error !== null): ?>
    <p class="aviso aviso--error" role="alert"><?= escapar($error) ?></p>
  <?php endif; ?>

  <?php if ($perfil !== null && $perfil['cv_archivo'] !== null): ?>

    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Ya tenés un currículum guardado</h2>
      <dl class="lista-datos">
        <dt>Formato</dt>
        <dd><?= escapar(mb_strtoupper($perfil['cv_extension'] ?? '', 'UTF-8')) ?></dd>
        <dt>Lo subiste el</dt>
        <dd><?= escapar(fecha_hora_en_palabras($perfil['cv_subido_en'])) ?></dd>
      </dl>

      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/archivo_cv.php">Descargar el mío</a>
        <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Revisar mis datos</a>
      </div>

      <form method="post" action="/cuenta/cv.php"
            data-confirmar="¿Seguro que querés borrar tu currículum? El archivo se borra del sistema y no se puede recuperar.">
        <?php campo_csrf(); ?>
        <input type="hidden" name="accion" value="quitar">
        <button class="boton boton--peligro" type="submit">Borrar mi currículum</button>
      </form>
    </section>

  <?php endif; ?>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">
      <?= ($perfil !== null && $perfil['cv_archivo'] !== null) ? 'Subir otro en su lugar' : 'Subir mi currículum' ?>
    </h2>

    <form method="post" action="/cuenta/cv.php" enctype="multipart/form-data">
      <?php campo_csrf(); ?>
      <input type="hidden" name="accion" value="subir">

      <div class="campo">
        <label class="etiqueta" for="cv">El archivo</label>
        <span class="ayuda" id="ayuda-cv">
          En PDF o en Word (.docx). Hasta <?= round(CV_TAMANO_MAXIMO_BYTES / 1024 / 1024, 1) ?> MB.
        </span>
        <input class="entrada" type="file" id="cv" name="cv"
               accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
               aria-describedby="ayuda-cv" required>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal boton--ancho" type="submit">Subir mi currículum</button>
      </div>
    </form>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">¿No tenés currículum?</h2>
    <p>
      No pasa nada, y no lo necesitás para usar la plataforma. Podés contarnos qué sabés hacer
      llenando un formulario, y con eso te armamos un currículum ordenado que podés imprimir
      o guardar.
    </p>
    <div class="acciones">
      <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Llenar el formulario</a>
    </div>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Qué hacemos con tu archivo</h2>
    <ul class="pila">
      <li>Se guarda en un lugar del servidor al que no se llega escribiendo una dirección web.</li>
      <li>Le ponemos un nombre al azar, así nadie puede adivinarlo.</li>
      <li>Solo vos podés descargarlo mientras no lo compartas con una oferta.</li>
      <li>Si lo borrás, el archivo se borra del disco de verdad, no solo de la lista.</li>
    </ul>
  </section>

  <p><a href="/cuenta/panel.php">Volver a mi cuenta</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
