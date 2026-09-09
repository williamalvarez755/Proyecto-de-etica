<?php
/**
 * PANEL — REGISTRO DE RECLUTADORES AUTORIZADOS
 * -----------------------------------------------------------------
 * Acá se carga a mano el registro público de reclutadores autorizados
 * del Ministerio de Trabajo.
 *
 * Es el dato más valioso de todo el proyecto (decisión D-006). En la
 * Fase 5, cualquiera va a poder escribir acá el nombre de quien lo
 * contactó por WhatsApp y saber si está autorizado. Por eso cada ficha
 * pide de qué documento o página se sacó: si mañana alguien pregunta
 * "¿y ustedes cómo saben?", la respuesta tiene que estar guardada.
 *
 * Los "otros nombres" existen porque un reclutador puede operar con un
 * nombre comercial distinto al del registro, y ese es justo el caso
 * donde la persona se confunde.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('reclutadores.gestionar');

$errores = [];
$editar  = null;

$datos = [
    'nombre' => '', 'numero_registro' => '', 'estado' => 'vigente',
    'vigencia_desde' => '', 'vigencia_hasta' => '',
    'fuente_registro' => '', 'notas' => '',
];

if (es_post()) {
    $accion = campo('accion');

    // --- Agregar otro nombre --------------------------------------
    if ($accion === 'agregar_alias') {
        $reclutador_id = id_valido(campo('reclutador_id'));
        $alias = limpiar_texto(campo('alias'));

        if ($reclutador_id === null || buscar_reclutador($reclutador_id) === null) {
            guardar_mensaje('error', 'Ese reclutador no existe.');
        } elseif (!largo_valido($alias, 3, 191)) {
            guardar_mensaje('error', 'El otro nombre tiene que tener entre 3 y 191 caracteres.');
        } else {
            agregar_alias($reclutador_id, $alias);
            registrar_accion('reclutador_alias_agregado', 'reclutador', $reclutador_id, $alias);
            guardar_mensaje('exito', 'Se agregó el otro nombre.');
        }
        redirigir('/admin/reclutadores.php?editar=' . (int) $reclutador_id);
    }

    // --- Quitar otro nombre ---------------------------------------
    if ($accion === 'borrar_alias') {
        $reclutador_id = id_valido(campo('reclutador_id'));
        $alias_id      = id_valido(campo('alias_id'));

        if ($reclutador_id !== null && $alias_id !== null) {
            borrar_alias($alias_id, $reclutador_id);
            registrar_accion('reclutador_alias_borrado', 'reclutador', $reclutador_id, 'Alias ' . $alias_id);
            guardar_mensaje('exito', 'Se quitó ese nombre.');
        }
        redirigir('/admin/reclutadores.php?editar=' . (int) $reclutador_id);
    }

    // --- Crear o editar la ficha ----------------------------------
    if ($accion === 'guardar') {
        $id_editando = campo('reclutador_id') === '' ? null : id_valido(campo('reclutador_id'));

        foreach ($datos as $campo => $valor) {
            $datos[$campo] = limpiar_texto(campo($campo));
        }

        if (!largo_valido($datos['nombre'], 3, 191)) {
            $errores['nombre'] = 'El nombre tiene que tener entre 3 y 191 caracteres.';
        }
        if (!en_catalogo($datos['estado'], ESTADOS_RECLUTADOR)) {
            $errores['estado'] = 'Elegí en qué estado está la autorización.';
        }
        if (!largo_valido($datos['fuente_registro'], 3, 191)) {
            $errores['fuente_registro'] = 'Escribí de dónde sacaste este dato. Es lo que respalda la ficha.';
        }
        if ($datos['vigencia_desde'] !== '' && !es_fecha_valida($datos['vigencia_desde'])) {
            $errores['vigencia_desde'] = 'Esa fecha no es válida.';
        }
        if ($datos['vigencia_hasta'] !== '' && !es_fecha_valida($datos['vigencia_hasta'])) {
            $errores['vigencia_hasta'] = 'Esa fecha no es válida.';
        }
        if ($datos['vigencia_desde'] !== '' && $datos['vigencia_hasta'] !== ''
            && $datos['vigencia_hasta'] < $datos['vigencia_desde']) {
            $errores['vigencia_hasta'] = 'La vigencia no puede terminar antes de empezar.';
        }

        if ($errores === []) {
            if ($id_editando === null) {
                $nuevo = crear_reclutador($datos, (int) id_usuario_actual());
                registrar_accion('reclutador_creado', 'reclutador', $nuevo, $datos['nombre']);
                guardar_mensaje('exito', 'El reclutador quedó en el registro.');
                redirigir('/admin/reclutadores.php?editar=' . $nuevo);
            } elseif (buscar_reclutador($id_editando) === null) {
                guardar_mensaje('error', 'Ese reclutador no existe.');
                redirigir('/admin/reclutadores.php');
            } else {
                actualizar_reclutador($id_editando, $datos);
                registrar_accion('reclutador_editado', 'reclutador', $id_editando, $datos['nombre']);
                guardar_mensaje('exito', 'Se guardaron los cambios.');
                redirigir('/admin/reclutadores.php');
            }
        }
    }
}

// --- ¿Se está editando uno? --------------------------------------
$id_editar = id_valido(parametro('editar'));
if ($id_editar !== null && $errores === []) {
    $editar = buscar_reclutador($id_editar);
    if ($editar !== null) {
        $datos = [
            'nombre'          => $editar['nombre'],
            'numero_registro' => $editar['numero_registro'] ?? '',
            'estado'          => $editar['estado'],
            'vigencia_desde'  => $editar['vigencia_desde'] ?? '',
            'vigencia_hasta'  => $editar['vigencia_hasta'] ?? '',
            'fuente_registro' => $editar['fuente_registro'],
            'notas'           => $editar['notas'] ?? '',
        ];
    }
}

$busqueda     = limpiar_texto(parametro('buscar'));
$reclutadores = listar_reclutadores($busqueda);

$titulo_pagina = 'Reclutadores autorizados';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Reclutadores autorizados</h1>
    <p class="texto-guia">
      El registro del Ministerio de Trabajo, cargado a mano. Es lo que permite decirle a alguien
      si quien lo contactó está autorizado o no.
    </p>
  </section>

  <form method="get" action="/admin/reclutadores.php" class="tarjeta">
    <div class="campo">
      <label class="etiqueta" for="buscar">Buscar por nombre o número de registro</label>
      <input class="entrada" type="search" id="buscar" name="buscar" maxlength="120" value="<?= escapar($busqueda) ?>">
    </div>
    <div class="acciones separado">
      <button class="boton boton--secundario" type="submit">Buscar</button>
      <?php if ($busqueda !== ''): ?>
        <a class="boton boton--secundario" href="/admin/reclutadores.php">Ver todos</a>
      <?php endif; ?>
    </div>
  </form>

  <section class="pila">
    <h2 class="subtitulo">En el registro</h2>

    <?php if ($reclutadores === []): ?>
      <p class="vacio">
        <?= $busqueda !== '' ? 'No hay ningún reclutador con ese nombre.' : 'Todavía no hay ningún reclutador cargado.' ?>
      </p>
    <?php else: ?>
      <div class="tabla-desliza">
        <table class="tabla">
          <thead>
            <tr><th>Nombre</th><th>Registro</th><th>Estado</th><th>Vigencia</th><th>Ofertas</th><th></th></tr>
          </thead>
          <tbody>
          <?php foreach ($reclutadores as $reclutador): ?>
            <tr>
              <td><?= escapar($reclutador['nombre']) ?></td>
              <td><?= escapar($reclutador['numero_registro'] ?? '—') ?></td>
              <td>
                <span class="insignia"><?= escapar(ESTADOS_RECLUTADOR[$reclutador['estado']] ?? $reclutador['estado']) ?></span>
                <?php if (!reclutador_vigente_hoy($reclutador)): ?>
                  <br><span class="texto-menor">No vigente hoy</span>
                <?php endif; ?>
              </td>
              <td class="texto-menor">
                <?= escapar(fecha_en_palabras($reclutador['vigencia_hasta'])) ?>
              </td>
              <td><?= contar_ofertas_de_reclutador((int) $reclutador['id']) ?></td>
              <td>
                <a class="boton boton--secundario" href="/admin/reclutadores.php?editar=<?= (int) $reclutador['id'] ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="pila">
    <h2 class="subtitulo"><?= $editar !== null ? 'Editar la ficha' : 'Agregar un reclutador' ?></h2>

    <form method="post" action="/admin/reclutadores.php" class="tarjeta">
      <?php campo_csrf(); ?>
      <input type="hidden" name="accion" value="guardar">
      <?php if ($editar !== null): ?>
        <input type="hidden" name="reclutador_id" value="<?= (int) $editar['id'] ?>">
      <?php endif; ?>

      <div class="campo">
        <label class="etiqueta" for="nombre">Nombre como aparece en el registro</label>
        <input class="entrada <?= isset($errores['nombre']) ? 'entrada--error' : '' ?>" type="text"
               id="nombre" name="nombre" maxlength="191" value="<?= escapar($datos['nombre']) ?>" required>
        <?php if (isset($errores['nombre'])): ?><span class="error-campo"><?= escapar($errores['nombre']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="numero_registro">Número de registro (opcional)</label>
        <input class="entrada" type="text" id="numero_registro" name="numero_registro" maxlength="60"
               value="<?= escapar($datos['numero_registro']) ?>">
      </div>

      <div class="campo">
        <label class="etiqueta" for="estado">Estado de la autorización</label>
        <select class="entrada" id="estado" name="estado" required>
          <?php foreach (ESTADOS_RECLUTADOR as $codigo => $nombre): ?>
            <option value="<?= escapar($codigo) ?>" <?= $datos['estado'] === $codigo ? 'selected' : '' ?>><?= escapar($nombre) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['estado'])): ?><span class="error-campo"><?= escapar($errores['estado']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="vigencia_desde">Vigente desde (opcional)</label>
        <input class="entrada <?= isset($errores['vigencia_desde']) ? 'entrada--error' : '' ?>" type="date"
               id="vigencia_desde" name="vigencia_desde" value="<?= escapar($datos['vigencia_desde']) ?>">
        <?php if (isset($errores['vigencia_desde'])): ?><span class="error-campo"><?= escapar($errores['vigencia_desde']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="vigencia_hasta">Vigente hasta (opcional)</label>
        <span class="ayuda" id="ayuda-vigencia">
          Si esta fecha ya pasó, el sistema lo trata como no vigente aunque el estado diga otra cosa,
          y no deja verificar ofertas suyas.
        </span>
        <input class="entrada <?= isset($errores['vigencia_hasta']) ? 'entrada--error' : '' ?>" type="date"
               id="vigencia_hasta" name="vigencia_hasta" aria-describedby="ayuda-vigencia"
               value="<?= escapar($datos['vigencia_hasta']) ?>">
        <?php if (isset($errores['vigencia_hasta'])): ?><span class="error-campo"><?= escapar($errores['vigencia_hasta']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="fuente_registro">De dónde sacaste este dato</label>
        <span class="ayuda" id="ayuda-fuente">
          El documento o la página del Ministerio, con su fecha. Es lo que respalda la ficha.
        </span>
        <input class="entrada <?= isset($errores['fuente_registro']) ? 'entrada--error' : '' ?>" type="text"
               id="fuente_registro" name="fuente_registro" maxlength="191" aria-describedby="ayuda-fuente"
               value="<?= escapar($datos['fuente_registro']) ?>" required>
        <?php if (isset($errores['fuente_registro'])): ?><span class="error-campo"><?= escapar($errores['fuente_registro']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="notas">Notas internas (opcional)</label>
        <textarea class="entrada" id="notas" name="notas" rows="3"><?= escapar($datos['notas']) ?></textarea>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal" type="submit">Guardar</button>
        <?php if ($editar !== null): ?>
          <a class="boton boton--secundario" href="/admin/reclutadores.php">Cancelar la edición</a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <?php if ($editar !== null): ?>
    <section class="pila">
      <h2 class="subtitulo">Otros nombres de <?= escapar($editar['nombre']) ?></h2>
      <p class="texto-guia">
        Nombres comerciales o formas distintas de escribirlo. Sirven para encontrarlo aunque la
        persona lo escriba de otra manera.
      </p>

      <?php $alias = listar_alias((int) $editar['id']); ?>

      <?php if ($alias === []): ?>
        <p class="vacio">Todavía no hay otros nombres cargados.</p>
      <?php else: ?>
        <ul class="pila">
          <?php foreach ($alias as $uno): ?>
            <li class="fila-titulo">
              <span><?= escapar($uno['alias']) ?></span>
              <form method="post" action="/admin/reclutadores.php"
                    data-confirmar="¿Quitar este nombre?">
                <?php campo_csrf(); ?>
                <input type="hidden" name="accion" value="borrar_alias">
                <input type="hidden" name="reclutador_id" value="<?= (int) $editar['id'] ?>">
                <input type="hidden" name="alias_id" value="<?= (int) $uno['id'] ?>">
                <button class="boton boton--peligro" type="submit">Quitar</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="post" action="/admin/reclutadores.php" class="tarjeta">
        <?php campo_csrf(); ?>
        <input type="hidden" name="accion" value="agregar_alias">
        <input type="hidden" name="reclutador_id" value="<?= (int) $editar['id'] ?>">
        <div class="campo">
          <label class="etiqueta" for="alias">Agregar otro nombre</label>
          <input class="entrada" type="text" id="alias" name="alias" maxlength="191" required>
        </div>
        <div class="acciones separado">
          <button class="boton boton--secundario" type="submit">Agregar</button>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <p><a href="/admin/index.php">Volver al panel</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
