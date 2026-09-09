<?php
/**
 * VALIDACIÓN DE ENTRADA
 * -----------------------------------------------------------------
 * Todo lo que llega del usuario se valida ACÁ, en el servidor.
 *
 * La validación del navegador (required, type="email", maxlength) es
 * comodidad para la persona, no seguridad: cualquiera puede mandar la
 * petición sin pasar por el formulario. Si un dato no se validó en el
 * servidor, no está validado.
 */

/** Deja solo texto imprimible: quita caracteres de control y bytes raros. */
function limpiar_texto(string $texto): string
{
    $texto = str_replace(["\0", "\r"], '', $texto);
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto);
    return trim($texto ?? '');
}

/** ¿Es un correo con forma válida? */
function es_correo_valido(string $correo): bool
{
    if (mb_strlen($correo) > 191) {
        return false;
    }
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Deja el correo en una sola forma: sin espacios y en minúsculas.
 * Así "Juan@Correo.com" y "juan@correo.com" no crean dos cuentas.
 */
function normalizar_correo(string $correo): string
{
    return mb_strtolower(trim($correo), 'UTF-8');
}

/** ¿El texto mide entre el mínimo y el máximo? */
function largo_valido(string $texto, int $minimo, int $maximo): bool
{
    $largo = mb_strlen($texto, 'UTF-8');
    return $largo >= $minimo && $largo <= $maximo;
}

/**
 * ¿El valor está en la lista de valores permitidos?
 *
 * Es la defensa contra los datos manipulados: aunque alguien cambie el
 * menú desplegable con las herramientas del navegador y mande
 * estado=lo_que_sea, si no está en el catálogo no entra a la base.
 */
function en_catalogo(string $valor, array $catalogo): bool
{
    return array_key_exists($valor, $catalogo);
}

/** Lo mismo, para listas simples (no asociativas). */
function en_lista(string $valor, array $lista): bool
{
    return in_array($valor, $lista, true);
}

/** Convierte a número entero dentro de un rango, o devuelve null. */
function entero_en_rango(string $valor, int $minimo, int $maximo): ?int
{
    if (!preg_match('/^\d+$/', $valor)) {
        return null;
    }
    $numero = (int) $valor;
    if ($numero < $minimo || $numero > $maximo) {
        return null;
    }
    return $numero;
}

/** Identificador que viene de una dirección (?id=12). Null si no sirve. */
function id_valido(string $valor): ?int
{
    return entero_en_rango($valor, 1, 4294967295);
}

/**
 * ¿Es una dirección web que se puede publicar sin peligro?
 *
 * Se exige que empiece con http:// o https://. Sin esta comprobación,
 * alguien con acceso al panel podría guardar una dirección que empiece
 * con "javascript:" y convertir un enlace en código que se ejecuta en
 * el navegador de la persona. Se valida al guardar Y al mostrar.
 */
function url_segura(?string $url): bool
{
    if ($url === null || $url === '') {
        return false;
    }
    if (!preg_match('#^https?://#i', $url)) {
        return false;
    }
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/** ¿Tiene forma de fecha AAAA-MM-DD y existe de verdad? */
function es_fecha_valida(string $fecha): bool
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $partes)) {
        return false;
    }
    return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
}


/**
 * Revisa que la contraseña sirva.
 * Devuelve el mensaje de error, o null si está bien.
 *
 * Se pide largo y no símbolos raros: obligar a "una mayúscula, un
 * número y un signo" hace que la gente escriba contraseñas peores y
 * las anote en un papel. Para nuestra población, que muchas veces
 * escribe desde un celular sencillo, además es una barrera real.
 */
function revisar_contrasena(string $contrasena): ?string
{
    $largo = mb_strlen($contrasena, 'UTF-8');

    if ($largo < CONTRASENA_LARGO_MINIMO) {
        return 'La contraseña debe tener al menos ' . CONTRASENA_LARGO_MINIMO . ' caracteres.';
    }
    if ($largo > CONTRASENA_LARGO_MAXIMO) {
        return 'La contraseña es demasiado larga.';
    }
    // Contraseñas evidentes que aparecen en cualquier lista de ataque.
    $obvias = ['contrasena', 'contraseña', '1234567890', 'password123', 'qwertyuiop'];
    if (in_array(mb_strtolower($contrasena, 'UTF-8'), $obvias, true)) {
        return 'Esa contraseña es demasiado fácil de adivinar. Escribí otra.';
    }
    return null;
}


/**
 * TRAMPA PARA FORMULARIOS AUTOMÁTICOS
 * -----------------------------------------------------------------
 * Un campo que está en el formulario pero que la persona no ve. Los
 * programas que llenan formularios automáticamente lo llenan igual,
 * porque leen el HTML y no la pantalla. Si viene con algo escrito,
 * sabemos que no fue una persona.
 *
 * Se eligió esto en vez de un captcha por dos razones:
 *
 *  1. Un captcha necesita un servicio externo (regla 11: no queremos
 *     depender de nadie) y carga imágenes pesadas, justo lo que no le
 *     sirve a alguien con datos contados.
 *
 *  2. Los captchas son una barrera real para gente con poca práctica
 *     usando páginas web, que es exactamente nuestra población. Sería
 *     ponerle el obstáculo a la persona equivocada.
 *
 * No es infalible contra alguien que se tome el trabajo de mirarlo,
 * pero para eso están los límites de uso. Esto para el ruido de
 * fondo, que es la mayoría.
 */
function campo_trampa(): void
{
    echo '<div class="trampa" aria-hidden="true">';
    echo '<label for="apellido_secundario">No llenes este campo</label>';
    echo '<input type="text" id="apellido_secundario" name="apellido_secundario" tabindex="-1" autocomplete="off">';
    echo '</div>';
}

/** ¿Cayó en la trampa? Entonces no es una persona. */
function cayo_en_trampa(): bool
{
    return trim((string) ($_POST['apellido_secundario'] ?? '')) !== '';
}


/**
 * Deja un nombre en una forma comparable: MAYÚSCULAS, sin tildes y con
 * un solo espacio entre palabras.
 *
 * Sirve para el verificador de reclutadores de la Fase 5: la persona va
 * a escribir "reclutadora el quetzál s.a." y en el registro del
 * Ministerio puede estar como "RECLUTADORA EL QUETZAL, S.A."
 */
function normalizar_nombre(string $nombre): string
{
    $nombre = mb_strtoupper(trim($nombre), 'UTF-8');

    $con_tilde = ['Á','É','Í','Ó','Ú','Ü','À','È','Ì','Ò','Ù','Â','Ê','Î','Ô','Û','Ñ'];
    $sin_tilde = ['A','E','I','O','U','U','A','E','I','O','U','A','E','I','O','U','N'];
    $nombre = str_replace($con_tilde, $sin_tilde, $nombre);

    // Quita puntuación y deja un solo espacio entre palabras.
    $nombre = preg_replace('/[^A-Z0-9 ]/u', ' ', $nombre);
    $nombre = preg_replace('/\s+/', ' ', $nombre);

    return trim($nombre ?? '');
}
