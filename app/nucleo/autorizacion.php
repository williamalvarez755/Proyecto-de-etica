<?php
/**
 * SESIÓN, ROL Y PERMISO  (regla 5)
 * -----------------------------------------------------------------
 * Esta es la única forma de comprobar quién puede hacer qué. Ninguna
 * página del proyecto verifica permisos por su cuenta.
 *
 * La regla completa: ocultar un botón NO es control de acceso. Toda
 * acción se comprueba acá, en el servidor, aunque la pantalla ya no
 * mostrara la opción. Si alguien escribe la dirección a mano, tiene
 * que rebotar igual.
 *
 * Detalle importante: los permisos se leen de la base en CADA petición,
 * no se guardan en la sesión. Cuesta una consulta, pero significa que
 * si el superadministrador desactiva a un administrador, ese cambio es
 * inmediato: no se queda adentro hasta que se le ocurra salir.
 */

/**
 * Los datos de quien está usando el sitio ahora mismo, o null.
 * Se consulta una sola vez por petición.
 */
function usuario_actual(): ?array
{
    static $usuario = null;
    static $ya_consultado = false;

    if ($ya_consultado) {
        return $usuario;
    }
    $ya_consultado = true;

    $id = $_SESSION['usuario_id'] ?? null;
    if ($id === null) {
        return null;
    }

    $usuario = consultar_una(
        'SELECT u.id, u.correo, u.nombre, u.activo, u.debe_cambiar_contrasena,
                u.creado_en, u.ultimo_acceso_en, u.contrasena_hash,
                r.codigo AS rol, r.id AS rol_id, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE u.id = ?',
        [$id]
    );

    // La cuenta se borró o se desactivó mientras la sesión seguía viva.
    if ($usuario === null || (int) $usuario['activo'] !== 1) {
        cerrar_sesion();
        return $usuario = null;
    }

    // La contraseña cambió desde que se abrió esta sesión (la cambió la
    // persona en otro dispositivo, o usó un código de restablecimiento).
    // Ver sello_de_clave() en sesion.php.
    $sello = (string) ($_SESSION['sello_clave'] ?? '');
    if (!hash_equals(sello_de_clave($usuario['contrasena_hash']), $sello)) {
        cerrar_sesion();
        guardar_mensaje('aviso', 'La contraseña de esta cuenta cambió. Volvé a entrar con la nueva.');
        return $usuario = null;
    }

    // El hash no sale de esta función: ninguna pantalla lo necesita.
    unset($usuario['contrasena_hash']);

    return $usuario;
}


function hay_sesion(): bool
{
    return usuario_actual() !== null;
}

function id_usuario_actual(): ?int
{
    $usuario = usuario_actual();
    return $usuario ? (int) $usuario['id'] : null;
}

function rol_actual(): ?string
{
    $usuario = usuario_actual();
    return $usuario ? $usuario['rol'] : null;
}

/** ¿Es administrador o superadministrador? */
function es_administrativo(): bool
{
    return in_array(rol_actual(), ROLES_ADMINISTRATIVOS, true);
}

function es_superadministrador(): bool
{
    return rol_actual() === ROL_SUPERADMINISTRADOR;
}


/**
 * Los códigos de permiso del rol de esta persona.
 * Una sola consulta por petición.
 */
function permisos_actuales(): array
{
    static $permisos = null;

    if ($permisos !== null) {
        return $permisos;
    }

    $usuario = usuario_actual();
    if ($usuario === null) {
        $permisos = [];
        return $permisos;
    }

    $filas = consultar_todas(
        'SELECT p.codigo
         FROM roles_permisos rp
         INNER JOIN permisos p ON p.id = rp.permiso_id
         WHERE rp.rol_id = ?',
        [$usuario['rol_id']]
    );

    $permisos = [];
    foreach ($filas as $fila) {
        $permisos[] = $fila['codigo'];
    }

    return $permisos;
}

/**
 * ¿Tiene este permiso?
 * Sirve para decidir si se muestra un botón. Para DEJAR hacer la
 * acción se usa requerir_permiso().
 */
function tiene_permiso(string $codigo): bool
{
    return in_array($codigo, permisos_actuales(), true);
}


// =================================================================
//  Las que cortan la ejecución
// =================================================================

/** Para las páginas del área personal. Sin sesión, manda a entrar. */
function requerir_sesion(): void
{
    if (!hay_sesion()) {
        guardar_mensaje_si_no_hay('aviso', 'Necesitás entrar a tu cuenta para ver esa página.');
        redirigir('/cuenta/entrar.php');
    }

    exigir_cambio_de_contrasena();
}


/**
 * Si la cuenta entró con una contraseña temporal (la que le dio un
 * administrador en un restablecimiento asistido), no la deja hacer
 * nada más hasta que la cambie.
 *
 * Sin esto, la contraseña temporal —que alguien más conoce— se
 * quedaría puesta para siempre.
 */
function exigir_cambio_de_contrasena(): void
{
    $usuario = usuario_actual();
    if ($usuario === null || (int) $usuario['debe_cambiar_contrasena'] !== 1) {
        return;
    }

    // No redirigir cuando ya está en la página de cambiar la
    // contraseña, o quedaría dando vueltas para siempre.
    $pagina_cambio = '/cuenta/cambiar_contrasena.php';
    if (($_SERVER['SCRIPT_NAME'] ?? '') === $pagina_cambio) {
        return;
    }
    // Tampoco al salir: siempre se tiene que poder cerrar la sesión.
    if (str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/salir.php')) {
        return;
    }

    guardar_mensaje('aviso', 'Antes de seguir, cambiá la contraseña temporal por una tuya.');
    redirigir($pagina_cambio);
}


/**
 * Para TODA página dentro de htdocs/admin/.
 *
 * Si no hay sesión, manda al ingreso del panel.
 * Si hay sesión pero es una cuenta de usuario normal, NO redirige:
 * responde 403 y lo deja registrado en la bitácora. Un usuario normal
 * probando direcciones del panel es justo lo que hay que poder ver
 * después.
 */
function requerir_administrativo(): void
{
    if (!hay_sesion()) {
        guardar_mensaje_si_no_hay('aviso', 'Ingresá con tu cuenta administrativa.');
        redirigir('/admin/entrar.php');
    }

    if (!es_administrativo()) {
        registrar_accion(
            'acceso_denegado',
            'panel',
            null,
            'Intento de entrar a ' . ($_SERVER['REQUEST_URI'] ?? '')
        );
        abortar(
            403,
            'Esta página no es para tu cuenta',
            'Tu cuenta no tiene acceso al panel de administración. '
            . 'Si creés que es un error, comunicate con la institución que administra la plataforma.'
        );
    }

    exigir_cambio_de_contrasena();
}


/** Exige un permiso concreto. Se llama ANTES de hacer la acción. */
function requerir_permiso(string $codigo): void
{
    requerir_administrativo();

    if (!tiene_permiso($codigo)) {
        registrar_accion(
            'permiso_denegado',
            'permiso',
            null,
            'Le faltó el permiso: ' . $codigo
        );
        abortar(
            403,
            'No tenés permiso para esta acción',
            'Tu cuenta administrativa no incluye este permiso. '
            . 'El superadministrador puede dártelo si te corresponde.'
        );
    }
}


/** Solo para lo que le toca al superadministrador. */
function requerir_superadministrador(): void
{
    requerir_administrativo();

    if (!es_superadministrador()) {
        registrar_accion(
            'acceso_denegado',
            'superadministracion',
            null,
            'Intento de entrar a ' . ($_SERVER['REQUEST_URI'] ?? '')
        );
        abortar(
            403,
            'Esta sección es del superadministrador',
            'Solo la cuenta responsable del sistema puede entrar acá.'
        );
    }
}


/**
 * Para las páginas del área personal: que un administrador no ande
 * usando el sitio como si fuera una persona buscando trabajo.
 */
function requerir_rol_usuario(): void
{
    requerir_sesion();

    if (rol_actual() !== ROL_USUARIO) {
        redirigir('/admin/index.php');
    }
}
