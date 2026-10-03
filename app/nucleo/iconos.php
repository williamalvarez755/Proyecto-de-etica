<?php
/**
 * ÍCONOS DE LA INTERFAZ
 * -----------------------------------------------------------------
 * Todos los íconos del sitio salen de acá, dibujados como SVG dentro
 * del HTML:
 *
 *     <?= icono('ubicacion') ?>
 *     <?= icono('escudo', 'icono icono--grande') ?>
 *
 * Por qué así y no con imágenes ni con una fuente de íconos:
 *  - No hay que descargar nada aparte: cero peticiones de más contra
 *    el tope diario del hosting y cero megas para quien paga por dato.
 *  - Toman el color del texto (currentColor), así que cambian solos con
 *    el modo noche.
 *  - Una fuente de íconos dependería de un servicio externo (regla 11)
 *    o sumaría archivos que cuentan contra el límite de inodes.
 *
 * Los íconos son SIEMPRE decorativos (aria-hidden): al lado va la
 * palabra. Un ícono solo no le dice nada a quien tiene poca práctica
 * con páginas web, y un lector de pantalla no sabe leer un dibujo.
 *
 * Seguridad: el nombre lo escribe el código, nunca viene del usuario,
 * y la clase se escapa igual. Un nombre que no existe devuelve vacío
 * en lugar de romper la página.
 */

const ICONOS = [
    'inicio'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
    'maletin'     => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 13h18"/>',
    'verificar'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="m8.3 11 1.9 1.9 3.5-3.6"/>',
    'buscar'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'alerta'      => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    'persona'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    'persona-mas' => '<circle cx="10" cy="8" r="4"/><path d="M3 21c0-4 3.1-7 7-7 1.5 0 2.9.4 4 1.2"/><path d="M19 14v6M16 17h6"/>',
    'panel'       => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    'escudo'      => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3z"/><path d="m9 12 2 2 4-4"/>',
    'ubicacion'   => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    'calendario'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'dinero'      => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5v5M18 9.5v5"/>',
    'etiqueta'    => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    'edificio'    => '<path d="M4 21V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16"/><path d="M15 9h4a1 1 0 0 1 1 1v11"/><path d="M3 21h18M8 8h3M8 12h3M8 16h3"/>',
    'reloj'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'estudios'    => '<path d="m2 9 10-5 10 5-10 5-10-5z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5"/><path d="M22 9v6"/>',
    'idioma'      => '<path d="M4 5h11a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H9l-4 3v-3H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/><path d="M16 9h4a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-1v3l-4-3h-3"/>',
    'mundo'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/>',
    'luna'        => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>',
    'sol'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
    'subir'       => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M12 18v-6M9.5 14.5 12 12l2.5 2.5"/>',
    'documento'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
    'guardar'     => '<path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4.5L5 21V4a1 1 0 0 1 1-1z"/>',
    'enviar'      => '<path d="M21 3 3 10.5l7 2.5 2.5 7z"/><path d="m21 3-11 10"/>',
    'lista'       => '<path d="M9 6h11M9 12h11M9 18h11"/><path d="m3.5 6 1 1 2-2M3.5 12l1 1 2-2M3.5 18l1 1 2-2"/>',
    'estrella'    => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
    'candado'     => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'llave'       => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l3 3M14 9l2 2"/>',
    'salir'       => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
    'flecha'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'volver'      => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    'telefono'    => '<path d="M5 3h3l2 5-2.5 1.5a11 11 0 0 0 5 5L14 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
    'mensaje'     => '<path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.6-5.2A8.5 8.5 0 1 1 21 12z"/>',
    'prohibido'   => '<circle cx="12" cy="12" r="9"/><path d="m5.7 5.7 12.6 12.6"/>',
    'listo'       => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    'info'        => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    'basura'      => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>',
    'editar'      => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
    'bandera'     => '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
    'enlace'      => '<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1"/>',
    'compartir'   => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.5M8.2 13.2l7.6 4.5"/>',
    'avion'       => '<path d="M10.2 13.8 3 11.5l1.5-1.5 9 .5 4-4a2.1 2.1 0 0 1 3 3l-4 4 .5 9-1.5 1.5-2.3-7.2"/><path d="m10.2 13.8-3.7 3.7H4l1.5 2.5L8 21.5v-2.5l3.7-3.7"/>',
];

/**
 * Devuelve el SVG de un ícono, listo para imprimir.
 *
 * @param string $nombre una de las claves de ICONOS
 * @param string $clase  clases CSS del <svg> (por defecto "icono")
 */
function icono(string $nombre, string $clase = 'icono'): string
{
    if (!isset(ICONOS[$nombre])) {
        return '';
    }

    return '<svg class="' . escapar($clase) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
         . ICONOS[$nombre]
         . '</svg>';
}
