<?php
/**
 * PANEL — QUIÉNES SE POSTULARON A UNA OFERTA
 * -----------------------------------------------------------------
 * Acá el panel ve los datos de las personas que autorizaron compartir
 * su currículum CON ESTA OFERTA.
 *
 * Y solo con esta. Que alguien haya subido un currículum a la
 * plataforma no le da a nadie derecho a verlo; que se haya postulado
 * a otra oferta, tampoco. El consentimiento es por oferta (regla 6) y
 * acá eso se traduce en que la única lista posible es la de esta
 * oferta.
 *
 * Entrar a esta pantalla queda registrado en la bitácora. Ver los
 * datos de personas migrantes tiene que dejar huella de quién los vio
 * y cuándo.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('ofertas.ver');

$id     = id_valido(parametro('oferta'));
$oferta = $id === null ? null : buscar_oferta($id);

if ($oferta === null) {
    abortar(404, 'Esa oferta no existe', 'Puede que la hayan borrado o que el enlace esté mal.');
}

$postulantes = listar_postulaciones_de_oferta((int) $oferta['id']);

// Se registra el acceso, no el contenido: quién entró y a qué oferta.
registrar_accion(
    'postulaciones_vistas',
    'oferta',
    (int) $oferta['id'],
    count($postulantes) . ' postulantes'
);

$titulo_pagina = 'Postulaciones';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Quiénes se postularon</h1>
    <p class="texto-guia"><?= escapar($oferta['titulo']) ?> · <?= escapar($oferta['empleador']) ?></p>
  </section>

  <p class="aviso aviso--aviso">
    Estas personas autorizaron compartir su currículum <strong>con esta oferta y solo con
    esta</strong>. Usar sus datos para cualquier otra cosa rompe el permiso que dieron.
    Cada descarga de un currículum queda registrada en la bitácora con tu nombre.
  </p>

  <?php if ($postulantes === []): ?>

    <p class="vacio">Todavía no se postuló nadie a esta oferta.</p>

  <?php else: ?>

    <p class="texto-menor">
      <?= count($postulantes) ?> persona<?= count($postulantes) === 1 ? '' : 's' ?>.
    </p>

    <div class="pila">
      <?php foreach ($postulantes as $postulante): ?>
        <article class="tarjeta pila">

          <div class="fila-titulo">
            <h2 class="tarjeta__titulo"><?= escapar($postulante['nombre']) ?></h2>
            <span class="insignia">
              <?= escapar(ESTADOS_POSTULACION[$postulante['estado']] ?? $postulante['estado']) ?>
            </span>
          </div>

          <dl class="lista-datos">
            <dt>Contacto</dt>
            <dd><?= escapar($postulante['correo']) ?></dd>

            <dt>Experiencia</dt>
            <dd>
              <?php if ((int) $postulante['anios_experiencia'] === 0): ?>
                Sin experiencia previa
              <?php else: ?>
                <?= (int) $postulante['anios_experiencia'] ?>
                año<?= (int) $postulante['anios_experiencia'] === 1 ? '' : 's' ?>
              <?php endif; ?>
            </dd>

            <dt>Estudios</dt>
            <dd><?= escapar(NIVELES_ESTUDIO[$postulante['nivel_estudios']] ?? 'Sin especificar') ?></dd>

            <dt>Disponibilidad</dt>
            <dd><?= escapar(DISPONIBILIDAD[$postulante['disponibilidad']] ?? 'Sin especificar') ?></dd>

            <dt>Autorizó compartir su currículum el</dt>
            <dd><?= escapar(fecha_hora_en_palabras($postulante['otorgado_en'])) ?></dd>

            <dt>Qué autorizó compartir</dt>
            <dd>
              <?= $postulante['cv_archivo'] === 'perfil'
                  ? 'Los datos de su perfil (no tenía archivo subido)'
                  : 'Un archivo de currículum' ?>
            </dd>
          </dl>

          <?php if ($postulante['cv_archivo'] !== 'perfil'): ?>
            <div class="acciones">
              <?php if (tiene_permiso('cv.descargar')): ?>
                <a class="boton boton--secundario"
                   href="/admin/archivo_cv.php?postulacion=<?= (int) $postulante['id'] ?>">
                  Descargar su currículum
                </a>
              <?php else: ?>
                <span class="texto-menor">Tu cuenta no tiene permiso para descargar currículums.</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

        </article>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

  <p><a href="/admin/ofertas.php">Volver a las ofertas</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
