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
 *
 * En el teléfono, las cuatro secciones principales bajan a una barra
 * de pestañas fija abajo, al alcance del pulgar, con ícono y palabra.
 * A propósito NO es un menú escondido detrás de tres rayitas: el
 * verificador tiene que estar a la vista en todas las páginas, y
 * mucha gente de nuestra población no reconoce ese ícono. Todo es
 * CSS: funciona igual sin JavaScript.
 */

$titulo_pagina = $titulo_pagina ?? SITIO_NOMBRE;
$mensaje       = tomar_mensaje();
$usuario       = usuario_actual();

// Qué sección se marca como la actual (aria-current), para que la
// persona sepa dónde está parada, también con lector de pantalla.
$ruta_actual = $_SERVER['SCRIPT_NAME'] ?? '';
$seccion_actual = match (true) {
    in_array($ruta_actual, ['/ofertas.php', '/oferta.php', '/reportar.php'], true) => 'ofertas',
    $ruta_actual === '/verificador.php'                                          => 'verificar',
    $ruta_actual === '/alertas.php'                                              => 'alertas',
    str_starts_with($ruta_actual, '/cuenta/'), str_starts_with($ruta_actual, '/admin/') => 'cuenta',
    default                                                                      => '',
};
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#12496B">
<title><?= escapar($titulo_pagina) ?> · <?= escapar(SITIO_NOMBRE) ?></title>
<!-- Ícono propio: sin él, cada navegador pide /favicon.ico, cae en el
     404 y gasta una petición de PHP del tope diario del hosting. -->
<link rel="icon" href="/recursos/icono.svg" type="image/svg+xml">
<link rel="stylesheet" href="/recursos/estilo.css">
</head>
<body>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<!-- Barra fina de "cargando" para las búsquedas que se actualizan sin
     recargar la página. Solo la usa app.js; sin JavaScript no aparece. -->
<div class="cargando-barra" aria-hidden="true"></div>

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
      <a class="navegacion__enlace" href="/ofertas.php"<?= $seccion_actual === 'ofertas' ? ' aria-current="page"' : '' ?>>
        <svg class="navegacion__icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M3 13h18"></path></svg>
        <span>Ofertas</span>
      </a>
      <a class="navegacion__enlace" href="/verificador.php"<?= $seccion_actual === 'verificar' ? ' aria-current="page"' : '' ?>>
        <svg class="navegacion__icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path><path d="m8.3 11 1.9 1.9 3.5-3.6"></path></svg>
        <span>Verificar</span>
      </a>
      <a class="navegacion__enlace" href="/alertas.php"<?= $seccion_actual === 'alertas' ? ' aria-current="page"' : '' ?>>
        <svg class="navegacion__icono" viewBox="0 0 24 24" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
        <span>Alertas</span>
      </a>

      <?php if ($usuario === null): ?>
        <a class="navegacion__enlace" href="/cuenta/entrar.php"<?= $seccion_actual === 'cuenta' ? ' aria-current="page"' : '' ?>>
          <svg class="navegacion__icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"></path></svg>
          <span>Entrar</span>
        </a>
      <?php elseif (es_administrativo()): ?>
        <a class="navegacion__enlace" href="/admin/index.php"<?= $seccion_actual === 'cuenta' ? ' aria-current="page"' : '' ?>>
          <svg class="navegacion__icono" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
          <span>Panel</span>
        </a>
      <?php else: ?>
        <a class="navegacion__enlace" href="/cuenta/panel.php"<?= $seccion_actual === 'cuenta' ? ' aria-current="page"' : '' ?>>
          <svg class="navegacion__icono" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"></path></svg>
          <span>Mi cuenta</span>
        </a>
      <?php endif; ?>
    </nav>

    <div class="barra__acciones">
      <?php if ($usuario === null): ?>
        <a class="boton-barra boton-barra--destacado" href="/cuenta/registrarse.php">Crear cuenta</a>
      <?php elseif (es_administrativo()): ?>
        <a class="boton-barra" href="/admin/salir.php">Salir</a>
      <?php else: ?>
        <a class="boton-barra" href="/cuenta/salir.php">Salir</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="contenido" id="contenido">

<?php if ($mensaje !== null): ?>
  <div class="contenedor">
    <p class="aviso aviso--<?= escapar($mensaje['tipo']) ?> aviso--flotante" role="status">
      <?= escapar($mensaje['texto']) ?>
    </p>
  </div>
<?php endif; ?>
