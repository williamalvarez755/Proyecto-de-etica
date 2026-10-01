<?php
/**
 * MI CUENTA
 * -----------------------------------------------------------------
 * El área personal: dónde está parada la persona, qué puede hacer, y
 * qué guardamos exactamente de ella.
 *
 * Esa lista de "qué guardamos" no es decoración: en un país sin ley de
 * protección de datos, poder ver en una pantalla todo lo que el sistema
 * tiene sobre uno es de las pocas garantías reales que se pueden dar.
 * Por eso sale de los mismos conteos que muestra la pantalla de borrar
 * la cuenta: si algo se guarda, aparece acá.
 *
 * El camino de tres pasos termina en "mirá las ofertas para vos", y no
 * en nada parecido a "conseguí trabajo": la plataforma no consigue
 * trabajo (regla 12), y la pantalla no puede dar a entender lo
 * contrario.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario    = usuario_actual();
$usuario_id = (int) $usuario['id'];
$mi_perfil  = buscar_perfil($usuario_id);
$confirmado = $mi_perfil !== null && $mi_perfil['confirmado_en'] !== null;
$tiene_cv   = $mi_perfil !== null && $mi_perfil['cv_archivo'] !== null;
$guardado   = resumen_de_lo_que_se_borra($usuario_id);

$titulo_pagina = 'Mi cuenta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Hola, <?= escapar($usuario['nombre']) ?></h1>
    <p class="texto-guia">
      Desde acá podés ver ofertas, guardar las que te sirvan y mantener tus datos al día.
    </p>
  </section>

  <section class="pila" aria-labelledby="titulo-camino">
    <h2 class="subtitulo" id="titulo-camino">
      <?= $confirmado ? 'Tu perfil está listo' : 'Para ver ofertas que coincidan con lo que sabés hacer' ?>
    </h2>

    <ol class="pasos">
      <li class="paso paso--hecho">
        <div>
          <p class="paso__titulo">Creaste tu cuenta</p>
          <p class="paso__texto">El <?= escapar(fecha_en_palabras($usuario['creado_en'])) ?>.</p>
        </div>
      </li>

      <li class="paso <?= $confirmado ? 'paso--hecho' : 'paso--siguiente' ?>"<?= $confirmado ? '' : ' aria-current="step"' ?>>
        <div>
          <p class="paso__titulo">Contanos qué sabés hacer</p>
          <?php if ($confirmado): ?>
            <p class="paso__texto">
              Revisaste tus datos el <?= escapar(fecha_en_palabras($mi_perfil['confirmado_en'])) ?>.
              Podés cambiarlos cuando quieras.
            </p>
            <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Revisar mis datos</a>
          <?php else: ?>
            <p class="paso__texto">
              Subí tu currículum o llená un formulario corto. Antes de mostrarte cualquier oferta
              vas a revisar y corregir lo que entendimos.
            </p>
            <div class="acciones">
              <a class="boton boton--principal" href="/cuenta/cv.php">Subir mi currículum</a>
              <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Llenar el formulario</a>
            </div>
          <?php endif; ?>
        </div>
      </li>

      <li class="paso <?= $confirmado ? 'paso--siguiente' : 'paso--despues' ?>"<?= $confirmado ? ' aria-current="step"' : '' ?>>
        <div>
          <p class="paso__titulo">Mirá las ofertas para vos</p>
          <?php if ($confirmado): ?>
            <p class="paso__texto">Las que coinciden con tu perfil, con la explicación de por qué te aparecen.</p>
            <a class="boton boton--principal" href="/cuenta/recomendadas.php">Ver ofertas para mí</a>
          <?php else: ?>
            <p class="paso__texto">Se habilita cuando termines el paso anterior.</p>
          <?php endif; ?>
        </div>
      </li>
    </ol>
  </section>

  <section class="rejilla rejilla--dos">
    <div class="tarjeta tarjeta--enlace pila">
      <h2 class="tarjeta__titulo">Todas las ofertas</h2>
      <p>Buscá por oficio, país y fecha. Todas con su origen verificado.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/ofertas.php">Ver las ofertas</a>
      </div>
    </div>

    <div class="tarjeta tarjeta--enlace pila">
      <h2 class="tarjeta__titulo">
        Mis postulaciones
        <?php if ($guardado['postulaciones'] > 0): ?>
          <span class="contador-tarjeta"><?= (int) $guardado['postulaciones'] ?></span>
        <?php endif; ?>
      </h2>
      <p>A qué ofertas autorizaste compartir tu currículum, y cuándo.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/postulaciones.php">Ver mis postulaciones</a>
      </div>
    </div>

    <div class="tarjeta tarjeta--enlace pila">
      <h2 class="tarjeta__titulo">
        Ofertas que guardé
        <?php if ($guardado['guardadas'] > 0): ?>
          <span class="contador-tarjeta"><?= (int) $guardado['guardadas'] ?></span>
        <?php endif; ?>
      </h2>
      <p>Las que apartaste para leer con calma después.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/guardadas.php">Ver mis guardadas</a>
      </div>
    </div>

    <div class="tarjeta tarjeta--enlace pila">
      <h2 class="tarjeta__titulo">Mi currículum</h2>
      <p>
        <?= $tiene_cv
            ? 'Tenés uno guardado. Podés cambiarlo, borrarlo o descargarlo.'
            : 'Subirlo, o armar uno ordenado con tus datos para imprimir.' ?>
      </p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/cv.php">Mi currículum</a>
        <?php if ($confirmado): ?>
          <a class="boton boton--secundario" href="/cuenta/perfil.php">Mi perfil</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Los datos de tu cuenta</h2>
    <dl class="lista-datos">
      <dt>Nombre</dt>
      <dd><?= escapar($usuario['nombre']) ?></dd>

      <dt>Correo</dt>
      <dd><?= escapar($usuario['correo']) ?></dd>

      <dt>Cuenta creada el</dt>
      <dd><?= escapar(fecha_en_palabras($usuario['creado_en'])) ?></dd>

      <dt>Última vez que entraste</dt>
      <dd><?= escapar(fecha_hora_en_palabras($usuario['ultimo_acceso_en'])) ?></dd>
    </dl>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Qué guardamos de vos</h2>
    <p>Esto es todo lo que el sistema tiene sobre vos en este momento:</p>
    <ul class="pila">
      <li>El nombre que escribiste.</li>
      <li>Tu correo electrónico.</li>
      <li>Tu contraseña, guardada de forma que ni nosotros la podemos leer.</li>
      <li>La fecha en que creaste la cuenta y la última vez que entraste.</li>
      <?php if ($confirmado): ?>
        <li>Tus oficios, años de experiencia, estudios, idiomas, a qué países irías y desde cuándo.</li>
      <?php endif; ?>
      <?php if ($guardado['archivos'] > 0): ?>
        <li>
          <?= $guardado['archivos'] === 1
              ? 'Un archivo de currículum'
              : $guardado['archivos'] . ' archivos de currículum (el actual y los que ya compartiste con alguna oferta)' ?>,
          en una carpeta que no se puede abrir desde internet y con un nombre al azar.
        </li>
      <?php endif; ?>
      <?php if ($guardado['postulaciones'] > 0): ?>
        <li>
          Tus <?= (int) $guardado['postulaciones'] ?>
          postulacion<?= $guardado['postulaciones'] === 1 ? '' : 'es' ?>, cada una con el registro
          de cuándo autorizaste compartir tu currículum y qué se compartió.
        </li>
      <?php endif; ?>
      <?php if ($guardado['guardadas'] > 0): ?>
        <li>Las <?= (int) $guardado['guardadas'] ?> oferta<?= $guardado['guardadas'] === 1 ? '' : 's' ?> que guardaste.</li>
      <?php endif; ?>
      <?php if ($guardado['reportes'] > 0): ?>
        <li>
          <?= $guardado['reportes'] === 1 ? 'El reporte' : 'Los ' . (int) $guardado['reportes'] . ' reportes' ?>
          que hiciste sobre ofertas sospechosas. Solo lo ve la institución, nunca el público.
        </li>
      <?php endif; ?>
    </ul>
    <p class="texto-menor">
      No tenemos tu DPI, ni tu pasaporte, ni tu situación migratoria, ni datos bancarios,
      ni tu fotografía. Tampoco tu edad, tu sexo ni de qué departamento sos:
      esos datos no se piden en ninguna parte de la plataforma.
    </p>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Tu contraseña</h2>
    <p>
      Si creés que alguien más la sabe, cambiala ahora: las sesiones que hayan quedado abiertas
      en otro teléfono o computadora se cierran solas. Acordate de que no hay recuperación por
      correo: si la olvidás, vas a necesitar ayuda de la institución.
    </p>
    <div class="acciones">
      <a class="boton boton--secundario" href="/cuenta/cambiar_contrasena.php">Cambiar mi contraseña</a>
      <a class="boton boton--secundario" href="/cuenta/salir.php">Cerrar mi sesión</a>
    </div>
  </section>

  <section class="tarjeta pila">
    <h2 class="tarjeta__titulo">Borrar mi cuenta</h2>
    <p>
      Podés borrar tu cuenta y todos tus datos cuando quieras, sin pedirle permiso a nadie
      ni escribirle a nadie. Se borra de verdad: los registros y el archivo de tu currículum.
    </p>
    <div class="acciones">
      <a class="boton boton--peligro" href="/cuenta/eliminar.php">Borrar mi cuenta</a>
    </div>
  </section>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
