<?php
/**
 * PANEL — RESTABLECER LA CONTRASEÑA DE ALGUIEN
 * -----------------------------------------------------------------
 * La primera mitad del restablecimiento asistido (decisión D-005).
 *
 * El administrador genera un código y se lo dicta a la persona. El
 * sistema guarda solo el hash del código, así que ni el administrador
 * ni nadie que se lleve la base de datos puede volver a verlo.
 *
 * Y esto es lo importante: el administrador NO puede escribir la
 * contraseña de otra persona, ni entrar a su cuenta. Solo puede
 * habilitar que ella misma ponga una nueva. No hay contraseña maestra
 * en ningún lado (regla 10).
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('usuarios.restablecer');

$errores = [];
$correo  = '';

if (es_post() && campo('accion') === 'generar') {
    $correo  = normalizar_correo(campo('correo'));
    $usuario = $correo === '' ? null : buscar_usuario_por_correo($correo);

    if (!es_correo_valido($correo)) {
        $errores['correo'] = 'Ese correo no parece estar completo.';
    } elseif ($usuario === null) {
        $errores['correo'] = 'No hay ninguna cuenta con ese correo.';
    } elseif ((int) $usuario['activo'] !== 1) {
        $errores['correo'] = 'Esa cuenta está desactivada. Primero hay que activarla.';
    } else {
        $codigo = crear_restablecimiento((int) $usuario['id'], (int) id_usuario_actual());

        // En la bitácora queda que se generó, jamás cuál es.
        registrar_accion(
            'restablecimiento_generado',
            'usuario',
            (int) $usuario['id'],
            'Código generado para ' . $usuario['correo']
        );

        $_SESSION['codigo_restablecimiento'] = [
            'correo' => $usuario['correo'],
            'nombre' => $usuario['nombre'],
            'codigo' => $codigo,
        ];

        redirigir('/admin/restablecimientos.php');
    }
}

// Se muestra una sola vez, después de redirigir.
$recien_generado = $_SESSION['codigo_restablecimiento'] ?? null;
unset($_SESSION['codigo_restablecimiento']);

$vigentes = restablecimientos_vigentes();

$titulo_pagina = 'Restablecer contraseñas';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Restablecer la contraseña de alguien</h1>
    <p class="texto-guia">
      Como la plataforma no manda correos, cuando alguien olvida su contraseña hay que darle
      un código para que <strong>ella misma</strong> ponga una nueva.
    </p>
  </section>

  <?php if ($recien_generado !== null): ?>
    <section class="aviso aviso--exito">
      <p><strong>Código para <?= escapar($recien_generado['nombre']) ?></strong></p>
      <p>Cuenta: <?= escapar($recien_generado['correo']) ?></p>
      <p class="codigo"><?= escapar($recien_generado['codigo']) ?></p>
      <p>
        Dictáselo por teléfono o dáselo en persona. Sirve por
        <?= (int) RESTABLECIMIENTO_VALIDEZ_MINUTOS ?> minutos y una sola vez.
      </p>
      <p class="texto-menor">
        <strong>Esta es la única vez que se ve.</strong> Ni vos ni nadie puede volver a
        consultarlo: si se pierde, se genera otro.
        Decile que entre a la página de poner contraseña nueva y escriba su correo y este código.
      </p>
    </section>
  <?php endif; ?>

  <section class="pila">
    <h2 class="subtitulo">Generar un código</h2>

    <form method="post" action="/admin/restablecimientos.php" class="tarjeta">
      <?php campo_csrf(); ?>
      <input type="hidden" name="accion" value="generar">

      <div class="campo">
        <label class="etiqueta" for="correo">Correo de la persona</label>
        <span class="ayuda" id="ayuda-correo">
          Comprobá primero que estás hablando con quien decís. Un código en manos equivocadas
          es la cuenta de esa persona en manos equivocadas.
        </span>
        <input class="entrada <?= isset($errores['correo']) ? 'entrada--error' : '' ?>" type="email"
               id="correo" name="correo" maxlength="191" aria-describedby="ayuda-correo"
               value="<?= escapar($correo) ?>" required>
        <?php if (isset($errores['correo'])): ?>
          <span class="error-campo"><?= escapar($errores['correo']) ?></span>
        <?php endif; ?>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal" type="submit">Generar el código</button>
      </div>
    </form>
  </section>

  <section class="pila">
    <h2 class="subtitulo">Códigos que están vigentes</h2>

    <?php if ($vigentes === []): ?>
      <p class="vacio">No hay ningún código pendiente de usar.</p>
    <?php else: ?>
      <div class="tabla-desliza">
        <table class="tabla">
          <thead>
            <tr><th>Persona</th><th>Se generó</th><th>Vence</th><th>Lo generó</th></tr>
          </thead>
          <tbody>
            <?php foreach ($vigentes as $uno): ?>
              <tr>
                <td>
                  <?= escapar($uno['nombre']) ?><br>
                  <span class="texto-menor"><?= escapar($uno['correo']) ?></span>
                </td>
                <td><?= escapar(fecha_hora_en_palabras($uno['creado_en'])) ?></td>
                <td><?= escapar(fecha_hora_en_palabras($uno['expira_en'])) ?></td>
                <td><?= escapar($uno['generado_por'] ?? 'Cuenta eliminada') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="texto-menor">
        El código en sí no aparece acá ni en ningún lado: se guarda cifrado. Si alguien lo perdió,
        generá uno nuevo y el anterior deja de servir en ese momento.
      </p>
    <?php endif; ?>
  </section>

  <p><a href="/admin/index.php">Volver al panel</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
