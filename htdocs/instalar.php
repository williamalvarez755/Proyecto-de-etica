<?php
/**
 * INSTALACIÓN: LA PRIMERA CUENTA RESPONSABLE DEL SISTEMA
 * -----------------------------------------------------------------
 * ESTE ARCHIVO SE USA UNA VEZ Y SE BORRA POR FTP.
 *
 * Por qué existe: el sistema no trae ninguna cuenta de fábrica, ni
 * contraseña maestra, ni usuario escondido (regla 10). Entonces hay
 * que crear la primera cuenta de alguna manera, y esa manera tiene que
 * ser explícita y documentada, no un atajo.
 *
 * Cómo se protege de que alguien más la use antes que vos:
 *
 *   1. Se niega a funcionar si YA existe una cuenta administrativa.
 *      Después de usarlo una vez, queda muerto solo.
 *
 *   2. Pide una clave que solo puede conocer quien tiene el FTP: hay
 *      que crear el archivo app/config/instalacion.txt (que está fuera
 *      de la carpeta pública) y escribir en el formulario exactamente
 *      lo mismo que dice ese archivo.
 *
 * Sin el segundo punto habría una ventana peligrosa: entre que se
 * suben los archivos y se crea la cuenta, cualquiera que adivine la
 * dirección /instalar.php se queda con el sistema. Los robots prueban
 * esa dirección todo el tiempo.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

$ruta_clave = RAIZ_APP . '/config/instalacion.txt';

// -----------------------------------------------------------------
//  1. ¿Ya hay alguien adentro? Entonces esto ya no se usa.
// -----------------------------------------------------------------
if (existe_alguna_cuenta_administrativa()) {
    $titulo_pagina = 'La plataforma ya está instalada';
    $texto = 'Ya existe una cuenta administrativa, así que esta página no hace nada. '
           . 'Borrá el archivo instalar.php por FTP: no debe quedarse en el servidor.';
    require RAIZ_APP . '/vistas/pagina_aviso.php';
    exit;
}

// -----------------------------------------------------------------
//  2. La clave de instalación, que vive fuera de htdocs
// -----------------------------------------------------------------
$clave_guardada = is_file($ruta_clave) ? trim((string) file_get_contents($ruta_clave)) : '';
$hay_clave      = mb_strlen($clave_guardada) >= 12;

$errores = [];
$nombre  = '';
$correo  = '';

if ($hay_clave && es_post()) {
    $nombre       = limpiar_texto(campo('nombre'));
    $correo       = normalizar_correo(campo('correo'));
    $contrasena   = campo_crudo('contrasena');
    $confirmacion = campo_crudo('confirmacion');
    $clave        = trim(campo_crudo('clave'));

    if (!hash_equals($clave_guardada, $clave)) {
        $errores['clave'] = 'La clave de instalación no coincide con la del archivo.';
    }
    if (!largo_valido($nombre, 2, 100)) {
        $errores['nombre'] = 'Escribí el nombre de la persona responsable.';
    }
    if (!es_correo_valido($correo)) {
        $errores['correo'] = 'Ese correo no parece estar completo.';
    }
    $problema = revisar_contrasena($contrasena);
    if ($problema !== null) {
        $errores['contrasena'] = $problema;
    } elseif ($contrasena !== $confirmacion) {
        $errores['confirmacion'] = 'Las dos contraseñas no son iguales.';
    }

    if ($errores === []) {
        $id = crear_usuario($correo, $contrasena, $nombre, ROL_SUPERADMINISTRADOR);

        registrar_accion('instalacion_inicial', 'usuario', $id,
                         'Primera cuenta del sistema', $id);

        // Se intenta borrar la clave: ya no sirve para nada y es una
        // cosa menos que quede olvidada en el servidor.
        @unlink($ruta_clave);

        iniciar_sesion_de_usuario($id);
        marcar_ultimo_acceso($id);

        guardar_mensaje(
            'exito',
            'Listo. Ahora borrá por FTP los archivos instalar.php y diagnostico.php.'
        );
        redirigir('/admin/index.php');
    }
}

$titulo_pagina = 'Instalación';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor contenedor--angosto pila">

  <h1 class="titulo-pagina">Crear la cuenta responsable del sistema</h1>

  <?php if (!$hay_clave): ?>

    <div class="aviso aviso--aviso">
      <p><strong>Falta un paso antes de poder seguir.</strong></p>
      <p>
        Para comprobar que sos vos quien está instalando, y no alguien que encontró esta
        dirección de casualidad, hace falta un archivo que solo puede crear quien tiene
        acceso al FTP.
      </p>
    </div>

    <div class="tarjeta pila">
      <h2 class="tarjeta__titulo">Qué hacer</h2>
      <ol class="pila">
        <li>Entrá al FTP del hosting.</li>
        <li>Creá el archivo <strong>app/config/instalacion.txt</strong>.</li>
        <li>
          Escribí adentro una frase larga que inventes vos, de al menos 12 caracteres.
          Por ejemplo: <em>instalacion-plataforma-septiembre</em>
        </li>
        <li>Guardá el archivo, volvé a esta página y recargala.</li>
      </ol>
      <p class="texto-menor">
        Después de crear la cuenta, ese archivo se borra solo.
      </p>
    </div>

  <?php else: ?>

    <p class="texto-guia">
      Esta cuenta va a ser la responsable del sistema: puede crear y desactivar
      administradores. Se crea una sola vez.
    </p>

    <form method="post" action="/instalar.php" class="tarjeta">
      <?php campo_csrf(); ?>

      <div class="campo">
        <label class="etiqueta" for="nombre">Nombre de la persona responsable</label>
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
        <label class="etiqueta" for="contrasena">Contraseña</label>
        <span class="ayuda" id="ayuda-contrasena">
          Al menos <?= (int) CONTRASENA_LARGO_MINIMO ?> caracteres.
          Esta es la cuenta más importante del sistema: que sea larga.
        </span>
        <input class="entrada <?= isset($errores['contrasena']) ? 'entrada--error' : '' ?>"
               type="password" id="contrasena" name="contrasena" data-ver
               autocomplete="new-password" aria-describedby="ayuda-contrasena" required>
        <?php if (isset($errores['contrasena'])): ?>
          <span class="error-campo"><?= escapar($errores['contrasena']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="confirmacion">Escribí otra vez la contraseña</label>
        <input class="entrada <?= isset($errores['confirmacion']) ? 'entrada--error' : '' ?>"
               type="password" id="confirmacion" name="confirmacion"
               autocomplete="new-password" required>
        <?php if (isset($errores['confirmacion'])): ?>
          <span class="error-campo"><?= escapar($errores['confirmacion']) ?></span>
        <?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="clave">Clave de instalación</label>
        <span class="ayuda" id="ayuda-clave">
          Exactamente lo que escribiste dentro de app/config/instalacion.txt
        </span>
        <input class="entrada <?= isset($errores['clave']) ? 'entrada--error' : '' ?>"
               type="text" id="clave" name="clave" autocomplete="off"
               aria-describedby="ayuda-clave" required>
        <?php if (isset($errores['clave'])): ?>
          <span class="error-campo"><?= escapar($errores['clave']) ?></span>
        <?php endif; ?>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal boton--ancho" type="submit">Crear la cuenta</button>
      </div>
    </form>

  <?php endif; ?>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
