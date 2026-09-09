<?php
/**
 * MI CURRÍCULUM ARMADO
 * -----------------------------------------------------------------
 * Para quien no tiene currículum: con los datos que ya llenó, se le
 * arma una hoja ordenada que puede imprimir o guardar como PDF desde
 * el mismo navegador.
 *
 * Por qué así y no generando un archivo PDF en el servidor: generar
 * PDF necesitaría otra librería, y cada dependencia es superficie de
 * ataque y archivos que cuentan contra el límite del hosting. El
 * navegador de cualquier teléfono ya sabe imprimir a PDF. Se resuelve
 * el problema real sin agregar nada al servidor.
 *
 * No se inventa nada: la hoja solo muestra lo que la persona escribió.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario    = usuario_actual();
$usuario_id = (int) $usuario['id'];
$perfil     = buscar_perfil($usuario_id);

if ($perfil === null || $perfil['confirmado_en'] === null) {
    guardar_mensaje('aviso', 'Primero contanos qué sabés hacer y con eso armamos tu currículum.');
    redirigir('/cuenta/confirmar_perfil.php');
}

$mis_rubros  = rubros_de_perfil($usuario_id);
$mis_idiomas = idiomas_de_perfil($usuario_id);
$mis_paises  = paises_de_perfil($usuario_id);

$titulo_pagina = 'Mi currículum armado';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila-grande">

  <section class="pila sin-imprimir">
    <h1 class="titulo-pagina">Tu currículum</h1>
    <p class="texto-guia">
      Armado con lo que llenaste. Para guardarlo en el teléfono, tocá imprimir y elegí
      <strong>«Guardar como PDF»</strong>.
    </p>
    <div class="acciones">
      <button class="boton boton--principal" type="button" data-imprimir>Imprimir o guardar como PDF</button>
      <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Corregir mis datos</a>
    </div>
  </section>

  <article class="hoja">

    <header class="hoja__encabezado">
      <h2 class="hoja__nombre"><?= escapar($usuario['nombre']) ?></h2>
      <p class="hoja__contacto"><?= escapar($usuario['correo']) ?></p>
    </header>

    <section class="hoja__bloque">
      <h3 class="hoja__titulo">Oficios</h3>
      <ul>
        <?php foreach ($mis_rubros as $rubro_id): ?>
          <li><?= escapar(nombre_de_rubro($rubro_id)) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <section class="hoja__bloque">
      <h3 class="hoja__titulo">Experiencia</h3>
      <p>
        <?php if ((int) $perfil['anios_experiencia'] === 0): ?>
          Sin experiencia previa formal. Disponible para aprender.
        <?php else: ?>
          <?= (int) $perfil['anios_experiencia'] ?>
          año<?= (int) $perfil['anios_experiencia'] === 1 ? '' : 's' ?> de experiencia.
        <?php endif; ?>
      </p>
    </section>

    <section class="hoja__bloque">
      <h3 class="hoja__titulo">Estudios</h3>
      <p><?= escapar(NIVELES_ESTUDIO[$perfil['nivel_estudios']] ?? 'Sin especificar') ?></p>
    </section>

    <?php if ($mis_idiomas !== []): ?>
      <section class="hoja__bloque">
        <h3 class="hoja__titulo">Idiomas</h3>
        <ul>
          <?php foreach ($mis_idiomas as $codigo): ?>
            <li><?= escapar(IDIOMAS[$codigo] ?? $codigo) ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <section class="hoja__bloque">
      <h3 class="hoja__titulo">Disponibilidad</h3>
      <p>
        <?= escapar(DISPONIBILIDAD[$perfil['disponibilidad']] ?? 'A convenir') ?>.
        <?php if ($mis_paises !== []): ?>
          <?php
          $nombres = [];
          foreach ($mis_paises as $codigo) {
              $nombres[] = PAISES[$codigo] ?? $codigo;
          }
          ?>
          Dispuesto a trabajar en: <?= escapar(implode(', ', $nombres)) ?>.
        <?php endif; ?>
      </p>
    </section>

    <footer class="hoja__pie">
      Hoja generada el <?= escapar(fecha_en_palabras(hoy())) ?>
    </footer>

  </article>

  <p class="sin-imprimir"><a href="/cuenta/perfil.php">Volver a mi perfil</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
