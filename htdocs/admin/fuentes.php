<?php
/**
 * PANEL — FUENTES DE LAS OFERTAS
 * -----------------------------------------------------------------
 * De dónde sale cada oferta. Es la base de la regla 1.
 *
 * Las fuentes no se borran nunca, se desactivan: hay ofertas que
 * apuntan acá y tienen que poder seguir diciendo de dónde vinieron,
 * aunque la institución deje de usar esa fuente.
 */

require __DIR__ . '/../app/nucleo/inicio.php';

requerir_permiso('fuentes.gestionar');

$errores = [];
$editar  = null;

$datos = ['nombre' => '', 'tipo' => 'institucion_publica', 'url' => '', 'descripcion' => '', 'notas' => ''];

if (es_post()) {
    $accion = campo('accion');

    // --- Activar o desactivar ------------------------------------
    if ($accion === 'cambiar_estado') {
        $id     = id_valido(campo('fuente_id'));
        $fuente = $id === null ? null : buscar_fuente($id);

        if ($fuente === null) {
            guardar_mensaje('error', 'Esa fuente no existe.');
        } else {
            $activar = (int) $fuente['activa'] === 0;
            cambiar_estado_fuente($id, $activar);
            registrar_accion('fuente_estado_cambiado', 'fuente', $id,
                             ($activar ? 'Activó' : 'Desactivó') . ': ' . $fuente['nombre']);
            guardar_mensaje('exito', $activar
                ? 'La fuente quedó activa: ya se puede usar para cargar ofertas.'
                : 'La fuente quedó desactivada. Las ofertas que ya entraron por ella no cambian.');
        }
        redirigir('/admin/fuentes.php');
    }

    // --- Crear o editar -------------------------------------------
    if ($accion === 'guardar') {
        $id_editando = campo('fuente_id') === '' ? null : id_valido(campo('fuente_id'));

        foreach ($datos as $campo => $valor) {
            $datos[$campo] = limpiar_texto(campo($campo));
        }

        if (!largo_valido($datos['nombre'], 3, 120)) {
            $errores['nombre'] = 'El nombre tiene que tener entre 3 y 120 caracteres.';
        } elseif (existe_nombre_de_fuente($datos['nombre'], $id_editando)) {
            $errores['nombre'] = 'Ya existe una fuente con ese nombre.';
        }

        if (!en_catalogo($datos['tipo'], TIPOS_FUENTE)) {
            $errores['tipo'] = 'Elegí qué clase de fuente es.';
        }

        if ($datos['url'] !== '' && !url_segura($datos['url'])) {
            $errores['url'] = 'La dirección tiene que empezar con http:// o https://';
        }

        if ($errores === []) {
            if ($id_editando === null) {
                $nuevo = crear_fuente($datos['nombre'], $datos['tipo'], $datos['url'],
                                      $datos['descripcion'], $datos['notas']);
                registrar_accion('fuente_creada', 'fuente', $nuevo, $datos['nombre']);
                guardar_mensaje('exito', 'La fuente quedó creada.');
            } elseif (buscar_fuente($id_editando) === null) {
                guardar_mensaje('error', 'Esa fuente no existe.');
            } else {
                actualizar_fuente($id_editando, $datos['nombre'], $datos['tipo'], $datos['url'],
                                  $datos['descripcion'], $datos['notas']);
                registrar_accion('fuente_editada', 'fuente', $id_editando, $datos['nombre']);
                guardar_mensaje('exito', 'Se guardaron los cambios.');
            }
            redirigir('/admin/fuentes.php');
        }
    }
}

// --- ¿Se está editando una? --------------------------------------
$id_editar = id_valido(parametro('editar'));
if ($id_editar !== null && $errores === []) {
    $editar = buscar_fuente($id_editar);
    if ($editar !== null) {
        $datos = [
            'nombre'      => $editar['nombre'],
            'tipo'        => $editar['tipo'],
            'url'         => $editar['url'] ?? '',
            'descripcion' => $editar['descripcion'] ?? '',
            'notas'       => $editar['notas'] ?? '',
        ];
    }
}

$fuentes = listar_fuentes();

$titulo_pagina = 'Fuentes';
require RAIZ_APP . '/vistas/cabecera.php';
?>

<div class="contenedor pila-grande">

  <section class="pila">
    <h1 class="titulo-pagina">Fuentes de las ofertas</h1>
    <p class="texto-guia">
      Toda oferta tiene que decir de dónde salió, y eso se le muestra a la persona.
      Sin fuente no se puede cargar una oferta.
    </p>
  </section>

  <section class="pila">
    <h2 class="subtitulo">Fuentes registradas</h2>

    <?php if ($fuentes === []): ?>
      <p class="vacio">Todavía no hay ninguna fuente. Creá la primera en el formulario de abajo.</p>
    <?php else: ?>
      <div class="tabla-desliza">
        <table class="tabla">
          <thead>
            <tr>
              <th>Nombre</th><th>Clase</th><th>Ofertas</th><th>Estado</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($fuentes as $fuente): ?>
            <tr>
              <td>
                <?= escapar($fuente['nombre']) ?>
                <?php if (url_segura($fuente['url'])): ?>
                  <br><span class="texto-menor"><?= escapar($fuente['url']) ?></span>
                <?php endif; ?>
              </td>
              <td><?= escapar(TIPOS_FUENTE[$fuente['tipo']] ?? $fuente['tipo']) ?></td>
              <td><?= contar_ofertas_de_fuente((int) $fuente['id']) ?></td>
              <td>
                <span class="insignia"><?= (int) $fuente['activa'] === 1 ? 'Activa' : 'Desactivada' ?></span>
              </td>
              <td>
                <div class="acciones">
                  <a class="boton boton--secundario" href="/admin/fuentes.php?editar=<?= (int) $fuente['id'] ?>">Editar</a>
                  <form method="post" action="/admin/fuentes.php">
                    <?php campo_csrf(); ?>
                    <input type="hidden" name="accion" value="cambiar_estado">
                    <input type="hidden" name="fuente_id" value="<?= (int) $fuente['id'] ?>">
                    <button class="boton boton--secundario" type="submit">
                      <?= (int) $fuente['activa'] === 1 ? 'Desactivar' : 'Activar' ?>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="pila">
    <h2 class="subtitulo"><?= $editar !== null ? 'Editar la fuente' : 'Crear una fuente' ?></h2>

    <form method="post" action="/admin/fuentes.php" class="tarjeta">
      <?php campo_csrf(); ?>
      <input type="hidden" name="accion" value="guardar">
      <?php if ($editar !== null): ?>
        <input type="hidden" name="fuente_id" value="<?= (int) $editar['id'] ?>">
      <?php endif; ?>

      <div class="campo">
        <label class="etiqueta" for="nombre">Nombre de la fuente</label>
        <span class="ayuda" id="ayuda-nombre">
          Como se le va a mostrar a la persona. Por ejemplo:
          "Programa de Trabajo Temporal, Ministerio de Trabajo".
        </span>
        <input class="entrada <?= isset($errores['nombre']) ? 'entrada--error' : '' ?>" type="text"
               id="nombre" name="nombre" maxlength="120" aria-describedby="ayuda-nombre"
               value="<?= escapar($datos['nombre']) ?>" required>
        <?php if (isset($errores['nombre'])): ?><span class="error-campo"><?= escapar($errores['nombre']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="tipo">Qué clase de fuente es</label>
        <select class="entrada" id="tipo" name="tipo" required>
          <?php foreach (TIPOS_FUENTE as $codigo => $nombre): ?>
            <option value="<?= escapar($codigo) ?>" <?= $datos['tipo'] === $codigo ? 'selected' : '' ?>><?= escapar($nombre) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['tipo'])): ?><span class="error-campo"><?= escapar($errores['tipo']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="url">Dirección web (opcional)</label>
        <span class="ayuda" id="ayuda-url">Sirve para volver a comprobarla cuando se verifica una oferta.</span>
        <input class="entrada <?= isset($errores['url']) ? 'entrada--error' : '' ?>" type="url"
               id="url" name="url" maxlength="255" aria-describedby="ayuda-url" value="<?= escapar($datos['url']) ?>">
        <?php if (isset($errores['url'])): ?><span class="error-campo"><?= escapar($errores['url']) ?></span><?php endif; ?>
      </div>

      <div class="campo">
        <label class="etiqueta" for="descripcion">Descripción corta (opcional)</label>
        <input class="entrada" type="text" id="descripcion" name="descripcion" maxlength="255"
               value="<?= escapar($datos['descripcion']) ?>">
      </div>

      <div class="campo">
        <label class="etiqueta" for="notas">Notas internas (opcional)</label>
        <span class="ayuda" id="ayuda-notas">Solo las ve el personal del panel. No se muestran en el sitio público.</span>
        <textarea class="entrada" id="notas" name="notas" rows="3" aria-describedby="ayuda-notas"><?= escapar($datos['notas']) ?></textarea>
      </div>

      <div class="acciones separado">
        <button class="boton boton--principal" type="submit">Guardar</button>
        <?php if ($editar !== null): ?>
          <a class="boton boton--secundario" href="/admin/fuentes.php">Cancelar la edición</a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <p><a href="/admin/index.php">Volver al panel</a></p>

</div>

<?php require RAIZ_APP . '/vistas/pie.php'; ?>
