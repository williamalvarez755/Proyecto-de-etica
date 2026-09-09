<?php
/**
 * BUSCADOR PÚBLICO DE OFERTAS
 * -----------------------------------------------------------------
 * No hace falta cuenta ni sesión (regla 4). Esta pantalla es la razón
 * por la que la Fase 2 se construyó antes que las cuentas: la
 * plataforma tiene que servir para algo sin pedirle nada a nadie.
 *
 * Todos los filtros se validan contra los catálogos antes de tocar la
 * base. Si alguien cambia el menú desplegable a mano y manda
 * pais=loquesea, el filtro simplemente se ignora.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

// --- Filtros, validados uno por uno ------------------------------
$texto = limpiar_texto(parametro('texto'));
if (mb_strlen($texto) > 80) {
    $texto = mb_substr($texto, 0, 80, 'UTF-8');
}

$rubro_id = id_valido(parametro('rubro'));
if ($rubro_id !== null && !rubro_valido($rubro_id)) {
    $rubro_id = null;
}

$pais = parametro('pais');
if (!en_catalogo($pais, PAISES)) {
    $pais = '';
}

// Ventanas de tiempo permitidas, en días.
$dias_permitidos = ['7' => 'Últimos 7 días', '30' => 'Último mes', '90' => 'Últimos 3 meses'];
$dias = parametro('dias');
if (!en_catalogo($dias, $dias_permitidos)) {
    $dias = '';
}

$filtros = [
    'texto'    => $texto,
    'rubro_id' => $rubro_id,
    'pais'     => $pais,
    'dias'     => $dias,
];

$hay_filtros = $texto !== '' || $rubro_id !== null || $pais !== '' || $dias !== '';

// --- Resultados ---------------------------------------------------
$total   = contar_ofertas_publicas($filtros);
$paginas = max(1, (int) ceil($total / OFERTAS_POR_PAGINA));
$pagina  = entero_en_rango(parametro('pagina', '1'), 1, $paginas) ?? 1;

$ofertas = listar_ofertas_publicas($filtros, OFERTAS_POR_PAGINA, ($pagina - 1) * OFERTAS_POR_PAGINA);

// Para saber si el listado está vacío porque no hay NADA publicado o
// porque los filtros son muy estrechos. No es lo mismo y el mensaje
// que necesita leer la persona tampoco.
$hay_ofertas_publicadas = $total > 0 ? true : contar_ofertas_publicas([]) > 0;

/** Arma un enlace conservando los filtros que ya están puestos. */
function enlace_con_filtros(array $filtros, int $pagina): string
{
    $partes = [];
    if ($filtros['texto'] !== '')      { $partes['texto'] = $filtros['texto']; }
    if ($filtros['rubro_id'] !== null) { $partes['rubro'] = $filtros['rubro_id']; }
    if ($filtros['pais'] !== '')       { $partes['pais']  = $filtros['pais']; }
    if ($filtros['dias'] !== '')       { $partes['dias']  = $filtros['dias']; }
    if ($pagina > 1)                   { $partes['pagina'] = $pagina; }

    return '/ofertas.php' . ($partes === [] ? '' : '?' . http_build_query($partes));
}

$titulo_pagina = 'Ofertas de trabajo';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Ofertas de trabajo</h1>
    <p class="texto-guia">
      Todas las ofertas de esta página tienen su origen verificado y muestran de dónde salieron.
      No necesitás cuenta para verlas.
    </p>
  </section>

  <!-- Los filtros van por GET a propósito: así la persona puede
       guardar el enlace de su búsqueda o mandárselo a alguien. -->
  <form method="get" action="/ofertas.php" class="tarjeta filtros">
    <div class="campo">
      <label class="etiqueta" for="texto">Buscar por oficio o empresa</label>
      <input class="entrada" type="search" id="texto" name="texto" maxlength="80"
             value="<?= escapar($texto) ?>" placeholder="albañil, cosecha, hotel...">
    </div>

    <div class="rejilla rejilla--tres">
      <div class="campo">
        <label class="etiqueta" for="rubro">Oficio</label>
        <select class="entrada" id="rubro" name="rubro">
          <option value="">Todos los oficios</option>
          <?php foreach (listar_rubros() as $rubro): ?>
            <option value="<?= (int) $rubro['id'] ?>" <?= $rubro_id === (int) $rubro['id'] ? 'selected' : '' ?>>
              <?= escapar($rubro['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="campo">
        <label class="etiqueta" for="pais">País</label>
        <select class="entrada" id="pais" name="pais">
          <option value="">Todos los países</option>
          <?php foreach (PAISES as $codigo => $nombre): ?>
            <option value="<?= escapar($codigo) ?>" <?= $pais === $codigo ? 'selected' : '' ?>>
              <?= escapar($nombre) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="campo">
        <label class="etiqueta" for="dias">Cuándo se publicó</label>
        <select class="entrada" id="dias" name="dias">
          <option value="">Cualquier fecha</option>
          <?php foreach ($dias_permitidos as $valor => $nombre): ?>
            <option value="<?= escapar($valor) ?>" <?= $dias === $valor ? 'selected' : '' ?>>
              <?= escapar($nombre) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="acciones separado">
      <button class="boton boton--principal" type="submit">Buscar</button>
      <?php if ($hay_filtros): ?>
        <a class="boton boton--secundario" href="/ofertas.php">Quitar los filtros</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($ofertas === []): ?>

    <?php if (!$hay_ofertas_publicadas): ?>
      <div class="vacio pila">
        <p><strong>Todavía no hay ofertas publicadas.</strong></p>
        <p>
          Las ofertas aparecen acá después de que alguien de la institución comprueba de dónde
          salieron. Preferimos no mostrar nada antes que mostrar algo sin verificar.
        </p>
      </div>
    <?php else: ?>
      <div class="vacio pila">
        <p><strong>No encontramos ofertas con esa búsqueda.</strong></p>
        <p>Probá con menos filtros o con otra palabra.</p>
        <p><a class="boton boton--secundario" href="/ofertas.php">Ver todas las ofertas</a></p>
      </div>
    <?php endif; ?>

  <?php else: ?>

    <section class="pila">
      <p class="texto-menor">
        <?= $total ?> oferta<?= $total === 1 ? '' : 's' ?>
        <?= $hay_filtros ? 'con esta búsqueda' : 'disponibles' ?>.
      </p>

      <div class="rejilla">
        <?php foreach ($ofertas as $oferta): ?>
          <?php require RAIZ_APP . '/vistas/tarjeta_oferta.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>

    <?php if ($paginas > 1): ?>
      <nav class="acciones" aria-label="Páginas de resultados">
        <?php if ($pagina > 1): ?>
          <a class="boton boton--secundario" href="<?= escapar(enlace_con_filtros($filtros, $pagina - 1)) ?>">Anterior</a>
        <?php endif; ?>
        <span class="insignia">Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?>
          <a class="boton boton--secundario" href="<?= escapar(enlace_con_filtros($filtros, $pagina + 1)) ?>">Siguiente</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>

  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
