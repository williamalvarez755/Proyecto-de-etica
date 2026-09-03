<?php
/**
 * BITÁCORA DE AUDITORÍA
 * -----------------------------------------------------------------
 * La lista de todo lo que se ha hecho en el panel.
 *
 * Se escribe desde la Fase 1 y no se puede borrar desde la interfaz:
 * una bitácora que el propio administrador puede limpiar no sirve
 * para auditar a nadie.
 *
 * Acá no hay contraseñas, ni códigos, ni contenido de currículums.
 * Solo quién hizo qué, cuándo y desde qué dirección.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_permiso('bitacora.ver');

$total   = contar_bitacora();
$paginas = max(1, (int) ceil($total / BITACORA_POR_PAGINA));

$pagina = entero_en_rango(parametro('pagina', '1'), 1, $paginas) ?? 1;
$desde  = ($pagina - 1) * BITACORA_POR_PAGINA;

$registros = leer_bitacora(BITACORA_POR_PAGINA, $desde);

$titulo_pagina = 'Bitácora';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Bitácora</h1>
    <p class="texto-guia">
      <?= $total ?> registro<?= $total === 1 ? '' : 's' ?> en total.
      Del más reciente al más antiguo.
    </p>
  </section>

  <?php if ($registros === []): ?>

    <p class="vacio">
      Todavía no hay nada registrado. En cuanto alguien haga algo en el panel, aparece acá.
    </p>

  <?php else: ?>

    <div class="tabla-desliza">
      <table class="tabla">
        <thead>
          <tr>
            <th>Cuándo</th>
            <th>Quién</th>
            <th>Qué hizo</th>
            <th>Detalle</th>
            <th>Desde</th>
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
            <td>
              <?= escapar(ACCIONES_BITACORA[$registro['accion']] ?? $registro['accion']) ?>
            </td>
            <td><?= escapar($registro['detalle'] ?? '') ?></td>
            <td class="texto-menor"><?= escapar($registro['ip']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($paginas > 1): ?>
      <nav class="acciones" aria-label="Páginas de la bitácora">
        <?php if ($pagina > 1): ?>
          <a class="boton boton--secundario" href="/admin/bitacora.php?pagina=<?= $pagina - 1 ?>">Anterior</a>
        <?php endif; ?>
        <span class="insignia">Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?>
          <a class="boton boton--secundario" href="/admin/bitacora.php?pagina=<?= $pagina + 1 ?>">Siguiente</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

  <?php endif; ?>

  <p><a href="/admin/index.php">Volver al panel</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
