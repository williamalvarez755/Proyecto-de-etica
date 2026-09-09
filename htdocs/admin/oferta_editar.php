<?php
/**
 * PANEL — CARGAR O EDITAR UNA OFERTA
 * -----------------------------------------------------------------
 * Un solo archivo para las dos cosas: si viene ?id= es edición, si no,
 * es una oferta nueva. Los campos y las validaciones son los mismos,
 * y así no hay dos formularios que se vayan separando con el tiempo.
 *
 * Lo importante: una oferta nueva SIEMPRE nace 'pendiente', y editar
 * una oferta ya verificada le quita la verificación. Si cambió el
 * empleador o el vencimiento, lo que alguien comprobó antes ya no es
 * lo que dice ahora.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

$id     = id_valido(parametro('id'));
$oferta = $id === null ? null : buscar_oferta($id);

if ($id !== null && $oferta === null) {
    abortar(404, 'Esa oferta no existe', 'Puede que la hayan borrado o que el enlace esté mal.');
}

requerir_permiso($oferta === null ? 'ofertas.crear' : 'ofertas.editar');

$fuentes = listar_fuentes();
$errores = [];

// --- Valores del formulario --------------------------------------
$datos = [
    'titulo'                   => $oferta['titulo'] ?? '',
    'descripcion'              => $oferta['descripcion'] ?? '',
    'empleador'                => $oferta['empleador'] ?? '',
    'reclutador_id'            => (string) ($oferta['reclutador_id'] ?? ''),
    'fuente_id'                => (string) ($oferta['fuente_id'] ?? ''),
    'pais_codigo'              => $oferta['pais_codigo'] ?? '',
    'ciudad'                   => $oferta['ciudad'] ?? '',
    'rubro_id'                 => (string) ($oferta['rubro_id'] ?? ''),
    'requisitos'               => $oferta['requisitos'] ?? '',
    'experiencia_anios_min'    => (string) ($oferta['experiencia_anios_min'] ?? '0'),
    'estudios_min'             => $oferta['estudios_min'] ?? 'ninguno',
    'disponibilidad_requerida' => $oferta['disponibilidad_requerida'] ?? 'a_convenir',
    'salario_texto'            => $oferta['salario_texto'] ?? '',
    'url_original'             => $oferta['url_original'] ?? '',
    'fecha_publicacion'        => $oferta['fecha_publicacion'] ?? hoy(),
    'fecha_vencimiento'        => $oferta['fecha_vencimiento'] ?? '',
];
$idiomas_elegidos = $oferta === null ? [] : idiomas_de_oferta((int) $oferta['id']);

if (es_post()) {
    foreach ($datos as $campo => $valor) {
        $datos[$campo] = limpiar_texto(campo($campo));
    }
    $idiomas_elegidos = campo_lista('idiomas');

    // --- Validaciones --------------------------------------------
    if (!largo_valido($datos['titulo'], 5, 200)) {
        $errores['titulo'] = 'El título tiene que tener entre 5 y 200 caracteres.';
    }
    if (!largo_valido($datos['descripcion'], 20, 5000)) {
        $errores['descripcion'] = 'Describí el trabajo con al menos 20 caracteres.';
    }
    if (!largo_valido($datos['empleador'], 2, 150)) {
        $errores['empleador'] = 'Escribí quién ofrece el trabajo.';
    }

    $fuente_id = id_valido($datos['fuente_id']);
    if ($fuente_id === null || buscar_fuente($fuente_id) === null) {
        $errores['fuente_id'] = 'Elegí de qué fuente salió esta oferta.';
    }

    $rubro_id = id_valido($datos['rubro_id']);
    if ($rubro_id === null || !rubro_valido($rubro_id)) {
        $errores['rubro_id'] = 'Elegí el oficio.';
    }

    if (!en_catalogo($datos['pais_codigo'], PAISES)) {
        $errores['pais_codigo'] = 'Elegí el país.';
    }
    if (!en_catalogo($datos['estudios_min'], NIVELES_ESTUDIO)) {
        $errores['estudios_min'] = 'Elegí el nivel de estudios.';
    }
    if (!en_catalogo($datos['disponibilidad_requerida'], DISPONIBILIDAD)) {
        $errores['disponibilidad_requerida'] = 'Elegí la disponibilidad.';
    }

    $experiencia = entero_en_rango($datos['experiencia_anios_min'], 0, 50);
    if ($experiencia === null) {
        $errores['experiencia_anios_min'] = 'Escribí un número de años entre 0 y 50.';
    }

    $reclutador_id = $datos['reclutador_id'] === '' ? null : id_valido($datos['reclutador_id']);
    if ($datos['reclutador_id'] !== '' && ($reclutador_id === null || buscar_reclutador($reclutador_id) === null)) {
        $errores['reclutador_id'] = 'Ese reclutador no está en el registro.';
    }

    if ($datos['fecha_publicacion'] === '' || !es_fecha_valida($datos['fecha_publicacion'])) {
        $errores['fecha_publicacion'] = 'Escribí la fecha de publicación. Se le muestra a la persona.';
    }
    if ($datos['fecha_vencimiento'] === '' || !es_fecha_valida($datos['fecha_vencimiento'])) {
        $errores['fecha_vencimiento'] = 'Escribí hasta cuándo está disponible. Sin esta fecha no se puede publicar.';
    } elseif ($datos['fecha_publicacion'] !== '' && $datos['fecha_vencimiento'] < $datos['fecha_publicacion']) {
        $errores['fecha_vencimiento'] = 'El vencimiento no puede ser anterior a la publicación.';
    }

    if ($datos['url_original'] !== '' && !url_segura($datos['url_original'])) {
        $errores['url_original'] = 'La dirección tiene que empezar con http:// o https://';
    }

    // --- Guardar --------------------------------------------------
    if ($errores === []) {
        $para_guardar = $datos;
        $para_guardar['fuente_id']             = $fuente_id;
        $para_guardar['rubro_id']              = $rubro_id;
        $para_guardar['reclutador_id']         = $reclutador_id;
        $para_guardar['experiencia_anios_min'] = $experiencia;

        if ($oferta === null) {
            $nuevo_id = crear_oferta($para_guardar, id_usuario_actual());
            guardar_idiomas_oferta($nuevo_id, $idiomas_elegidos);

            registrar_accion('oferta_creada', 'oferta', $nuevo_id, $datos['titulo']);
            guardar_mensaje('exito', 'La oferta quedó cargada como pendiente. Falta verificarla para poder publicarla.');

        } else {
            $volvio_a_pendiente = actualizar_oferta((int) $oferta['id'], $para_guardar);
            guardar_idiomas_oferta((int) $oferta['id'], $idiomas_elegidos);

            registrar_accion('oferta_editada', 'oferta', (int) $oferta['id'], $datos['titulo']);

            guardar_mensaje(
                'exito',
                $volvio_a_pendiente
                    ? 'Se guardaron los cambios. Como la oferta estaba verificada, volvió a pendiente: hay que verificarla otra vez.'
                    : 'Se guardaron los cambios.'
            );
        }

        redirigir('/admin/ofertas.php');
    }
}

$titulo_pagina = $oferta === null ? 'Cargar una oferta' : 'Editar la oferta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <h1 class="titulo-pagina"><?= escapar($titulo_pagina) ?></h1>

  <?php if ($fuentes === []): ?>

    <div class="vacio pila">
      <p><strong>Antes hay que crear al menos una fuente.</strong></p>
      <p>
        Ninguna oferta se puede cargar sin decir de dónde salió. Es la primera regla del
        proyecto y por eso el sistema no deja saltársela.
      </p>
      <?php if (tiene_permiso('fuentes.gestionar')): ?>
        <p><a class="boton boton--principal" href="/admin/fuentes.php">Crear una fuente</a></p>
      <?php else: ?>
        <p class="texto-menor">Tu cuenta no tiene permiso para crear fuentes. Pedíselo a quien administre el sistema.</p>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <?php if ($oferta !== null && in_array($oferta['estado'], ['verificada', 'publicada'], true)): ?>
      <p class="aviso aviso--aviso">
        Esta oferta está <?= escapar(ESTADOS_OFERTA[$oferta['estado']]) ?>.
        Si guardás cambios, vuelve a <strong>pendiente</strong> y hay que verificarla de nuevo.
      </p>
    <?php endif; ?>

    <form method="post" action="<?= $oferta === null ? '/admin/oferta_editar.php' : '/admin/oferta_editar.php?id=' . (int) $oferta['id'] ?>" class="tarjeta">
      <?php campo_csrf(); ?>

      <div class="campo">
        <label class="etiqueta" for="titulo">Título del puesto</label>
        <input class="entrada <?= isset($errores['titulo']) ? 'entrada--error' : '' ?>" type="text"
               id="titulo" name="titulo" maxlength="200" value="<?= escapar($datos['titulo']) ?>" required>
        <?php if (isset($errores['titulo'])): ?><span class="error-campo"><?= escapar($errores['titulo']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="empleador">Quién ofrece el trabajo</label>
        <span class="ayuda" id="ayuda-empleador">La empresa o el empleador, como se le va a mostrar a la persona.</span>
        <input class="entrada <?= isset($errores['empleador']) ? 'entrada--error' : '' ?>" type="text"
               id="empleador" name="empleador" maxlength="150" aria-describedby="ayuda-empleador"
               value="<?= escapar($datos['empleador']) ?>" required>
        <?php if (isset($errores['empleador'])): ?><span class="error-campo"><?= escapar($errores['empleador']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="descripcion">En qué consiste el trabajo</label>
        <textarea class="entrada <?= isset($errores['descripcion']) ? 'entrada--error' : '' ?>"
                  id="descripcion" name="descripcion" rows="6" required><?= escapar($datos['descripcion']) ?></textarea>
        <?php if (isset($errores['descripcion'])): ?><span class="error-campo"><?= escapar($errores['descripcion']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="fuente_id">De dónde salió esta oferta</label>
        <select class="entrada <?= isset($errores['fuente_id']) ? 'entrada--error' : '' ?>" id="fuente_id" name="fuente_id" required>
          <option value="">Elegí la fuente</option>
          <?php foreach ($fuentes as $fuente): ?>
            <option value="<?= (int) $fuente['id'] ?>" <?= $datos['fuente_id'] === (string) $fuente['id'] ? 'selected' : '' ?>>
              <?= escapar($fuente['nombre']) ?><?= (int) $fuente['activa'] === 0 ? ' (desactivada)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['fuente_id'])): ?><span class="error-campo"><?= escapar($errores['fuente_id']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="reclutador_id">Reclutador autorizado (si corresponde)</label>
        <span class="ayuda" id="ayuda-reclutador">
          Solo si la oferta llega por medio de un reclutador. Aparecen los que están vigentes en el registro.
        </span>
        <select class="entrada" id="reclutador_id" name="reclutador_id" aria-describedby="ayuda-reclutador">
          <option value="">Sin reclutador de por medio</option>
          <?php foreach (listar_reclutadores_vigentes() as $reclutador): ?>
            <option value="<?= (int) $reclutador['id'] ?>" <?= $datos['reclutador_id'] === (string) $reclutador['id'] ? 'selected' : '' ?>>
              <?= escapar($reclutador['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['reclutador_id'])): ?><span class="error-campo"><?= escapar($errores['reclutador_id']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="pais_codigo">País</label>
        <select class="entrada <?= isset($errores['pais_codigo']) ? 'entrada--error' : '' ?>" id="pais_codigo" name="pais_codigo" required>
          <option value="">Elegí el país</option>
          <?php foreach (PAISES as $codigo => $nombre): ?>
            <option value="<?= escapar($codigo) ?>" <?= $datos['pais_codigo'] === $codigo ? 'selected' : '' ?>><?= escapar($nombre) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['pais_codigo'])): ?><span class="error-campo"><?= escapar($errores['pais_codigo']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="ciudad">Ciudad o región (opcional)</label>
        <input class="entrada" type="text" id="ciudad" name="ciudad" maxlength="100" value="<?= escapar($datos['ciudad']) ?>">
      </div>

      <div class="campo">
        <label class="etiqueta" for="rubro_id">Oficio</label>
        <select class="entrada <?= isset($errores['rubro_id']) ? 'entrada--error' : '' ?>" id="rubro_id" name="rubro_id" required>
          <option value="">Elegí el oficio</option>
          <?php foreach (listar_rubros() as $rubro): ?>
            <option value="<?= (int) $rubro['id'] ?>" <?= $datos['rubro_id'] === (string) $rubro['id'] ? 'selected' : '' ?>>
              <?= escapar($rubro['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['rubro_id'])): ?><span class="error-campo"><?= escapar($errores['rubro_id']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="experiencia_anios_min">Años de experiencia que se piden</label>
        <span class="ayuda" id="ayuda-exp">Escribí 0 si no se pide experiencia.</span>
        <input class="entrada <?= isset($errores['experiencia_anios_min']) ? 'entrada--error' : '' ?>" type="number"
               id="experiencia_anios_min" name="experiencia_anios_min" min="0" max="50"
               aria-describedby="ayuda-exp" value="<?= escapar($datos['experiencia_anios_min']) ?>" required>
        <?php if (isset($errores['experiencia_anios_min'])): ?><span class="error-campo"><?= escapar($errores['experiencia_anios_min']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="estudios_min">Estudios que se piden</label>
        <select class="entrada" id="estudios_min" name="estudios_min" required>
          <?php foreach (NIVELES_ESTUDIO as $codigo => $nombre): ?>
            <option value="<?= escapar($codigo) ?>" <?= $datos['estudios_min'] === $codigo ? 'selected' : '' ?>><?= escapar($nombre) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <fieldset class="campo">
        <legend class="etiqueta">Idiomas que se piden</legend>
        <span class="ayuda">Dejalo sin marcar si no se pide ningún idioma en particular.</span>
        <?php foreach (IDIOMAS as $codigo => $nombre): ?>
          <label class="casilla">
            <input type="checkbox" name="idiomas[]" value="<?= escapar($codigo) ?>"
                   <?= in_array($codigo, $idiomas_elegidos, true) ? 'checked' : '' ?>>
            <?= escapar($nombre) ?>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <div class="campo">
        <label class="etiqueta" for="disponibilidad_requerida">Cuándo hay que estar disponible</label>
        <select class="entrada" id="disponibilidad_requerida" name="disponibilidad_requerida" required>
          <?php foreach (DISPONIBILIDAD as $codigo => $nombre): ?>
            <option value="<?= escapar($codigo) ?>" <?= $datos['disponibilidad_requerida'] === $codigo ? 'selected' : '' ?>><?= escapar($nombre) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="campo">
        <label class="etiqueta" for="requisitos">Otros requisitos (opcional)</label>
        <textarea class="entrada" id="requisitos" name="requisitos" rows="3"><?= escapar($datos['requisitos']) ?></textarea>
      </div>

      <div class="campo">
        <label class="etiqueta" for="salario_texto">Pago (opcional)</label>
        <span class="ayuda" id="ayuda-salario">Tal como lo dice la oferta original. No inventar ni redondear.</span>
        <input class="entrada" type="text" id="salario_texto" name="salario_texto" maxlength="120"
               aria-describedby="ayuda-salario" value="<?= escapar($datos['salario_texto']) ?>">
      </div>

      <div class="campo">
        <label class="etiqueta" for="fecha_publicacion">Fecha de publicación</label>
        <input class="entrada <?= isset($errores['fecha_publicacion']) ? 'entrada--error' : '' ?>" type="date"
               id="fecha_publicacion" name="fecha_publicacion" value="<?= escapar($datos['fecha_publicacion']) ?>" required>
        <?php if (isset($errores['fecha_publicacion'])): ?><span class="error-campo"><?= escapar($errores['fecha_publicacion']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="fecha_vencimiento">Disponible hasta</label>
        <span class="ayuda" id="ayuda-vence">
          Obligatoria. Cuando llega esa fecha, la oferta deja de verse sola en el sitio público.
        </span>
        <input class="entrada <?= isset($errores['fecha_vencimiento']) ? 'entrada--error' : '' ?>" type="date"
               id="fecha_vencimiento" name="fecha_vencimiento" aria-describedby="ayuda-vence"
               value="<?= escapar($datos['fecha_vencimiento']) ?>" required>
        <?php if (isset($errores['fecha_vencimiento'])): ?><span class="error-campo"><?= escapar($errores['fecha_vencimiento']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="url_original">Dirección de la publicación original (opcional)</label>
        <input class="entrada <?= isset($errores['url_original']) ? 'entrada--error' : '' ?>" type="url"
               id="url_original" name="url_original" maxlength="255" value="<?= escapar($datos['url_original']) ?>">
        <?php if (isset($errores['url_original'])): ?><span class="error-campo"><?= escapar($errores['url_original']) ?></span><?php endif; ?>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal" type="submit">Guardar</button>
        <a class="boton boton--secundario" href="/admin/ofertas.php">Cancelar</a>
      </div>
    </form>

  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
