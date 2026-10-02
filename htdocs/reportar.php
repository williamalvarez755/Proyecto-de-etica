<?php
/**
 * REPORTAR UNA OFERTA
 * -----------------------------------------------------------------
 * Se pide cuenta porque el administrador necesita poder distinguir un
 * aviso serio de una campaña de diez cuentas nuevas reportando lo
 * mismo. Pero la identidad de quien reporta NO se muestra en ninguna
 * pantalla pública: solo la ve el panel.
 *
 * Y se le dice a la persona, con todas las letras, que la oferta no va
 * a desaparecer sola. Prometer que sí sería mentirle, y además el
 * sistema no funciona así a propósito: si bastara con reportes para
 * tumbar una oferta, cualquiera podría sacar del aire a los
 * empleadores que sí cumplen.
 */

require __DIR__ . '/app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();

$id     = id_valido(parametro('id'));
$oferta = $id === null ? null : buscar_oferta_publica($id);

if ($oferta === null) {
    abortar(
        404,
        'Esa oferta ya no está disponible',
        'Puede que se haya vencido o que la institución la haya retirado.'
    );
}

$ya_reportada = ya_reporto($usuario_id, (int) $oferta['id']);
$errores      = [];
$motivo       = '';
$descripcion  = '';

if (es_post() && !$ya_reportada) {
    $motivo      = campo('motivo');
    $descripcion = limpiar_texto(campo('descripcion'));

    if (!en_catalogo($motivo, MOTIVOS_REPORTE)) {
        $errores['motivo'] = 'Elegí qué fue lo que viste.';
    }
    if (mb_strlen($descripcion) > 2000) {
        $errores['descripcion'] = 'El texto es demasiado largo. Contanos lo más importante.';
    }
    if ($motivo === 'otro' && mb_strlen($descripcion) < 10) {
        $errores['descripcion'] = 'Como elegiste "otro motivo", contanos qué pasó.';
    }

    if ($errores === []) {
        crear_reporte((int) $oferta['id'], $usuario_id, $motivo, $descripcion);

        guardar_mensaje(
            'exito',
            'Gracias por avisar. Una persona de la institución va a revisar tu reporte.'
        );
        redirigir('/oferta.php?id=' . (int) $oferta['id']);
    }
}

$titulo_pagina = 'Reportar esta oferta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila-grande">

  <p class="texto-menor">
    <a href="/oferta.php?id=<?= (int) $oferta['id'] ?>">← Volver a la oferta</a>
  </p>

  <section class="pila">
    <h1 class="titulo-pagina">Reportar esta oferta</h1>
    <p class="texto-guia"><?= escapar($oferta['titulo']) ?> · <?= escapar($oferta['empleador']) ?></p>
  </section>

  <?php if ($ya_reportada): ?>

    <div class="aviso aviso--exito">
      <p><strong>Ya reportaste esta oferta.</strong></p>
      <p>Tu aviso está en la lista para que lo revise una persona de la institución.</p>
    </div>
    <p><a class="boton boton--secundario" href="/ofertas.php">Ver otras ofertas</a></p>

  <?php else: ?>

    <p class="aviso aviso--aviso">
      <strong>Si te pidieron dinero o tus documentos, no esperes a que revisemos esto.</strong>
      Mirá <a href="/alertas.php">dónde denunciar</a> y guardá todos los mensajes.
    </p>

    <form method="post" action="/reportar.php?id=<?= (int) $oferta['id'] ?>" class="tarjeta">
      <?php campo_csrf(); ?>

      <fieldset class="campo">
        <legend class="etiqueta">¿Qué fue lo que viste?</legend>
        <?php if (isset($errores['motivo'])): ?>
          <span class="error-campo"><?= escapar($errores['motivo']) ?></span>
        <?php endif; ?>
        <?php foreach (MOTIVOS_REPORTE as $codigo => $texto): ?>
          <label class="casilla">
            <input type="radio" name="motivo" value="<?= escapar($codigo) ?>"
                   <?= $motivo === $codigo ? 'checked' : '' ?> required>
            <span><?= escapar($texto) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <div class="campo">
        <label class="etiqueta" for="descripcion">Contanos qué pasó (opcional pero ayuda mucho)</label>
        <span class="ayuda" id="ayuda-descripcion">
          Por ejemplo: qué te dijeron, por dónde te contactaron, cuánto dinero te pidieron.
          No hace falta que pongas tus datos personales.
        </span>
        <textarea class="entrada <?= isset($errores['descripcion']) ? 'entrada--error' : '' ?>"
                  id="descripcion" name="descripcion" rows="5" maxlength="2000"
                  aria-describedby="ayuda-descripcion"><?= escapar($descripcion) ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
          <span class="error-campo"><?= escapar($errores['descripcion']) ?></span>
        <?php endif; ?>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal boton--ancho" type="submit">Enviar el reporte</button>
        <a class="boton boton--secundario boton--ancho" href="/oferta.php?id=<?= (int) $oferta['id'] ?>">Cancelar</a>
      </div>
    </form>

    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Qué pasa con tu reporte</h2>
      <ul class="pila">
        <li>Lo revisa <strong>una persona</strong> de la institución, no un programa.</li>
        <li>
          <strong>La oferta no desaparece sola.</strong> Si se retirara automáticamente por
          recibir reportes, cualquiera podría tumbar ofertas legítimas reportándolas en masa.
        </li>
        <li>Tu nombre <strong>no se muestra en ninguna parte pública</strong>. Solo lo ve el personal que revisa.</li>
      </ul>
    </section>

  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
