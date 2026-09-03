<?php
/**
 * SALIR DEL PANEL
 * -----------------------------------------------------------------
 * Igual que el de los usuarios: se cierra con POST, no con un enlace,
 * y queda registrado en la bitácora.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

if (es_post()) {
    registrar_accion('logout_admin', 'usuario', id_usuario_actual());
    cerrar_sesion();
    guardar_mensaje('exito', 'Cerraste la sesión del panel.');
    redirigir('/admin/entrar.php');
}

if (!hay_sesion()) {
    redirigir('/admin/entrar.php');
}

$titulo_pagina = 'Salir del panel';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">
  <h1 class="titulo-pagina">¿Cerrar la sesión del panel?</h1>
  <p class="texto-guia">Cerrá la sesión si vas a dejar esta computadora.</p>

  <form method="post" action="/admin/salir.php" class="acciones">
    <?php campo_csrf(); ?>
    <button class="boton boton--principal" type="submit">Sí, cerrar sesión</button>
    <a class="boton boton--secundario" href="/admin/index.php">No, volver al panel</a>
  </form>
</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
