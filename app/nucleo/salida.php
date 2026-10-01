<?php
/**
 * SALIDA Y FORMATO
 * -----------------------------------------------------------------
 * Todo lo que se imprime en pantalla pasa por acá.
 */

/**
 * Escapa texto antes de imprimirlo en el HTML.
 *
 * Sin esto, si alguien pone <script>...</script> en el nombre de una
 * empresa, ese código se ejecutaría en el navegador de quien vea la
 * página. Se llama XSS y es la forma más común de robar sesiones.
 *
 * La regla del proyecto no admite excepciones:
 * TODO dato que venga de la base de datos o del usuario se imprime así:
 *
 *   <?= escapar($oferta['titulo']) ?>
 */
function escapar(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


/**
 * La fecha y hora de AHORA, lista para guardar en la base.
 *
 * Todas las fechas del sistema salen de acá y de ningún otro lado.
 * Nunca se usa NOW() de MySQL: si el servidor de base de datos tuviera
 * otra zona horaria, tendríamos dos relojes distintos y un consentimiento
 * podría quedar fechado horas antes de que la persona lo diera.
 */
function ahora(): string
{
    return date('Y-m-d H:i:s');
}

/** La fecha de hoy, sin hora. */
function hoy(): string
{
    return date('Y-m-d');
}

/** Una fecha futura o pasada, en minutos. Para vencimientos y bloqueos. */
function ahora_mas_minutos(int $minutos): string
{
    return date('Y-m-d H:i:s', time() + ($minutos * 60));
}

function ahora_menos_minutos(int $minutos): string
{
    return date('Y-m-d H:i:s', time() - ($minutos * 60));
}

function ahora_menos_dias(int $dias): string
{
    return date('Y-m-d H:i:s', time() - ($dias * 86400));
}


const MESES = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
];

/**
 * Fecha en palabras: "1 de septiembre de 2026".
 *
 * Se escribe así y no como 01/09/2026 porque en un formato con números
 * nadie sabe si el 03/09 es marzo o septiembre, y acá las fechas de
 * vencimiento y de verificación son información con la que la persona
 * toma una decisión.
 */
function fecha_en_palabras(?string $fecha): string
{
    if ($fecha === null || $fecha === '' || str_starts_with($fecha, '0000')) {
        return 'sin fecha';
    }

    $tiempo = strtotime($fecha);
    if ($tiempo === false) {
        return 'sin fecha';
    }

    return sprintf(
        '%d de %s de %d',
        (int) date('j', $tiempo),
        MESES[(int) date('n', $tiempo)],
        (int) date('Y', $tiempo)
    );
}

/** Fecha con hora, para la bitácora: "1 de septiembre de 2026, 14:35". */
function fecha_hora_en_palabras(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return 'sin fecha';
    }
    $tiempo = strtotime($fecha);
    if ($tiempo === false) {
        return 'sin fecha';
    }
    return fecha_en_palabras($fecha) . ', ' . date('H:i', $tiempo);
}


/**
 * Guarda un mensaje para mostrarlo DESPUÉS de una redirección.
 *
 * Es el patrón de siempre: se recibe el formulario, se guarda, se
 * redirige y se muestra "listo, se guardó". Sin la redirección, si la
 * persona recarga la página vuelve a enviar el formulario.
 *
 * Tipos: 'exito', 'error', 'aviso'.
 */
function guardar_mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
}

/**
 * Lo mismo, pero sin pisar un mensaje que ya estaba esperando.
 *
 * Lo usan las páginas que exigen sesión: si la sesión se acaba de
 * cerrar por un motivo concreto ("pasó un rato sin actividad", "la
 * contraseña cambió"), ese motivo es lo que la persona necesita leer,
 * no el genérico "necesitás entrar".
 */
function guardar_mensaje_si_no_hay(string $tipo, string $texto): void
{
    if (!isset($_SESSION['mensaje'])) {
        guardar_mensaje($tipo, $texto);
    }
}

/** Saca el mensaje guardado (y lo borra, para que salga una sola vez). */
function tomar_mensaje(): ?array
{
    if (!isset($_SESSION['mensaje'])) {
        return null;
    }
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
    return $mensaje;
}
