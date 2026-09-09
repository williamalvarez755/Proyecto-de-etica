<?php
/**
 * VERIFICADOR DE RECLUTADORES
 * =================================================================
 * La función que diferencia a este proyecto de cualquier bolsa de
 * empleo, y la que sirve incluso a quien nunca va a subir un
 * currículum: alguien recibe un mensaje por WhatsApp, escribe acá el
 * nombre de quien lo contactó, y averigua si está en el registro de
 * reclutadores autorizados del Ministerio de Trabajo.
 *
 * Todo se resuelve contra nuestra propia base de datos. Ni una llamada
 * externa, así que funciona bien en este hosting y no se cae porque
 * un tercero se cayó (regla 11).
 *
 * -----------------------------------------------------------------
 * LO MÁS DELICADO DE TODA LA PLATAFORMA ESTÁ ACÁ, Y ES EL LENGUAJE
 * -----------------------------------------------------------------
 * "No aparece en el registro" NO significa "es una estafa", y decirlo
 * así sería mentir en la dirección contraria: el registro es solo de
 * reclutadores autorizados, o sea intermediarios. Una empresa que
 * contrata directamente a alguien no tiene por qué estar ahí.
 *
 * Si dijéramos "no está en el registro, es falso", estaríamos
 * acusando a empleadores legítimos y, peor todavía, enseñándole a la
 * gente a confiar en un sello que no significa lo que cree.
 *
 * Entonces se dice exactamente lo que sabemos: que no lo encontramos,
 * qué significa eso y qué no, y qué puede hacer para averiguar más.
 * Ni una palabra de más.
 */

require __DIR__ . '/app/nucleo/inicio.php';

$busqueda   = limpiar_texto(parametro('nombre'));
$resultado  = null;
$demasiadas = false;

if ($busqueda !== '') {
    if (mb_strlen($busqueda) > 120) {
        $busqueda = mb_substr($busqueda, 0, 120, 'UTF-8');
    }

    // Límite por conexión: evita que alguien recorra el registro
    // entero automáticamente y, de paso, cuida el tope diario de
    // peticiones del hosting.
    if (contar_intentos_por_ip('verificador', 60) >= VERIFICADOR_MAX_CONSULTAS_HORA) {
        $demasiadas = true;
    } else {
        // Se registra la CONSULTA, no lo que la persona buscó: el
        // identificador es la conexión. Saber qué nombres consulta la
        // gente no nos hace falta para nada, y guardarlo sería juntar
        // datos que no necesitamos (regla 4).
        registrar_intento('verificador', ip_cliente(), true);

        $resultado = buscar_en_registro($busqueda);
    }
}

$total_registro = contar_reclutadores();
$actualizado    = fecha_actualizacion_registro();

$titulo_pagina = 'Verificar un reclutador';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">¿Quién te contactó está autorizado?</h1>
    <p class="texto-guia">
      Escribí el nombre de la empresa o de la persona que te ofreció el trabajo, y te decimos
      si aparece en el registro de reclutadores autorizados del Ministerio de Trabajo.
      No necesitás cuenta ni dar ningún dato tuyo.
    </p>
  </section>

  <form method="get" action="/verificador.php" class="tarjeta">
    <div class="campo">
      <label class="etiqueta" for="nombre">Nombre de la empresa o del reclutador</label>
      <span class="ayuda" id="ayuda-nombre">
        Escribilo como te lo dijeron. No importan las mayúsculas ni las tildes.
      </span>
      <input class="entrada" type="search" id="nombre" name="nombre" maxlength="120"
             aria-describedby="ayuda-nombre" value="<?= escapar($busqueda) ?>" required>
    </div>
    <div class="acciones separado">
      <button class="boton boton--principal boton--ancho" type="submit">Buscar en el registro</button>
    </div>
  </form>

  <?php if ($demasiadas): ?>

    <p class="aviso aviso--aviso">
      Se hicieron muchas consultas desde esta conexión en la última hora. Esperá un rato
      y volvé a intentar.
    </p>

  <?php elseif ($resultado !== null): ?>

    <?php if ($resultado['exactas'] !== []): ?>

      <?php foreach ($resultado['exactas'] as $reclutador): ?>
        <?php $vigente = reclutador_vigente_hoy($reclutador); ?>

        <section class="<?= $vigente ? 'verificacion' : 'aviso aviso--error' ?>">

          <?php if ($vigente): ?>
            <p class="sello">
              <span aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3z"></path>
                  <path d="m9 12 2 2 4-4"></path>
                </svg>
              </span>
              Sí aparece en el registro
            </p>
          <?php else: ?>
            <p><strong>Aparece en el registro, pero su autorización NO está vigente.</strong></p>
          <?php endif; ?>

          <dl class="lista-datos separado">
            <dt>Nombre en el registro</dt>
            <dd><?= escapar($reclutador['nombre']) ?></dd>

            <?php if (!empty($reclutador['numero_registro'])): ?>
              <dt>Número de registro</dt>
              <dd><?= escapar($reclutador['numero_registro']) ?></dd>
            <?php endif; ?>

            <dt>Estado de la autorización</dt>
            <dd><?= escapar(ESTADOS_RECLUTADOR[$reclutador['estado']] ?? $reclutador['estado']) ?></dd>

            <?php if (!empty($reclutador['vigencia_hasta'])): ?>
              <dt>Vigente hasta</dt>
              <dd><?= escapar(fecha_en_palabras($reclutador['vigencia_hasta'])) ?></dd>
            <?php endif; ?>

            <dt>De dónde sacamos este dato</dt>
            <dd><?= escapar($reclutador['fuente_registro']) ?></dd>
          </dl>

          <?php if ($vigente): ?>
            <p class="separado">
              <strong>Que esté autorizado no quiere decir que la oferta sea buena, ni que vayas
              a conseguir el trabajo.</strong> Quiere decir que esta persona o empresa está
              inscrita para reclutar. Aunque esté autorizada, <strong>sigue sin poder cobrarte
              dinero</strong>: eso está prohibido por ley siempre.
            </p>
          <?php else: ?>
            <p>
              Que su autorización esté vencida o suspendida es un motivo serio para no seguir
              adelante sin averiguar más. Consultá directamente en el Ministerio de Trabajo
              antes de entregar cualquier dato o documento.
            </p>
          <?php endif; ?>

        </section>
      <?php endforeach; ?>

    <?php elseif ($resultado['parecidas'] !== []): ?>

      <section class="aviso aviso--aviso pila">
        <p><strong>No encontramos ese nombre exacto, pero hay parecidos en el registro.</strong></p>
        <p>Fijate bien si alguno es el mismo. Un nombre parecido no es el mismo nombre:</p>
        <ul>
          <?php foreach ($resultado['parecidas'] as $reclutador): ?>
            <li>
              <?= escapar($reclutador['nombre']) ?>
              — <?= escapar(ESTADOS_RECLUTADOR[$reclutador['estado']] ?? $reclutador['estado']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="texto-menor">
          Que el nombre se parezca es justamente una técnica de estafa: usar un nombre casi
          igual al de una empresa real. Comprobalo bien antes de seguir.
        </p>
      </section>

    <?php else: ?>

      <section class="aviso aviso--aviso pila">
        <p><strong>No encontramos ese nombre en el registro.</strong></p>

        <p>
          <strong>Ojo con lo que esto significa.</strong> No quiere decir que sea una estafa.
          El registro es solo de <strong>reclutadores autorizados</strong>, es decir de quienes
          hacen de intermediarios. Una empresa que contrata directo a sus trabajadores no tiene
          por qué estar en esa lista.
        </p>

        <p>
          Lo que sí quiere decir es que <strong>nosotros no podemos confirmarte nada</strong>
          sobre esta persona o empresa. Antes de dar tus documentos o cualquier dinero,
          revisá las señales de abajo y consultá en el Ministerio de Trabajo.
        </p>
      </section>

    <?php endif; ?>

    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Revisá estas señales, aparezca o no en el registro</h2>
      <p class="texto-menor">
        Estas cosas son señal de estafa aunque quien te contactó esté autorizado.
      </p>
      <ul class="pila">
        <?php foreach (SENALES_ALERTA as $senal): ?>
          <li><strong><?= escapar($senal['titulo']) ?></strong></li>
        <?php endforeach; ?>
      </ul>
      <div class="acciones">
        <a class="boton boton--secundario" href="/alertas.php">Ver qué significa cada una</a>
      </div>
    </section>

  <?php endif; ?>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">De dónde salen estos datos</h2>
    <p>
      El registro que consultamos lo carga a mano el personal de la institución, a partir del
      listado público de reclutadores autorizados del Ministerio de Trabajo.
    </p>
    <p class="texto-menor">
      <?php if ($total_registro === 0): ?>
        Todavía no hay reclutadores cargados en el registro. Mientras tanto, esta búsqueda no
        puede confirmarte nada.
      <?php else: ?>
        Hay <?= $total_registro ?> reclutador<?= $total_registro === 1 ? '' : 'es' ?> cargado<?= $total_registro === 1 ? '' : 's' ?>.
        Última actualización: <?= escapar(fecha_en_palabras($actualizado)) ?>.
        Si el listado del Ministerio cambió después de esa fecha, acá todavía no se refleja.
      <?php endif; ?>
    </p>
  </section>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
