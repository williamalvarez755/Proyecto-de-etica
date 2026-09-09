<?php
/**
 * MI CUENTA
 * -----------------------------------------------------------------
 * El área personal. En esta fase muestra lo que existe: los datos de
 * la cuenta y qué guardamos exactamente de la persona.
 *
 * Esa lista de "qué guardamos" no es decoración: en un país sin ley de
 * protección de datos, poder ver en una pantalla todo lo que el sistema
 * tiene sobre uno es de las pocas garantías reales que se pueden dar.
 */

require __DIR__ . '/../../app/nucleo/inicio.php';

requerir_rol_usuario();

$usuario = usuario_actual();

$titulo_pagina = 'Mi cuenta';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <?php
  $mi_perfil  = buscar_perfil((int) $usuario['id']);
  $confirmado = $mi_perfil !== null && $mi_perfil['confirmado_en'] !== null;
  ?>

  <section class="pila">
    <h1 class="titulo-pagina">Hola, <?= escapar($usuario['nombre']) ?></h1>
    <p class="texto-guia">
      Desde acá podés ver ofertas, guardar las que te sirvan y mantener tus datos al día.
    </p>
  </section>

  <?php if (!$confirmado): ?>
    <section class="aviso aviso--aviso pila">
      <p><strong>Todavía no completaste tu perfil.</strong></p>
      <p>
        Contanos qué sabés hacer y vamos a poder mostrarte las ofertas que coinciden con tu
        experiencia. Podés subir tu currículum o llenar un formulario corto.
      </p>
      <div class="acciones">
        <a class="boton boton--principal" href="/cuenta/cv.php">Subir mi currículum</a>
        <a class="boton boton--secundario" href="/cuenta/confirmar_perfil.php">Llenar el formulario</a>
      </div>
    </section>
  <?php endif; ?>

  <section class="rejilla rejilla--dos">
    <?php if ($confirmado): ?>
      <div class="tarjeta pila">
        <h2 class="tarjeta__titulo">Ofertas para vos</h2>
        <p>Las que coinciden con tu perfil, con la explicación de por qué te aparecen.</p>
        <div class="acciones">
          <a class="boton boton--principal" href="/cuenta/recomendadas.php">Ver ofertas para mí</a>
        </div>
      </div>
    <?php endif; ?>

    <div class="tarjeta pila">
      <h2 class="tarjeta__titulo">Todas las ofertas</h2>
      <p>Buscá por oficio, país y fecha. Todas con su origen verificado.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/ofertas.php">Ver las ofertas</a>
      </div>
    </div>

    <div class="tarjeta pila">
      <h2 class="tarjeta__titulo">Mis postulaciones</h2>
      <p>A qué ofertas autorizaste compartir tu currículum, y cuándo.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/postulaciones.php">Ver mis postulaciones</a>
      </div>
    </div>

    <div class="tarjeta pila">
      <h2 class="tarjeta__titulo">Ofertas que guardé</h2>
      <p>Las que apartaste para leer con calma después.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/guardadas.php">Ver mis guardadas</a>
      </div>
    </div>

    <div class="tarjeta pila">
      <h2 class="tarjeta__titulo">Mi perfil</h2>
      <p>Qué sabés hacer, tus estudios, tus idiomas y a dónde estarías dispuesto a ir.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/perfil.php">Ver mi perfil</a>
      </div>
    </div>

    <div class="tarjeta pila">
      <h2 class="tarjeta__titulo">Mi currículum</h2>
      <p>Subirlo, cambiarlo, borrarlo, o armar uno con tus datos.</p>
      <div class="acciones">
        <a class="boton boton--secundario" href="/cuenta/cv.php">Mi currículum</a>
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
      <?php if ($mi_perfil !== null && $mi_perfil['cv_archivo'] !== null): ?>
        <li>El archivo de tu currículum, guardado donde no se puede llegar por una dirección web.</li>
      <?php endif; ?>
      <li>Las ofertas que hayas guardado.</li>
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
      Si creés que alguien más la sabe, cambiala ahora. Acordate de que no hay recuperación
      por correo: si la olvidás, vas a necesitar ayuda de la institución.
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
