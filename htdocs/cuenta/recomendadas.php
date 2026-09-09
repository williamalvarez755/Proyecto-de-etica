<?php
/**
 * OFERTAS QUE COINCIDEN CON MI PERFIL
 * -----------------------------------------------------------------
 * REGLA 9: si la persona todavía no revisó y confirmó lo que el
 * sistema entendió de ella, no se le muestra ninguna oferta
 * recomendada. Primero corrige sus datos, después ve resultados.
 * Recomendar sobre datos que ella no vio sería decidir por ella.
 *
 * Ojo: esto no le impide ver ofertas. El buscador público sigue
 * abierto para todo el mundo, con cuenta o sin ella. Lo que necesita
 * el perfil confirmado es la recomendación personalizada.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario_id = (int) id_usuario_actual();

if (!perfil_confirmado($usuario_id)) {
    guardar_mensaje(
        'aviso',
        'Antes de mostrarte ofertas, revisá que tus datos estén correctos. '
        . 'Así lo que te mostremos tiene sentido.'
    );
    redirigir('/cuenta/confirmar_perfil.php');
}

$recomendadas = ofertas_recomendadas($usuario_id);
$mis_rubros   = rubros_de_perfil($usuario_id);
$mis_paises   = paises_de_perfil($usuario_id);

$titulo_pagina = 'Ofertas para vos';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Ofertas para vos</h1>
    <p class="texto-guia">
      Estas son las ofertas verificadas que coinciden con lo que pusiste en tu perfil.
      Debajo de cada una te explicamos por qué te aparece.
    </p>
  </section>

  <?php if ($recomendadas === []): ?>

    <div class="vacio pila">
      <p><strong>Por ahora no hay ofertas que coincidan con tu perfil.</strong></p>
      <p>
        Buscamos ofertas de
        <?php
        $nombres = [];
        foreach ($mis_rubros as $rubro_id) {
            $nombres[] = mb_strtolower(nombre_de_rubro($rubro_id), 'UTF-8');
        }
        echo escapar(implode(', ', $nombres));
        ?>
        en
        <?php
        $nombres = [];
        foreach ($mis_paises as $codigo) {
            $nombres[] = PAISES[$codigo] ?? $codigo;
        }
        echo escapar(implode(', ', $nombres));
        ?>.
      </p>
      <p>
        No hay nada verificado con esas características en este momento. Podés ver todas las
        ofertas disponibles, o agregar más oficios o más países a tu perfil.
      </p>
      <div class="acciones">
        <a class="boton boton--principal" href="/ofertas.php">Ver todas las ofertas</a>
        <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Cambiar mi perfil</a>
      </div>
    </div>

  <?php else: ?>

    <p class="texto-menor">
      <?= count($recomendadas) ?> oferta<?= count($recomendadas) === 1 ? '' : 's' ?>
      <?= count($recomendadas) === 1 ? 'coincide' : 'coinciden' ?> con tu perfil.
      Las de arriba son las que más coinciden.
    </p>

    <div class="rejilla">
      <?php foreach ($recomendadas as $oferta): ?>
        <?php require RAIZ_APP . '/vistas/tarjeta_recomendada.php'; ?>
      <?php endforeach; ?>
    </div>

    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">¿Por qué te mostramos estas y no otras?</h2>
      <p>
        Solo usamos seis cosas de tu perfil: <strong>los oficios que sabés, tus años de
        experiencia, tus estudios, tus idiomas, a qué países irías y desde cuándo podrías
        viajar.</strong>
      </p>
      <p>
        No usamos tu edad, ni tu sexo, ni de qué departamento sos, ni tu apellido. No los
        usamos porque no los tenemos: nunca te los pedimos en ninguna pantalla.
      </p>
    </section>

  <?php endif; ?>

  <p><a href="/cuenta/panel.php">Volver a mi cuenta</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
