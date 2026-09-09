<?php
/**
 * CREAR CUENTA
 * -----------------------------------------------------------------
 * Se piden cuatro cosas y nada más: cómo llamarte, un correo, y la
 * contraseña dos veces (regla 4, datos mínimos).
 *
 * No se pide apellido, edad, sexo, DPI, lugar de origen ni situación
 * migratoria. No es que no se muestren: no se recolectan. Así la regla
 * 7 se puede comprobar leyendo el código, en vez de creerle a una
 * promesa.
 *
 * El rol se escribe fijo acá (ROL_USUARIO). En ningún caso sale de lo
 * que mandó el formulario.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

if (hay_sesion()) {
    redirigir(es_administrativo() ? '/admin/index.php' : '/cuenta/panel.php');
}

$errores = [];
$nombre  = '';
$correo  = '';

if (es_post()) {
    $nombre       = limpiar_texto(campo('nombre'));
    $correo       = normalizar_correo(campo('correo'));
    $contrasena   = campo_crudo('contrasena');
    $confirmacion = campo_crudo('confirmacion');

    // --- Trampa para programas que llenan formularios solos -------
    // Si el campo escondido viene lleno, no fue una persona. Se
    // responde como si todo hubiera salido bien, para no enseñarle al
    // programa cuál fue el problema, pero no se crea nada.
    if (cayo_en_trampa()) {
        registrar_intento('registro', $correo, false);
        guardar_mensaje('exito', 'Listo, tu cuenta quedó creada.');
        redirigir('/cuenta/entrar.php');
    }

    // --- Límite de cuentas nuevas por conexión -------------------
    // Sin esto, alguien puede crear miles de cuentas y agotar el tope
    // diario de peticiones del hosting, dejando el sitio caído.
    if (contar_intentos_por_ip('registro', REGISTRO_VENTANA_HORAS * 60) >= REGISTRO_MAX_POR_IP) {
        $errores['general'] = 'Se crearon varias cuentas desde esta conexión en las últimas horas. '
                            . 'Probá más tarde.';
    }

    // --- Validaciones -------------------------------------------
    if (!largo_valido($nombre, 2, 100)) {
        $errores['nombre'] = 'Escribí cómo querés que te llamemos (entre 2 y 100 letras).';
    }

    if (!es_correo_valido($correo)) {
        $errores['correo'] = 'Ese correo no parece estar completo. Revisalo.';
    } elseif (existe_correo($correo)) {
        // Acá sí se dice que el correo ya está en uso: es un formulario
        // de registro y sin este aviso la persona no entiende qué pasó.
        // En el formulario de ENTRAR nunca se dice, para no revelar
        // quién tiene cuenta en la plataforma.
        $errores['correo'] = 'Ya existe una cuenta con ese correo. Entrá con tu contraseña.';
    }

    $problema_contrasena = revisar_contrasena($contrasena);
    if ($problema_contrasena !== null) {
        $errores['contrasena'] = $problema_contrasena;
    } elseif ($contrasena !== $confirmacion) {
        $errores['confirmacion'] = 'Las dos contraseñas no son iguales.';
    }

    // --- Crear la cuenta ----------------------------------------
    if ($errores === []) {
        $usuario_id = crear_usuario($correo, $contrasena, $nombre, ROL_USUARIO);

        registrar_intento('registro', $correo, true);

        iniciar_sesion_de_usuario($usuario_id);
        marcar_ultimo_acceso($usuario_id);

        guardar_mensaje('exito', 'Listo, tu cuenta quedó creada.');
        redirigir('/cuenta/panel.php');
    }

    registrar_intento('registro', $correo, false);
}

$titulo_pagina = 'Crear mi cuenta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">

  <h1 class="titulo-pagina">Crear mi cuenta</h1>
  <p class="texto-guia">
    Solo necesitamos un correo y una contraseña. No pedimos DPI, pasaporte,
    situación migratoria ni datos bancarios.
  </p>

  <?php if (isset($errores['general'])): ?>
    <p class="aviso aviso--error"><?= escapar($errores['general']) ?></p>
  <?php endif; ?>

  <form method="post" action="/cuenta/registrarse.php" class="tarjeta">
    <?php campo_csrf(); ?>
    <?php campo_trampa(); ?>

    <div class="campo">
      <label class="etiqueta" for="nombre">¿Cómo querés que te llamemos?</label>
      <span class="ayuda" id="ayuda-nombre">Puede ser solo tu nombre. No hace falta el apellido.</span>
      <input class="entrada <?= isset($errores['nombre']) ? 'entrada--error' : '' ?>"
             type="text" id="nombre" name="nombre" maxlength="100"
             aria-describedby="ayuda-nombre"
             value="<?= escapar($nombre) ?>" required>
      <?php if (isset($errores['nombre'])): ?>
        <span class="error-campo"><?= escapar($errores['nombre']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo">
      <label class="etiqueta" for="correo">Tu correo electrónico</label>
      <span class="ayuda" id="ayuda-correo">Con este correo vas a entrar después.</span>
      <input class="entrada <?= isset($errores['correo']) ? 'entrada--error' : '' ?>"
             type="email" id="correo" name="correo" maxlength="191"
             autocomplete="email" aria-describedby="ayuda-correo"
             value="<?= escapar($correo) ?>" required>
      <?php if (isset($errores['correo'])): ?>
        <span class="error-campo"><?= escapar($errores['correo']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo">
      <label class="etiqueta" for="contrasena">Tu contraseña</label>
      <span class="ayuda" id="ayuda-contrasena">
        Al menos <?= (int) CONTRASENA_LARGO_MINIMO ?> letras o números.
        Mejor una frase que te acordés, como <em>mibicicletaazul</em>.
      </span>
      <input class="entrada <?= isset($errores['contrasena']) ? 'entrada--error' : '' ?>"
             type="password" id="contrasena" name="contrasena" data-ver
             autocomplete="new-password" aria-describedby="ayuda-contrasena" required>
      <?php if (isset($errores['contrasena'])): ?>
        <span class="error-campo"><?= escapar($errores['contrasena']) ?></span>
      <?php endif; ?>
    </div>

    <div class="campo">
      <label class="etiqueta" for="confirmacion">Escribí otra vez tu contraseña</label>
      <span class="ayuda" id="ayuda-confirmacion">
        Es para estar seguros de que no se fue un dedazo.
      </span>
      <input class="entrada <?= isset($errores['confirmacion']) ? 'entrada--error' : '' ?>"
             type="password" id="confirmacion" name="confirmacion"
             autocomplete="new-password" aria-describedby="ayuda-confirmacion" required>
      <?php if (isset($errores['confirmacion'])): ?>
        <span class="error-campo"><?= escapar($errores['confirmacion']) ?></span>
      <?php endif; ?>
    </div>

    <p class="aviso aviso--aviso separado">
      <strong>Guardá bien tu contraseña.</strong>
      Esta plataforma no manda correos, así que no hay forma de recuperarla sola.
      Si se te olvida, un administrador tiene que ayudarte a cambiarla.
    </p>

    <div class="acciones">
      <button class="boton boton--principal boton--ancho" type="submit">Crear mi cuenta</button>
    </div>
  </form>

  <p class="texto-menor">
    ¿Ya tenés cuenta? <a href="/cuenta/entrar.php">Entrá desde acá</a>.
  </p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
