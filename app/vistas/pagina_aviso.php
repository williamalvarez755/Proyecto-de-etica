<?php
/**
 * PÁGINA DE AVISO
 * -----------------------------------------------------------------
 * La que se muestra cuando algo no se puede hacer: sin permiso,
 * página que no existe, token vencido.
 *
 * Espera dos variables ya definidas: $titulo_pagina y $texto.
 * La usa abortar() en app/nucleo/peticion.php.
 *
 * Siempre lleva una salida. Ninguna pantalla del sitio deja a la
 * persona sin saber a dónde ir.
 */

require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto">
  <div class="vacio pila">
    <p><span class="vacio__icono"><?= icono('info') ?></span></p>
    <h1 class="titulo-pagina"><?= escapar($titulo_pagina) ?></h1>
    <p class="texto-guia"><?= escapar($texto) ?></p>

    <div class="acciones">
      <a class="boton boton--principal" href="/"><?= icono('inicio') ?> Ir al inicio</a>
      <?php if (hay_sesion() && es_administrativo()): ?>
        <a class="boton boton--secundario" href="/admin/index.php">Volver al panel</a>
      <?php elseif (hay_sesion()): ?>
        <a class="boton boton--secundario" href="/cuenta/panel.php">Ir a mi cuenta</a>
      <?php else: ?>
        <a class="boton boton--secundario" href="/ofertas.php">Ver las ofertas</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
