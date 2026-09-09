<?php
/**
 * MI PERFIL
 * -----------------------------------------------------------------
 * Lo que el sistema sabe de la persona, en una sola pantalla y en
 * palabras normales.
 *
 * Que alguien pueda ver de un vistazo todo lo que la plataforma
 * guarda sobre él no es un lujo: en un país sin ley de protección de
 * datos, es de las pocas garantías reales que se pueden dar.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();
$perfil     = buscar_perfil($usuario_id);
$confirmado = $perfil !== null && $perfil['confirmado_en'] !== null;

$mis_rubros  = $confirmado ? rubros_de_perfil($usuario_id)  : [];
$mis_idiomas = $confirmado ? idiomas_de_perfil($usuario_id) : [];
$mis_paises  = $confirmado ? paises_de_perfil($usuario_id)  : [];

$titulo_pagina = 'Mi perfil';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila-grande">

  <h1 class="titulo-pagina">Mi perfil</h1>

  <?php if (!$confirmado): ?>

    <div class="vacio pila">
      <p><strong>Todavía no completaste tu perfil.</strong></p>
      <p>
        Contanos qué sabés hacer y podremos mostrarte las ofertas que coinciden con tu
        experiencia. Toma unos minutos.
      </p>
      <div class="acciones">
        <a class="boton boton--principal" href="/cuenta/confirmar_perfil.php">Completar mi perfil</a>
        <a class="boton boton--secundario" href="/cuenta/cv.php">Subir mi currículum</a>
      </div>
    </div>

  <?php else: ?>

    <section class="tarjeta">
      <h2 class="tarjeta__titulo">Lo que sabés hacer</h2>
      <dl class="lista-datos">
        <dt>Oficios</dt>
        <dd>
          <?php
          $nombres = [];
          foreach ($mis_rubros as $rubro_id) {
              $nombres[] = nombre_de_rubro($rubro_id);
          }
          echo escapar($nombres === [] ? 'Sin oficios marcados' : implode(', ', $nombres));
          ?>
        </dd>

        <dt>Experiencia</dt>
        <dd>
          <?php if ((int) $perfil['anios_experiencia'] === 0): ?>
            Estoy empezando
          <?php else: ?>
            <?= (int) $perfil['anios_experiencia'] ?>
            año<?= (int) $perfil['anios_experiencia'] === 1 ? '' : 's' ?>
          <?php endif; ?>
        </dd>

        <dt>Estudios</dt>
        <dd><?= escapar(NIVELES_ESTUDIO[$perfil['nivel_estudios']] ?? 'Sin especificar') ?></dd>

        <dt>Idiomas</dt>
        <dd>
          <?php
          $nombres = [];
          foreach ($mis_idiomas as $codigo) {
              $nombres[] = IDIOMAS[$codigo] ?? $codigo;
          }
          echo escapar($nombres === [] ? 'Sin idiomas marcados' : implode(', ', $nombres));
          ?>
        </dd>

        <dt>Estaría dispuesto a ir a</dt>
        <dd>
          <?php
          $nombres = [];
          foreach ($mis_paises as $codigo) {
              $nombres[] = PAISES[$codigo] ?? $codigo;
          }
          echo escapar($nombres === [] ? 'Sin países marcados' : implode(', ', $nombres));
          ?>
        </dd>

        <dt>Podría viajar</dt>
        <dd>
          <?= escapar(DISPONIBILIDAD[$perfil['disponibilidad']] ?? 'Sin especificar') ?>
          <?php if (!empty($perfil['disponible_desde'])): ?>
            <br><span class="texto-menor">A partir del <?= escapar(fecha_en_palabras($perfil['disponible_desde'])) ?></span>
          <?php endif; ?>
        </dd>

        <dt>Lo revisaste el</dt>
        <dd><?= escapar(fecha_hora_en_palabras($perfil['confirmado_en'])) ?></dd>
      </dl>

      <div class="acciones separado">
        <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Corregir mis datos</a>
      </div>
    </section>

    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Mi currículum</h2>
      <?php if ($perfil['cv_archivo'] !== null): ?>
        <p>
          Tenés uno guardado en <?= escapar(mb_strtoupper($perfil['cv_extension'] ?? '', 'UTF-8')) ?>,
          subido el <?= escapar(fecha_en_palabras($perfil['cv_subido_en'])) ?>.
        </p>
        <div class="acciones">
          <a class="boton boton--secundario" href="/cuenta/archivo_cv.php">Descargarlo</a>
          <a class="boton boton--secundario" href="/cuenta/cv.php">Cambiarlo o borrarlo</a>
        </div>
      <?php else: ?>
        <p>Todavía no subiste ningún archivo. No hace falta para usar la plataforma.</p>
        <div class="acciones">
          <a class="boton boton--secundario" href="/cuenta/cv.php">Subir mi currículum</a>
          <a class="boton boton--secundario" href="/cuenta/generar_cv.php">Armar uno con mis datos</a>
        </div>
      <?php endif; ?>
    </section>

    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Un currículum ordenado, con tus datos</h2>
      <p>
        Con lo que llenaste podemos armarte una hoja ordenada para imprimir o guardar,
        por si te la piden en algún lado.
      </p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/generar_cv.php">Ver mi currículum armado</a>
      </div>
    </section>

  <?php endif; ?>

  <p><a href="/cuenta/panel.php">Volver a mi cuenta</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
