<?php
/**
 * SELLO DE VERIFICACIÓN
 * -----------------------------------------------------------------
 * El elemento más importante de toda la interfaz, y por eso está
 * escrito UNA sola vez y se incluye donde haga falta:
 *
 *     $oferta = ...;
 *     require RAIZ_APP . '/vistas/sello_verificacion.php';
 *
 * Reglas que este archivo hace cumplir, por construcción:
 *
 *  - Regla 1: el sello NUNCA aparece solo. Siempre sale acompañado de
 *    fuente, fecha de publicación, fecha de última verificación,
 *    empleador y estado. Como todo eso está acá adentro, no hay forma
 *    de poner el sello en una pantalla y "olvidar" los datos.
 *
 *  - Regla 2: dice "Origen verificado", no "oferta segura" ni
 *    "empleo garantizado". Y lo aclara con todas sus letras: se
 *    comprobó de dónde viene la oferta, no que vayas a conseguir el
 *    trabajo. Eso es lo único que podemos afirmar honestamente.
 */
?>
<div class="verificacion">

  <p class="sello">
    <span aria-hidden="true">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3z"></path>
        <path d="m9 12 2 2 4-4"></path>
      </svg>
    </span>
    Origen verificado
  </p>

  <p class="verificacion__aclaracion">
    Comprobamos <strong>de dónde viene esta oferta</strong>. No es una garantía de empleo
    ni una recomendación: la decisión es tuya.
  </p>

  <dl class="verificacion__datos">
    <dt>Quién ofrece</dt>
    <dd><?= escapar($oferta['empleador']) ?></dd>

    <?php if (!empty($oferta['reclutador_nombre'])): ?>
      <dt>Reclutador</dt>
      <dd>
        <?= escapar($oferta['reclutador_nombre']) ?>
        <?php if (!empty($oferta['reclutador_registro'])): ?>
          <br><span class="texto-menor">Registro <?= escapar($oferta['reclutador_registro']) ?>
          del Ministerio de Trabajo</span>
        <?php endif; ?>
      </dd>
    <?php endif; ?>

    <dt>De dónde salió</dt>
    <dd>
      <?= escapar($oferta['fuente_nombre']) ?>
      <span class="texto-menor">(<?= escapar(TIPOS_FUENTE[$oferta['fuente_tipo']] ?? $oferta['fuente_tipo']) ?>)</span>
    </dd>

    <dt>Se publicó el</dt>
    <dd><?= escapar(fecha_en_palabras($oferta['fecha_publicacion'])) ?></dd>

    <dt>Se revisó por última vez el</dt>
    <dd>
      <?= escapar(fecha_en_palabras($oferta['verificada_en'])) ?>
      <?php if (!empty($oferta['verificador_nombre'])): ?>
        <br><span class="texto-menor">Revisada por <?= escapar($oferta['verificador_nombre']) ?></span>
      <?php endif; ?>
    </dd>

    <dt>Estado</dt>
    <dd><?= escapar(ESTADOS_OFERTA[$oferta['estado']] ?? $oferta['estado']) ?></dd>

    <dt>Está disponible hasta el</dt>
    <dd><?= escapar(fecha_en_palabras($oferta['fecha_vencimiento'])) ?></dd>
  </dl>

</div>
