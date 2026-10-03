<?php
/**
 * OFERTAS QUE GUARDÉ
 * -----------------------------------------------------------------
 * Se muestran con la misma condición pública que el buscador: si una
 * oferta guardada se venció o se retiró, deja de aparecer.
 *
 * Y se le dice a la persona cuántas de las que había guardado ya no
 * están, en vez de dejarlas desaparecer sin explicación. Que una
 * oferta se caiga sin aviso es justo la sensación que dan las páginas
 * de las que estamos protegiendo a la gente.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();

if (es_post()) {
    $oferta_id = id_valido(campo('oferta_id'));

    if ($oferta_id !== null && campo('accion') === 'quitar') {
        quitar_oferta_guardada($usuario_id, $oferta_id);
        guardar_mensaje('exito', 'La quitamos de tus guardadas.');
    }
    redirigir('/cuenta/guardadas.php');
}

$guardadas       = listar_ofertas_guardadas($usuario_id);
$ya_no_estan     = contar_guardadas_no_disponibles($usuario_id);

$titulo_pagina = 'Ofertas que guardé';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="cabeza">
    <span class="cabeza__icono"><?= icono('guardar') ?></span>
    <div>
      <h1 class="titulo-pagina">Ofertas que guardé</h1>
      <p class="texto-guia">
        Guardar una oferta no es postularse. No compartimos ningún dato tuyo con nadie.
      </p>
    </div>
  </section>

  <?php if ($ya_no_estan > 0): ?>
    <p class="aviso aviso--aviso">
      <?= $ya_no_estan ?> oferta<?= $ya_no_estan === 1 ? '' : 's' ?> que habías guardado
      ya no <?= $ya_no_estan === 1 ? 'está disponible' : 'están disponibles' ?>:
      se vencieron o la institución las retiró.
    </p>
  <?php endif; ?>

  <?php if ($guardadas === []): ?>

    <div class="vacio pila">
      <p><strong>Todavía no guardaste ninguna oferta.</strong></p>
      <p>
        Cuando veas una que te sirva, tocá «Guardar» y la vas a encontrar acá para leerla
        con calma después.
      </p>
      <p><a class="boton boton--principal" href="/ofertas.php">Ver las ofertas</a></p>
    </div>

  <?php else: ?>

    <div class="rejilla">
      <?php foreach ($guardadas as $oferta): ?>
        <div class="pila">
          <?php require RAIZ_APP . '/vistas/tarjeta_oferta.php'; ?>
          <form method="post" action="/cuenta/guardadas.php">
            <?php campo_csrf(); ?>
            <input type="hidden" name="accion" value="quitar">
            <input type="hidden" name="oferta_id" value="<?= (int) $oferta['id'] ?>">
            <button class="boton boton--secundario" type="submit">Quitar de guardadas</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>

  <p><a href="/cuenta/panel.php">Volver a mi cuenta</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
