<?php
/**
 * PONER UNA CONTRASEÑA NUEVA CON UN CÓDIGO
 * -----------------------------------------------------------------
 * La segunda mitad del restablecimiento asistido (decisión D-005).
 * La primera la hace un administrador desde el panel.
 *
 * Cuidados:
 *
 *  - El mensaje de error es siempre el mismo, exista o no el correo y
 *    sea correcto o no el código. Si dijera "ese correo no existe",
 *    cualquiera podría averiguar quién tiene cuenta acá.
 *
 *  - Se limita la cantidad de intentos: sin eso, un código de diez
 *    caracteres se podría ir probando hasta acertar.
 *
 *  - Apenas se usa, el código queda marcado y no sirve nunca más.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

if (hay_sesion()) {
    redirigir(es_administrativo() ? '/admin/index.php' : '/cuenta/panel.php');
}

$error  = null;
$listo  = false;
$correo = '';

if (es_post()) {
    $correo       = normalizar_correo(campo('correo'));
    $codigo       = mb_strtoupper(trim(campo('codigo')), 'UTF-8');
    $nueva        = campo_crudo('nueva');
    $confirmacion = campo_crudo('confirmacion');

    $mensaje_generico = 'El correo o el código no coinciden, o el código ya venció. '
                      . 'Pedí uno nuevo a la institución.';

    if ($correo === '' || $codigo === '') {
        $error = 'Escribí tu correo y el código que te dieron.';

    } elseif (esta_bloqueado('restablecimiento', $correo, LOGIN_MAX_INTENTOS, LOGIN_BLOQUEO_MINUTOS)) {
        $minutos = minutos_para_reintentar('restablecimiento', $correo, LOGIN_BLOQUEO_MINUTOS);
        $error = 'Hubo varios intentos fallidos. Esperá ' . $minutos
               . ' minuto' . ($minutos === 1 ? '' : 's') . ' y volvé a probar.';

    } else {
        $problema = revisar_contrasena($nueva);

        if ($problema !== null) {
            $error = $problema;
        } elseif ($nueva !== $confirmacion) {
            $error = 'Las dos contraseñas no son iguales.';
        } else {
            $usuario = buscar_usuario_por_correo($correo);
            $vale    = null;

            if ($usuario !== null && (int) $usuario['activo'] === 1) {
                $vale = validar_restablecimiento((int) $usuario['id'], $codigo);
            }

            if ($vale === null) {
                registrar_intento('restablecimiento', $correo, false);
                $error = $mensaje_generico;
            } else {
                cambiar_contrasena((int) $usuario['id'], $nueva);
                marcar_restablecimiento_usado((int) $vale['id']);
                invalidar_restablecimientos((int) $usuario['id']);

                registrar_intento('restablecimiento', $correo, true);
                limpiar_fallos('restablecimiento', $correo);

                // En la bitácora queda que se usó, nunca el código.
                registrar_accion(
                    'restablecimiento_usado',
                    'usuario',
                    (int) $usuario['id'],
                    'La persona puso una contraseña nueva con su código',
                    (int) $usuario['id']
                );

                $listo = true;
            }
        }
    }
}

$titulo_pagina = 'Poner una contraseña nueva';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">

  <h1 class="titulo-pagina">Poner una contraseña nueva</h1>

  <?php if ($listo): ?>

    <p class="aviso aviso--exito">
      Tu contraseña quedó cambiada. Ya podés entrar con la nueva.
    </p>
    <p><a class="boton boton--principal" href="/cuenta/entrar.php">Entrar a mi cuenta</a></p>

  <?php else: ?>

    <p class="texto-guia">
      Si se te olvidó tu contraseña, la institución te da un código por teléfono o en persona.
      Escribilo acá junto con tu correo.
    </p>

    <?php if ($error !== null): ?>
      <p class="aviso aviso--error" role="alert"><?= escapar($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/cuenta/restablecer.php" class="tarjeta">
      <?php campo_csrf(); ?>

      <div class="campo">
        <label class="etiqueta" for="correo">Tu correo</label>
        <input class="entrada" type="email" id="correo" name="correo" maxlength="191"
               autocomplete="email" value="<?= escapar($correo) ?>" required>
      </div>

      <div class="campo">
        <label class="etiqueta" for="codigo">El código que te dieron</label>
        <span class="ayuda" id="ayuda-codigo">
          Son letras y números. No lleva la letra O ni el número 0, para que no se confundan.
        </span>
        <input class="entrada" type="text" id="codigo" name="codigo" maxlength="20"
               autocomplete="off" aria-describedby="ayuda-codigo" required>
      </div>

      <div class="campo">
        <label class="etiqueta" for="nueva">Tu contraseña nueva</label>
        <span class="ayuda" id="ayuda-nueva">Al menos <?= (int) CONTRASENA_LARGO_MINIMO ?> letras o números.</span>
        <input class="entrada" type="password" id="nueva" name="nueva" data-ver minlength="<?= (int) CONTRASENA_LARGO_MINIMO ?>" data-medir
               autocomplete="new-password" aria-describedby="ayuda-nueva" required>
      </div>

      <div class="campo">
        <label class="etiqueta" for="confirmacion">Escribila otra vez</label>
        <input class="entrada" type="password" id="confirmacion" name="confirmacion" data-igual-a="nueva"
               autocomplete="new-password" required>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal boton--ancho" type="submit">Guardar mi contraseña nueva</button>
      </div>
    </form>

    <div class="tarjeta pila">
      <h2 class="tarjeta__titulo">Cuidado con esto</h2>
      <p>
        Nadie de la plataforma te va a pedir nunca tu contraseña, ni por teléfono ni por WhatsApp.
        El código sirve una sola vez y solo para que <strong>vos</strong> pongas una contraseña nueva.
      </p>
    </div>

  <?php endif; ?>

  <p><a href="/cuenta/entrar.php">Volver a entrar</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
