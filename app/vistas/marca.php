<?php
/**
 * LA MARCA: logo + nombre + lema
 * -----------------------------------------------------------------
 * La usan la barra de arriba y el pie. Está una sola vez para que el
 * nombre no se escriba de dos maneras.
 *
 * El nombre sale de SITIO_NOMBRE ("Mjob for all") y se dibuja como en
 * el logo: la primera palabra en negrita, con la M en el azul de la M
 * del logo (::first-letter en estilo.css), y el resto ("for all") más
 * delgado. Si el nombre fuera una sola palabra, se ve entera en negrita.
 */

$partes_nombre = explode(' ', trim(SITIO_NOMBRE), 2);
?>
<a class="marca" href="/" aria-label="<?= escapar(SITIO_NOMBRE) ?>, ir al inicio">
  <svg class="marca__logo" viewBox="0 0 120 70" aria-hidden="true" focusable="false"><use href="#mj-marca" xlink:href="#mj-marca"></use></svg>
  <span class="marca__textos">
    <span class="marca__nombre"><span class="marca__principal"><?= escapar($partes_nombre[0]) ?></span><?php
      if (isset($partes_nombre[1])): ?> <span class="marca__resto"><?= escapar($partes_nombre[1]) ?></span><?php endif; ?></span>
    <?php if (SITIO_LEMA !== ''): ?>
      <span class="marca__lema"><?= escapar(SITIO_LEMA) ?></span>
    <?php endif; ?>
  </span>
</a>
