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
 * Aun así, acá también se ve la fuente y las fechas: la persona tiene
 * que poder distinguir una oferta de otra sin abrir seis pestañas,
 * que en un celular con datos limitados no es un detalle menor.
 *
 * Toda la tarjeta se puede tocar: el enlace del título se estira
 * sobre ella con CSS (.oferta__titulo a::after). Por eso abajo ya no
 * hay un segundo enlace a lo mismo, solo la indicación "Ver la oferta".
 *
 * OFERTAS RECOMENDADAS (reglas 7 y 8): si $oferta trae las claves
 * 'razones' y 'advertencias' que puso el emparejamiento, se muestran
 * acá. NO hay ningún porcentaje ni ninguna barra de coincidencia: hay
 * frases concretas que la persona puede juzgar por su cuenta. Y las
 * advertencias se ven igual de claras que las razones, no escondidas
 * en letra chica: el punto es que la persona decida, no que confíe a
 * ciegas.
 */

$razones      = $oferta['razones'] ?? [];
$advertencias = $oferta['advertencias'] ?? [];
?>
<article class="oferta">

  <div class="oferta__arriba">
    <p class="chip"><?= icono('ubicacion') ?> <?= escapar(lugar_de_oferta($oferta)) ?></p>
    <p class="sello sello--chico"><?= icono('escudo') ?> Origen verificado</p>
  </div>

  <h3 class="oferta__titulo">
    <a href="/oferta.php?id=<?= (int) $oferta['id'] ?>"><?= escapar($oferta['titulo']) ?></a>
  </h3>

  <p class="oferta__empleador"><?= icono('edificio') ?> <?= escapar($oferta['empleador']) ?></p>

  <ul class="oferta__datos">
    <li><?= icono('etiqueta') ?> <span><?= escapar($oferta['rubro_nombre']) ?></span></li>
    <?php if (!empty($oferta['salario_texto'])): ?>
      <li><?= icono('dinero') ?> <span><?= escapar($oferta['salario_texto']) ?></span></li>
    <?php endif; ?>
    <li><?= icono('calendario') ?> <span>Disponible hasta el <?= escapar(fecha_en_palabras($oferta['fecha_vencimiento'])) ?></span></li>
  </ul>

  <?php if ($razones !== []): ?>
    <div class="motivos motivos--coincide">
      <p class="motivos__titulo">Te aparece porque:</p>
      <ul>
        <?php foreach ($razones as $razon): ?>
          <li><?= escapar($razon) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($advertencias !== []): ?>
    <div class="motivos motivos--ojo">
      <p class="motivos__titulo">Tomá en cuenta:</p>
      <ul>
        <?php foreach ($advertencias as $advertencia): ?>
          <li><?= escapar($advertencia) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="texto-menor">
        Esto no quiere decir que no podás postularte. Vos decidís si igual querés intentarlo.
      </p>
    </div>
  <?php endif; ?>

  <div class="oferta__pie">
    <p class="oferta__origen">
      Fuente: <?= escapar($oferta['fuente_nombre']) ?><br>
      Publicada el <?= escapar(fecha_en_palabras($oferta['fecha_publicacion'])) ?>
    </p>
    <span class="oferta__ir" aria-hidden="true">Ver la oferta <?= icono('flecha') ?></span>
  </div>

</article>
