<?php
/**
 * REVISAR Y CORREGIR MIS DATOS  (regla 9)
 * -----------------------------------------------------------------
 * La pantalla más importante del lado de la persona.
 *
 * "La máquina propone, la persona decide": acá se muestra lo que el
 * sistema entendió del currículum, y la persona lo corrige ANTES de
 * que se le muestre ninguna oferta. La extracción se equivoca seguido,
 * y quien tiene la última palabra sobre su propia experiencia es ella.
 *
 * También funciona sin currículum: es el formulario para quien no
 * tiene uno. En ese caso simplemente empieza vacío.
 *
 * Ojo con los campos: son exactamente los que permite la regla 7.
 * No se pregunta edad, ni sexo, ni de dónde es la persona. Lo que se
 * pregunta de ubicación es a dónde QUIERE ir (decisión D-008).
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();
$perfil     = buscar_perfil($usuario_id);
$errores    = [];

$ya_confirmado = $perfil !== null && $perfil['confirmado_en'] !== null;

// -----------------------------------------------------------------
//  De dónde salen los valores que se muestran
// -----------------------------------------------------------------
$aviso_lectura = null;

if ($ya_confirmado) {
    // Ya lo revisó antes: se muestra lo que él mismo guardó.
    $valores = [
        'rubros'            => rubros_de_perfil($usuario_id),
        'anios_experiencia' => (int) $perfil['anios_experiencia'],
        'nivel_estudios'    => $perfil['nivel_estudios'],
        'idiomas'           => idiomas_de_perfil($usuario_id),
        'paises'            => paises_de_perfil($usuario_id),
        'disponibilidad'    => $perfil['disponibilidad'],
        'disponible_desde'  => $perfil['disponible_desde'] ?? '',
    ];
} else {
    // Primera vez: se propone lo que se pudo leer del currículum.
    $valores = [
        'rubros' => [], 'anios_experiencia' => 0, 'nivel_estudios' => null,
        // Guatemala ya viene marcada: las ofertas son de empleo acá (D-054).
        'idiomas' => [], 'paises' => ['gt'], 'disponibilidad' => null, 'disponible_desde' => '',
    ];

    if ($perfil !== null && $perfil['cv_archivo'] !== null) {
        $ruta = ruta_de_cv($perfil['cv_archivo']);
        $extension = $perfil['cv_extension'] ?? '';

        if ($ruta !== null && se_puede_leer($extension)) {
            $texto = leer_texto_de_cv($ruta, $extension);

            if ($texto !== null && mb_strlen($texto) > 40) {
                $propuesta = extraer_datos_del_cv($texto);

                foreach ($propuesta['rubros'] as $codigo) {
                    $rubro_id = id_de_rubro($codigo);
                    if ($rubro_id !== null) {
                        $valores['rubros'][] = $rubro_id;
                    }
                }
                $valores['anios_experiencia'] = $propuesta['anios_experiencia'];
                $valores['nivel_estudios']    = $propuesta['nivel_estudios'];
                $valores['idiomas']           = $propuesta['idiomas'];
            } else {
                $aviso_lectura = 'Pudimos guardar tu archivo pero no logramos leer el texto de adentro. '
                               . 'Puede ser que sea una foto o un escaneo. Llená los datos vos mismo, '
                               . 'que es igual de válido.';
            }
        } elseif ($ruta !== null && $extension === 'pdf') {
            $aviso_lectura = 'Tu archivo quedó guardado, pero en este momento el sistema no puede leer '
                           . 'el texto de los PDF. Llená los datos vos mismo: no perdés nada.';
        }
    }
}

// -----------------------------------------------------------------
//  Guardar
// -----------------------------------------------------------------
if (es_post()) {
    $rubros_marcados = campo_lista('rubros');
    $valores['rubros'] = array_map('intval', $rubros_marcados);
    $valores['idiomas'] = campo_lista('idiomas');
    $valores['paises']  = campo_lista('paises');
    $valores['nivel_estudios']   = campo('nivel_estudios');
    $valores['disponibilidad']   = campo('disponibilidad');
    $valores['disponible_desde'] = campo('disponible_desde');

    $anios = entero_en_rango(campo('anios_experiencia', '0'), 0, 50);
    $valores['anios_experiencia'] = $anios ?? 0;

    // --- Validación ----------------------------------------------
    if ($valores['rubros'] === []) {
        $errores['rubros'] = 'Marcá al menos un oficio. Es lo que usamos para buscarte trabajo.';
    }
    foreach ($valores['rubros'] as $rubro_id) {
        if (!rubro_valido($rubro_id)) {
            $errores['rubros'] = 'Alguno de los oficios marcados no existe.';
            break;
        }
    }

    if ($anios === null) {
        $errores['anios_experiencia'] = 'Escribí un número de años entre 0 y 50.';
    }

    if ($valores['nivel_estudios'] === '' || !en_catalogo($valores['nivel_estudios'], NIVELES_ESTUDIO)) {
        $errores['nivel_estudios'] = 'Elegí hasta dónde estudiaste.';
    }

    if ($valores['paises'] === []) {
        $errores['paises'] = 'Marcá al menos un lugar donde podrías trabajar.';
    }
    foreach ($valores['paises'] as $codigo) {
        if (!en_catalogo($codigo, PAISES)) {
            $errores['paises'] = 'Alguno de los países marcados no existe.';
            break;
        }
    }

    if ($valores['disponibilidad'] === '' || !en_catalogo($valores['disponibilidad'], DISPONIBILIDAD)) {
        $errores['disponibilidad'] = 'Elegí desde cuándo podrías empezar a trabajar.';
    }

    if ($valores['disponible_desde'] !== '' && !es_fecha_valida($valores['disponible_desde'])) {
        $errores['disponible_desde'] = 'Esa fecha no es válida.';
    }

    // --- Guardar --------------------------------------------------
    if ($errores === []) {
        guardar_perfil($usuario_id, [
            'anios_experiencia' => $valores['anios_experiencia'],
            'nivel_estudios'    => $valores['nivel_estudios'],
            'disponibilidad'    => $valores['disponibilidad'],
            'disponible_desde'  => $valores['disponible_desde'],
        ]);
        guardar_rubros_de_perfil($usuario_id, $valores['rubros']);
        guardar_idiomas_de_perfil($usuario_id, $valores['idiomas']);
        guardar_paises_de_perfil($usuario_id, $valores['paises']);

        guardar_mensaje('exito', 'Listo, tus datos quedaron guardados como vos los corregiste.');
        redirigir('/cuenta/perfil.php');
    }
}

$titulo_pagina = $ya_confirmado ? 'Corregir mis datos' : 'Revisá lo que entendimos';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina"><?= escapar($titulo_pagina) ?></h1>

    <?php if (!$ya_confirmado): ?>
      <p class="texto-guia">
        Esto es lo que el sistema entendió. <strong>Puede estar equivocado.</strong>
        Corregí lo que haga falta: vos sabés mejor que nadie qué sabés hacer.
      </p>
    <?php else: ?>
      <p class="texto-guia">
        Podés cambiar tus datos cuando quieras. Las ofertas que te mostramos dependen de esto.
      </p>
    <?php endif; ?>
  </section>

  <?php if ($aviso_lectura !== null): ?>
    <p class="aviso aviso--aviso"><?= escapar($aviso_lectura) ?></p>
  <?php endif; ?>

  <form method="post" action="/cuenta/confirmar_perfil.php" class="tarjeta">
    <?php campo_csrf(); ?>

    <fieldset class="campo">
      <legend class="etiqueta">¿Qué sabés hacer?</legend>
      <span class="ayuda">Marcá todos los oficios en los que tengas experiencia.</span>
      <?php if (isset($errores['rubros'])): ?>
        <span class="error-campo"><?= escapar($errores['rubros']) ?></span>
      <?php endif; ?>
      <?php foreach (listar_rubros() as $rubro): ?>
        <label class="casilla">
          <input type="checkbox" name="rubros[]" value="<?= (int) $rubro['id'] ?>"
                 <?= in_array((int) $rubro['id'], $valores['rubros'], true) ? 'checked' : '' ?>>
          <span><?= escapar($rubro['nombre']) ?></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div class="campo">
      <label class="etiqueta" for="anios_experiencia">¿Cuántos años de experiencia tenés?</label>
      <span class="ayuda" id="ayuda-anios">Sumando todo lo que hayas trabajado. Escribí 0 si estás empezando.</span>
      <input class="entrada <?= isset($errores['anios_experiencia']) ? 'entrada--error' : '' ?>"
             type="number" id="anios_experiencia" name="anios_experiencia" min="0" max="50"
             aria-describedby="ayuda-anios" value="<?= (int) $valores['anios_experiencia'] ?>" required>
      <?php if (isset($errores['anios_experiencia'])): ?>
        <span class="error-campo"><?= escapar($errores['anios_experiencia']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo">
      <label class="etiqueta" for="nivel_estudios">¿Hasta dónde estudiaste?</label>
      <select class="entrada <?= isset($errores['nivel_estudios']) ? 'entrada--error' : '' ?>"
              id="nivel_estudios" name="nivel_estudios" required>
        <option value="">Elegí una opción</option>
        <?php foreach (NIVELES_ESTUDIO as $codigo => $nombre): ?>
          <option value="<?= escapar($codigo) ?>" <?= $valores['nivel_estudios'] === $codigo ? 'selected' : '' ?>>
            <?= escapar($nombre) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errores['nivel_estudios'])): ?>
        <span class="error-campo"><?= escapar($errores['nivel_estudios']) ?></span>
      <?php endif; ?>
    </div>

    <fieldset class="campo">
      <legend class="etiqueta">¿Qué idiomas hablás?</legend>
      <span class="ayuda">Marcá todos los que hables, aunque sea poco. Para algunos trabajos suma.</span>
      <?php foreach (IDIOMAS as $codigo => $nombre): ?>
        <label class="casilla">
          <input type="checkbox" name="idiomas[]" value="<?= escapar($codigo) ?>"
                 <?= in_array($codigo, $valores['idiomas'], true) ? 'checked' : '' ?>>
          <span><?= escapar($nombre) ?></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <fieldset class="campo">
      <legend class="etiqueta">¿Dónde podrías trabajar?</legend>
      <span class="ayuda">
        Guatemala ya viene marcada. Si también te interesa trabajar en otro país, marcalo.
        Solo preguntamos dónde podrías trabajar, nunca de dónde sos.
      </span>
      <?php if (isset($errores['paises'])): ?>
        <span class="error-campo"><?= escapar($errores['paises']) ?></span>
      <?php endif; ?>
      <?php foreach (PAISES as $codigo => $nombre): ?>
        <label class="casilla">
          <input type="checkbox" name="paises[]" value="<?= escapar($codigo) ?>"
                 <?= in_array($codigo, $valores['paises'], true) ? 'checked' : '' ?>>
          <span><?= escapar($nombre) ?></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div class="campo">
      <label class="etiqueta" for="disponibilidad">¿Desde cuándo podrías empezar a trabajar?</label>
      <select class="entrada <?= isset($errores['disponibilidad']) ? 'entrada--error' : '' ?>"
              id="disponibilidad" name="disponibilidad" required>
        <option value="">Elegí una opción</option>
        <?php foreach (DISPONIBILIDAD as $codigo => $nombre): ?>
          <option value="<?= escapar($codigo) ?>" <?= $valores['disponibilidad'] === $codigo ? 'selected' : '' ?>>
            <?= escapar($nombre) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errores['disponibilidad'])): ?>
        <span class="error-campo"><?= escapar($errores['disponibilidad']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo">
      <label class="etiqueta" for="disponible_desde">Si ya sabés la fecha exacta (opcional)</label>
      <input class="entrada <?= isset($errores['disponible_desde']) ? 'entrada--error' : '' ?>"
             type="date" id="disponible_desde" name="disponible_desde"
             value="<?= escapar($valores['disponible_desde']) ?>">
      <?php if (isset($errores['disponible_desde'])): ?>
        <span class="error-campo"><?= escapar($errores['disponible_desde']) ?></span>
      <?php endif; ?>
    </div>

    <div class="acciones separado">
      <button class="boton boton--principal boton--ancho" type="submit">
        <?= $ya_confirmado ? 'Guardar los cambios' : 'Está correcto, guardar' ?>
      </button>
    </div>
  </form>

  <p><a href="/cuenta/panel.php">Volver a mi cuenta</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
