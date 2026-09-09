<?php
/**
 * PANEL — LISTADO DE OFERTAS Y CAMBIOS DE ESTADO
 * -----------------------------------------------------------------
 * Acá vive el ciclo de vida de una oferta. Tres cosas lo gobiernan, y
 * las tres se comprueban en el servidor aunque el botón no se vea:
 *
 *  1. La transición tiene que estar permitida (TRANSICIONES_OFERTA).
 *     No se puede saltar de 'pendiente' a 'publicada' sin pasar por
 *     la verificación, ni "despublicar" una oferta retirada.
 *
 *  2. Cada destino pide su propio permiso. Un administrador puede
 *     tener permiso de verificar y no de publicar.
 *
 *  3. Para publicar, la oferta tiene que estar completa (regla 1):
 *     verificada, con fuente, con fecha de publicación y con fecha de
 *     vencimiento que no haya pasado. Si le falta algo, no se publica
 *     y se dice qué le falta.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('ofertas.ver');

/**
 * Qué permiso hace falta para llevar una oferta a cada estado, y qué
 * se escribe en la bitácora cuando se logra.
 */
const PERMISO_POR_ESTADO = [
    'publicada'   => ['ofertas.publicar',  'oferta_publicada'],
    'retirada'    => ['ofertas.retirar',   'oferta_retirada'],
    'vencida'     => ['ofertas.retirar',   'oferta_vencida'],
    'en_revision' => ['ofertas.retirar',   'oferta_en_revision'],
    'pendiente'   => ['ofertas.verificar', 'oferta_estado_cambiado'],
    'verificada'  => ['ofertas.verificar', 'oferta_verificada'],
];

if (es_post() && campo('accion') === 'cambiar_estado') {

    $oferta_id    = id_valido(campo('oferta_id'));
    $nuevo_estado = campo('nuevo_estado');
    $motivo       = limpiar_texto(campo('motivo'));

    $oferta = $oferta_id === null ? null : buscar_oferta($oferta_id);

    if ($oferta === null) {
        guardar_mensaje('error', 'Esa oferta no existe.');

    } elseif (!en_catalogo($nuevo_estado, ESTADOS_OFERTA)) {
        guardar_mensaje('error', 'Ese estado no existe.');

    } elseif (!transicion_permitida($oferta['estado'], $nuevo_estado)) {
        guardar_mensaje(
            'error',
            'No se puede pasar de "' . ESTADOS_OFERTA[$oferta['estado']] . '" a "'
            . ESTADOS_OFERTA[$nuevo_estado] . '".'
        );

    } else {
        // El permiso se exige acá, del lado del servidor, aunque el
        // botón correspondiente no se le haya mostrado a esta cuenta.
        [$permiso, $accion_bitacora] = PERMISO_POR_ESTADO[$nuevo_estado];
        requerir_permiso($permiso);

        $problema = $nuevo_estado === 'publicada' ? motivo_para_no_publicar($oferta) : null;

        if ($problema !== null) {
            guardar_mensaje('error', 'No se puede publicar todavía. ' . $problema);

        } elseif (($nuevo_estado === 'retirada' || $nuevo_estado === 'en_revision') && $motivo === '') {
            guardar_mensaje('error', 'Escribí por qué la estás retirando o poniendo en revisión.');

        } else {
            // Volver a 'pendiente' significa que se le quita la
            // verificación: no puede quedar el sello de alguien que ya
            // no responde por esta oferta.
            if ($nuevo_estado === 'pendiente') {
                consultar(
                    'UPDATE ofertas SET estado = ?, verificada_en = NULL, verificada_por = NULL,
                            actualizado_en = ? WHERE id = ?',
                    ['pendiente', ahora(), $oferta_id]
                );
            } else {
                cambiar_estado_oferta($oferta_id, $nuevo_estado, $motivo ?: null);
            }

            registrar_accion(
                $accion_bitacora,
                'oferta',
                $oferta_id,
                'De ' . $oferta['estado'] . ' a ' . $nuevo_estado . ($motivo !== '' ? '. Motivo: ' . $motivo : '')
            );

            guardar_mensaje('exito', 'La oferta quedó en "' . ESTADOS_OFERTA[$nuevo_estado] . '".');
        }
    }

    redirigir('/admin/ofertas.php');
}

// --- Filtros del listado -----------------------------------------
$estado = parametro('estado');
if (!en_catalogo($estado, ESTADOS_OFERTA)) {
    $estado = '';
}
$texto = limpiar_texto(parametro('texto'));

$filtros = ['estado' => $estado, 'texto' => $texto];

$total   = contar_ofertas_admin($filtros);
$paginas = max(1, (int) ceil($total / OFERTAS_POR_PAGINA));
$pagina  = entero_en_rango(parametro('pagina', '1'), 1, $paginas) ?? 1;

$ofertas = listar_ofertas_admin($filtros, OFERTAS_POR_PAGINA, ($pagina - 1) * OFERTAS_POR_PAGINA);
$conteo  = contar_por_estado();

// Cuántas personas se postularon a cada oferta, en una sola consulta
// en vez de una por oferta.
$postulaciones_por_oferta = contar_postulaciones_por_oferta();

$titulo_pagina = 'Ofertas';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Ofertas</h1>
    <p class="texto-guia">
      Una oferta solo se ve en el sitio público cuando está <strong>publicada</strong>,
      verificada por alguien y con fecha de vencimiento que no haya pasado.
    </p>
    <?php if (tiene_permiso('ofertas.crear')): ?>
      <div class="acciones">
        <a class="boton boton--principal" href="/admin/oferta_editar.php">Cargar una oferta</a>
        <?php if (tiene_permiso('importacion.csv')): ?>
          <a class="boton boton--secundario" href="/admin/importar_csv.php">Importar desde CSV</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Pestañas por estado: lo primero que necesita ver quien
       administra es cuántas ofertas están esperando verificación. -->
  <nav class="pestanas" aria-label="Filtrar por estado">
    <a class="pestana <?= $estado === '' ? 'pestana--activa' : '' ?>" href="/admin/ofertas.php">
      Todas (<?= array_sum($conteo) ?>)
    </a>
    <?php foreach (ESTADOS_OFERTA as $codigo => $nombre): ?>
      <a class="pestana <?= $estado === $codigo ? 'pestana--activa' : '' ?>"
         href="/admin/ofertas.php?estado=<?= escapar($codigo) ?>">
        <?= escapar($nombre) ?> (<?= (int) ($conteo[$codigo] ?? 0) ?>)
      </a>
    <?php endforeach; ?>
  </nav>

  <form method="get" action="/admin/ofertas.php" class="tarjeta">
    <?php if ($estado !== ''): ?>
      <input type="hidden" name="estado" value="<?= escapar($estado) ?>">
    <?php endif; ?>
    <div class="campo">
      <label class="etiqueta" for="texto">Buscar por título o empleador</label>
      <input class="entrada" type="search" id="texto" name="texto" maxlength="80" value="<?= escapar($texto) ?>">
    </div>
    <div class="acciones separado">
      <button class="boton boton--secundario" type="submit">Buscar</button>
    </div>
  </form>

  <?php if ($ofertas === []): ?>

    <div class="vacio pila">
      <?php if ($total === 0 && $estado === '' && $texto === ''): ?>
        <p><strong>Todavía no hay ninguna oferta cargada.</strong></p>
        <p>Cargá la primera a mano, o importá varias desde un archivo CSV.</p>
      <?php else: ?>
        <p><strong>No hay ofertas con ese filtro.</strong></p>
        <p><a href="/admin/ofertas.php">Ver todas</a></p>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <div class="pila">
    <?php foreach ($ofertas as $oferta): ?>
      <?php
      $problema_publicar = motivo_para_no_publicar($oferta);
      $siguientes = TRANSICIONES_OFERTA[$oferta['estado']] ?? [];
      ?>
      <article class="tarjeta pila">

        <div class="fila-titulo">
          <h2 class="tarjeta__titulo"><?= escapar($oferta['titulo']) ?></h2>
          <span class="insignia insignia--<?= escapar($oferta['estado']) ?>">
            <?= escapar(ESTADOS_OFERTA[$oferta['estado']]) ?>
          </span>
        </div>

        <p class="texto-menor">
          <?= escapar($oferta['empleador']) ?> ·
          <?= escapar(PAISES[$oferta['pais_codigo']] ?? $oferta['pais_codigo']) ?> ·
          <?= escapar($oferta['rubro_nombre']) ?><br>
          Fuente: <?= escapar($oferta['fuente_nombre']) ?> ·
          Vence el <?= escapar(fecha_en_palabras($oferta['fecha_vencimiento'])) ?>
          <?php if (!empty($oferta['verificador_nombre'])): ?>
            <br>Verificada por <?= escapar($oferta['verificador_nombre']) ?>
            el <?= escapar(fecha_en_palabras($oferta['verificada_en'])) ?>
          <?php endif; ?>
        </p>

        <?php if ($oferta['estado'] === 'verificada' && $problema_publicar !== null): ?>
          <p class="aviso aviso--aviso">Para publicarla falta: <?= escapar($problema_publicar) ?></p>
        <?php endif; ?>

        <div class="acciones">
          <?php if (tiene_permiso('ofertas.editar')): ?>
            <a class="boton boton--secundario" href="/admin/oferta_editar.php?id=<?= (int) $oferta['id'] ?>">Editar</a>
          <?php endif; ?>

          <?php $cuantos = (int) ($postulaciones_por_oferta[(int) $oferta['id']] ?? 0); ?>
          <?php if ($cuantos > 0): ?>
            <a class="boton boton--secundario" href="/admin/postulaciones.php?oferta=<?= (int) $oferta['id'] ?>">
              Postulaciones (<?= $cuantos ?>)
            </a>
          <?php endif; ?>

          <?php if (in_array('verificada', $siguientes, true) && tiene_permiso('ofertas.verificar')): ?>
            <a class="boton boton--principal" href="/admin/oferta_verificar.php?id=<?= (int) $oferta['id'] ?>">
              Verificar
            </a>
          <?php endif; ?>

          <?php foreach ($siguientes as $destino): ?>
            <?php
            // 'verificada' tiene su propia pantalla con la lista de
            // comprobaciones: no se llega ahí con un botón suelto.
            if ($destino === 'verificada') { continue; }
            [$permiso_destino] = PERMISO_POR_ESTADO[$destino];
            if (!tiene_permiso($permiso_destino)) { continue; }
            $pide_motivo = in_array($destino, ['retirada', 'en_revision'], true);
            ?>
            <form method="post" action="/admin/ofertas.php" class="accion-estado"
                  <?= $pide_motivo ? '' : 'data-confirmar="¿Confirmás el cambio de estado?"' ?>>
              <?php campo_csrf(); ?>
              <input type="hidden" name="accion" value="cambiar_estado">
              <input type="hidden" name="oferta_id" value="<?= (int) $oferta['id'] ?>">
              <input type="hidden" name="nuevo_estado" value="<?= escapar($destino) ?>">
              <?php if ($pide_motivo): ?>
                <label class="etiqueta texto-menor" for="motivo-<?= (int) $oferta['id'] ?>-<?= escapar($destino) ?>">
                  Motivo para <?= escapar(mb_strtolower(ESTADOS_OFERTA[$destino], 'UTF-8')) ?>
                </label>
                <input class="entrada" type="text" maxlength="200" required
                       id="motivo-<?= (int) $oferta['id'] ?>-<?= escapar($destino) ?>" name="motivo">
              <?php endif; ?>
              <button class="boton <?= $destino === 'publicada' ? 'boton--principal' : 'boton--secundario' ?>" type="submit">
                <?= escapar(ESTADOS_OFERTA[$destino]) ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>

      </article>
    <?php endforeach; ?>
    </div>

    <?php if ($paginas > 1): ?>
      <nav class="acciones" aria-label="Páginas">
        <?php
        $base = '/admin/ofertas.php?' . http_build_query(array_filter([
            'estado' => $estado, 'texto' => $texto,
        ]));
        ?>
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
