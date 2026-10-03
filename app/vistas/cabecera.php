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
 * En el teléfono, las secciones bajan a una barra de pestañas fija
 * abajo, al alcance del pulgar, con ícono y palabra. A propósito NO es
 * un menú escondido detrás de tres rayitas: el verificador tiene que
 * estar a la vista en todas las páginas, y mucha gente de nuestra
 * población no reconoce ese ícono. Todo es CSS: funciona igual sin
 * JavaScript.
 *
 * El modo noche (D-051): PHP pone data-tema en <html> si la persona ya
 * eligió uno con el botón; si no, manda lo que diga el teléfono. El
 * botón lo muestra app.js: sin JavaScript no podría funcionar, y la
 * regla del proyecto es que no haya botones que no hagan nada.
 */

$titulo_pagina = $titulo_pagina ?? SITIO_NOMBRE;
$mensaje       = tomar_mensaje();
$usuario       = usuario_actual();
$tema          = tema_elegido();

// Qué sección se marca como la actual (aria-current), para que la
// persona sepa dónde está parada, también con lector de pantalla.
$ruta_actual = $_SERVER['SCRIPT_NAME'] ?? '';
$seccion_actual = match (true) {
    $ruta_actual === '/index.php', $ruta_actual === '/'                          => 'inicio',
    in_array($ruta_actual, ['/ofertas.php', '/oferta.php', '/reportar.php'], true) => 'ofertas',
    $ruta_actual === '/verificador.php'                                          => 'verificar',
    $ruta_actual === '/alertas.php'                                              => 'alertas',
    str_starts_with($ruta_actual, '/cuenta/'), str_starts_with($ruta_actual, '/admin/') => 'cuenta',
    default                                                                      => '',
};

// Las pestañas de la navegación: [sección, dirección, ícono, palabra
// corta (teléfono), palabra larga (pantalla ancha)].
$pestanas = [
    ['inicio',    '/',                'inicio',    'Inicio',    'Inicio'],
    ['ofertas',   '/ofertas.php',     'maletin',   'Ofertas',   'Ofertas'],
    ['verificar', '/verificador.php', 'verificar', 'Verificar', 'Verificar reclutador'],
    ['alertas',   '/alertas.php',     'alerta',    'Alertas',   'Señales de estafa'],
];
if ($usuario === null) {
    $pestanas[] = ['cuenta', '/cuenta/entrar.php', 'persona', 'Entrar', 'Entrar'];
} elseif (es_administrativo()) {
    $pestanas[] = ['cuenta', '/admin/index.php', 'panel', 'Panel', 'Panel'];
} else {
    $pestanas[] = ['cuenta', '/cuenta/panel.php', 'persona', 'Mi cuenta', 'Mi cuenta'];
}

// Color de la barra del navegador del teléfono. Si la persona eligió
// un tema, uno solo; si no, uno para cada tema del teléfono.
$color_claro  = '#FFFFFF';
$color_oscuro = '#0A1120';
?>
<!doctype html>
<html lang="es"<?= $tema !== '' ? ' data-tema="' . escapar($tema) . '"' : '' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<?php if ($tema === ''): ?>
<meta name="theme-color" content="<?= escapar($color_claro) ?>" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="<?= escapar($color_oscuro) ?>" media="(prefers-color-scheme: dark)">
<?php else: ?>
<meta name="theme-color" content="<?= escapar($tema === 'oscuro' ? $color_oscuro : $color_claro) ?>">
<?php endif; ?>
<title><?= escapar($titulo_pagina) ?> · <?= escapar(SITIO_NOMBRE) ?></title>
<!-- Ícono propio: sin él, cada navegador pide /favicon.ico, cae en el
     404 y gasta una petición de PHP del tope diario del hosting. -->
<link rel="icon" href="/recursos/icono.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/recursos/icono-180.png">
<link rel="stylesheet" href="<?= escapar(recurso('/recursos/estilo.css')) ?>">
</head>
<body>

<?php require RAIZ_APP . '/vistas/logo.php'; ?>

<a class="saltar" href="#contenido">Saltar al contenido</a>

<!-- Barra fina de "cargando" para las búsquedas que se actualizan sin
     recargar la página. Solo la usa app.js; sin JavaScript no aparece. -->
<div class="cargando-barra" aria-hidden="true"></div>

<header class="barra">
  <div class="barra__interior">
    <?php require RAIZ_APP . '/vistas/marca.php'; ?>

    <nav class="navegacion" aria-label="Navegación principal">
      <!-- Ofertas y verificador se ven sin cuenta (regla 4). El
           verificador es la función más útil para quien llega asustado
           por un mensaje de WhatsApp: tiene que estar a la vista en
           todas las páginas, no escondido. -->
      <?php foreach ($pestanas as [$seccion, $destino, $dibujo, $corto, $largo]): ?>
        <a class="navegacion__enlace<?= $seccion === 'inicio' ? ' navegacion__enlace--inicio' : '' ?>" href="<?= escapar($destino) ?>"<?= $seccion_actual === $seccion ? ' aria-current="page"' : '' ?>>
          <span class="navegacion__icono"><?= icono($dibujo) ?></span>
          <?php if ($corto === $largo): ?>
            <span class="navegacion__texto"><?= escapar($corto) ?></span>
          <?php else: ?>
            <span class="navegacion__texto navegacion__texto--corto"><?= escapar($corto) ?></span>
            <span class="navegacion__texto navegacion__texto--largo"><?= escapar($largo) ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="barra__acciones">
      <!-- Lo muestra app.js. El texto dice a qué modo se pasa al tocarlo. -->
      <button class="boton-tema" type="button" data-tema-boton hidden>
        <span class="boton-tema__icono boton-tema__icono--luna"><?= icono('luna') ?></span>
        <span class="boton-tema__icono boton-tema__icono--sol"><?= icono('sol') ?></span>
        <span class="boton-tema__texto">Modo noche</span>
      </button>

      <?php if ($usuario === null): ?>
        <a class="boton-barra boton-barra--destacado" href="/cuenta/registrarse.php"><?= icono('persona-mas') ?><span class="boton-barra__texto">Crear cuenta</span></a>
      <?php elseif (es_administrativo()): ?>
        <a class="boton-barra boton-barra--salir" href="/admin/salir.php"><?= icono('salir') ?><span>Salir</span></a>
      <?php else: ?>
        <a class="boton-barra boton-barra--salir" href="/cuenta/salir.php"><?= icono('salir') ?><span>Salir</span></a>
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
