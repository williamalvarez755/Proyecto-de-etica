<?php
/**
 * CABECERA COMÚN
 * -----------------------------------------------------------------
 * Se incluye al principio de cada página:
 *
 *     $titulo_pagina = 'Entrar a mi cuenta';
 *     require RAIZ_APP . '/vistas/cabecera.php';
 *
 * La navegación cambia según quién esté adentro. Ojo: esconder un
 * enlace NO es control de acceso (regla 5). Lo que protege de verdad
 * es requerir_permiso() en la página de destino; esto es solo para no
 * mostrarle a la gente puertas que no le sirven.
 */

$titulo_pagina = $titulo_pagina ?? SITIO_NOMBRE;
$mensaje       = tomar_mensaje();
$usuario       = usuario_actual();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= escapar($titulo_pagina) ?> · <?= escapar(SITIO_NOMBRE) ?></title>
<link rel="stylesheet" href="/recursos/estilo.css">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<header class="barra">
  <div class="barra__interior">
    <a class="marca" href="/">
      <span class="marca__icono" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3z"></path>
          <path d="m9 12 2 2 4-4"></path>
        </svg>
      </span>
      <span class="marca__texto"><?= escapar(SITIO_NOMBRE) ?></span>
    </a>

    <nav class="navegacion" aria-label="Navegación principal">
      <!-- Ofertas y verificador van primero y se ven sin cuenta
           (regla 4). El verificador es la función más útil para quien
           llega asustado por un mensaje de WhatsApp, así que tiene que
           estar a la vista en todas las páginas, no escondida. -->
      <a class="navegacion__enlace" href="/ofertas.php">Ofertas</a>
      <a class="navegacion__enlace" href="/verificador.php">Verificar</a>
      <a class="navegacion__enlace" href="/alertas.php">Alertas</a>

      <?php if ($usuario === null): ?>
        <a class="navegacion__enlace" href="/cuenta/entrar.php">Entrar</a>
        <a class="navegacion__enlace navegacion__enlace--destacado" href="/cuenta/registrarse.php">Crear cuenta</a>
      <?php elseif (es_administrativo()): ?>
        <a class="navegacion__enlace" href="/admin/index.php">Panel</a>
        <a class="navegacion__enlace" href="/admin/salir.php">Salir</a>
      <?php else: ?>
        <a class="navegacion__enlace" href="/cuenta/panel.php">Mi cuenta</a>
        <a class="navegacion__enlace" href="/cuenta/salir.php">Salir</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="contenido" id="contenido">

<?php if ($mensaje !== null): ?>
  <div class="contenedor">
    <p class="aviso aviso--<?= escapar($mensaje['tipo']) ?>" role="status">
      <?= escapar($mensaje['texto']) ?>
    </p>
  </div>
<?php endif; ?>
