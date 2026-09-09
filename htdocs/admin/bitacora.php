<?php
/**
 * PANEL — BITÁCORA DE AUDITORÍA
 * -----------------------------------------------------------------
 * Todo lo que se ha hecho en el panel, con filtros para poder
 * encontrar algo de verdad.
 *
 * No se puede borrar desde acá, y es a propósito: una bitácora que el
 * propio administrador puede limpiar no sirve para auditar a nadie.
 * Solo se borra lo más viejo que el plazo de retención, desde
 * mantenimiento, y ese plazo está en la configuración.
 *
 * Acá no hay contraseñas, ni códigos, ni contenido de currículums.
 * Solo quién hizo qué, cuándo y desde qué dirección.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('bitacora.ver');

// --- Filtros, validados uno por uno ------------------------------
$accion = parametro('accion');
if ($accion !== '' && !in_array($accion, acciones_registradas(), true)) {
    $accion = '';
}

$desde_fecha = parametro('desde');
if ($desde_fecha !== '' && !es_fecha_valida($desde_fecha)) {
    $desde_fecha = '';
}

$hasta_fecha = parametro('hasta');
if ($hasta_fecha !== '' && !es_fecha_valida($hasta_fecha)) {
    $hasta_fecha = '';
}

$quien = id_valido(parametro('quien'));

$filtros = [
    'accion'     => $accion,
    'usuario_id' => $quien,
    'desde'      => $desde_fecha,
    'hasta'      => $hasta_fecha,
];

$hay_filtros = $accion !== '' || $quien !== null || $desde_fecha !== '' || $hasta_fecha !== '';

$total   = contar_bitacora($filtros);
$paginas = max(1, (int) ceil($total / BITACORA_POR_PAGINA));
$pagina  = entero_en_rango(parametro('pagina', '1'), 1, $paginas) ?? 1;

$registros = leer_bitacora($filtros, BITACORA_POR_PAGINA, ($pagina - 1) * BITACORA_POR_PAGINA);

$titulo_pagina = 'Bitácora';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Bitácora</h1>
    <p class="texto-guia">
      <?= $total ?> registro<?= $total === 1 ? '' : 's' ?>
      <?= $hay_filtros ? 'con estos filtros' : 'en total' ?>.
      Del más reciente al más antiguo.
    </p>
  </section>

  <form method="get" action="/admin/bitacora.php" class="tarjeta">
    <div class="campo">
      <label class="etiqueta" for="accion">Qué acción</label>
      <select class="entrada" id="accion" name="accion">
        <option value="">Todas las acciones</option>
        <?php foreach (acciones_registradas() as $codigo): ?>
          <option value="<?= escapar($codigo) ?>" <?= $accion === $codigo ? 'selected' : '' ?>>
            <?= escapar(ACCIONES_BITACORA[$codigo] ?? $codigo) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="campo">
      <label class="etiqueta" for="quien">Quién la hizo</label>
      <select class="entrada" id="quien" name="quien">
        <option value="">Cualquiera</option>
        <?php foreach (listar_cuentas_administrativas() as $cuenta): ?>
          <option value="<?= (int) $cuenta['id'] ?>" <?= $quien === (int) $cuenta['id'] ? 'selected' : '' ?>>
            <?= escapar($cuenta['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="rejilla rejilla--dos">
      <div class="campo">
        <label class="etiqueta" for="desde">Desde</label>
        <input class="entrada" type="date" id="desde" name="desde" value="<?= escapar($desde_fecha) ?>">
      </div>
      <div class="campo">
        <label class="etiqueta" for="hasta">Hasta</label>
        <input class="entrada" type="date" id="hasta" name="hasta" value="<?= escapar($hasta_fecha) ?>">
      </div>
    </div>

    <div class="acciones separado">
      <button class="boton boton--principal" type="submit">Filtrar</button>
      <?php if ($hay_filtros): ?>
        <a class="boton boton--secundario" href="/admin/bitacora.php">Quitar los filtros</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($registros === []): ?>

    <p class="vacio">
      <?= $hay_filtros
          ? 'No hay ningún registro con esos filtros.'
          : 'Todavía no hay nada registrado. En cuanto alguien haga algo en el panel, aparece acá.' ?>
    </p>

  <?php else: ?>

    <div class="tabla-desliza">
      <table class="tabla">
        <thead>
          <tr>
            <th>Cuándo</th><th>Quién</th><th>Qué hizo</th><th>Detalle</th><th>Desde</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $registro): ?>
          <tr>
            <td><?= escapar(fecha_hora_en_palabras($registro['creado_en'])) ?></td>
            <td>
              <?php if ($registro['nombre_usuario'] !== null): ?>
                <?= escapar($registro['nombre_usuario']) ?><br>
                <span class="texto-menor"><?= escapar($registro['correo_usuario']) ?></span>
              <?php else: ?>
                <span class="texto-menor">Sin sesión o cuenta eliminada</span>
              <?php endif; ?>
            </td>
            <td><?= escapar(ACCIONES_BITACORA[$registro['accion']] ?? $registro['accion']) ?></td>
            <td><?= escapar($registro['detalle'] ?? '') ?></td>
            <td class="texto-menor"><?= escapar($registro['ip']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($paginas > 1): ?>
      <nav class="acciones" aria-label="Páginas de la bitácora">
        <?php
        $base = '/admin/bitacora.php?' . http_build_query(array_filter([
            'accion' => $accion,
            'quien'  => $quien,
            'desde'  => $desde_fecha,
            'hasta'  => $hasta_fecha,
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
