<?php
/**
 * INGRESO AL PANEL DE ADMINISTRACIÓN
 * -----------------------------------------------------------------
 * Entrada separada de la de los usuarios, con límites más estrictos:
 * es la puerta más valiosa del sistema.
 *
 * Si una cuenta de usuario normal escribe acá su correo y su
 * contraseña correctos, recibe exactamente el mismo mensaje que si se
 * hubiera equivocado. Así, desde afuera, no se puede averiguar cuáles
 * correos son administrativos.
 *
 * Todo intento, bueno o malo, queda en la bitácora.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

if (hay_sesion() && es_administrativo()) {
    redirigir('/admin/index.php');
}

$error  = null;
$correo = '';

if (es_post()) {
    $correo     = normalizar_correo(campo('correo'));
    $contrasena = campo_crudo('contrasena');

    if ($correo === '' || $contrasena === '') {
        $error = 'Escribí tu correo y tu contraseña.';
    } elseif (esta_bloqueado('login_admin', $correo, LOGIN_ADMIN_MAX_INTENTOS, LOGIN_ADMIN_BLOQUEO_MINUTOS)) {
        $minutos = minutos_para_reintentar('login_admin', $correo, LOGIN_ADMIN_BLOQUEO_MINUTOS);
        $error = 'Esta cuenta quedó bloqueada por intentos fallidos. '
               . 'Esperá ' . $minutos . ' minuto' . ($minutos === 1 ? '' : 's') . '.';
        registrar_accion('login_admin_bloqueado', 'usuario', null, 'Correo: ' . $correo);
    } else {
        $usuario = autenticar($correo, $contrasena);

        // Mismo trato para "no existe", "contraseña mala" y
        // "existe pero no es cuenta administrativa".
        if ($usuario === null || !in_array($usuario['rol'], ROLES_ADMINISTRATIVOS, true)) {
            registrar_intento('login_admin', $correo, false);
            registrar_accion('login_admin_fallido', 'usuario', null, 'Correo: ' . $correo);
            $error = 'El correo o la contraseña no coinciden.';
        } else {
            registrar_intento('login_admin', $correo, true);
            limpiar_fallos('login_admin', $correo);

            iniciar_sesion_de_usuario((int) $usuario['id']);
            marcar_ultimo_acceso((int) $usuario['id']);

            registrar_accion('login_admin_exitoso', 'usuario', (int) $usuario['id'],
                             'Rol: ' . $usuario['rol'], (int) $usuario['id']);

            redirigir('/admin/index.php');
        }
    }
}

$titulo_pagina = 'Panel de administración';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">

  <h1 class="titulo-pagina">Panel de administración</h1>
  <p class="texto-guia">
    Esta entrada es para el personal de la institución que carga y verifica ofertas.
  </p>

  <?php if ($error !== null): ?>
    <p class="aviso aviso--error" role="alert"><?= escapar($error) ?></p>
  <?php endif; ?>

  <form method="post" action="/admin/entrar.php" class="tarjeta">
    <?php campo_csrf(); ?>

    <div class="campo">
      <label class="etiqueta" for="correo">Correo</label>
      <input class="entrada" type="email" id="correo" name="correo"
             maxlength="191" autocomplete="username"
             value="<?= escapar($correo) ?>" required>
    </div>

    <div class="campo">
      <label class="etiqueta" for="contrasena">Contraseña</label>
      <input class="entrada" type="password" id="contrasena" name="contrasena"
             data-ver autocomplete="current-password" required>
    </div>

    <div class="acciones separado">
      <button class="boton boton--principal boton--ancho" type="submit">Entrar al panel</button>
    </div>
  </form>

  <p class="texto-menor">
    ¿Buscabas tu cuenta personal? <a href="/cuenta/entrar.php">Entrá por acá</a>.
  </p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
