<?php
/**
 * OFERTAS
 * -----------------------------------------------------------------
 * El corazón de datos del proyecto.
 *
 * La regla 1 y la regla 2 están escritas acá adentro, en una sola
 * condición SQL que usan TODAS las consultas públicas. Si algún día
 * hay que cambiar qué se considera visible, se cambia en un lugar y
 * no hay forma de que una pantalla se quede con la versión vieja.
 */

/**
 * Lo que tiene que cumplir una oferta para que el público la vea.
 * Las cinco condiciones son necesarias:
 *
 *   estado = publicada       -> alguien decidió publicarla
 *   verificada_por no vacío  -> alguien la verificó y quedó su nombre
 *   fecha_publicacion existe -> hay una fecha que mostrar (regla 1)
 *   fecha_vencimiento >= hoy -> no permanece publicada después de vencer
 *   reclutador vigente hoy   -> si vino por un reclutador, su
 *                               autorización sigue en pie (D-045)
 *
 * La última existe porque D-020 bloquea VERIFICAR una oferta de un
 * reclutador no autorizado, pero nada volvía a mirarlo después: si la
 * autorización se vencía o la suspendían con la oferta ya publicada,
 * seguía apareciendo con el sello y el número de registro. Ahora deja
 * de verse sola, igual que una oferta vencida (D-018). Se mira el
 * estado Y la fecha, como en reclutador_vigente_hoy().
 *
 * Está escrito para fallar del lado seguro: si un dato falta o queda
 * mal escrito, la oferta NO se muestra. Nunca al revés.
 *
 * Los dos parámetros (:hoy y :hoy_reclutador) los arma
 * parametros_oferta_publica(). Son dos nombres para el mismo valor
 * porque PDO no deja repetir un parámetro con nombre.
 */
const CONDICION_OFERTA_PUBLICA = "
    o.estado = 'publicada'
    AND o.verificada_por IS NOT NULL
    AND o.fecha_publicacion IS NOT NULL
    AND o.fecha_vencimiento IS NOT NULL
    AND o.fecha_vencimiento >= :hoy
    AND (
        o.reclutador_id IS NULL
        OR EXISTS (
            SELECT 1 FROM reclutadores_autorizados rv
            WHERE rv.id = o.reclutador_id
              AND rv.estado = 'vigente'
              AND (rv.vigencia_hasta IS NULL OR rv.vigencia_hasta >= :hoy_reclutador)
        )
    )
";

/** Los parámetros que necesita CONDICION_OFERTA_PUBLICA. */
function parametros_oferta_publica(): array
{
    return [':hoy' => hoy(), ':hoy_reclutador' => hoy()];
}

/** Las columnas que se muestran, con el nombre de la fuente y el rubro. */
const SELECCION_OFERTA = "
    SELECT o.*,
           f.nombre AS fuente_nombre, f.tipo AS fuente_tipo, f.url AS fuente_url,
           ru.nombre AS rubro_nombre, ru.codigo AS rubro_codigo,
           rec.nombre AS reclutador_nombre, rec.numero_registro AS reclutador_registro,
           rec.estado AS reclutador_estado, rec.vigencia_hasta AS reclutador_vigencia,
           v.nombre AS verificador_nombre
    FROM ofertas o
    INNER JOIN fuentes f ON f.id = o.fuente_id
    INNER JOIN rubros ru ON ru.id = o.rubro_id
    LEFT JOIN reclutadores_autorizados rec ON rec.id = o.reclutador_id
    LEFT JOIN usuarios v ON v.id = o.verificada_por
";


// =================================================================
//  CONSULTAS PÚBLICAS  (no hacen falta cuenta ni sesión)
// =================================================================

/**
 * Arma las condiciones de los filtros del buscador.
 *
 * Ojo con esto: lo que se pega al texto de la consulta son SOLO
 * nombres de columna escritos por nosotros. Lo que escribió la persona
 * viaja aparte, como parámetro. En este proyecto no existe una sola
 * consulta donde un dato del usuario termine dentro del texto del SQL.
 */
function filtros_de_busqueda(array $filtros): array
{
    $condiciones = [];
    $parametros  = parametros_oferta_publica();

    if (!empty($filtros['rubro_id'])) {
        $condiciones[] = 'o.rubro_id = :rubro_id';
        $parametros[':rubro_id'] = (int) $filtros['rubro_id'];
    }

    if (!empty($filtros['pais'])) {
        $condiciones[] = 'o.pais_codigo = :pais';
        $parametros[':pais'] = $filtros['pais'];
    }

    if (!empty($filtros['departamento'])) {
        $condiciones[] = 'o.departamento_codigo = :departamento';
        $parametros[':departamento'] = $filtros['departamento'];
    }

    if (!empty($filtros['dias'])) {
        $condiciones[] = 'o.fecha_publicacion >= :desde_fecha';
        $parametros[':desde_fecha'] = date('Y-m-d', time() - ((int) $filtros['dias'] * 86400));
    }

    if (!empty($filtros['texto'])) {
        // Un nombre distinto para cada aparición, aunque el valor sea
        // el mismo: con las consultas preparadas de verdad
        // (EMULATE_PREPARES en false, ver bd.php) PDO NO deja repetir un
        // parámetro con nombre. Si se repite, la consulta revienta con
        // "Invalid parameter number" y el buscador entero da error.
        $condiciones[] = '(o.titulo LIKE :texto1 OR o.empleador LIKE :texto2 OR o.descripcion LIKE :texto3)';
        $parametros += parametros_de_texto($filtros['texto'], 3);
    }

    $sql = $condiciones === [] ? '' : ' AND ' . implode(' AND ', $condiciones);

    return [$sql, $parametros];
}


/**
 * Dónde es el trabajo, en palabras: "Ciudad de Guatemala, Guatemala",
 * "Melchor de Mencos, Petén", "Kelowna, Canadá".
 *
 * En Guatemala se dice el departamento (D-057) y no se repite el país,
 * porque todas las ofertas de la plataforma son de acá salvo que digan
 * otra cosa. Afuera se dice el país.
 */
function lugar_de_oferta(array $oferta): string
{
    $partes = [];
    if (!empty($oferta['ciudad'])) {
        $partes[] = $oferta['ciudad'];
    }

    if ($oferta['pais_codigo'] === 'gt' && !empty($oferta['departamento_codigo'])) {
        $partes[] = DEPARTAMENTOS[$oferta['departamento_codigo']] ?? $oferta['departamento_codigo'];
    } else {
        $partes[] = PAISES[$oferta['pais_codigo']] ?? $oferta['pais_codigo'];
    }

    return implode(', ', $partes);
}


function listar_ofertas_publicas(array $filtros, int $limite, int $desde): array
{
    [$extra, $parametros] = filtros_de_busqueda($filtros);

    return consultar_paginado(
        SELECCION_OFERTA . ' WHERE ' . CONDICION_OFERTA_PUBLICA . $extra
        . ' ORDER BY o.fecha_publicacion DESC, o.id DESC',
        $parametros,
        $limite,
        $desde
    );
}

function contar_ofertas_publicas(array $filtros): int
{
    [$extra, $parametros] = filtros_de_busqueda($filtros);

    $sentencia = bd()->prepare(
        'SELECT COUNT(*) FROM ofertas o WHERE ' . CONDICION_OFERTA_PUBLICA . $extra
    );
    $sentencia->execute($parametros);

    return (int) $sentencia->fetchColumn();
}

/**
 * Una oferta, solo si el público puede verla.
 *
 * Se usa la MISMA condición que el listado. Sin esto, alguien podría
 * escribir a mano el número de una oferta que todavía no se verificó y
 * verla igual: es el agujero más común de este tipo de páginas.
 */
function buscar_oferta_publica(int $id): ?array
{
    return consultar_una(
        SELECCION_OFERTA . ' WHERE ' . CONDICION_OFERTA_PUBLICA . ' AND o.id = :id',
        parametros_oferta_publica() + [':id' => $id]
    );
}


// =================================================================
//  CONSULTAS DEL PANEL  (protegidas por permiso en cada pantalla)
// =================================================================

function listar_ofertas_admin(array $filtros, int $limite, int $desde): array
{
    $condiciones = [];
    $parametros  = [];

    if (!empty($filtros['estado'])) {
        $condiciones[] = 'o.estado = :estado';
        $parametros[':estado'] = $filtros['estado'];
    }
    if (!empty($filtros['texto'])) {
        $condiciones[] = '(o.titulo LIKE :texto1 OR o.empleador LIKE :texto2)';
        $parametros += parametros_de_texto($filtros['texto'], 2);
    }

    $donde = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

    return consultar_paginado(
        SELECCION_OFERTA . $donde . ' ORDER BY o.id DESC',
        $parametros,
        $limite,
        $desde
    );
}

function contar_ofertas_admin(array $filtros): int
{
    $condiciones = [];
    $parametros  = [];

    if (!empty($filtros['estado'])) {
        $condiciones[] = 'estado = :estado';
        $parametros[':estado'] = $filtros['estado'];
    }
    if (!empty($filtros['texto'])) {
        $condiciones[] = '(titulo LIKE :texto1 OR empleador LIKE :texto2)';
        $parametros += parametros_de_texto($filtros['texto'], 2);
    }

    $donde = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);

    $sentencia = bd()->prepare('SELECT COUNT(*) FROM ofertas' . $donde);
    $sentencia->execute($parametros);

    return (int) $sentencia->fetchColumn();
}

/** Una oferta cualquiera, en el estado que esté. Solo para el panel. */
function buscar_oferta(int $id): ?array
{
    return consultar_una(SELECCION_OFERTA . ' WHERE o.id = :id', [':id' => $id]);
}

/** Cuántas ofertas hay en cada estado, para las pestañas del panel. */
function contar_por_estado(): array
{
    $conteo = [];
    foreach (consultar_todas('SELECT estado, COUNT(*) AS total FROM ofertas GROUP BY estado') as $fila) {
        $conteo[$fila['estado']] = (int) $fila['total'];
    }
    return $conteo;
}


// =================================================================
//  ALTA Y EDICIÓN
// =================================================================

/**
 * Crea una oferta. SIEMPRE nace en 'pendiente'.
 *
 * No hay ningún camino en el sistema para que una oferta entre ya
 * verificada o ya publicada, ni siquiera importándola por CSV. La
 * palabra "verificada" se gana pasando por el proceso (regla 2).
 */
function crear_oferta(array $datos, ?int $admin_id): int
{
    consultar(
        'INSERT INTO ofertas
            (titulo, descripcion, empleador, reclutador_id, fuente_id, pais_codigo,
             departamento_codigo, ciudad, rubro_id, requisitos, experiencia_anios_min,
             estudios_min, disponibilidad_requerida, salario_texto, url_original,
             forma_postulacion, estado, fecha_publicacion, fecha_vencimiento,
             creada_por, creado_en)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $datos['titulo'],
            $datos['descripcion'],
            $datos['empleador'],
            $datos['reclutador_id'] ?: null,
            $datos['fuente_id'],
            $datos['pais_codigo'],
            ($datos['departamento_codigo'] ?? '') ?: null,
            $datos['ciudad'] ?: null,
            $datos['rubro_id'],
            $datos['requisitos'] ?: null,
            $datos['experiencia_anios_min'],
            $datos['estudios_min'],
            $datos['disponibilidad_requerida'],
            $datos['salario_texto'] ?: null,
            $datos['url_original'] ?: null,
            ($datos['forma_postulacion'] ?? '') ?: 'plataforma',
            'pendiente',
            $datos['fecha_publicacion'] ?: null,
            $datos['fecha_vencimiento'] ?: null,
            $admin_id,
            ahora(),
        ]
    );

    return (int) bd()->lastInsertId();
}

/**
 * Edita una oferta.
 *
 * Cambiar los datos de una oferta ya verificada la devuelve a
 * 'pendiente': si cambió el empleador o el vencimiento, lo que se
 * verificó antes ya no es lo que dice ahora. Sin esto, "verificada"
 * se podría convertir en una etiqueta que alguien puso una vez y
 * después dejó de ser cierta.
 */
function actualizar_oferta(int $id, array $datos): bool
{
    $anterior = buscar_oferta($id);
    $devolver_a_pendiente = $anterior !== null
        && in_array($anterior['estado'], ['verificada', 'publicada'], true);

    consultar(
        'UPDATE ofertas
         SET titulo = ?, descripcion = ?, empleador = ?, reclutador_id = ?, fuente_id = ?,
             pais_codigo = ?, departamento_codigo = ?, ciudad = ?, rubro_id = ?, requisitos = ?,
             experiencia_anios_min = ?, estudios_min = ?, disponibilidad_requerida = ?,
             salario_texto = ?, url_original = ?, forma_postulacion = ?,
             fecha_publicacion = ?, fecha_vencimiento = ?,
             actualizado_en = ?
         WHERE id = ?',
        [
            $datos['titulo'],
            $datos['descripcion'],
            $datos['empleador'],
            $datos['reclutador_id'] ?: null,
            $datos['fuente_id'],
            $datos['pais_codigo'],
            ($datos['departamento_codigo'] ?? '') ?: null,
            $datos['ciudad'] ?: null,
            $datos['rubro_id'],
            $datos['requisitos'] ?: null,
            $datos['experiencia_anios_min'],
            $datos['estudios_min'],
            $datos['disponibilidad_requerida'],
            $datos['salario_texto'] ?: null,
            $datos['url_original'] ?: null,
            ($datos['forma_postulacion'] ?? '') ?: 'plataforma',
            $datos['fecha_publicacion'] ?: null,
            $datos['fecha_vencimiento'] ?: null,
            ahora(),
            $id,
        ]
    );

    if ($devolver_a_pendiente) {
        consultar(
            'UPDATE ofertas SET estado = ?, verificada_en = NULL, verificada_por = NULL WHERE id = ?',
            ['pendiente', $id]
        );
    }

    return $devolver_a_pendiente;
}


// -----------------------------------------------------------------
//  Idiomas que pide la oferta
// -----------------------------------------------------------------

function idiomas_de_oferta(int $oferta_id): array
{
    $codigos = [];
    foreach (consultar_todas(
        'SELECT idioma_codigo FROM ofertas_idiomas WHERE oferta_id = ?',
        [$oferta_id]
    ) as $fila) {
        $codigos[] = $fila['idioma_codigo'];
    }
    return $codigos;
}

function guardar_idiomas_oferta(int $oferta_id, array $codigos): void
{
    consultar('DELETE FROM ofertas_idiomas WHERE oferta_id = ?', [$oferta_id]);

    foreach ($codigos as $codigo) {
        if (!en_catalogo($codigo, IDIOMAS)) {
            continue;
        }
        consultar(
            'INSERT INTO ofertas_idiomas (oferta_id, idioma_codigo, obligatorio) VALUES (?, ?, 1)',
            [$oferta_id, $codigo]
        );
    }
}


// =================================================================
//  CICLO DE VIDA DE LA OFERTA
// =================================================================

/**
 * ¿Se puede pasar de este estado a este otro?
 * La respuesta sale de TRANSICIONES_OFERTA, en catalogos.php.
 */
function transicion_permitida(string $actual, string $nuevo): bool
{
    if (!isset(TRANSICIONES_OFERTA[$actual])) {
        return false;
    }
    return in_array($nuevo, TRANSICIONES_OFERTA[$actual], true);
}

/**
 * Qué le falta a una oferta para poder publicarse.
 * Devuelve el motivo en palabras, o null si ya está lista.
 *
 * Acá se aplica la regla 1 completa: si un dato no se puede mostrar,
 * la oferta no se publica. No es una advertencia, es un bloqueo.
 */
function motivo_para_no_publicar(array $oferta): ?string
{
    if ($oferta['verificada_por'] === null) {
        return 'Todavía no está verificada.';
    }
    if (empty($oferta['fecha_publicacion'])) {
        return 'Le falta la fecha de publicación, y esa fecha se le muestra a la persona.';
    }
    if (empty($oferta['fecha_vencimiento'])) {
        return 'Le falta la fecha de vencimiento. Sin ella la oferta se quedaría publicada para siempre.';
    }
    if ($oferta['fecha_vencimiento'] < hoy()) {
        return 'La fecha de vencimiento ya pasó.';
    }
    if (empty($oferta['fuente_id'])) {
        return 'Le falta la fuente, y toda oferta tiene que mostrar de dónde salió.';
    }
    // D-058: si la persona se postula en la página de la empresa, esa
    // página tiene que existir. Si no, el botón no llevaría a ningún lado.
    if (($oferta['forma_postulacion'] ?? 'plataforma') === 'externa'
        && !url_segura((string) ($oferta['url_original'] ?? ''))) {
        return 'Se postula en la página de la empresa, pero le falta esa dirección (o no es válida).';
    }
    // D-020 y D-045: el reclutador pudo perder la autorización entre
    // que se verificó la oferta y ahora.
    if (!empty($oferta['reclutador_id'])) {
        $reclutador = buscar_reclutador((int) $oferta['reclutador_id']);
        if ($reclutador === null || !reclutador_vigente_hoy($reclutador)) {
            return 'El reclutador de esta oferta ya no tiene la autorización vigente. '
                 . 'Aunque se publicara, no se mostraría en el sitio.';
        }
    }
    return null;
}

/** Cambia el estado. La transición ya tuvo que validarse antes. */
function cambiar_estado_oferta(int $id, string $nuevo_estado, ?string $motivo = null): void
{
    consultar(
        'UPDATE ofertas SET estado = ?, motivo_retiro = ?, actualizado_en = ? WHERE id = ?',
        [$nuevo_estado, $motivo, ahora(), $id]
    );
}

/**
 * Marca la oferta como verificada y deja registrado QUIÉN la verificó.
 * Ese nombre se muestra después en la oferta pública (regla 1).
 */
function marcar_verificada(int $id, int $admin_id): void
{
    consultar(
        'UPDATE ofertas
         SET estado = ?, verificada_en = ?, verificada_por = ?, actualizado_en = ?
         WHERE id = ?',
        ['verificada', ahora(), $admin_id, ahora(), $id]
    );
}


// =================================================================
//  VENCIMIENTO
//
//  El hosting no tiene tareas programadas (decisión D-003), así que
//  nadie marca las ofertas vencidas por su cuenta. Se resuelve en dos
//  capas:
//
//   1. La consulta pública ya exige fecha_vencimiento >= hoy, así que
//      una oferta vencida deja de verse SOLA, sin que nadie haga nada.
//      Esta es la que protege a la persona.
//
//   2. El administrador dispara desde el panel el cambio de estado a
//      'vencida', para que el listado interno diga la verdad y quede
//      registrado en la bitácora.
// =================================================================

/** Las que siguen en 'publicada' aunque su fecha ya pasó. */
function ofertas_publicadas_vencidas(): array
{
    return consultar_todas(
        'SELECT id, titulo, fecha_vencimiento FROM ofertas
         WHERE estado = ? AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento < ?
         ORDER BY fecha_vencimiento ASC',
        ['publicada', hoy()]
    );
}

/**
 * Las que figuran como publicadas pero vienen por un reclutador que ya
 * no tiene la autorización vigente (por estado o por fecha).
 *
 * Ya no se ven en el sitio (CONDICION_OFERTA_PUBLICA lo impide), pero
 * el panel tiene que avisarlo: si no, alguien ve "publicada" en la
 * lista y no entiende por qué la persona no la encuentra.
 */
function ofertas_publicadas_con_reclutador_no_vigente(): array
{
    return consultar_todas(
        'SELECT o.id, o.titulo, r.nombre AS reclutador_nombre, r.estado AS reclutador_estado,
                r.vigencia_hasta AS reclutador_vigencia
         FROM ofertas o
         INNER JOIN reclutadores_autorizados r ON r.id = o.reclutador_id
         WHERE o.estado = ?
           AND (r.estado <> ? OR (r.vigencia_hasta IS NOT NULL AND r.vigencia_hasta < ?))
         ORDER BY o.id DESC',
        ['publicada', 'vigente', hoy()]
    );
}

/** Las marca como vencidas. Devuelve cuántas cambió. */
function vencer_ofertas_publicadas(): int
{
    $sentencia = consultar(
        'UPDATE ofertas SET estado = ?, actualizado_en = ?
         WHERE estado = ? AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento < ?',
        ['vencida', ahora(), 'publicada', hoy()]
    );
    return $sentencia->rowCount();
}


/**
 * Las ofertas públicas que podrían servirle a un perfil: mismo oficio
 * y en un país al que la persona dijo que iría.
 *
 * Se filtra en la base y no en PHP para no traer todas las ofertas y
 * descartarlas después. En un hosting compartido y con tope de
 * peticiones diarias, traer de más se paga.
 *
 * Ojo con los IN: lo que se pega al texto de la consulta son nombres
 * de parámetro que generamos nosotros (:rubro0, :rubro1...). Los
 * valores viajan aparte, como en cualquier otra consulta.
 */
function ofertas_para_perfil(array $rubro_ids, array $paises): array
{
    if ($rubro_ids === [] || $paises === []) {
        return [];
    }

    $parametros = parametros_oferta_publica();

    $marcas_rubro = [];
    foreach (array_values($rubro_ids) as $i => $id) {
        $marcas_rubro[] = ':rubro' . $i;
        $parametros[':rubro' . $i] = (int) $id;
    }

    $marcas_pais = [];
    foreach (array_values($paises) as $i => $codigo) {
        $marcas_pais[] = ':pais' . $i;
        $parametros[':pais' . $i] = $codigo;
    }

    return consultar_todas(
        SELECCION_OFERTA . ' WHERE ' . CONDICION_OFERTA_PUBLICA
        . ' AND o.rubro_id IN (' . implode(', ', $marcas_rubro) . ')'
        . ' AND o.pais_codigo IN (' . implode(', ', $marcas_pais) . ')'
        . ' ORDER BY o.fecha_publicacion DESC',
        $parametros
    );
}


/**
 * ¿Ya existe una oferta igual de la misma fuente?
 * Lo usa la importación por CSV para no cargar la misma dos veces
 * cada vez que se vuelve a subir el archivo.
 */
function existe_oferta_igual(string $titulo, string $empleador, int $fuente_id): bool
{
    return consultar_valor(
        'SELECT 1 FROM ofertas WHERE titulo = ? AND empleador = ? AND fuente_id = ?',
        [$titulo, $empleador, $fuente_id]
    ) !== null;
}
