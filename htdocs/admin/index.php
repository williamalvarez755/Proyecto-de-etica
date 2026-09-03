<?php
/**
 * PANEL DE ADMINISTRACIÓN — INICIO
 * -----------------------------------------------------------------
 * Muestra únicamente las secciones que ya existen y a las que esta
 * cuenta tiene permiso.
 *
 * Ojo: que un enlace no aparezca NO es lo que protege la sección. Lo
 * que protege es el requerir_permiso() que hay al principio de cada
 * página (regla 5). Esto es solo para no enseñar puertas cerradas.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_administrativo();

$usuario = usuario_actual();

$total_usuarios = (int) consultar_valor(
    'SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE r.codigo = ?',
    [ROL_USUARIO]
);
$total_administrativos = (int) consultar_valor(
    'SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
     WHERE r.codigo IN (?, ?) AND u.activo = 1',
    [ROL_ADMINISTRADOR, ROL_SUPERADMINISTRADOR]
);

$titulo_pagina = 'Panel';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Panel de administración</h1>
    <p class="texto-guia">
      Entraste como <strong><?= escapar($usuario['nombre']) ?></strong>
      <span class="insignia insignia--rol"><?= escapar($usuario['rol_nombre']) ?></span>
    </p>
  </section>

  <section class="rejilla rejilla--dos">
    <div class="tarjeta">
      <h2 class="tarjeta__titulo">Cuentas de personas</h2>
      <p class="texto-guia"><?= $total_usuarios ?> registrada<?= $total_usuarios === 1 ? '' : 's' ?></p>
    </div>
    <div class="tarjeta">
      <h2 class="tarjeta__titulo">Cuentas administrativas activas</h2>
      <p class="texto-guia"><?= $total_administrativos ?></p>
    </div>
  </section>

  <section class="pila">
    <h2 class="subtitulo">Secciones</h2>

    <div class="rejilla rejilla--dos">

      <?php if (tiene_permiso('bitacora.ver')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Bitácora</h3>
          <p>Todo lo que se ha hecho en el panel: quién, qué y cuándo.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/bitacora.php">Ver la bitácora</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (es_superadministrador()): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Cuentas administrativas</h3>
          <p>Crear administradores nuevos, desactivar los que ya no trabajan acá.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/administradores.php">Administrar cuentas</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="tarjeta pila">
        <h3 class="tarjeta__titulo">Mi contraseña</h3>
        <p>Cambiala si creés que alguien más la conoce.</p>
        <div class="acciones">
          <a class="boton boton--secundario" href="/cuenta/cambiar_contrasena.php">Cambiar mi contraseña</a>
        </div>
      </div>

    </div>
  </section>

  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Lo que todavía no está construido</h2>
    <p>
      La gestión de ofertas, las fuentes, el registro de reclutadores autorizados y los
      reportes se construyen en las siguientes etapas. Se prefiere no mostrar botones
      que no hagan nada.
    </p>
  </section>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
