<?php
/**
 * CAMBIAR MI CONTRASEÑA
 * -----------------------------------------------------------------
 * La usan los tres roles.
 *
 * Siempre se pide la contraseña actual, aunque la persona ya tenga la
 * sesión abierta: si alguien encuentra un teléfono con la sesión
 * puesta, no puede quedarse con la cuenta sin saber la contraseña.
 *
 * Cuando un administrador hizo un restablecimiento asistido, la cuenta
 * queda marcada y el sistema obliga a pasar por acá antes de hacer
 * cualquier otra cosa: así la contraseña temporal —que otra persona
 * conoce— no se queda puesta.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_sesion();

$usuario   = usuario_actual();
$obligado  = (int) $usuario['debe_cambiar_contrasena'] === 1;
$errores   = [];

if (es_post()) {
    $actual       = campo_crudo('actual');
    $nueva        = campo_crudo('nueva');
    $confirmacion = campo_crudo('confirmacion');

    $fila = buscar_usuario_por_id((int) $usuario['id']);

    if ($fila === null || !password_verify($actual, $fila['contrasena_hash'])) {
        $errores['actual'] = 'Esa no es tu contraseña actual.';
    }

    $problema = revisar_contrasena($nueva);
    if ($problema !== null) {
        $errores['nueva'] = $problema;
    } elseif ($nueva !== $confirmacion) {
        $errores['confirmacion'] = 'Las dos contraseñas nuevas no son iguales.';
    } elseif ($nueva === $actual) {
        $errores['nueva'] = 'La contraseña nueva tiene que ser distinta de la anterior.';
    }

    if ($errores === []) {
        cambiar_contrasena((int) $usuario['id'], $nueva);

        // Identificador de sesión nuevo para esta, y sello nuevo para
        // que siga valiendo. Las OTRAS sesiones abiertas de esta cuenta
        // (un café internet, un teléfono prestado) se cierran solas en
        // su próxima página, porque su sello ya no coincide con la
        // contraseña nueva (ver sello_de_clave() en sesion.php).
        session_regenerate_id(true);
        renovar_sello_de_clave((int) $usuario['id']);

        if (es_administrativo()) {
            registrar_accion('contrasena_cambiada', 'usuario', (int) $usuario['id'],
                             'Cambió su propia contraseña');
        }

        guardar_mensaje('exito', 'Tu contraseña quedó cambiada.');
        redirigir(es_administrativo() ? '/admin/index.php' : '/cuenta/panel.php');
    }
}

$titulo_pagina = 'Cambiar mi contraseña';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">

  <h1 class="titulo-pagina">Cambiar mi contraseña</h1>

  <?php if ($obligado): ?>
    <p class="aviso aviso--aviso">
      Estás usando una contraseña temporal que te dio un administrador.
      Cambiala por una tuya para poder seguir.
    </p>
  <?php endif; ?>

  <form method="post" action="/cuenta/cambiar_contrasena.php" class="tarjeta">
    <?php campo_csrf(); ?>

    <div class="campo">
      <label class="etiqueta" for="actual">Tu contraseña actual</label>
      <input class="entrada <?= isset($errores['actual']) ? 'entrada--error' : '' ?>"
             type="password" id="actual" name="actual"
             autocomplete="current-password" required>
      <?php if (isset($errores['actual'])): ?>
        <span class="error-campo"><?= escapar($errores['actual']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo">
      <label class="etiqueta" for="nueva">Tu contraseña nueva</label>
      <span class="ayuda" id="ayuda-nueva">
        Al menos <?= (int) CONTRASENA_LARGO_MINIMO ?> letras o números.
      </span>
      <input class="entrada <?= isset($errores['nueva']) ? 'entrada--error' : '' ?>"
             type="password" id="nueva" name="nueva" data-ver
             autocomplete="new-password" aria-describedby="ayuda-nueva" required>
      <?php if (isset($errores['nueva'])): ?>
        <span class="error-campo"><?= escapar($errores['nueva']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo">
      <label class="etiqueta" for="confirmacion">Escribí otra vez la contraseña nueva</label>
      <input class="entrada <?= isset($errores['confirmacion']) ? 'entrada--error' : '' ?>"
             type="password" id="confirmacion" name="confirmacion"
             autocomplete="new-password" required>
      <?php if (isset($errores['confirmacion'])): ?>
        <span class="error-campo"><?= escapar($errores['confirmacion']) ?></span>
      <?php endif; ?>
    </div>

    <div class="acciones separado">
      <button class="boton boton--principal boton--ancho" type="submit">Guardar la contraseña nueva</button>
    </div>
  </form>

  <?php if (!$obligado): ?>
    <p class="texto-menor">
      <a href="<?= es_administrativo() ? '/admin/index.php' : '/cuenta/panel.php' ?>">Volver sin cambiar nada</a>
    </p>
  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
