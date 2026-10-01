<?php
/**
 * CUENTAS DE USUARIO
 * -----------------------------------------------------------------
 * Todas las consultas que tocan la tabla usuarios viven acá.
 *
 * Una sola tabla para las tres clases de cuenta. El rol NUNCA sale de
 * un formulario: el registro público escribe ROL_USUARIO fijo en el
 * código, y el único lugar donde se asigna un rol administrativo es la
 * pantalla del superadministrador.
 */

/** El id numérico de un rol a partir de su código. */
function id_de_rol(string $codigo): ?int
{
    $id = consultar_valor('SELECT id FROM roles WHERE codigo = ?', [$codigo]);
    return $id === null ? null : (int) $id;
}


function buscar_usuario_por_correo(string $correo): ?array
{
    return consultar_una(
        'SELECT u.*, r.codigo AS rol
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.correo = ?',
        [normalizar_correo($correo)]
    );
}

function buscar_usuario_por_id(int $id): ?array
{
    return consultar_una(
        'SELECT u.*, r.codigo AS rol, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.id = ?',
        [$id]
    );
}

/**
 * ¿Ese correo es de una cuenta administrativa?
 * Solo para el control de abuso del formulario público de entrada.
 * La respuesta NUNCA se le muestra a quien está intentando entrar.
 */
function es_correo_administrativo(string $correo): bool
{
    return consultar_valor(
        'SELECT 1 FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.correo = ? AND r.codigo IN (?, ?)',
        [normalizar_correo($correo), ROL_ADMINISTRADOR, ROL_SUPERADMINISTRADOR]
    ) !== null;
}

function existe_correo(string $correo): bool
{
    return consultar_valor('SELECT 1 FROM usuarios WHERE correo = ?', [normalizar_correo($correo)]) !== null;
}


/**
 * Crea una cuenta y devuelve su id.
 *
 * La contraseña se guarda con password_hash y PASSWORD_DEFAULT: PHP
 * elige el algoritmo más fuerte que tenga disponible y le agrega sal
 * solo. Nunca, en ningún lugar del proyecto, se guarda ni se registra
 * una contraseña en texto plano.
 */
function crear_usuario(
    string $correo,
    string $contrasena,
    string $nombre,
    string $rol_codigo,
    bool $debe_cambiar_contrasena = false
): int {
    $rol_id = id_de_rol($rol_codigo);
    if ($rol_id === null) {
        throw new RuntimeException('Rol inexistente: ' . $rol_codigo);
    }

    consultar(
        'INSERT INTO usuarios (rol_id, correo, contrasena_hash, nombre, activo, debe_cambiar_contrasena, creado_en)
         VALUES (?, ?, ?, ?, 1, ?, ?)',
        [
            $rol_id,
            normalizar_correo($correo),
            password_hash($contrasena, PASSWORD_DEFAULT),
            $nombre,
            $debe_cambiar_contrasena ? 1 : 0,
            ahora(),
        ]
    );

    return (int) bd()->lastInsertId();
}


/**
 * Comprueba correo y contraseña. Devuelve el usuario o null.
 *
 * Dos detalles que parecen manías y no lo son:
 *
 * 1. Cuando el correo no existe, igual se corre password_verify contra
 *    un hash de mentira. Si no lo hiciéramos, el servidor respondería
 *    más rápido con los correos que no existen, y eso deja averiguar
 *    quién tiene cuenta en la plataforma. Acá eso importa más que en
 *    otros sitios: saber que alguien está buscando trabajo afuera es
 *    información delicada.
 *
 * 2. Si el algoritmo de PHP mejoró desde que se creó la cuenta, se
 *    vuelve a guardar la contraseña con el nuevo, aprovechando que en
 *    este momento la tenemos.
 */
function autenticar(string $correo, string $contrasena): ?array
{
    $usuario = buscar_usuario_por_correo($correo);

    if ($usuario === null) {
        // Hash de relleno, solo para gastar el mismo tiempo que un
        // usuario real. Tiene que tener el MISMO costo que los hashes
        // de verdad: el anterior estaba fijo en costo 10, y desde PHP
        // 8.4 password_hash() usa costo 12. Ahí un correo inexistente
        // respondía cuatro veces más rápido (67 ms contra 265 ms) y se
        // podía averiguar quién tiene cuenta midiendo el tiempo.
        password_verify($contrasena, hash_de_relleno());
        return null;
    }

    if (!password_verify($contrasena, $usuario['contrasena_hash'])) {
        return null;
    }

    if ((int) $usuario['activo'] !== 1) {
        return null;
    }

    if (password_needs_rehash($usuario['contrasena_hash'], PASSWORD_DEFAULT)) {
        cambiar_contrasena((int) $usuario['id'], $contrasena);
    }

    return $usuario;
}


/**
 * Un hash bcrypt bien formado, con el costo que usa password_hash() en
 * este servidor, que no corresponde a ninguna contraseña.
 * PASSWORD_BCRYPT_DEFAULT_COST cambia solo con la versión de PHP, así
 * que el relleno sigue empatado con los hashes reales sin tocar nada.
 */
function hash_de_relleno(): string
{
    return sprintf('$2y$%02d$', PASSWORD_BCRYPT_DEFAULT_COST) . str_repeat('A', 53);
}


function cambiar_contrasena(int $usuario_id, string $nueva): void
{
    consultar(
        'UPDATE usuarios
         SET contrasena_hash = ?, debe_cambiar_contrasena = 0, actualizado_en = ?
         WHERE id = ?',
        [password_hash($nueva, PASSWORD_DEFAULT), ahora(), $usuario_id]
    );
}


function marcar_ultimo_acceso(int $usuario_id): void
{
    consultar(
        'UPDATE usuarios SET ultimo_acceso_en = ? WHERE id = ?',
        [ahora(), $usuario_id]
    );
}


/**
 * ¿Ya existe alguna cuenta administrativa?
 * De esto depende que el instalador se niegue a correr una segunda vez.
 */
function existe_alguna_cuenta_administrativa(): bool
{
    $cuenta = consultar_valor(
        'SELECT COUNT(*)
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE r.codigo IN (?, ?)',
        [ROL_ADMINISTRADOR, ROL_SUPERADMINISTRADOR]
    );
    return (int) $cuenta > 0;
}


/** Las cuentas administrativas, para la pantalla del superadministrador. */
function listar_cuentas_administrativas(): array
{
    return consultar_todas(
        'SELECT u.id, u.correo, u.nombre, u.activo, u.creado_en, u.ultimo_acceso_en,
                u.desactivado_en, r.codigo AS rol, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE r.codigo IN (?, ?)
         ORDER BY u.activo DESC, u.nombre ASC',
        [ROL_ADMINISTRADOR, ROL_SUPERADMINISTRADOR]
    );
}


/**
 * Desactivar y no borrar: la bitácora tiene que poder seguir diciendo
 * quién verificó cada oferta. Una cuenta desactivada no puede entrar.
 */
function desactivar_usuario(int $usuario_id): void
{
    consultar(
        'UPDATE usuarios SET activo = 0, desactivado_en = ?, actualizado_en = ? WHERE id = ?',
        [ahora(), ahora(), $usuario_id]
    );
}

function reactivar_usuario(int $usuario_id): void
{
    consultar(
        'UPDATE usuarios SET activo = 1, desactivado_en = NULL, actualizado_en = ? WHERE id = ?',
        [ahora(), $usuario_id]
    );
}


/** Cuántos superadministradores activos quedan (para no quedarse sin ninguno). */
function contar_superadministradores_activos(): int
{
    return (int) consultar_valor(
        'SELECT COUNT(*)
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE r.codigo = ? AND u.activo = 1',
        [ROL_SUPERADMINISTRADOR]
    );
}
