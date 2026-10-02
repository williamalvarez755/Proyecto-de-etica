<?php
/**
 * PORTADA
 * -----------------------------------------------------------------
 * Dice qué es la plataforma, qué no es, y en qué punto va.
 * Solo enseña lo que ya funciona: no hay botones que no lleven a
 * ninguna parte ni secciones anunciadas "para después".
 *
 * Lo primero que se ve es el buscador del verificador, y no un botón
 * que lleva a él: quien llega acá suele venir asustada por un mensaje
 * de WhatsApp, y cada pantalla de más es una oportunidad de que se
 * vaya sin comprobar nada.
 */

require __DIR__ . '/app/nucleo/inicio.php';

// Las cifras de la plataforma, para que la persona vea con números
// qué hay detrás (y desde cuándo). Si la base no responde, la portada
// se muestra igual sin ellas: el resto de la página no depende de la
// base, y la regla 11 dice que nada tiene que tumbar lo que sí anda.
$cifras = null;
try {
    $cifras = [
        'ofertas'      => contar_ofertas_publicas([]),
        'reclutadores' => contar_reclutadores(),
        'actualizado'  => fecha_actualizacion_registro(),
    ];
} catch (Throwable $e) {
    registrar_error('Portada sin cifras: ' . $e->getMessage(), __FILE__, __LINE__);
}

$titulo_pagina = 'Ofertas de trabajo con origen verificado';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="portada">
    <h1 class="portada__titulo">Antes de creerle a una oferta de trabajo, revisá de dónde viene</h1>
    <p class="texto-guia">
      Muchas ofertas de trabajo en el extranjero que circulan por Facebook y WhatsApp son falsas.
      Acá solo hay ofertas cuyo origen fue verificado, y siempre vas a poder ver de dónde salió
      cada una, quién la ofrece y cuándo se revisó por última vez.
    </p>

    <form method="get" action="/verificador.php" role="search">
      <label class="etiqueta" for="nombre-portada">
        ¿Te contactaron por WhatsApp? Escribí el nombre de la empresa o del reclutador:
      </label>
      <div class="buscador-grande">
        <input class="entrada" type="search" id="nombre-portada" name="nombre" maxlength="120"
               placeholder="El nombre que te dieron" aria-describedby="ayuda-portada" required>
        <button class="boton boton--principal" type="submit">Comprobar</button>
      </div>
      <p class="ayuda separado-poco" id="ayuda-portada">
        Te decimos si está en el registro de reclutadores autorizados del Ministerio de Trabajo.
        No necesitás cuenta ni dar ningún dato tuyo.
      </p>
    </form>

    <div class="acciones">
      <a class="boton boton--principal" href="/ofertas.php">Ver las ofertas verificadas</a>
      <a class="boton boton--secundario" href="/alertas.php">Ver señales de estafa</a>
    </div>
  </section>

  <?php if ($cifras !== null): ?>
    <section class="cifras" aria-label="La plataforma hoy">
      <p class="cifra">
        <span class="cifra__numero"><?= (int) $cifras['ofertas'] ?></span>
        <span class="cifra__texto">
          <?= $cifras['ofertas'] === 1 ? 'oferta con origen verificado, disponible hoy' : 'ofertas con origen verificado, disponibles hoy' ?>
        </span>
      </p>
      <p class="cifra">
        <span class="cifra__numero"><?= (int) $cifras['reclutadores'] ?></span>
        <span class="cifra__texto">reclutadores en nuestra copia del registro del Ministerio</span>
      </p>
      <p class="cifra">
        <span class="cifra__numero cifra__numero--fecha"><?= escapar(fecha_en_palabras($cifras['actualizado'])) ?></span>
        <span class="cifra__texto">última vez que actualizamos esa copia</span>
      </p>
    </section>
  <?php endif; ?>

  <section class="pila">
    <h2 class="subtitulo">Cómo te puede servir</h2>
    <ol class="pasos pasos--fila">
      <li class="paso">
        <div>
          <p class="paso__titulo">Mirá ofertas verificadas</p>
          <p class="paso__texto">
            Cada una dice de dónde salió, quién la ofrece y hasta cuándo está disponible.
            No hace falta cuenta.
          </p>
        </div>
      </li>
      <li class="paso">
        <div>
          <p class="paso__titulo">Comprobá a quien te contactó</p>
          <p class="paso__texto">
            Si te escribieron por fuera, buscá el nombre en el registro de reclutadores
            autorizados antes de dar datos o dinero.
          </p>
        </div>
      </li>
      <li class="paso">
        <div>
          <p class="paso__titulo">Si querés, creá tu cuenta</p>
          <p class="paso__texto">
            Contanos qué sabés hacer y te mostramos las ofertas que coinciden, con la
            explicación de por qué.
          </p>
        </div>
      </li>
    </ol>
    <?php if (!hay_sesion()): ?>
      <p><a class="boton boton--secundario" href="/cuenta/registrarse.php">Crear mi cuenta</a></p>
    <?php endif; ?>
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

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
