<?php
/**
 * PIE COMÚN
 * -----------------------------------------------------------------
 * El texto de acá no es relleno: es la regla 12 puesta en pantalla.
 * La plataforma tiene que decir con todas sus letras lo que NO hace,
 * porque la gente llega con la esperanza de que le consigan trabajo y
 * esa esperanza es justamente de lo que se aprovechan las estafas.
 */
?>
</main>

<!-- Para lectores de pantalla: cuando los resultados cambian sin
     recargar la página, app.js escribe acá cuántos hay. -->
<div id="anuncio" class="solo-lector" aria-live="polite"></div>

<footer class="pie">
  <div class="contenedor">
    <p class="pie__aclaracion">
      Esta plataforma <strong>no consigue trabajo</strong>, no gestiona trámites migratorios,
      no cobra dinero y no representa legalmente a nadie.
      Lo que hace es mostrar ofertas cuyo origen fue verificado, e indicar de dónde salió cada una.
    </p>
    <p class="pie__nota">
      <?= escapar(SITIO_NOMBRE) ?> · Proyecto de responsabilidad social,
      Universidad Rafael Landívar
    </p>
  </div>
</footer>

<script src="<?= escapar(recurso('/recursos/app.js')) ?>"></script>
</body>
</html>
