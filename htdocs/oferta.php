<?php
/**
 * UNA OFERTA
 * -----------------------------------------------------------------
 * Usa buscar_oferta_publica(), que aplica exactamente la misma
 * condición que el listado. Es lo que impide el agujero clásico:
 * escribir a mano el número de una oferta que todavía no se verificó,
 * o que ya se retiró, y verla igual.
 *
 * Si la oferta no se puede mostrar, no se dice "no existe" a secas:
 * se explica que pudo haberse vencido o retirado, porque casi siempre
 * es eso y la persona necesita entender qué pasó.
 */

require __DIR__ . '/app/nucleo/inicio.php';

$id = id_valido(parametro('id'));
$oferta = $id === null ? null : buscar_oferta_publica($id);

// Guardar o quitar de guardadas. Solo tiene sentido con la sesión
// abierta, y de todos modos se comprueba acá: si alguien manda el
// formulario sin sesión, no pasa nada.
if (es_post() && $oferta !== null && hay_sesion() && rol_actual() === ROL_USUARIO) {
    $usuario_id = (int) id_usuario_actual();

    if (campo('accion') === 'guardar') {
        guardar_oferta($usuario_id, (int) $oferta['id']);
        guardar_mensaje('exito', 'La guardamos. La vas a encontrar en "Ofertas que guardé".');
    } elseif (campo('accion') === 'quitar') {
        quitar_oferta_guardada($usuario_id, (int) $oferta['id']);
        guardar_mensaje('exito', 'La quitamos de tus guardadas.');
    }

    redirigir('/oferta.php?id=' . (int) $oferta['id']);
}

if ($oferta === null) {
    http_response_code(404);
    $titulo_pagina = 'Esta oferta ya no está disponible';
    $texto = 'Puede que se haya vencido, que la institución la haya retirado, o que el enlace '
           . 'esté incompleto. Buscá en las ofertas disponibles: puede haber otra parecida.';
    require RAIZ_APP . '/vistas/pagina_aviso.php';
    exit;
}

$idiomas = idiomas_de_oferta((int) $oferta['id']);

$titulo_pagina = $oferta['titulo'];
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila">

  <a class="volver" href="/ofertas.php"><?= icono('volver') ?> Volver a las ofertas</a>

  <!-- En el teléfono todo va en una columna y en este orden: título,
       sello con sus datos y botones, y después el detalle. En pantalla
       ancha, el sello y los botones pasan a la columna de la derecha. -->
  <div class="detalle">

    <section class="detalle__cabeza">
      <div class="chips">
        <p class="chip"><?= icono('ubicacion') ?> <?= escapar(lugar_de_oferta($oferta)) ?></p>
        <p class="chip"><?= icono('etiqueta') ?> <?= escapar($oferta['rubro_nombre']) ?></p>
      </div>
      <h1 class="titulo-pagina"><?= escapar($oferta['titulo']) ?></h1>
      <p class="oferta__empleador"><?= icono('edificio') ?> <?= escapar($oferta['empleador']) ?></p>
    </section>

    <div class="detalle__lado">

      <?php require RAIZ_APP . '/vistas/sello_verificacion.php'; ?>

      <?php
      // D-058: hay ofertas a las que se postula en la página oficial de
      // la empresa. Para esas, el botón lleva ahí, y se dice a qué
      // dirección lleva, para que la persona pueda comprobar que es la
      // de la empresa y no otra.
      $postulacion_externa = $oferta['forma_postulacion'] === 'externa' && url_segura($oferta['url_original']);
      $dominio_empresa     = $postulacion_externa ? (string) parse_url($oferta['url_original'], PHP_URL_HOST) : '';
      ?>

      <?php if ($postulacion_externa): ?>
        <section class="tarjeta pila">
          <a class="boton boton--principal boton--ancho" href="<?= escapar($oferta['url_original']) ?>"
             target="_blank" rel="noopener noreferrer nofollow">
            <?= icono('enviar') ?> Postularme en la página de la empresa
          </a>
          <p class="texto-menor">
            Te lleva a <strong><?= escapar($dominio_empresa) ?></strong>, la página oficial de
            <?= escapar($oferta['empleador']) ?>. Ahí le mandás tus datos directamente a la empresa:
            esta plataforma no recibe ni guarda nada de esa postulación.
            <strong>Nadie te puede cobrar por postularte.</strong>
          </p>

          <?php if (hay_sesion() && rol_actual() === ROL_USUARIO): ?>
            <?php $guardada = esta_guardada((int) id_usuario_actual(), (int) $oferta['id']); ?>
            <form method="post" action="/oferta.php?id=<?= (int) $oferta['id'] ?>">
              <?php campo_csrf(); ?>
              <input type="hidden" name="accion" value="<?= $guardada ? 'quitar' : 'guardar' ?>">
              <button class="boton boton--secundario boton--ancho" type="submit">
                <?= icono('guardar') ?> <?= $guardada ? 'Quitar de mis guardadas' : 'Guardar para verla después' ?>
              </button>
            </form>
          <?php elseif (!hay_sesion()): ?>
            <p class="texto-menor">
              Si querés guardarla para verla después, <a href="/cuenta/entrar.php">entrá a tu cuenta</a>.
            </p>
          <?php endif; ?>
        </section>

      <?php elseif (hay_sesion() && rol_actual() === ROL_USUARIO): ?>
        <?php
        $guardada  = esta_guardada((int) id_usuario_actual(), (int) $oferta['id']);
        $postulado = ya_se_postulo((int) id_usuario_actual(), (int) $oferta['id']);
        ?>
        <section class="tarjeta pila">
          <?php if ($postulado): ?>
            <a class="boton boton--secundario boton--ancho" href="/cuenta/postulaciones.php">
              <?= icono('listo') ?> Ya te postulaste · ver mis postulaciones
            </a>
          <?php else: ?>
            <a class="boton boton--principal boton--ancho" href="/cuenta/postular.php?id=<?= (int) $oferta['id'] ?>">
              <?= icono('enviar') ?> Postularme a esta oferta
            </a>
          <?php endif; ?>

          <form method="post" action="/oferta.php?id=<?= (int) $oferta['id'] ?>">
            <?php campo_csrf(); ?>
            <input type="hidden" name="accion" value="<?= $guardada ? 'quitar' : 'guardar' ?>">
            <button class="boton boton--secundario boton--ancho" type="submit">
              <?= icono('guardar') ?> <?= $guardada ? 'Quitar de mis guardadas' : 'Guardar para verla después' ?>
            </button>
          </form>

          <?php if (!$postulado): ?>
            <p class="texto-menor">
              Postularte no comparte nada todavía: primero te vamos a mostrar exactamente qué se
              comparte y con quién, y vos decidís.
            </p>
          <?php endif; ?>
        </section>

      <?php elseif (!hay_sesion()): ?>
        <section class="tarjeta pila">
          <p>Para guardar esta oferta o postularte, entrá a tu cuenta.</p>
          <div class="acciones">
            <a class="boton boton--principal" href="/cuenta/entrar.php"><?= icono('persona') ?> Entrar</a>
            <a class="boton boton--secundario" href="/cuenta/registrarse.php">Crear mi cuenta</a>
          </div>
        </section>
      <?php endif; ?>

    </div>

    <div class="detalle__principal">

      <section class="tarjeta pila">
        <h2 class="tarjeta__titulo">En qué consiste el trabajo</h2>
        <p><?= nl2br(escapar($oferta['descripcion'])) ?></p>

        <?php if (!empty($oferta['salario_texto'])): ?>
          <p class="pago"><?= icono('dinero') ?> Pago: <?= escapar($oferta['salario_texto']) ?></p>
        <?php endif; ?>
      </section>

      <section class="tarjeta pila">
        <h2 class="tarjeta__titulo">Qué se pide</h2>
        <dl class="datos-oferta">
          <div class="dato">
            <dt><?= icono('etiqueta') ?> Oficio</dt>
            <dd><?= escapar($oferta['rubro_nombre']) ?></dd>
          </div>

          <div class="dato">
            <dt><?= icono('reloj') ?> Experiencia</dt>
            <dd>
              <?php if ((int) $oferta['experiencia_anios_min'] === 0): ?>
                No se pide experiencia previa
              <?php else: ?>
                <?= (int) $oferta['experiencia_anios_min'] ?>
                año<?= (int) $oferta['experiencia_anios_min'] === 1 ? '' : 's' ?> de experiencia
              <?php endif; ?>
            </dd>
          </div>

          <div class="dato">
            <dt><?= icono('estudios') ?> Estudios</dt>
            <dd><?= escapar(NIVELES_ESTUDIO[$oferta['estudios_min']] ?? $oferta['estudios_min']) ?></dd>
          </div>

          <div class="dato">
            <dt><?= icono('idioma') ?> Idiomas</dt>
            <dd>
              <?php if ($idiomas === []): ?>
                No se pide ningún idioma en particular
              <?php else: ?>
                <?php
                $nombres = [];
                foreach ($idiomas as $codigo) {
                    $nombres[] = IDIOMAS[$codigo] ?? $codigo;
                }
                echo escapar(implode(', ', $nombres));
                ?>
              <?php endif; ?>
            </dd>
          </div>

          <div class="dato dato--ancho">
            <dt><?= icono('calendario') ?> Cuándo hay que estar disponible</dt>
            <dd><?= escapar(DISPONIBILIDAD[$oferta['disponibilidad_requerida']] ?? $oferta['disponibilidad_requerida']) ?></dd>
          </div>

          <?php if (!empty($oferta['requisitos'])): ?>
            <div class="dato dato--ancho">
              <dt><?= icono('lista') ?> Otros requisitos</dt>
              <dd><?= nl2br(escapar($oferta['requisitos'])) ?></dd>
            </div>
          <?php endif; ?>
        </dl>
      </section>

      <!-- Este aviso va en la página de cada oferta y no escondido en una
           sección aparte: tiene que verse sin buscarlo, justo cuando la
           persona está por escribirle a alguien. -->
      <section class="aviso aviso--aviso pila">
        <h2 class="tarjeta__titulo">Antes de contactar a alguien</h2>
        <ul class="pila">
          <li>
            <strong>Nadie puede cobrarte por darte trabajo.</strong> Un reclutador autorizado
            tiene prohibido cobrarle al trabajador. Si te piden dinero por adelantado, por el
            trámite o por "apartar el cupo", es una estafa.
          </li>
          <li>
            <strong>No mandés fotos de tu DPI ni de tu pasaporte</strong> antes de una entrevista
            formal con la empresa.
          </li>
          <li>
            <strong>Desconfiá si solo te hablan por WhatsApp</strong> y no hay una empresa con
            nombre, dirección y teléfono que puedas comprobar.
          </li>
        </ul>
      </section>

      <?php if (!$postulacion_externa && url_segura($oferta['url_original'])): ?>
        <section class="tarjeta pila">
          <h2 class="tarjeta__titulo">La publicación original</h2>
          <p class="texto-menor">
            Este enlace lleva fuera de la plataforma, al sitio de donde se tomó la oferta.
          </p>
          <p>
            <a class="boton boton--secundario" href="<?= escapar($oferta['url_original']) ?>"
               target="_blank" rel="noopener noreferrer nofollow">
              <?= icono('enlace') ?> Abrir la publicación original
            </a>
          </p>
        </section>
      <?php endif; ?>

    </div>

    <div class="detalle__extra">

      <?php
      // Compartir: esta población se pasa las ofertas por WhatsApp. Si la
      // oferta circula CON el enlace a esta página, quien la recibe puede
      // abrirla y ver de dónde salió, que es lo contrario a la cadena sin
      // origen de la que estamos protegiendo a la gente.
      $url_oferta     = SITIO_URL . '/oferta.php?id=' . (int) $oferta['id'];
      $dominio_sitio  = (string) parse_url(SITIO_URL, PHP_URL_HOST);
      $texto_whatsapp = 'Mirá esta oferta de trabajo con origen verificado: '
                      . $oferta['titulo'] . ' — ' . $url_oferta;
      ?>
      <section class="tarjeta pila">
        <h2 class="tarjeta__titulo">¿Le puede servir a alguien más?</h2>
        <div class="compartir" data-compartir-url="<?= escapar($url_oferta) ?>"
             data-compartir-titulo="<?= escapar($oferta['titulo']) ?>">
          <a class="boton boton--secundario" href="https://wa.me/?text=<?= escapar(rawurlencode($texto_whatsapp)) ?>"
             target="_blank" rel="noopener noreferrer">
            <?= icono('mensaje') ?> Mandar por WhatsApp
          </a>
          <span class="compartir__estado" aria-live="polite"></span>
        </div>
        <p class="texto-menor">
          Si alguien te manda una oferta diciendo que es de esta plataforma, abrí el enlace y fijate
          que la dirección empiece con <strong><?= escapar($dominio_sitio) ?></strong>.
          Si no, no es nuestra.
        </p>
      </section>

      <section class="tarjeta pila">
        <h2 class="tarjeta__titulo">¿Viste algo raro en esta oferta?</h2>
        <p>
          Si te pidieron dinero, si los datos no cuadran, o si algo no te da confianza, avisanos.
          Lo revisa una persona.
        </p>
        <div class="acciones">
          <?php if (hay_sesion() && rol_actual() === ROL_USUARIO): ?>
            <a class="boton boton--secundario" href="/reportar.php?id=<?= (int) $oferta['id'] ?>">
              <?= icono('bandera') ?> Reportar esta oferta
            </a>
          <?php else: ?>
            <a class="boton boton--secundario" href="/cuenta/entrar.php"><?= icono('bandera') ?> Entrar para reportarla</a>
          <?php endif; ?>
          <a class="boton boton--secundario" href="/verificador.php"><?= icono('verificar') ?> Comprobar al reclutador</a>
        </div>
      </section>

    </div>

  </div>

  <p class="separado"><a class="boton boton--secundario" href="/ofertas.php"><?= icono('maletin') ?> Ver más ofertas</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
