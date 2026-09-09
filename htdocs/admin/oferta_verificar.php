<?php
/**
 * PANEL — VERIFICAR UNA OFERTA
 * -----------------------------------------------------------------
 * Esta pantalla existe porque la regla 2 dice que la palabra
 * "verificada" se gana. Si verificar fuera un botón en una lista,
 * en tres meses sería un clic de trámite y la palabra no significaría
 * nada — y esa palabra es lo único que esta plataforma le ofrece a
 * alguien que ya fue estafado antes.
 *
 * Entonces: para verificar hay que marcar, una por una, las
 * comprobaciones que se hicieron. Todas son obligatorias. Cuáles se
 * marcaron queda escrito en la bitácora, con el nombre de quien las
 * marcó y la fecha.
 *
 * Y hay un bloqueo que no se puede saltar: si la oferta viene por un
 * reclutador cuya autorización no está vigente, no se verifica. Punto.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_permiso('ofertas.verificar');

$id     = id_valido(parametro('id'));
$oferta = $id === null ? null : buscar_oferta($id);

if ($oferta === null) {
    abortar(404, 'Esa oferta no existe', 'Puede que la hayan borrado o que el enlace esté mal.');
}

if (!transicion_permitida($oferta['estado'], 'verificada')) {
    abortar(
        400,
        'Esta oferta no se puede verificar ahora',
        'Está en estado "' . (ESTADOS_OFERTA[$oferta['estado']] ?? $oferta['estado'])
        . '", y desde ahí no se puede pasar a verificada.'
    );
}

/**
 * Las comprobaciones. El texto es el que va a leer quien verifica, así
 * que está escrito como una pregunta concreta que se puede contestar
 * mirando algo, no como una fórmula.
 */
const COMPROBACIONES = [
    'fuente'      => 'Entré a la fuente y la oferta está publicada ahí de verdad.',
    'empleador'   => 'Comprobé que el empleador existe: tiene nombre, y una dirección o un teléfono que se puede verificar.',
    'sin_pago'    => 'La oferta no le pide dinero al trabajador por ningún concepto: ni trámite, ni viaje, ni "apartar el cupo".',
    'sin_papeles' => 'La oferta no pide fotos de DPI, pasaporte, visa ni datos bancarios antes de una entrevista formal.',
    'datos'       => 'Los datos que cargamos (empleador, país, oficio, fechas) coinciden con la publicación original.',
];

// Si hay reclutador de por medio, se agrega una comprobación más.
$reclutador = $oferta['reclutador_id'] === null ? null : buscar_reclutador((int) $oferta['reclutador_id']);
$comprobaciones = COMPROBACIONES;
if ($reclutador !== null) {
    $comprobaciones['reclutador'] =
        'Confirmé que el reclutador está en el registro del Ministerio de Trabajo y su autorización está vigente.';
}

$reclutador_bloquea = $reclutador !== null && !reclutador_vigente_hoy($reclutador);

$errores = [];

if (es_post() && !$reclutador_bloquea) {
    $marcadas = campo_lista('comprobaciones');
    $faltan   = [];

    foreach ($comprobaciones as $clave => $texto) {
        if (!in_array($clave, $marcadas, true)) {
            $faltan[] = $clave;
        }
    }

    if ($faltan !== []) {
        $errores['comprobaciones'] = 'Faltan comprobaciones por marcar. Para verificar hay que haberlas hecho todas.';
    } else {
        marcar_verificada((int) $oferta['id'], (int) id_usuario_actual());

        registrar_accion(
            'oferta_verificada',
            'oferta',
            (int) $oferta['id'],
            'Comprobó: ' . implode(', ', array_keys($comprobaciones))
        );

        guardar_mensaje(
            'exito',
            'La oferta quedó verificada. Todavía no se ve en el sitio público: falta publicarla.'
        );
        redirigir('/admin/ofertas.php?estado=verificada');
    }
}

$titulo_pagina = 'Verificar la oferta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Verificar la oferta</h1>
    <p class="texto-guia">
      Verificar quiere decir que comprobaste <strong>de dónde viene</strong> esta oferta.
      No que el trabajo sea bueno ni que la persona lo vaya a conseguir.
    </p>
  </section>

  <section class="tarjeta">
    <h2 class="tarjeta__titulo"><?= escapar($oferta['titulo']) ?></h2>
    <dl class="lista-datos">
      <dt>Quién ofrece</dt>
      <dd><?= escapar($oferta['empleador']) ?></dd>

      <dt>Fuente</dt>
      <dd>
        <?= escapar($oferta['fuente_nombre']) ?>
        <?php if (url_segura($oferta['fuente_url'])): ?>
          <br><a href="<?= escapar($oferta['fuente_url']) ?>" target="_blank" rel="noopener noreferrer nofollow">Abrir la fuente</a>
        <?php endif; ?>
      </dd>

      <?php if (url_segura($oferta['url_original'])): ?>
        <dt>Publicación original</dt>
        <dd><a href="<?= escapar($oferta['url_original']) ?>" target="_blank" rel="noopener noreferrer nofollow">Abrir la publicación</a></dd>
      <?php endif; ?>

      <dt>Lugar</dt>
      <dd>
        <?= escapar(PAISES[$oferta['pais_codigo']] ?? $oferta['pais_codigo']) ?><?php
        if (!empty($oferta['ciudad'])): ?>, <?= escapar($oferta['ciudad']) ?><?php endif; ?>
      </dd>

      <dt>Fechas</dt>
      <dd>
        Publicación: <?= escapar(fecha_en_palabras($oferta['fecha_publicacion'])) ?><br>
        Vence: <?= escapar(fecha_en_palabras($oferta['fecha_vencimiento'])) ?>
      </dd>

      <dt>Descripción</dt>
      <dd><?= nl2br(escapar($oferta['descripcion'])) ?></dd>
    </dl>
  </section>

  <?php if ($reclutador !== null): ?>
    <section class="<?= $reclutador_bloquea ? 'aviso aviso--error' : 'tarjeta' ?> pila">
      <h2 class="tarjeta__titulo">El reclutador de esta oferta</h2>
      <p>
        <strong><?= escapar($reclutador['nombre']) ?></strong><br>
        <?php if (!empty($reclutador['numero_registro'])): ?>
          Registro <?= escapar($reclutador['numero_registro']) ?> ·
        <?php endif; ?>
        <?= escapar(ESTADOS_RECLUTADOR[$reclutador['estado']] ?? $reclutador['estado']) ?>
        <?php if (!empty($reclutador['vigencia_hasta'])): ?>
          · vigente hasta el <?= escapar(fecha_en_palabras($reclutador['vigencia_hasta'])) ?>
        <?php endif; ?>
      </p>
      <?php if ($reclutador_bloquea): ?>
        <p>
          <strong>Esta oferta no se puede verificar.</strong>
          La autorización de este reclutador no está vigente. Publicarla sería decirle a la
          persona que comprobamos algo que no es cierto.
        </p>
        <p class="texto-menor">
          Si la autorización se renovó, actualizá primero la ficha del reclutador en el registro.
        </p>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <?php if (!$reclutador_bloquea): ?>
    <form method="post" action="/admin/oferta_verificar.php?id=<?= (int) $oferta['id'] ?>" class="tarjeta pila">
      <?php campo_csrf(); ?>

      <h2 class="tarjeta__titulo">Qué comprobaste</h2>
      <p class="texto-menor">
        Marcá solo lo que hiciste de verdad. Esto queda registrado con tu nombre y la fecha.
      </p>

      <?php if (isset($errores['comprobaciones'])): ?>
        <p class="aviso aviso--error" role="alert"><?= escapar($errores['comprobaciones']) ?></p>
      <?php endif; ?>

      <?php foreach ($comprobaciones as $clave => $texto): ?>
        <label class="casilla casilla--grande">
          <input type="checkbox" name="comprobaciones[]" value="<?= escapar($clave) ?>">
          <span><?= escapar($texto) ?></span>
        </label>
      <?php endforeach; ?>

      <div class="acciones separado">
        <button class="boton boton--principal" type="submit">Marcar como verificada</button>
        <a class="boton boton--secundario" href="/admin/ofertas.php">Cancelar</a>
      </div>
    </form>
  <?php else: ?>
    <p><a class="boton boton--secundario" href="/admin/ofertas.php">Volver a las ofertas</a></p>
  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
