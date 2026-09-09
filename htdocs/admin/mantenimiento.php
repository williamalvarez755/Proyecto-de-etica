<?php
/**
 * PANEL — MANTENIMIENTO
 * =================================================================
 * El hosting no tiene tareas programadas (decisión D-003), así que
 * nada se limpia solo. Lo que hay que hacer cada cierto tiempo se
 * dispara desde acá, a mano, y queda en la bitácora.
 *
 * POR QUÉ UN BOTÓN Y NO AUTOMÁTICO AL ENTRAR EL ADMINISTRADOR:
 *
 *  1. Un borrado que ocurre sin que nadie lo pida es un borrado que
 *     nadie revisó. Con el botón, hay una persona que apretó y una
 *     línea en la bitácora con su nombre. Si algún día la limpieza
 *     tiene un error, hay a quién preguntarle qué pasó.
 *
 *  2. Colgarle trabajo pesado al inicio de sesión hace lento justo el
 *     momento en que alguien está entrando a trabajar, y en un
 *     hosting compartido eso se nota.
 *
 * Para que no se olvide, el panel avisa cuando hay algo pendiente.
 *
 * Sobre el vencimiento de ofertas, que es lo importante: la protección
 * de la persona NO depende de este botón. La consulta pública ya exige
 * que la fecha de vencimiento no haya pasado, así que una oferta
 * vencida deja de verse sola aunque nadie entre acá en seis meses.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('mantenimiento.ejecutar');

if (es_post()) {
    $accion = campo('accion');

    if ($accion === 'vencer_ofertas') {
        $cuantas = vencer_ofertas_publicadas();
        registrar_accion('ofertas_vencidas_en_lote', 'oferta', null, $cuantas . ' ofertas');
        guardar_mensaje('exito', $cuantas === 0
            ? 'No había ninguna oferta por vencer. Todo está al día.'
            : $cuantas . ' oferta' . ($cuantas === 1 ? '' : 's') . ' pasaron a "vencida".');

    } elseif ($accion === 'limpiar_intentos') {
        $cuantos = limpiar_intentos_viejos();
        registrar_accion('limpieza_ejecutada', 'intentos_acceso', null, $cuantos . ' registros');
        guardar_mensaje('exito', $cuantos . ' registro' . ($cuantos === 1 ? '' : 's')
            . ' de intentos viejos se borraron.');

    } elseif ($accion === 'limpiar_restablecimientos') {
        $cuantos = limpiar_restablecimientos_viejos();
        registrar_accion('limpieza_ejecutada', 'restablecimientos', null, $cuantos . ' registros');
        guardar_mensaje('exito', $cuantos . ' código' . ($cuantos === 1 ? '' : 's')
            . ' usados o vencidos se borraron.');

    } elseif ($accion === 'limpiar_bitacora') {
        // Solo el superadministrador: es el único borrado que toca la
        // auditoría, y no debería poder hacerlo cualquiera.
        requerir_superadministrador();
        $cuantos = limpiar_bitacora_vieja();
        registrar_accion('limpieza_ejecutada', 'bitacora_admin', null, $cuantos . ' registros');
        guardar_mensaje('exito', $cuantos . ' entrada' . ($cuantos === 1 ? '' : 's')
            . ' de bitácora con más de ' . RETENCION_BITACORA_DIAS . ' días se borraron.');
    }

    redirigir('/admin/mantenimiento.php');
}

$por_vencer = ofertas_publicadas_vencidas();
$pendientes = pendientes_de_limpieza();
$estado     = revisar_estado_del_sistema();

$titulo_pagina = 'Mantenimiento';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Mantenimiento</h1>
    <p class="texto-guia">
      Tareas que hay que hacer de vez en cuando. Conviene entrar acá una vez por semana.
    </p>
  </section>

  <!-- ============================================================
       Estado del sistema (regla 11: aviso claro al administrador)
       ============================================================ -->
  <section class="pila">
    <h2 class="subtitulo">Cómo está el sistema</h2>
    <p class="texto-guia">
      Si algo de acá está mal, la gente que usa el sitio no ve un error técnico: ve una página
      normal. Por eso hay que revisarlo desde acá.
    </p>

    <div class="tabla-desliza">
      <table class="tabla">
        <thead><tr><th>Qué</th><th>Cómo está</th></tr></thead>
        <tbody>
          <?php foreach ($estado as $revision): ?>
            <tr>
              <td><?= escapar($revision['nombre']) ?></td>
              <td class="<?= $revision['bien'] ? 'bien' : 'mal' ?>">
                <?= escapar($revision['texto']) ?>
                <?= $revision['bien'] ? ' ✔' : ' ✘' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- ============================================================
       Ofertas vencidas
       ============================================================ -->
  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Ofertas que ya pasaron su fecha</h2>

    <p class="aviso aviso--exito">
      Estas ofertas <strong>ya no se ven en el sitio público</strong>. Dejaron de verse solas
      el día que pasó su fecha de vencimiento, sin que nadie tuviera que hacer nada.
    </p>

    <?php if ($por_vencer === []): ?>
      <p class="vacio">No hay ninguna oferta publicada con la fecha pasada. Todo al día.</p>
    <?php else: ?>
      <p>
        Hay <strong><?= count($por_vencer) ?></strong> que todavía figuran como "publicada" en
        el panel. Marcarlas como vencidas pone al día el listado interno.
      </p>

      <div class="tabla-desliza">
        <table class="tabla">
          <thead><tr><th>Oferta</th><th>Venció el</th></tr></thead>
          <tbody>
            <?php foreach ($por_vencer as $oferta): ?>
              <tr>
                <td><?= escapar($oferta['titulo']) ?></td>
                <td><?= escapar(fecha_en_palabras($oferta['fecha_vencimiento'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <form method="post" action="/admin/mantenimiento.php"
            data-confirmar="¿Marcar como vencidas todas las ofertas que ya pasaron su fecha?">
        <?php campo_csrf(); ?>
        <input type="hidden" name="accion" value="vencer_ofertas">
        <button class="boton boton--principal" type="submit">Marcar como vencidas</button>
      </form>
    <?php endif; ?>
  </section>

  <!-- ============================================================
       Limpieza de registros viejos
       ============================================================ -->
  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Limpiar registros viejos</h2>
    <p>
      La base de datos no puede crecer para siempre en un hosting con espacio contado.
      Estos son registros que ya no sirven para nada.
    </p>

    <div class="tabla-desliza">
      <table class="tabla">
        <thead><tr><th>Qué</th><th>Cuántos</th><th>Se conservan</th><th></th></tr></thead>
        <tbody>
          <tr>
            <td>Intentos de inicio de sesión viejos</td>
            <td><?= (int) $pendientes['intentos'] ?></td>
            <td><?= (int) RETENCION_INTENTOS_DIAS ?> días</td>
            <td>
              <?php if ($pendientes['intentos'] > 0): ?>
                <form method="post" action="/admin/mantenimiento.php">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="accion" value="limpiar_intentos">
                  <button class="boton boton--secundario" type="submit">Limpiar</button>
                </form>
              <?php else: ?>
                <span class="texto-menor">Nada que limpiar</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <td>Códigos de contraseña ya usados o vencidos</td>
            <td><?= (int) $pendientes['restablecimientos'] ?></td>
            <td><?= (int) RETENCION_INTENTOS_DIAS ?> días</td>
            <td>
              <?php if ($pendientes['restablecimientos'] > 0): ?>
                <form method="post" action="/admin/mantenimiento.php">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="accion" value="limpiar_restablecimientos">
                  <button class="boton boton--secundario" type="submit">Limpiar</button>
                </form>
              <?php else: ?>
                <span class="texto-menor">Nada que limpiar</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <td>
              Bitácora muy vieja
              <br><span class="texto-menor">Solo el superadministrador</span>
            </td>
            <td><?= (int) $pendientes['bitacora'] ?></td>
            <td><?= (int) RETENCION_BITACORA_DIAS ?> días</td>
            <td>
              <?php if ($pendientes['bitacora'] > 0 && es_superadministrador()): ?>
                <form method="post" action="/admin/mantenimiento.php"
                      data-confirmar="Esto borra entradas de auditoría de más de dos años. ¿Continuar?">
                  <?php campo_csrf(); ?>
                  <input type="hidden" name="accion" value="limpiar_bitacora">
                  <button class="boton boton--peligro" type="submit">Limpiar</button>
                </form>
              <?php else: ?>
                <span class="texto-menor">Nada que limpiar</span>
              <?php endif; ?>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="texto-menor">
      Los plazos están en el archivo de configuración y no se pueden acortar desde acá.
      La bitácora se conserva dos años a propósito: es el registro de quién hizo qué.
    </p>
  </section>

  <?php if (tiene_permiso('mantenimiento.ejecutar')): ?>
    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Respaldo de la base de datos</h2>
      <p>Bajar una copia de todo, para poder restaurarla si algo pasa.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/admin/respaldo.php">Ir a respaldos</a>
      </div>
    </section>
  <?php endif; ?>

  <p><a href="/admin/index.php">Volver al panel</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
