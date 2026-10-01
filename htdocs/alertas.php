<?php
/**
 * SEÑALES DE ALERTA Y DÓNDE DENUNCIAR
 * =================================================================
 * Pensada para que sirva incluso a quien llegó acá por una oferta que
 * NO salió de esta plataforma. Por eso no pide cuenta, no pide nada, y
 * está enlazada desde la barra de arriba en todas las páginas: tiene
 * que verse sin buscarla.
 *
 * -----------------------------------------------------------------
 * OJO, PARA QUIEN MANTENGA ESTO
 * -----------------------------------------------------------------
 * Los números de teléfono y las instituciones de la sección "dónde
 * denunciar" hay que VERIFICARLOS antes de publicar el sitio, y
 * revisarlos cada cierto tiempo. Un número equivocado acá no es un
 * detalle: es alguien en problemas llamando a un teléfono que no
 * contesta. Si no se pueden verificar, es mejor dejar solo el nombre
 * de la institución que dar un número dudoso.
 */

require __DIR__ . '/app/nucleo/inicio.php';

$titulo_pagina = 'Cómo reconocer una estafa';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Cómo reconocer una oferta falsa</h1>
    <p class="texto-guia">
      Estas son las señales que más se repiten en las estafas con ofertas de trabajo en el
      extranjero. Si ves una sola de estas, pará y averiguá antes de seguir.
    </p>
  </section>

  <!-- Numeradas y en ámbar, no en rojo: son señales para frenar y
       averiguar. Una pared de cajas rojas asusta y se deja de leer. -->
  <ol class="senales">
    <?php foreach (SENALES_ALERTA as $senal): ?>
      <li class="senal">
        <div>
          <p class="senal__titulo"><?= escapar($senal['titulo']) ?></p>
          <p class="senal__texto"><?= escapar($senal['texto']) ?></p>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Lo más importante de todo</h2>
    <p>
      <strong>Un reclutador no puede cobrarle dinero al trabajador. Nunca, por ningún
      concepto.</strong> Está prohibido por la ley, aunque el reclutador esté autorizado.
    </p>
    <p>
      Si alguien te pide dinero para conseguirte trabajo en el extranjero, no importa cuán
      convincente sea, cuánta prisa te meta ni qué papeles te enseñe: es una estafa.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Antes de dar tus datos, comprobá quién te está hablando</h2>
    <p>
      Podés escribir acá el nombre de la empresa o del reclutador y te decimos si aparece en el
      registro de reclutadores autorizados del Ministerio de Trabajo. No hace falta cuenta.
    </p>
    <div class="acciones">
      <a class="boton boton--principal" href="/verificador.php">Comprobar un reclutador</a>
    </div>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Si ya entregaste dinero o documentos</h2>
    <p>
      No es tu culpa. Estas redes están hechas para engañar a gente que está buscando salir
      adelante, y son muy buenas en eso.
    </p>
    <ul class="pila">
      <li><strong>No sigas mandando dinero</strong>, aunque te digan que ya falta poco o que si no perdés lo que pagaste.</li>
      <li><strong>Guardá todo</strong>: los mensajes, los números de teléfono, los recibos, los nombres, las capturas de pantalla. Sirven para la denuncia.</li>
      <li><strong>Contale a alguien de confianza</strong>. La vergüenza es lo que hace que estas estafas se sigan repitiendo sin que nadie las denuncie.</li>
      <li><strong>Denunciá</strong>, aunque creas que ya no vas a recuperar el dinero. La denuncia sirve para que no le pase a otro.</li>
    </ul>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Dónde denunciar</h2>

    <dl class="lista-datos">
      <dt>Si hay alguien en peligro ahora mismo</dt>
      <dd>Policía Nacional Civil: <a class="telefono" href="tel:110">110</a></dd>

      <dt>Si sospechás que es un caso de trata de personas</dt>
      <dd>
        Secretaría contra la Violencia Sexual, Explotación y Trata de Personas (SVET)<br>
        Línea de denuncia: <a class="telefono" href="tel:1546">1546</a>
      </dd>

      <dt>Para denunciar el delito</dt>
      <dd>
        Ministerio Público. Podés ir a cualquier fiscalía o agencia del MP.
        Llevá todo lo que hayas guardado.
      </dd>

      <dt>Si es un reclutador que está operando irregularmente</dt>
      <dd>
        Ministerio de Trabajo y Previsión Social, Dirección General de Empleo.
        Es la institución que autoriza y supervisa a los reclutadores.
      </dd>

      <dt>Si sentís que se violaron tus derechos</dt>
      <dd>Procuraduría de los Derechos Humanos (PDH)</dd>
    </dl>

    <p class="texto-menor">
      Denunciar no te pone en riesgo por tu situación migratoria: estas instituciones reciben
      la denuncia igual.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">¿Viste algo raro en una oferta de esta plataforma?</h2>
    <p>
      Avisanos. Revisamos cada aviso a mano, uno por uno. Ninguna oferta se retira sola por
      recibir reportes: eso permitiría que cualquiera tumbara ofertas legítimas reportándolas
      en masa. Las revisa una persona.
    </p>
    <div class="acciones">
      <a class="boton boton--secundario" href="/ofertas.php">Ver las ofertas</a>
    </div>
  </section>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
