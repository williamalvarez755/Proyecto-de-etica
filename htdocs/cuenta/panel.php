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

  <section class="pila">
    <h1 class="titulo-pagina">Hola, <?= escapar($usuario['nombre']) ?></h1>
    <p class="texto-guia">
      Tu cuenta ya está lista. Todavía no hay ofertas cargadas en la plataforma;
      cuando las haya, vas a poder verlas y guardarlas desde acá.
    </p>
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

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
