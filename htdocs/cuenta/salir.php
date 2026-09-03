<?php
/**
 * CERRAR SESIÓN
 * -----------------------------------------------------------------
 * Pide confirmación y cierra con POST, no con un enlace.
 *
 * Si cerrar sesión se hiciera con un simple enlace, otra página podría
 * incluir una imagen apuntando acá y sacar de la sesión a la persona
 * sin que se dé cuenta. Es una molestia menor comparada con otras,
 * pero se arregla con una pantalla y ya queda bien hecho.
 *
 * De paso, la pantalla ayuda: mucha gente va a entrar desde un celular
 * prestado o de un café internet, y conviene que cerrar sesión sea un
 * paso claro y visible.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

if (es_post()) {
    cerrar_sesion();
    guardar_mensaje('exito', 'Cerraste tu sesión. Ya podés dejar este teléfono o esta computadora.');
    redirigir('/');
}

if (!hay_sesion()) {
    redirigir('/');
}

$titulo_pagina = 'Cerrar sesión';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">
  <h1 class="titulo-pagina">¿Querés cerrar tu sesión?</h1>
  <p class="texto-guia">
    Si estás en un teléfono o una computadora que no es tuya, cerrá la sesión antes de irte.
  </p>

  <form method="post" action="/cuenta/salir.php" class="acciones">
    <?php campo_csrf(); ?>
    <button class="boton boton--principal" type="submit">Sí, cerrar mi sesión</button>
    <a class="boton boton--secundario" href="/cuenta/panel.php">No, volver a mi cuenta</a>
  </form>
</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
