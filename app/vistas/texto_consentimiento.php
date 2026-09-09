<?php
/**
 * EL TEXTO DEL CONSENTIMIENTO
 * -----------------------------------------------------------------
 * Está en un solo archivo a propósito: es lo que la persona acepta, y
 * tiene que decir exactamente lo mismo en la pantalla donde lo acepta
 * y en el historial donde después lo consulta.
 *
 * Si este texto cambia, hay que subir CONSENTIMIENTO_VERSION en
 * app/config/limites.php. Cada consentimiento guarda con qué versión
 * se dio, así que cambiar la redacción no reescribe lo que la gente
 * aceptó antes.
 *
 * Espera: $oferta (la fila de la oferta) y $que_se_comparte
 * ('archivo' o 'perfil').
 */
?>
<div class="consentimiento">

  <h2 class="tarjeta__titulo">Qué vas a autorizar</h2>

  <p>
    Al confirmar, autorizás que <strong>tu currículum se comparta con esta oferta y
    solamente con esta</strong>:
  </p>

  <dl class="lista-datos">
    <dt>La oferta</dt>
    <dd><?= escapar($oferta['titulo']) ?></dd>

    <dt>Quién la ofrece</dt>
    <dd><?= escapar($oferta['empleador']) ?></dd>

    <?php if (!empty($oferta['reclutador_nombre'])): ?>
      <dt>Reclutador de por medio</dt>
      <dd>
        <?= escapar($oferta['reclutador_nombre']) ?>
        <?php if (!empty($oferta['reclutador_registro'])): ?>
          <br><span class="texto-menor">Registro <?= escapar($oferta['reclutador_registro']) ?>
          del Ministerio de Trabajo</span>
        <?php endif; ?>
      </dd>
    <?php endif; ?>

    <dt>De dónde salió la oferta</dt>
    <dd><?= escapar($oferta['fuente_nombre']) ?></dd>

    <dt>Qué se comparte</dt>
    <dd>
      <?php if ($que_se_comparte === 'archivo'): ?>
        El archivo de currículum que subiste.
      <?php else: ?>
        Los datos de tu perfil: tus oficios, tus años de experiencia, tus estudios,
        tus idiomas y tu disponibilidad. No tenés un archivo subido, así que no se
        comparte ningún archivo.
      <?php endif; ?>
    </dd>
  </dl>

  <p class="separado">
    <strong>Esta autorización vale solo para esta oferta.</strong>
    Si más adelante querés postularte a otra, te vamos a volver a preguntar.
    Nunca reutilizamos un permiso que diste para otra cosa.
  </p>

  <p>
    Queda registrado el día y la hora en que autorizaste, y lo podés consultar cuando
    quieras en tu historial de postulaciones.
  </p>

  <p class="texto-menor">
    Recordá: postularte no es tener el trabajo. La plataforma no consigue empleo,
    no cobra dinero y no hace trámites. Y nadie puede pedirte dinero por una oferta.
  </p>

</div>
