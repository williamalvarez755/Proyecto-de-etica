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

require __DIR__ . '/app/nucleo/inicio.php';

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

/** El mismo enlace, sin uno de los filtros (para las fichas de "quitar"). */
function enlace_sin_filtro(array $filtros, string $clave): string
{
    $filtros[$clave] = $clave === 'rubro_id' ? null : '';
    return enlace_con_filtros($filtros, 1);
}

// Las fichas de los filtros que están puestos, con el nombre legible.
$fichas = [];
if ($texto !== '') {
    $fichas[] = ['«' . $texto . '»', enlace_sin_filtro($filtros, 'texto')];
}
if ($rubro_id !== null) {
    $fichas[] = [nombre_de_rubro($rubro_id), enlace_sin_filtro($filtros, 'rubro_id')];
}
if ($pais !== '') {
    $fichas[] = [PAISES[$pais], enlace_sin_filtro($filtros, 'pais')];
}
if ($dias !== '') {
    $fichas[] = [$dias_permitidos[$dias], enlace_sin_filtro($filtros, 'dias')];
}

// Cuántos de los filtros plegables están puestos: si hay alguno, el
// grupo viene abierto, para que la persona vea lo que eligió.
$filtros_plegados = ($rubro_id !== null ? 1 : 0) + ($pais !== '' ? 1 : 0) + ($dias !== '' ? 1 : 0);

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
       guardar el enlace de su búsqueda o mandárselo a alguien.
       data-en-vivo: con JavaScript, los resultados cambian en el lugar
       sin recargar la página (ver app.js). Sin JavaScript, el
       formulario se envía como siempre. -->
  <form method="get" action="/ofertas.php" class="tarjeta filtros" data-en-vivo="#resultados" role="search">
    <div class="campo">
      <label class="etiqueta" for="texto">Buscar por oficio o empresa</label>
      <div class="buscador-grande">
        <input class="entrada" type="search" id="texto" name="texto" maxlength="80"
               value="<?= escapar($texto) ?>" placeholder="albañil, cosecha, hotel...">
        <button class="boton boton--principal" type="submit">Buscar</button>
      </div>
    </div>

    <details class="filtros__mas"<?= $filtros_plegados > 0 ? ' open' : '' ?>>
      <summary>
        Filtrar por oficio, país o fecha
        <?php if ($filtros_plegados > 0): ?>
          <span class="filtros__cuantos" aria-label="<?= (int) $filtros_plegados ?> puestos"><?= (int) $filtros_plegados ?></span>
        <?php endif; ?>
      </summary>

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
    </details>
  </form>

  <!-- Todo lo que cambia con la búsqueda vive acá adentro: es lo único
       que app.js reemplaza cuando se actualiza en el lugar. -->
  <div id="resultados" class="resultados pila">

    <?php if ($fichas !== []): ?>
      <div class="fichas" aria-label="Filtros puestos">
        <?php foreach ($fichas as [$nombre_ficha, $enlace_ficha]): ?>
          <a class="ficha" href="<?= escapar($enlace_ficha) ?>" data-en-vivo-enlace="#resultados">
            <?= escapar($nombre_ficha) ?>
            <span class="ficha__x" aria-hidden="true">×</span>
            <span class="solo-lector">(quitar este filtro)</span>
          </a>
        <?php endforeach; ?>
        <?php if (count($fichas) > 1): ?>
          <a class="texto-menor" href="/ofertas.php" data-en-vivo-enlace="#resultados">Quitar todos</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($ofertas === []): ?>

      <?php if (!$hay_ofertas_publicadas): ?>
        <div class="vacio pila">
          <p data-anuncio><strong>Todavía no hay ofertas publicadas.</strong></p>
          <p>
            Las ofertas aparecen acá después de que alguien de la institución comprueba de dónde
            salieron. Preferimos no mostrar nada antes que mostrar algo sin verificar.
          </p>
        </div>
      <?php else: ?>
        <div class="vacio pila">
          <p data-anuncio><strong>No encontramos ofertas con esa búsqueda.</strong></p>
          <p>Probá con menos filtros o con otra palabra.</p>
          <p><a class="boton boton--secundario" href="/ofertas.php" data-en-vivo-enlace="#resultados">Ver todas las ofertas</a></p>
        </div>
      <?php endif; ?>

    <?php else: ?>

      <p class="texto-menor" data-anuncio>
        <?= $total ?> oferta<?= $total === 1 ? '' : 's' ?>
        <?= $hay_filtros ? 'con esta búsqueda' : 'disponibles' ?>.
      </p>

      <div class="rejilla rejilla--ofertas">
        <?php foreach ($ofertas as $oferta): ?>
          <?php require RAIZ_APP . '/vistas/tarjeta_oferta.php'; ?>
        <?php endforeach; ?>
      </div>

      <?php if ($paginas > 1): ?>
        <nav class="paginacion" aria-label="Páginas de resultados">
          <?php if ($pagina > 1): ?>
            <a class="boton boton--secundario" href="<?= escapar(enlace_con_filtros($filtros, $pagina - 1)) ?>"
               data-en-vivo-enlace="#resultados">← Anterior</a>
          <?php endif; ?>
          <span class="insignia">Página <?= $pagina ?> de <?= $paginas ?></span>
          <?php if ($pagina < $paginas): ?>
            <a class="boton boton--secundario" href="<?= escapar(enlace_con_filtros($filtros, $pagina + 1)) ?>"
               data-en-vivo-enlace="#resultados">Siguiente →</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
