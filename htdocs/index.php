<?php
/**
 * PORTADA
 * -----------------------------------------------------------------
 * Dice qué es la plataforma, qué no es, y en qué punto va.
 * Solo enseña lo que ya funciona: no hay botones que no lleven a
 * ninguna parte ni secciones anunciadas "para después".
 *
 * Arriba de todo hay dos puertas, y cada una trae su buscador adentro,
 * no un botón que lleva a otra pantalla:
 *
 *  - "Busco trabajo": empleos en Guatemala con origen verificado, para
 *    quien regresó (D-054).
 *  - "Me ofrecieron un trabajo": el verificador de reclutadores. Quien
 *    llega acá suele venir asustada por un mensaje de WhatsApp, y cada
 *    pantalla de más es una oportunidad de que se vaya sin comprobar
 *    nada.
 *
 * Dos puertas con palabras de todos los días, en lugar de un menú: la
 * persona elige lo que le pasa a ella, no una sección del sitio.
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

$es_persona_usuaria = hay_sesion() && rol_actual() === ROL_USUARIO;

$titulo_pagina = 'Ofertas de trabajo con origen verificado';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="portada" aria-labelledby="titulo-portada">
    <?php if (SITIO_LEMA !== ''): ?>
      <p class="portada__lema"><?= icono('mundo', 'icono icono--chico') ?> <?= escapar(SITIO_LEMA) ?></p>
    <?php endif; ?>

    <h1 class="portada__titulo" id="titulo-portada">Antes de creerle a una oferta de trabajo, revisá de dónde viene</h1>

    <p class="portada__texto">
      Si regresaste a Guatemala y buscás trabajo, acá hay empleos tomados solo de fuentes
      oficiales: cada uno dice quién lo ofrece, de dónde salió y cuándo se revisó.
      Y si te ofrecen irte otra vez, comprobalo antes: muchas de esas ofertas son falsas.
    </p>

    <div class="puertas">

      <form class="puerta" method="get" action="/ofertas.php" role="search">
        <div class="puerta__cabeza">
          <span class="puerta__icono"><?= icono('maletin') ?></span>
          <div>
            <h2 class="puerta__titulo">Busco trabajo</h2>
            <p class="puerta__texto">Empleos en Guatemala con origen verificado. No necesitás cuenta.</p>
          </div>
        </div>
        <div>
          <label class="etiqueta" for="texto-portada">¿Qué sabés hacer o qué trabajo buscás?</label>
          <div class="buscador-grande">
            <input class="entrada" type="search" id="texto-portada" name="texto" maxlength="80"
                   placeholder="albañil, cosecha, cocina…">
            <button class="boton boton--principal" type="submit"><?= icono('buscar') ?> Buscar</button>
          </div>
        </div>
        <a class="puerta__enlace" href="/ofertas.php">Ver todas las ofertas <?= icono('flecha', 'icono icono--chico') ?></a>
      </form>

      <form class="puerta" method="get" action="/verificador.php" role="search">
        <div class="puerta__cabeza">
          <span class="puerta__icono puerta__icono--ambar"><?= icono('verificar') ?></span>
          <div>
            <h2 class="puerta__titulo">Me ofrecieron un trabajo</h2>
            <p class="puerta__texto">¿Te ofrecieron trabajo en el extranjero? Comprobá si quien te escribió está autorizado.</p>
          </div>
        </div>
        <div>
          <label class="etiqueta" for="nombre-portada">Nombre de la empresa o del reclutador</label>
          <div class="buscador-grande">
            <input class="entrada" type="search" id="nombre-portada" name="nombre" maxlength="120"
                   placeholder="El nombre que te dieron" aria-describedby="ayuda-portada" required>
            <button class="boton boton--principal" type="submit"><?= icono('escudo') ?> Comprobar</button>
          </div>
          <p class="ayuda separado-poco" id="ayuda-portada">
            Lo buscamos en el registro de reclutadores autorizados del Ministerio de Trabajo.
            No necesitás cuenta ni dar ningún dato tuyo.
          </p>
        </div>
        <a class="puerta__enlace" href="/alertas.php">Ver las señales de una estafa <?= icono('flecha', 'icono icono--chico') ?></a>
      </form>

    </div>
  </section>

  <?php if ($cifras !== null): ?>
    <section class="cifras" aria-label="La plataforma hoy">
      <p class="cifra">
        <span class="cifra__icono cifra__icono--verde"><?= icono('escudo') ?></span>
        <span>
          <span class="cifra__numero"><?= (int) $cifras['ofertas'] ?></span>
          <span class="cifra__texto">
            <?= $cifras['ofertas'] === 1 ? 'oferta con origen verificado, disponible hoy' : 'ofertas con origen verificado, disponibles hoy' ?>
          </span>
        </span>
      </p>
      <p class="cifra">
        <span class="cifra__icono"><?= icono('lista') ?></span>
        <span>
          <span class="cifra__numero"><?= (int) $cifras['reclutadores'] ?></span>
          <span class="cifra__texto">reclutadores en nuestra copia del registro del Ministerio</span>
        </span>
      </p>
      <p class="cifra">
        <span class="cifra__icono"><?= icono('calendario') ?></span>
        <span>
          <span class="cifra__numero cifra__numero--fecha"><?= escapar(fecha_en_palabras($cifras['actualizado'])) ?></span>
          <span class="cifra__texto">última vez que actualizamos esa copia</span>
        </span>
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
          <p class="paso__titulo">Si querés, subí tu currículum</p>
          <p class="paso__texto">
            Contanos qué sabés hacer y te mostramos las ofertas que coinciden, con la
            explicación de por qué.
          </p>
        </div>
      </li>
    </ol>
    <div class="acciones">
      <?php if (!hay_sesion()): ?>
        <a class="boton boton--principal" href="/cuenta/registrarse.php"><?= icono('persona-mas') ?> Crear mi cuenta</a>
        <a class="boton boton--secundario" href="/cuenta/entrar.php">Ya tengo cuenta</a>
      <?php elseif ($es_persona_usuaria): ?>
        <a class="boton boton--principal" href="/cuenta/cv.php"><?= icono('subir') ?> Subir mi currículum</a>
        <a class="boton boton--secundario" href="/cuenta/panel.php">Ir a mi cuenta</a>
      <?php endif; ?>
    </div>
  </section>

  <div class="rejilla rejilla--dos">
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
      <ul class="lista-iconos lista-iconos--no">
        <li><?= icono('prohibido') ?><span><strong>Dinero.</strong> Ni acá, ni por ninguna gestión. Un reclutador autorizado tampoco puede cobrarte.</span></li>
        <li><?= icono('prohibido') ?><span><strong>Fotos de tus documentos:</strong> ni DPI, ni pasaporte, ni visa, ni datos bancarios.</span></li>
        <li><?= icono('prohibido') ?><span><strong>Tu situación migratoria.</strong> No la preguntamos en ninguna parte.</span></li>
      </ul>
      <p class="texto-menor">
        Si alguien que dice representarnos te pide alguna de estas cosas, no es de esta plataforma.
      </p>
    </section>
  </div>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
