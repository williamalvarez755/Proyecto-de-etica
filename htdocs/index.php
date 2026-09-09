<?php
/**
 * PORTADA
 * -----------------------------------------------------------------
 * Dice qué es la plataforma, qué no es, y en qué punto va.
 * Solo enseña lo que ya funciona: no hay botones que no lleven a
 * ninguna parte ni secciones anunciadas "para después".
 */

require __DIR__ . '/../app/nucleo/inicio.php';

$titulo_pagina = 'Ofertas de trabajo con origen verificado';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Antes de creerle a una oferta de trabajo, revisá de dónde viene</h1>
    <p class="texto-guia">
      Muchas ofertas de trabajo en el extranjero que circulan por Facebook y WhatsApp son falsas.
      Acá vas a encontrar únicamente ofertas cuyo origen fue verificado, y siempre vas a poder ver
      de dónde salió cada una, quién la ofrece y cuándo se revisó por última vez.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Qué significa que una oferta esté verificada</h2>
    <p>
      Significa que alguien de la institución comprobó <strong>de dónde viene la oferta</strong>:
      que la fuente es real, que el empleador o el reclutador existe, y que el reclutador está en
      el registro de reclutadores autorizados del Ministerio de Trabajo cuando corresponde.
    </p>
    <p class="aviso aviso--aviso">
      <strong>Verificada no quiere decir que tenés el trabajo asegurado.</strong>
      Quiere decir que la oferta no salió de la nada. La decisión y el trato siguen siendo tuyos.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">¿Te contactaron por WhatsApp y no sabés si es real?</h2>
    <p>
      Escribí el nombre de la empresa o del reclutador y te decimos si aparece en el registro
      de reclutadores autorizados del Ministerio de Trabajo. <strong>No necesitás cuenta ni
      dar ningún dato tuyo.</strong>
    </p>
    <div class="acciones">
      <a class="boton boton--principal" href="/verificador.php">Comprobar quién me contactó</a>
      <a class="boton boton--secundario" href="/alertas.php">Ver señales de estafa</a>
    </div>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Tres cosas que nunca te vamos a pedir</h2>
    <ul class="pila">
      <li>Dinero. Ni acá, ni por ninguna gestión. Un reclutador autorizado tampoco puede cobrarte.</li>
      <li>Fotos de tu DPI, tu pasaporte, tu visa ni tus datos bancarios.</li>
      <li>Que digas tu situación migratoria.</li>
    </ul>
    <p class="texto-menor">
      Si alguien que dice representarnos te pide alguna de estas cosas, no es de esta plataforma.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Ver las ofertas</h2>
    <p>
      No necesitás cuenta ni dar ningún dato para verlas. Podés buscar por oficio, por país
      y por fecha.
    </p>
    <div class="acciones">
      <a class="boton boton--principal" href="/ofertas.php">Ver las ofertas</a>
      <?php if (!hay_sesion()): ?>
        <a class="boton boton--secundario" href="/cuenta/registrarse.php">Crear mi cuenta</a>
      <?php endif; ?>
    </div>
  </section>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
