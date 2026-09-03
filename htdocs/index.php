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

  <?php if (!hay_sesion()): ?>
    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Estamos construyendo la plataforma</h2>
      <p>
        Por ahora podés crear tu cuenta. Todavía no hay ofertas cargadas:
        cuando las haya, se van a poder ver sin necesidad de tener cuenta.
      </p>
      <div class="acciones">
        <a class="boton boton--principal" href="/cuenta/registrarse.php">Crear mi cuenta</a>
        <a class="boton boton--secundario" href="/cuenta/entrar.php">Ya tengo cuenta</a>
      </div>
    </section>
  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
