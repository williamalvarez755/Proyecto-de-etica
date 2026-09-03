<?php
/**
 * CUENTAS ADMINISTRATIVAS  (solo el superadministrador)
 * -----------------------------------------------------------------
 * Acá está la única parte del sistema que asigna un rol
 * administrativo. En ningún otro lugar del proyecto se escribe la
 * columna rol_id de una cuenta.
 *
 * Tres cuidados:
 *
 * 1. La contraseña se genera acá y se muestra UNA sola vez. Nunca se
 *    guarda en texto plano ni se escribe en la bitácora. La cuenta
 *    nueva queda obligada a cambiarla la primera vez que entra.
 *
 * 2. Las cuentas no se borran, se desactivan. La bitácora tiene que
 *    poder seguir diciendo quién verificó cada oferta, aunque esa
 *    persona ya no trabaje en la institución.
 *
 * 3. El sistema no deja quedarse sin superadministradores activos ni
 *    desactivarse uno a sí mismo. Sin esa red, un clic deja el panel
 *    cerrado para siempre y no hay puerta trasera para volver a
 *    entrar (regla 10), porque a propósito no la hay.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_superadministrador();

$yo      = usuario_actual();
$errores = [];
$nombre  = '';
$correo  = '';
$rol     = ROL_ADMINISTRADOR;

if (es_post()) {
    $accion = campo('accion');

    // --- Crear una cuenta administrativa ------------------------
    if ($accion === 'crear') {
        $nombre = limpiar_texto(campo('nombre'));
        $correo = normalizar_correo(campo('correo'));
        $rol    = campo('rol');

        if (!largo_valido($nombre, 2, 100)) {
            $errores['nombre'] = 'Escribí el nombre de la persona (entre 2 y 100 letras).';
        }
        if (!es_correo_valido($correo)) {
            $errores['correo'] = 'Ese correo no parece estar completo.';
        } elseif (existe_correo($correo)) {
            $errores['correo'] = 'Ya existe una cuenta con ese correo.';
        }
        if (!en_lista($rol, ROLES_ADMINISTRATIVOS)) {
            $errores['rol'] = 'Elegí un rol válido.';
        }

        if ($errores === []) {
            $temporal = generar_contrasena_temporal();
            $nuevo_id = crear_usuario($correo, $temporal, $nombre, $rol, true);

            registrar_accion('administrador_creado', 'usuario', $nuevo_id,
                             'Rol asignado: ' . $rol);

            // Se muestra una sola vez, después de redirigir.
            $_SESSION['credencial_nueva'] = ['correo' => $correo, 'contrasena' => $temporal];

            redirigir('/admin/administradores.php');
        }
    }

    // --- Desactivar ---------------------------------------------
    if ($accion === 'desactivar') {
        $id = id_valido(campo('usuario_id'));
        $cuenta = $id === null ? null : buscar_usuario_por_id($id);

        if ($cuenta === null || !in_array($cuenta['rol'], ROLES_ADMINISTRATIVOS, true)) {
            guardar_mensaje('error', 'Esa cuenta no existe.');
        } elseif ($id === (int) $yo['id']) {
            guardar_mensaje('error', 'No podés desactivar tu propia cuenta.');
        } elseif ($cuenta['rol'] === ROL_SUPERADMINISTRADOR && contar_superadministradores_activos() <= 1) {
            guardar_mensaje('error', 'Es el único superadministrador activo. Creá otro antes de desactivarlo.');
        } else {
            desactivar_usuario($id);
            registrar_accion('administrador_desactivado', 'usuario', $id, 'Correo: ' . $cuenta['correo']);
            guardar_mensaje('exito', 'La cuenta quedó desactivada y ya no puede entrar.');
        }
        redirigir('/admin/administradores.php');
    }

    // --- Reactivar ----------------------------------------------
    if ($accion === 'reactivar') {
        $id = id_valido(campo('usuario_id'));
        $cuenta = $id === null ? null : buscar_usuario_por_id($id);

        if ($cuenta === null || !in_array($cuenta['rol'], ROLES_ADMINISTRATIVOS, true)) {
            guardar_mensaje('error', 'Esa cuenta no existe.');
        } else {
            reactivar_usuario($id);
            registrar_accion('administrador_reactivado', 'usuario', $id, 'Correo: ' . $cuenta['correo']);
            guardar_mensaje('exito', 'La cuenta quedó activa otra vez.');
        }
        redirigir('/admin/administradores.php');
    }
}

$cuentas = listar_cuentas_administrativas();

// La credencial recién creada, para enseñarla una sola vez.
$credencial = $_SESSION['credencial_nueva'] ?? null;
unset($_SESSION['credencial_nueva']);

$titulo_pagina = 'Cuentas administrativas';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Cuentas administrativas</h1>
    <p class="texto-guia">
      Acá se crean y se desactivan las cuentas del personal que administra la plataforma.
    </p>
  </section>

  <?php if ($credencial !== null): ?>
    <section class="aviso aviso--exito">
      <p><strong>La cuenta quedó creada. Esta contraseña se muestra una sola vez.</strong></p>
      <p>Correo: <strong><?= escapar($credencial['correo']) ?></strong></p>
      <p>Contraseña temporal: <strong><?= escapar($credencial['contrasena']) ?></strong></p>
      <p class="texto-menor">
        Dásela a la persona en mano o por un medio seguro. Cuando entre, el sistema la va a
        obligar a cambiarla. Si se pierde, se crea otra: no hay forma de volver a verla.
      </p>
    </section>
  <?php endif; ?>

  <section class="pila">
    <h2 class="subtitulo">Cuentas existentes</h2>

    <?php if ($cuentas === []): ?>
      <p class="vacio">Todavía no hay ninguna cuenta administrativa además de la tuya.</p>
    <?php else: ?>
      <div class="tabla-desliza">
        <table class="tabla">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Última entrada</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($cuentas as $cuenta): ?>
            <tr>
              <td><?= escapar($cuenta['nombre']) ?></td>
              <td><?= escapar($cuenta['correo']) ?></td>
              <td><?= escapar($cuenta['rol_nombre']) ?></td>
              <td>
                <?php if ((int) $cuenta['activo'] === 1): ?>
                  <span class="insignia">Activa</span>
                <?php else: ?>
                  <span class="insignia">Desactivada</span>
                <?php endif; ?>
              </td>
              <td><?= escapar(fecha_hora_en_palabras($cuenta['ultimo_acceso_en'])) ?></td>
              <td>
                <?php if ((int) $cuenta['id'] === (int) $yo['id']): ?>
                  <span class="texto-menor">Sos vos</span>
                <?php elseif ((int) $cuenta['activo'] === 1): ?>
                  <form method="post" action="/admin/administradores.php"
                        data-confirmar="¿Seguro que querés desactivar esta cuenta? No va a poder entrar más.">
                    <?php campo_csrf(); ?>
                    <input type="hidden" name="accion" value="desactivar">
                    <input type="hidden" name="usuario_id" value="<?= (int) $cuenta['id'] ?>">
                    <button class="boton boton--peligro" type="submit">Desactivar</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="/admin/administradores.php">
                    <?php campo_csrf(); ?>
                    <input type="hidden" name="accion" value="reactivar">
                    <input type="hidden" name="usuario_id" value="<?= (int) $cuenta['id'] ?>">
                    <button class="boton boton--secundario" type="submit">Reactivar</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="pila">
    <h2 class="subtitulo">Crear una cuenta nueva</h2>

    <form method="post" action="/admin/administradores.php" class="tarjeta">
      <?php campo_csrf(); ?>
      <input type="hidden" name="accion" value="crear">

      <div class="campo">
        <label class="etiqueta" for="nombre">Nombre de la persona</label>
        <input class="entrada <?= isset($errores['nombre']) ? 'entrada--error' : '' ?>"
               type="text" id="nombre" name="nombre" maxlength="100"
               value="<?= escapar($nombre) ?>" required>
        <?php if (isset($errores['nombre'])): ?>
          <span class="error-campo"><?= escapar($errores['nombre']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="correo">Correo</label>
        <input class="entrada <?= isset($errores['correo']) ? 'entrada--error' : '' ?>"
               type="email" id="correo" name="correo" maxlength="191"
               value="<?= escapar($correo) ?>" required>
        <?php if (isset($errores['correo'])): ?>
          <span class="error-campo"><?= escapar($errores['correo']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="rol">Rol</label>
        <span class="ayuda" id="ayuda-rol">
          El administrador carga y verifica ofertas. El superadministrador además
          puede crear y desactivar cuentas como esta.
        </span>
        <select class="entrada" id="rol" name="rol" aria-describedby="ayuda-rol">
          <option value="<?= escapar(ROL_ADMINISTRADOR) ?>" <?= $rol === ROL_ADMINISTRADOR ? 'selected' : '' ?>>Administrador</option>
          <option value="<?= escapar(ROL_SUPERADMINISTRADOR) ?>" <?= $rol === ROL_SUPERADMINISTRADOR ? 'selected' : '' ?>>Superadministrador</option>
        </select>
        <?php if (isset($errores['rol'])): ?>
          <span class="error-campo"><?= escapar($errores['rol']) ?></span>
        <?php endif; ?>
      </div>

      <p class="ayuda separado">
        El sistema genera la contraseña y la muestra una sola vez.
        La persona va a tener que cambiarla en cuanto entre.
      </p>

      <div class="acciones">
        <button class="boton boton--principal" type="submit">Crear la cuenta</button>
      </div>
    </form>
  </section>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
