<?php
/**
 * ENTRAR A MI CUENTA
 * -----------------------------------------------------------------
 * Dos cuidados que se ven raros y son a propósito:
 *
 * 1. El mensaje de error es SIEMPRE el mismo, exista o no el correo.
 *    Si dijéramos "ese correo no está registrado", cualquiera podría
 *    ir probando correos para averiguar quién tiene cuenta acá. Saber
 *    que una persona está buscando trabajo en el extranjero es
 *    información delicada para ella.
 *
 * 2. Después de varios fallos se bloquea un rato, para que nadie ande
 *    probando contraseñas hasta acertar.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

if (hay_sesion()) {
    redirigir(es_administrativo() ? '/admin/index.php' : '/cuenta/panel.php');
}

$error  = null;
$correo = '';

if (es_post()) {
    $correo     = normalizar_correo(campo('correo'));
    $contrasena = campo_crudo('contrasena');

    // Las cuentas administrativas también pueden entrar por acá, así
    // que este formulario tiene que respetar el bloqueo MÁS ESTRICTO
    // del panel (LOGIN_ADMIN_*). Si no, para adivinarle la contraseña
    // a un administrador alcanzaba con probar por esta puerta, que
    // tiene más intentos y no dejaba nada en la bitácora.
    $bloqueo_usuario = esta_bloqueado('login_usuario', $correo, LOGIN_MAX_INTENTOS, LOGIN_BLOQUEO_MINUTOS);
    $bloqueo_admin   = esta_bloqueado('login_admin', $correo, LOGIN_ADMIN_MAX_INTENTOS, LOGIN_ADMIN_BLOQUEO_MINUTOS);

    if ($correo === '' || $contrasena === '') {
        $error = 'Escribí tu correo y tu contraseña.';
    } elseif ($bloqueo_usuario || $bloqueo_admin) {
        $minutos = max(
            $bloqueo_usuario ? minutos_para_reintentar('login_usuario', $correo, LOGIN_BLOQUEO_MINUTOS) : 0,
            $bloqueo_admin ? minutos_para_reintentar('login_admin', $correo, LOGIN_ADMIN_BLOQUEO_MINUTOS) : 0
        );
        $error = 'Hubo varios intentos fallidos con este correo. '
               . 'Esperá ' . $minutos . ' minuto' . ($minutos === 1 ? '' : 's') . ' y volvé a probar.';
    } else {
        $usuario = autenticar($correo, $contrasena);

        if ($usuario === null) {
            registrar_intento('login_usuario', $correo, false);

            // El mensaje en pantalla es el mismo de siempre: no se le
            // dice a nadie que ese correo es de una cuenta del panel.
            // Pero el fallo cuenta para el bloqueo del panel y queda
            // en la bitácora, igual que si hubiera entrado por allá.
            if (es_correo_administrativo($correo)) {
                registrar_intento('login_admin', $correo, false);
                registrar_accion('login_admin_fallido', 'usuario', null,
                                 'Correo: ' . $correo . ' (desde el formulario público)');
            }

            $error = 'El correo o la contraseña no coinciden.';
        } else {
            registrar_intento('login_usuario', $correo, true);
            limpiar_fallos('login_usuario', $correo);
            limpiar_fallos('login_admin', $correo);

            iniciar_sesion_de_usuario((int) $usuario['id']);
            marcar_ultimo_acceso((int) $usuario['id']);

            // Una cuenta administrativa que entró por acá va a su panel.
            if (in_array($usuario['rol'], ROLES_ADMINISTRATIVOS, true)) {
                registrar_accion('login_admin_exitoso', 'usuario', (int) $usuario['id'],
                                 'Ingresó desde el formulario público', (int) $usuario['id']);
                redirigir('/admin/index.php');
            }

            redirigir('/cuenta/panel.php');
        }
    }
}

$titulo_pagina = 'Entrar a mi cuenta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">

  <h1 class="titulo-pagina">Entrar a mi cuenta</h1>

  <?php if ($error !== null): ?>
    <p class="aviso aviso--error" role="alert"><?= escapar($error) ?></p>
  <?php endif; ?>

  <form method="post" action="/cuenta/entrar.php" class="tarjeta">
    <?php campo_csrf(); ?>

    <div class="campo">
      <label class="etiqueta" for="correo">Tu correo electrónico</label>
      <input class="entrada" type="email" id="correo" name="correo"
             maxlength="191" autocomplete="email"
             value="<?= escapar($correo) ?>" required>
    </div>

    <div class="campo">
      <label class="etiqueta" for="contrasena">Tu contraseña</label>
      <input class="entrada" type="password" id="contrasena" name="contrasena"
             data-ver autocomplete="current-password" required>
    </div>

    <div class="acciones separado">
      <button class="boton boton--principal boton--ancho" type="submit">Entrar</button>
    </div>
  </form>

  <div class="tarjeta pila">
    <h2 class="tarjeta__titulo">¿Se te olvidó la contraseña?</h2>
    <p>
      Esta plataforma no manda correos, así que no hay un botón para recuperarla sola.
      Tenés que pedirle ayuda a la institución que administra la plataforma:
      te van a dar un código de un solo uso para que pongas una contraseña nueva.
    </p>
    <p class="texto-menor">
      Nunca le des tu contraseña a nadie, ni siquiera a alguien que diga trabajar acá.
    </p>
    <p>
      <a class="boton boton--secundario" href="/cuenta/restablecer.php">Ya tengo un código</a>
    </p>
  </div>

  <p class="texto-menor">
    ¿Todavía no tenés cuenta? <a href="/cuenta/registrarse.php">Creala desde acá</a>.
  </p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
