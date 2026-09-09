<?php
/**
 * TARJETA DE OFERTA RECOMENDADA
 * -----------------------------------------------------------------
 * Espera $oferta, ya con las claves 'razones' y 'advertencias' que
 * puso el emparejamiento.
 *
 * REGLA 8 en pantalla: acá NO hay ningún porcentaje ni ninguna barra
 * de coincidencia. Hay frases concretas que la persona puede juzgar
 * por su cuenta, sin saber nada de tecnología.
 *
 * Y las advertencias se muestran igual de visibles que las razones,
 * no escondidas abajo en letra chica. El punto de mostrarlas es que
 * la persona decida por sí misma, no que confíe a ciegas.
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
  </p>

  <?php if ($oferta['razones'] !== []): ?>
    <div class="motivos motivos--coincide">
      <p class="motivos__titulo">Te aparece porque:</p>
      <ul>
        <?php foreach ($oferta['razones'] as $razon): ?>
          <li><?= escapar($razon) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($oferta['advertencias'] !== []): ?>
    <div class="motivos motivos--ojo">
      <p class="motivos__titulo">Tomá en cuenta:</p>
      <ul>
        <?php foreach ($oferta['advertencias'] as $advertencia): ?>
          <li><?= escapar($advertencia) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="texto-menor">
        Esto no quiere decir que no podás postularte. Vos decidís si igual querés intentarlo.
      </p>
    </div>
  <?php endif; ?>

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

  <div class="acciones">
    <a class="boton boton--secundario" href="/oferta.php?id=<?= (int) $oferta['id'] ?>">Ver la oferta completa</a>
  </div>

</article>
