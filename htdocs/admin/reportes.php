<?php
/**
 * PANEL — REPORTES DE LOS USUARIOS
 * -----------------------------------------------------------------
 * Acá se revisan los avisos que manda la gente.
 *
 * Fijate en lo que esta pantalla NO hace: no cambia el estado de
 * ninguna oferta. Resolver un reporte y retirar una oferta son dos
 * decisiones distintas, y la segunda se toma en la pantalla de
 * ofertas, a propósito. Así nadie retira una oferta "de paso"
 * mientras limpia la lista de reportes.
 *
 * Cuando un reporte amerita sacar la oferta del aire, desde acá se va
 * a la oferta y se la pone en revisión o se la retira, con su motivo.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('reportes.revisar');

if (es_post() && campo('accion') === 'resolver') {
    $reporte_id = id_valido(campo('reporte_id'));
    $estado     = campo('estado');
    $resolucion = limpiar_texto(campo('resolucion'));

    $reporte = $reporte_id === null ? null : buscar_reporte($reporte_id);

    if ($reporte === null) {
        guardar_mensaje('error', 'Ese reporte no existe.');
    } elseif (!en_catalogo($estado, ESTADOS_REPORTE)) {
        guardar_mensaje('error', 'Ese estado no existe.');
    } elseif (in_array($estado, ['resuelto', 'descartado'], true) && $resolucion === '') {
        guardar_mensaje('error', 'Escribí qué se hizo con el reporte. Eso es lo que queda de registro.');
    } else {
        resolver_reporte($reporte_id, $estado, $resolucion, (int) id_usuario_actual());

        registrar_accion(
            'reporte_revisado',
            'reporte',
            $reporte_id,
            'Quedó como ' . $estado . ($resolucion !== '' ? '. ' . $resolucion : '')
        );

        guardar_mensaje('exito', 'El reporte quedó como "' . ESTADOS_REPORTE[$estado] . '".');
    }

    redirigir('/admin/reportes.php');
}

$estado = parametro('estado');
if (!en_catalogo($estado, ESTADOS_REPORTE)) {
    $estado = '';
}

$total   = contar_reportes($estado);
$paginas = max(1, (int) ceil($total / REPORTES_POR_PAGINA));
$pagina  = entero_en_rango(parametro('pagina', '1'), 1, $paginas) ?? 1;

$reportes = listar_reportes($estado, REPORTES_POR_PAGINA, ($pagina - 1) * REPORTES_POR_PAGINA);
$conteo   = contar_reportes_por_estado();
$ofertas_senaladas = ofertas_con_reportes_pendientes();

$titulo_pagina = 'Reportes';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Reportes de los usuarios</h1>
    <p class="texto-guia">
      Lo que la gente avisó sobre las ofertas. Cada uno lo revisa una persona.
    </p>
  </section>

  <p class="aviso aviso--aviso">
    <strong>Ninguna oferta se retira sola por acumular reportes.</strong>
    Si un reporte amerita sacarla del aire, hay que ir a la oferta y ponerla en revisión o
    retirarla, escribiendo el motivo. Es a propósito: si fuera automático, bastaría con
    reportar en masa para tumbar ofertas legítimas.
  </p>

  <?php if ($ofertas_senaladas !== []): ?>
    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Ofertas con reportes sin resolver</h2>
      <div class="tabla-desliza">
        <table class="tabla">
          <thead><tr><th>Oferta</th><th>Estado</th><th>Reportes</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($ofertas_senaladas as $senalada): ?>
              <tr>
                <td>
                  <?= escapar($senalada['titulo']) ?><br>
                  <span class="texto-menor"><?= escapar($senalada['empleador']) ?></span>
                </td>
                <td>
                  <span class="insignia insignia--<?= escapar($senalada['estado']) ?>">
                    <?= escapar(ESTADOS_OFERTA[$senalada['estado']] ?? $senalada['estado']) ?>
                  </span>
                </td>
                <td><strong><?= (int) $senalada['cuantos'] ?></strong></td>
                <td>
                  <a class="boton boton--secundario"
                     href="/admin/ofertas.php?texto=<?= urlencode($senalada['titulo']) ?>">
                    Ir a la oferta
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <nav class="pestanas" aria-label="Filtrar por estado">
    <a class="pestana <?= $estado === '' ? 'pestana--activa' : '' ?>" href="/admin/reportes.php">
      Todos (<?= array_sum($conteo) ?>)
    </a>
    <?php foreach (ESTADOS_REPORTE as $codigo => $nombre): ?>
      <a class="pestana <?= $estado === $codigo ? 'pestana--activa' : '' ?>"
         href="/admin/reportes.php?estado=<?= escapar($codigo) ?>">
        <?= escapar($nombre) ?> (<?= (int) ($conteo[$codigo] ?? 0) ?>)
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($reportes === []): ?>

    <p class="vacio">
      <?= $estado === '' ? 'Todavía no hay ningún reporte.' : 'No hay reportes en ese estado.' ?>
    </p>

  <?php else: ?>

    <div class="pila">
      <?php foreach ($reportes as $reporte): ?>
        <article class="tarjeta pila">

          <div class="fila-titulo">
            <h2 class="tarjeta__titulo"><?= escapar($reporte['oferta_titulo']) ?></h2>
            <span class="insignia"><?= escapar(ESTADOS_REPORTE[$reporte['estado']] ?? $reporte['estado']) ?></span>
          </div>

          <dl class="lista-datos">
            <dt>Qué reportaron</dt>
            <dd><strong><?= escapar(MOTIVOS_REPORTE[$reporte['motivo']] ?? $reporte['motivo']) ?></strong></dd>

            <?php if (!empty($reporte['descripcion'])): ?>
              <dt>Lo que contaron</dt>
              <dd><?= nl2br(escapar($reporte['descripcion'])) ?></dd>
            <?php endif; ?>

            <dt>Cuándo</dt>
            <dd><?= escapar(fecha_hora_en_palabras($reporte['creado_en'])) ?></dd>

            <dt>Quién lo reportó</dt>
            <dd>
              <?php if ($reporte['reporta_nombre'] !== null): ?>
                <?= escapar($reporte['reporta_nombre']) ?>
                <span class="texto-menor">(<?= escapar($reporte['reporta_correo']) ?>)</span>
              <?php else: ?>
                <span class="texto-menor">La cuenta fue eliminada</span>
              <?php endif; ?>
            </dd>

            <dt>Estado de la oferta ahora</dt>
            <dd>
              <span class="insignia insignia--<?= escapar($reporte['oferta_estado']) ?>">
                <?= escapar(ESTADOS_OFERTA[$reporte['oferta_estado']] ?? $reporte['oferta_estado']) ?>
              </span>
            </dd>

            <?php if (!empty($reporte['resolucion'])): ?>
              <dt>Qué se hizo</dt>
              <dd>
                <?= escapar($reporte['resolucion']) ?><br>
                <span class="texto-menor">
                  <?= escapar($reporte['revisor_nombre'] ?? 'Cuenta eliminada') ?>,
                  el <?= escapar(fecha_hora_en_palabras($reporte['revisado_en'])) ?>
                </span>
              </dd>
            <?php endif; ?>
          </dl>

          <?php if (in_array($reporte['estado'], ['pendiente', 'en_revision'], true)): ?>
            <form method="post" action="/admin/reportes.php" class="pila">
              <?php campo_csrf(); ?>
              <input type="hidden" name="accion" value="resolver">
              <input type="hidden" name="reporte_id" value="<?= (int) $reporte['id'] ?>">

              <div class="campo">
                <label class="etiqueta" for="estado-<?= (int) $reporte['id'] ?>">Qué hacemos con este reporte</label>
                <select class="entrada" id="estado-<?= (int) $reporte['id'] ?>" name="estado" required>
                  <?php foreach (ESTADOS_REPORTE as $codigo => $nombre): ?>
                    <option value="<?= escapar($codigo) ?>" <?= $reporte['estado'] === $codigo ? 'selected' : '' ?>>
                      <?= escapar($nombre) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="campo">
                <label class="etiqueta" for="resolucion-<?= (int) $reporte['id'] ?>">Qué se comprobó y qué se hizo</label>
                <span class="ayuda">Queda registrado. Escribilo pensando en quien lo lea dentro de un año.</span>
                <textarea class="entrada" id="resolucion-<?= (int) $reporte['id'] ?>"
                          name="resolucion" rows="3"></textarea>
              </div>

              <div class="acciones">
                <button class="boton boton--principal" type="submit">Guardar</button>
                <a class="boton boton--secundario" href="/oferta.php?id=<?= (int) $reporte['oferta_id'] ?>" target="_blank" rel="noopener">
                  Ver la oferta como la ve la gente
                </a>
              </div>
            </form>
          <?php endif; ?>

        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($paginas > 1): ?>
      <nav class="acciones" aria-label="Páginas">
        <?php $base = '/admin/reportes.php?' . http_build_query(array_filter(['estado' => $estado])); ?>
        <?php if ($pagina > 1): ?>
          <a class="boton boton--secundario" href="<?= escapar($base . '&pagina=' . ($pagina - 1)) ?>">Anterior</a>
        <?php endif; ?>
        <span class="insignia">Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?>
          <a class="boton boton--secundario" href="<?= escapar($base . '&pagina=' . ($pagina + 1)) ?>">Siguiente</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

  <?php endif; ?>

  <p><a href="/admin/index.php">Volver al panel</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
