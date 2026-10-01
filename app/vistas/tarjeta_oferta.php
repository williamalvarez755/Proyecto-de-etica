<?php
/**
 * TARJETA DE OFERTA (para los listados)
 * -----------------------------------------------------------------
 * Espera la variable $oferta.
 *
 * Es la versión corta: lo justo para decidir si abrir la oferta. Los
 * datos completos de verificación van en la página de detalle, con el
 * sello entero.
 *
 * Aun así, acá también se ve la fuente y la fecha: la persona tiene
 * que poder distinguir una oferta de otra sin abrir seis pestañas,
 * que en un celular con datos limitados no es un detalle menor.
 *
 * Toda la tarjeta se puede tocar: el enlace del título se estira
 * sobre ella con CSS (.oferta__titulo a::after). Por eso abajo ya no
 * hay un segundo enlace a lo mismo, solo la indicación "Ver la oferta".
 */
?>
<article class="oferta">

  <h3 class="oferta__titulo">
    <a href="/oferta.php?id=<?= (int) $oferta['id'] ?>"><?= escapar($oferta['titulo']) ?></a>
  </h3>

  <p class="oferta__empleador"><?= escapar($oferta['empleador']) ?></p>

  <p class="oferta__lugar">
    <?= escapar(PAISES[$oferta['pais_codigo']] ?? $oferta['pais_codigo']) ?><?php
    if (!empty($oferta['ciudad'])): ?>, <?= escapar($oferta['ciudad']) ?><?php endif; ?>
    · <?= escapar($oferta['rubro_nombre']) ?>
  </p>

  <p class="sello sello--chico">
    <span aria-hidden="true">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3z"></path>
        <path d="m9 12 2 2 4-4"></path>
      </svg>
    </span>
    Origen verificado
  </p>

  <p class="oferta__origen">
    Fuente: <?= escapar($oferta['fuente_nombre']) ?><br>
    Publicada el <?= escapar(fecha_en_palabras($oferta['fecha_publicacion'])) ?> ·
    disponible hasta el <?= escapar(fecha_en_palabras($oferta['fecha_vencimiento'])) ?>
  </p>

  <p class="oferta__pie">
    <span class="oferta__ir" aria-hidden="true">Ver la oferta completa</span>
  </p>

</article>
