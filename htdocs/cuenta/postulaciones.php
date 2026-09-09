<?php
/**
 * MIS POSTULACIONES
 * -----------------------------------------------------------------
 * El historial de consentimientos, escrito en lenguaje normal:
 *
 *   "Autorizaste compartir tu currículum con esta oferta
 *    el 1 de septiembre de 2026."
 *
 * Que la persona pueda ver, en una sola pantalla y sin tecnicismos,
 * a quién le dio permiso y cuándo, es lo que hace que el
 * consentimiento signifique algo. Un permiso que no se puede
 * consultar después es un permiso que no se dio de verdad.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();

if (es_post() && campo('accion') === 'retirar') {
    $postulacion_id = id_valido(campo('postulacion_id'));

    if ($postulacion_id !== null && retirar_postulacion($postulacion_id, $usuario_id)) {
        guardar_mensaje('exito', 'Marcamos tu postulación como retirada.');
    } else {
        guardar_mensaje('error', 'No pudimos retirar esa postulación.');
    }
    redirigir('/cuenta/postulaciones.php');
}

$postulaciones = listar_postulaciones_de($usuario_id);

$titulo_pagina = 'Mis postulaciones';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Mis postulaciones</h1>
    <p class="texto-guia">
      Acá está cada oferta con la que autorizaste compartir tu currículum, y en qué momento
      lo hiciste.
    </p>
  </section>

  <?php if ($postulaciones === []): ?>

    <div class="vacio pila">
      <p><strong>Todavía no te postulaste a ninguna oferta.</strong></p>
      <p>
        Cuando te postules, acá vas a poder consultar siempre a quién le autorizaste compartir
        tu currículum y cuándo.
      </p>
      <div class="acciones">
        <a class="boton boton--principal" href="/cuenta/recomendadas.php">Ver ofertas para mí</a>
        <a class="boton boton--secundario" href="/ofertas.php">Ver todas las ofertas</a>
      </div>
    </div>

  <?php else: ?>

    <div class="pila">
      <?php foreach ($postulaciones as $postulacion): ?>
        <article class="tarjeta pila">

          <div class="fila-titulo">
            <h2 class="tarjeta__titulo"><?= escapar($postulacion['titulo']) ?></h2>
            <span class="insignia">
              <?= escapar(ESTADOS_POSTULACION[$postulacion['estado']] ?? $postulacion['estado']) ?>
            </span>
          </div>

          <p class="texto-menor">
            <?= escapar($postulacion['empleador']) ?> ·
            <?= escapar(PAISES[$postulacion['pais_codigo']] ?? $postulacion['pais_codigo']) ?><br>
            Fuente: <?= escapar($postulacion['fuente_nombre']) ?>
            <?php if (!empty($postulacion['reclutador_nombre'])): ?>
              · Reclutador: <?= escapar($postulacion['reclutador_nombre']) ?>
            <?php endif; ?>
          </p>

          <!-- Esta frase es el corazón de la pantalla. -->
          <p class="aviso aviso--exito">
            Autorizaste compartir tu currículum con esta oferta el
            <strong><?= escapar(fecha_en_palabras($postulacion['otorgado_en'])) ?></strong>,
            a las <?= escapar(date('H:i', strtotime($postulacion['otorgado_en']))) ?>.
            <br>
            <span class="texto-menor">
              Se compartió
              <?= $postulacion['cv_archivo'] === 'perfil'
                  ? 'los datos de tu perfil'
                  : 'el archivo de currículum que tenías subido en ese momento' ?>.
              Esta autorización vale solo para esta oferta.
            </span>
          </p>

          <?php if ($postulacion['oferta_estado'] !== 'publicada'): ?>
            <p class="texto-menor">
              Esta oferta ya no está publicada en la plataforma.
            </p>
          <?php endif; ?>

          <div class="acciones">
            <a class="boton boton--secundario" href="/oferta.php?id=<?= (int) $postulacion['oferta_id'] ?>">
              Ver la oferta
            </a>

            <?php if ($postulacion['estado'] === 'enviada'): ?>
              <form method="post" action="/cuenta/postulaciones.php"
                    data-confirmar="¿Retirar esta postulación? Quedará registrado que la retiraste.">
                <?php campo_csrf(); ?>
                <input type="hidden" name="accion" value="retirar">
                <input type="hidden" name="postulacion_id" value="<?= (int) $postulacion['id'] ?>">
                <button class="boton boton--peligro" type="submit">Retirar mi postulación</button>
              </form>
            <?php endif; ?>
          </div>

          <?php if ($postulacion['estado'] === 'retirada'): ?>
            <p class="texto-menor">
              La retiraste el <?= escapar(fecha_en_palabras($postulacion['actualizado_en'])) ?>.
              Tené en cuenta que si el empleador ya había visto tu currículum, retirarla no
              deshace eso. Te lo decimos con claridad para que sepas a qué atenerte.
            </p>
          <?php endif; ?>

        </article>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

  <p><a href="/cuenta/panel.php">Volver a mi cuenta</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
