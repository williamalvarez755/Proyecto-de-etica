<?php
/**
 * ARCHIVOS SUBIDOS  (currículums)
 * -----------------------------------------------------------------
 * Es la parte más delicada del proyecto. Acá se guardan currículums de
 * personas migrantes en un país sin ley de protección de datos. Si uno
 * de estos archivos se filtra, no es una nota mala: es información de
 * alguien vulnerable en manos de cualquiera.
 *
 * Las cinco defensas, y por qué cada una:
 *
 *  1. Se comprueba el tipo REAL del archivo con finfo, mirando su
 *     contenido. Nunca la extensión ni lo que diga el navegador: las
 *     dos las escribe quien sube el archivo. Un .php renombrado a
 *     .pdf se rechaza acá.
 *
 *  2. El nombre se descarta y se genera uno aleatorio. El nombre
 *     original puede traer datos de la persona, caracteres raros o
 *     rutas ("../../"), y además un nombre adivinable es un archivo
 *     que se puede pedir a ciegas.
 *
 *  3. Se guarda en app/almacen/cv, que en este hosting queda DENTRO de
 *     htdocs (D-044) y la cierran tres .htaccess redundantes. No es lo
 *     mismo que "fuera": depende de que Apache lea esos archivos. Por
 *     eso la defensa 2 (nombre al azar) importa tanto como esta.
 *
 *  4. Solo se sirve por un script que comprueba la sesión y que quien
 *     pide el archivo sea su dueño, o alguien con permiso.
 *
 *  5. Se manda siempre como descarga y con nosniff, para que el
 *     navegador no lo interprete como página.
 */

/** Se asegura de que exista la carpeta donde van los currículums. */
function preparar_carpeta_cv(): bool
{
    if (is_dir(RUTA_CV)) {
        return true;
    }
    return @mkdir(RUTA_CV, 0750, true);
}


/**
 * Revisa un archivo recién subido.
 * Devuelve el mensaje de error, o null si está bien.
 */
function revisar_cv_subido(?array $archivo): ?string
{
    if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return 'Elegí el archivo de tu currículum.';
    }

    if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
        return 'El archivo pesa demasiado. El máximo son '
             . round(CV_TAMANO_MAXIMO_BYTES / 1024 / 1024, 1) . ' MB.';
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return 'El archivo no se subió completo. Probá otra vez.';
    }

    if (!is_uploaded_file($archivo['tmp_name'])) {
        return 'No se pudo leer el archivo.';
    }

    if ($archivo['size'] > CV_TAMANO_MAXIMO_BYTES) {
        return 'El archivo pesa más de ' . round(CV_TAMANO_MAXIMO_BYTES / 1024 / 1024, 1)
             . ' MB. Probá con uno más liviano.';
    }

    if ($archivo['size'] === 0) {
        return 'Ese archivo está vacío.';
    }

    // El contenido real manda. La extensión no se mira.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo  = (string) $finfo->file($archivo['tmp_name']);

    if (!array_key_exists($tipo, CV_TIPOS_PERMITIDOS)) {
        return 'Solo se aceptan archivos PDF o Word (.docx). '
             . 'Si cambiaste la extensión del archivo, no funciona: revisamos el contenido.';
    }

    return null;
}


/** La extensión que corresponde al contenido real del archivo. */
function extension_real(string $ruta_temporal): ?string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo  = (string) $finfo->file($ruta_temporal);

    return CV_TIPOS_PERMITIDOS[$tipo] ?? null;
}


/**
 * Guarda el archivo con un nombre aleatorio.
 * Devuelve el nombre guardado, o null si no se pudo.
 */
function guardar_cv(array $archivo): ?string
{
    if (!preparar_carpeta_cv()) {
        registrar_error('No se pudo crear la carpeta de currículums: ' . RUTA_CV, __FILE__, __LINE__);
        return null;
    }

    $extension = extension_real($archivo['tmp_name']);
    if ($extension === null) {
        return null;
    }

    // 32 caracteres al azar: no se adivina ni se deduce de quién es.
    $nombre = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!move_uploaded_file($archivo['tmp_name'], RUTA_CV . '/' . $nombre)) {
        registrar_error('No se pudo mover el currículum subido', __FILE__, __LINE__);
        return null;
    }

    @chmod(RUTA_CV . '/' . $nombre, 0640);

    return $nombre;
}


/**
 * Borra un currículum del disco.
 *
 * Se usa al reemplazarlo y al eliminar la cuenta. La regla 4 dice que
 * borrar la cuenta borra de verdad: el archivo del disco también, no
 * solo la fila de la base.
 */
function borrar_cv(?string $nombre): void
{
    if ($nombre === null || $nombre === '') {
        return;
    }

    // Solo nombres con la forma que genera este archivo. Así, aunque
    // llegara un valor manipulado desde otro lado, no se puede salir
    // de la carpeta ni borrar otra cosa.
    if (!preg_match('/^[a-f0-9]{32}\.(pdf|docx)$/', $nombre)) {
        registrar_error('Se intentó borrar un archivo con nombre raro: ' . $nombre, __FILE__, __LINE__);
        return;
    }

    $ruta = RUTA_CV . '/' . $nombre;
    if (is_file($ruta)) {
        @unlink($ruta);
    }
}


/** La ruta completa de un currículum, si el nombre tiene la forma correcta. */
function ruta_de_cv(?string $nombre): ?string
{
    if ($nombre === null || !preg_match('/^[a-f0-9]{32}\.(pdf|docx)$/', $nombre)) {
        return null;
    }
    $ruta = RUTA_CV . '/' . $nombre;

    return is_file($ruta) ? $ruta : null;
}


/**
 * Manda el archivo al navegador como descarga.
 * Quien llama a esto YA tiene que haber comprobado el permiso.
 */
function entregar_cv(string $ruta, string $nombre_visible): void
{
    $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
    $tipos = [
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    // Se limpia cualquier salida anterior para que el archivo no salga
    // con espacios pegados adelante y quede corrupto.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: ' . ($tipos[$extension] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($ruta));
    header('Content-Disposition: attachment; filename="' . $nombre_visible . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');

    readfile($ruta);
    exit;
}
