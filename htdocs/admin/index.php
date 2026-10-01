<?php
/**
 * PANEL DE ADMINISTRACIÓN — INICIO
 * -----------------------------------------------------------------
 * Muestra únicamente las secciones que ya existen y a las que esta
 * cuenta tiene permiso.
 *
 * Ojo: que un enlace no aparezca NO es lo que protege la sección. Lo
 * que protege es el requerir_permiso() que hay al principio de cada
 * página (regla 5). Esto es solo para no enseñar puertas cerradas.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_administrativo();

$usuario = usuario_actual();

$total_usuarios = (int) consultar_valor(
    'SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE r.codigo = ?',
    [ROL_USUARIO]
);
$total_administrativos = (int) consultar_valor(
    'SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
     WHERE r.codigo IN (?, ?) AND u.activo = 1',
    [ROL_ADMINISTRADOR, ROL_SUPERADMINISTRADOR]
);

$titulo_pagina = 'Panel';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Panel de administración</h1>
    <p class="texto-guia">
      Entraste como <strong><?= escapar($usuario['nombre']) ?></strong>
      <span class="insignia insignia--rol"><?= escapar($usuario['rol_nombre']) ?></span>
    </p>
  </section>

  <?php
  $conteo_ofertas = contar_por_estado();
  $pendientes     = (int) ($conteo_ofertas['pendiente'] ?? 0);
  $publicadas     = (int) ($conteo_ofertas['publicada'] ?? 0);
  $por_vencer     = count(ofertas_publicadas_vencidas());
  ?>

  <?php if ($pendientes > 0 && tiene_permiso('ofertas.verificar')): ?>
    <p class="aviso aviso--aviso">
      Hay <strong><?= $pendientes ?></strong> oferta<?= $pendientes === 1 ? '' : 's' ?>
      esperando verificación. Mientras tanto no se ven en el sitio público.
      <a href="/admin/ofertas.php?estado=pendiente">Revisarlas</a>
    </p>
  <?php endif; ?>

  <?php $reportes_sin_resolver = tiene_permiso('reportes.revisar') ? reportes_pendientes() : 0; ?>
  <?php if ($reportes_sin_resolver > 0): ?>
    <p class="aviso aviso--error">
      Hay <strong><?= (int) $reportes_sin_resolver ?></strong>
      reporte<?= $reportes_sin_resolver === 1 ? '' : 's' ?> de usuarios sin resolver.
      <a href="/admin/reportes.php">Revisarlos</a>
    </p>
  <?php endif; ?>

  <?php $sin_reclutador_vigente = tiene_permiso('ofertas.ver') ? ofertas_publicadas_con_reclutador_no_vigente() : []; ?>
  <?php if ($sin_reclutador_vigente !== []): ?>
    <div class="aviso aviso--error pila">
      <p>
        <strong><?= count($sin_reclutador_vigente) ?></strong>
        oferta<?= count($sin_reclutador_vigente) === 1 ? '' : 's' ?> publicada<?= count($sin_reclutador_vigente) === 1 ? '' : 's' ?>
        dejó de verse en el sitio porque su reclutador ya no tiene la autorización vigente.
        Revisá cada una y ponela en revisión o retirala.
      </p>
      <ul>
        <?php foreach ($sin_reclutador_vigente as $fila): ?>
          <li>
            <a href="/admin/oferta_editar.php?id=<?= (int) $fila['id'] ?>"><?= escapar($fila['titulo']) ?></a>
            · <?= escapar($fila['reclutador_nombre']) ?>
            (<?= escapar(ESTADOS_RECLUTADOR[$fila['reclutador_estado']] ?? $fila['reclutador_estado']) ?><?php
            if ($fila['reclutador_vigencia'] !== null): ?>, hasta el <?= escapar(fecha_en_palabras($fila['reclutador_vigencia'])) ?><?php endif; ?>)
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($por_vencer > 0 && tiene_permiso('mantenimiento.ejecutar')): ?>
    <p class="aviso aviso--aviso">
      Hay <strong><?= $por_vencer ?></strong> oferta<?= $por_vencer === 1 ? '' : 's' ?>
      con la fecha vencida que todavía figuran como publicadas. Ya no se ven en el sitio,
      pero conviene ponerlas al día.
      <a href="/admin/mantenimiento.php">Ir a mantenimiento</a>
    </p>
  <?php endif; ?>

  <section class="rejilla rejilla--dos">
    <div class="tarjeta">
      <h2 class="tarjeta__titulo">Ofertas publicadas</h2>
      <p class="texto-guia"><?= $publicadas ?> visibles en el sitio</p>
    </div>
    <div class="tarjeta">
      <h2 class="tarjeta__titulo">Esperando verificación</h2>
      <p class="texto-guia"><?= $pendientes ?></p>
    </div>
    <div class="tarjeta">
      <h2 class="tarjeta__titulo">Cuentas de personas</h2>
      <p class="texto-guia"><?= $total_usuarios ?> registrada<?= $total_usuarios === 1 ? '' : 's' ?></p>
    </div>
    <div class="tarjeta">
      <h2 class="tarjeta__titulo">Cuentas administrativas activas</h2>
      <p class="texto-guia"><?= $total_administrativos ?></p>
    </div>
  </section>

  <section class="pila">
    <h2 class="subtitulo">Secciones</h2>

    <div class="rejilla rejilla--dos">

      <?php if (tiene_permiso('ofertas.ver')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Ofertas</h3>
          <p>Cargar, verificar, publicar y retirar ofertas.</p>
          <div class="acciones">
            <a class="boton boton--principal" href="/admin/ofertas.php">Ver las ofertas</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (tiene_permiso('fuentes.gestionar')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Fuentes</h3>
          <p>De dónde salen las ofertas. Sin fuente no se puede cargar ninguna.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/fuentes.php">Ver las fuentes</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (tiene_permiso('reclutadores.gestionar')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Reclutadores autorizados</h3>
          <p>El registro del Ministerio de Trabajo, cargado a mano.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/reclutadores.php">Ver el registro</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (tiene_permiso('importacion.csv')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Importar ofertas</h3>
          <p>Cargar varias ofertas de una vez desde un archivo CSV.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/importar_csv.php">Importar</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (tiene_permiso('reportes.revisar')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Reportes</h3>
          <p>Lo que la gente avisó sobre las ofertas. Ninguna se retira sola: la decisión es tuya.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/reportes.php">Ver los reportes</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (tiene_permiso('usuarios.restablecer')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Restablecer contraseñas</h3>
          <p>Darle un código a alguien que olvidó su contraseña, para que ponga una nueva.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/restablecimientos.php">Abrir</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (tiene_permiso('mantenimiento.ejecutar')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Mantenimiento</h3>
          <p>Poner al día las ofertas vencidas. Conviene entrar una vez por semana.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/mantenimiento.php">Abrir</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (tiene_permiso('bitacora.ver')): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Bitácora</h3>
          <p>Todo lo que se ha hecho en el panel: quién, qué y cuándo.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/bitacora.php">Ver la bitácora</a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (es_superadministrador()): ?>
        <div class="tarjeta pila">
          <h3 class="tarjeta__titulo">Cuentas administrativas</h3>
          <p>Crear administradores nuevos, desactivar los que ya no trabajan acá.</p>
          <div class="acciones">
            <a class="boton boton--secundario" href="/admin/administradores.php">Administrar cuentas</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="tarjeta pila">
        <h3 class="tarjeta__titulo">Mi contraseña</h3>
        <p>Cambiala si creés que alguien más la conoce.</p>
        <div class="acciones">
          <a class="boton boton--secundario" href="/cuenta/cambiar_contrasena.php">Cambiar mi contraseña</a>
        </div>
      </div>

    </div>
  </section>

  <section class="tarjeta">
    <h2 class="tarjeta__titulo">Lo que todavía no está construido</h2>
    <p>
      El emparejamiento entre perfiles y ofertas, la postulación con consentimiento, los
      reportes de usuarios y el verificador público de reclutadores se construyen en las
      siguientes etapas. Se prefiere no mostrar botones que no hagan nada.
    </p>
  </section>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
