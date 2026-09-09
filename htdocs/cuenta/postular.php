<?php
/**
 * POSTULARME A UNA OFERTA
 * =================================================================
 * La pantalla del consentimiento. Es la más importante de la Fase 4.
 *
 * Antes de compartir nada, acá se muestra: de qué oferta se trata,
 * quién es el empleador, si hay un reclutador de por medio, de qué
 * fuente salió, y qué exactamente se va a compartir. Recién después
 * se pide la confirmación, y la confirmación es una casilla que hay
 * que marcar a propósito, no un botón que se aprieta de paso.
 *
 * REGLA 6: esto se pide UNA VEZ POR OFERTA. Postularse a otra vuelve
 * a pasar por esta misma pantalla, siempre. No existe ninguna forma
 * de reutilizar un permiso que se dio para otra cosa.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();
$id     = id_valido(parametro('id'));
$oferta = $id === null ? null : buscar_oferta_publica($id);

if ($oferta === null) {
    abortar(
        404,
        'Esta oferta ya no está disponible',
        'Puede que se haya vencido o que la institución la haya retirado. '
        . 'Buscá en las ofertas disponibles: puede haber otra parecida.'
    );
}

// --- Todo lo que tiene que estar en orden antes de poder postularse ---
$perfil = buscar_perfil($usuario_id);
$listo  = $perfil !== null && $perfil['confirmado_en'] !== null;

$ya_postulado = ya_se_postulo($usuario_id, (int) $oferta['id']);

// Si tiene archivo se comparte el archivo; si no, sus datos del perfil.
// Nunca se comparte más de lo que dice la pantalla.
$que_se_comparte = ($perfil !== null && $perfil['cv_archivo'] !== null) ? 'archivo' : 'perfil';

$error = null;

if (es_post() && $listo && !$ya_postulado) {

    if (campo('autorizo') !== 'si') {
        $error = 'Para postularte tenés que marcar la casilla de autorización.';

    } elseif (contar_postulaciones_del_dia($usuario_id) >= POSTULACIONES_MAX_POR_DIA) {
        $error = 'Te postulaste a varias ofertas hoy. Probá mañana: así también nos aseguramos '
               . 'de que cada postulación sea una decisión pensada.';

    } else {
        $guardado = $que_se_comparte === 'archivo' ? (string) $perfil['cv_archivo'] : 'perfil';

        crear_postulacion($usuario_id, (int) $oferta['id'], $guardado);

        registrar_intento('postulacion', (string) $usuario_id, true);

        guardar_mensaje(
            'exito',
            'Listo. Quedó registrado que autorizaste compartir tu currículum con esta oferta, '
            . 'con la fecha y la hora.'
        );
        redirigir('/cuenta/postulaciones.php');
    }
}

$titulo_pagina = 'Postularme';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila-grande">

  <p class="texto-menor">
    <a href="/oferta.php?id=<?= (int) $oferta['id'] ?>">← Volver a la oferta</a>
  </p>

  <h1 class="titulo-pagina">Postularme a esta oferta</h1>

  <?php if ($ya_postulado): ?>

    <div class="aviso aviso--exito">
      <p><strong>Ya te postulaste a esta oferta.</strong></p>
      <p>Podés ver cuándo autorizaste compartir tu currículum en tu historial.</p>
    </div>
    <div class="acciones">
      <a class="boton boton--principal" href="/cuenta/postulaciones.php">Ver mis postulaciones</a>
      <a class="boton boton--secundario" href="/ofertas.php">Ver otras ofertas</a>
    </div>

  <?php elseif (!$listo): ?>

    <div class="vacio pila">
      <p><strong>Antes de postularte, completá tu perfil.</strong></p>
      <p>
        Necesitamos saber qué sabés hacer para poder compartirlo con esta oferta.
        Podés subir tu currículum o llenar un formulario corto.
      </p>
      <div class="acciones">
        <a class="boton boton--principal" href="/cuenta/cv.php">Subir mi currículum</a>
        <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Llenar el formulario</a>
      </div>
    </div>

  <?php else: ?>

    <?php if ($error !== null): ?>
      <p class="aviso aviso--error" role="alert"><?= escapar($error) ?></p>
    <?php endif; ?>

    <div class="tarjeta">
      <?php require RAIZ_APP . '/vistas/texto_consentimiento.php'; ?>
    </div>

    <?php if ($que_se_comparte === 'perfil'): ?>
      <p class="aviso aviso--aviso">
        No tenés un archivo de currículum subido. Se van a compartir los datos de tu perfil.
        Si preferís mandar un archivo, <a href="/cuenta/cv.php">subilo primero</a>.
      </p>
    <?php endif; ?>

    <form method="post" action="/cuenta/postular.php?id=<?= (int) $oferta['id'] ?>" class="tarjeta">
      <?php campo_csrf(); ?>

      <label class="casilla casilla--grande">
        <input type="checkbox" name="autorizo" value="si" required>
        <span>
          <strong>Autorizo compartir mi currículum con esta oferta</strong>
          y entiendo que esta autorización vale solo para esta.
        </span>
      </label>

      <div class="acciones separado">
        <button class="boton boton--principal boton--ancho" type="submit">Confirmar y postularme</button>
        <a class="boton boton--secundario boton--ancho" href="/oferta.php?id=<?= (int) $oferta['id'] ?>">
          No, volver a la oferta
        </a>
      </div>
    </form>

  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
