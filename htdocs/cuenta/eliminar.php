<?php
/**
 * ELIMINAR MI CUENTA
 * =================================================================
 * La regla 4: borrar la cuenta borra de verdad, base de datos y
 * archivo del disco.
 *
 * Esta pantalla está hecha con dos cuidados que tiran para lados
 * opuestos, y los dos importan:
 *
 *  - Que sea DIFÍCIL de hacer sin querer: se pide la contraseña y
 *    escribir una palabra. Es irreversible y no hay papelera.
 *
 *  - Que sea FÁCIL de encontrar y que nadie la esconda. Una persona
 *    que quiere que borremos sus datos no tiene que rogar ni escribir
 *    a nadie: aprieta dos botones y listo. Esconder el borrado detrás
 *    de un trámite es la forma educada de no dejar borrar.
 *
 * Antes de confirmar se le muestra en números qué va a perder, para
 * que la decisión sea informada y no un "¿seguro? sí".
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario    = usuario_actual();
$usuario_id = (int) $usuario['id'];
$resumen    = resumen_de_lo_que_se_borra($usuario_id);
$error      = null;

if (es_post()) {
    $contrasena   = campo_crudo('contrasena');
    $confirmacion = mb_strtoupper(trim(campo('confirmacion')), 'UTF-8');

    $fila = buscar_usuario_por_id($usuario_id);

    if ($fila === null || !password_verify($contrasena, $fila['contrasena_hash'])) {
        $error = 'Esa no es tu contraseña.';
    } elseif ($confirmacion !== 'BORRAR') {
        $error = 'Para confirmar, escribí la palabra BORRAR.';
    } else {
        $archivos = eliminar_cuenta($usuario_id);

        // La sesión se cierra sola porque la cuenta ya no existe, pero
        // se cierra explícitamente para no dejar nada colgando.
        cerrar_sesion();

        guardar_mensaje(
            'exito',
            'Tu cuenta y tus datos se borraron'
            . ($archivos > 0 ? ', incluido tu archivo de currículum' : '')
            . '. Gracias por haber usado la plataforma.'
        );
        redirigir('/');
    }
}

$titulo_pagina = 'Borrar mi cuenta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila-grande">

  <h1 class="titulo-pagina">Borrar mi cuenta</h1>

  <p class="aviso aviso--error">
    <strong>Esto no se puede deshacer.</strong> No hay papelera ni forma de recuperar nada
    después. Si solo querés dejar de recibir ofertas, podés simplemente no entrar más.
  </p>

  <?php if ($error !== null): ?>
    <p class="aviso aviso--error" role="alert"><?= escapar($error) ?></p>
  <?php endif; ?>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Qué se borra</h2>
    <ul class="pila">
      <li>Tu cuenta, tu nombre y tu correo.</li>
      <?php if ($resumen['tiene_perfil']): ?>
        <li>Tu perfil: tus oficios, tu experiencia, tus estudios, tus idiomas y tus países.</li>
      <?php endif; ?>
      <?php if ($resumen['archivos'] > 0): ?>
        <li>
          <strong>Tu archivo de currículum</strong>, borrado del disco del servidor de verdad.
          <?php if ($resumen['archivos'] > 1): ?>
            (Son <?= (int) $resumen['archivos'] ?> archivos, contando los que compartiste antes.)
          <?php endif; ?>
        </li>
      <?php endif; ?>
      <?php if ($resumen['postulaciones'] > 0): ?>
        <li>
          Tus <?= (int) $resumen['postulaciones'] ?>
          postulacion<?= $resumen['postulaciones'] === 1 ? '' : 'es' ?> y el registro de los
          permisos que diste.
        </li>
      <?php endif; ?>
      <?php if ($resumen['guardadas'] > 0): ?>
        <li>Las <?= (int) $resumen['guardadas'] ?> ofertas que tenías guardadas.</li>
      <?php endif; ?>
    </ul>
  </section>

  <?php if ($resumen['postulaciones'] > 0): ?>
    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Algo que tenés que saber</h2>
      <p>
        Si ya compartiste tu currículum con alguna oferta, <strong>el empleador pudo haberlo
        descargado antes de que borres tu cuenta</strong>. Nosotros borramos lo nuestro, pero
        no podemos borrar lo que ya salió de acá. Te lo decimos claro para que sepas a qué
        atenerte.
      </p>
    </section>
  <?php endif; ?>

  <?php if ($resumen['reportes'] > 0): ?>
    <section class="tarjeta pila">
      <h2 class="tarjeta__titulo">Qué se queda, sin tu nombre</h2>
      <p>
        Los <?= (int) $resumen['reportes'] ?> reporte<?= $resumen['reportes'] === 1 ? '' : 's' ?>
        que hiciste sobre ofertas sospechosas se conservan, pero <strong>sin ningún dato
        tuyo</strong>: dejan de estar asociados a vos.
      </p>
      <p class="texto-menor">
        Ese aviso puede evitar que otra persona caiga en una estafa, y ya no apunta a nadie.
      </p>
    </section>
  <?php endif; ?>

  <form method="post" action="/cuenta/eliminar.php" class="tarjeta"
        data-confirmar="Esta es la última advertencia: tu cuenta y tus datos se van a borrar y no se pueden recuperar. ¿Continuar?">
    <?php campo_csrf(); ?>

    <div class="campo">
      <label class="etiqueta" for="contrasena">Escribí tu contraseña</label>
      <input class="entrada" type="password" id="contrasena" name="contrasena"
             autocomplete="current-password" required>
    </div>

    <div class="campo">
      <label class="etiqueta" for="confirmacion">Escribí la palabra BORRAR</label>
      <span class="ayuda" id="ayuda-confirmacion">
        Con mayúsculas o minúsculas, da igual. Es para estar seguros de que no fue un dedazo.
      </span>
      <input class="entrada" type="text" id="confirmacion" name="confirmacion"
             autocomplete="off" aria-describedby="ayuda-confirmacion" required>
    </div>

    <div class="acciones separado">
      <button class="boton boton--peligro boton--ancho" type="submit">
        Borrar mi cuenta para siempre
      </button>
      <a class="boton boton--principal boton--ancho" href="/cuenta/panel.php">
        No, mejor no
      </a>
    </div>
  </form>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
