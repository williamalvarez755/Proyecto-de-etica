<?php
/**
 * ZONA ÚNICA DE CONFIGURACIÓN
 * -----------------------------------------------------------------
 * TODOS los números del sistema viven acá y en ningún otro lugar.
 *
 * La regla es simple: si en cualquier archivo del proyecto aparece un
 * número suelto que signifique un límite, un tamaño o un tiempo, está
 * mal puesto y hay que traerlo para acá.
 *
 * Por qué importa: el día que haya que aflojar el bloqueo del login o
 * subir el tamaño máximo del currículum, se cambia UN número en UN
 * archivo, y no se anda buscando dónde más quedó escrito.
 */

// =================================================================
//  Zona horaria
//  Todas las fechas del sistema las genera PHP con esta zona.
//  Nunca se usa NOW() de MySQL: el servidor de base de datos puede
//  estar en otra zona y terminaríamos con consentimientos fechados
//  horas antes de que ocurrieran.
// =================================================================
define('ZONA_HORARIA', 'America/Guatemala');


// =================================================================
//  Sesiones
// =================================================================

// Minutos sin actividad antes de cerrar la sesión sola.
define('SESION_INACTIVIDAD_MINUTOS', 60);

// Duración máxima de una sesión aunque la persona siga activa.
// Obliga a volver a escribir la contraseña de vez en cuando.
define('SESION_DURACION_MAXIMA_MINUTOS', 480);

// Nombre de la cookie de sesión. Uno propio, para no anunciar que es PHP.
define('SESION_NOMBRE', 'tvsesion');


// =================================================================
//  Contraseñas
//
//  Se pide LARGO y no "una mayúscula, un número y un símbolo".
//  Obligar a símbolos raros produce contraseñas peores (la gente
//  termina escribiendo Password1! y anotándola en un papel), y para
//  nuestra población, que muchas veces escribe desde un celular de
//  gama baja, es una barrera de entrada real.
// =================================================================
define('CONTRASENA_LARGO_MINIMO', 10);
define('CONTRASENA_LARGO_MAXIMO', 200);


// =================================================================
//  Límites de uso (protección contra abuso)
//
//  Doble propósito: evitar que le adivinen la contraseña a alguien,
//  y evitar que un abuso consuma las 30 000 peticiones diarias que
//  da InfinityFree y deje el sitio caído para todos.
// =================================================================

// -- Inicio de sesión --
define('LOGIN_MAX_INTENTOS',      5);   // fallos permitidos...
define('LOGIN_VENTANA_MINUTOS',   15);  // ...dentro de esta ventana
define('LOGIN_BLOQUEO_MINUTOS',   15);  // y este es el castigo

// El panel administrativo es más estricto: es una puerta más valiosa.
define('LOGIN_ADMIN_MAX_INTENTOS',    3);
define('LOGIN_ADMIN_BLOQUEO_MINUTOS', 30);

// -- Registro de cuentas nuevas (por dirección IP) --
define('REGISTRO_MAX_POR_IP',     3);
define('REGISTRO_VENTANA_HORAS',  24);

// -- Currículums (Fase 3) --
define('CV_SUBIDAS_MAX_POR_DIA',  5);

// -- Postulaciones (Fase 4) --
define('POSTULACIONES_MAX_POR_DIA', 20);

// -- Verificador público de reclutadores (Fase 5) --
define('VERIFICADOR_MAX_CONSULTAS_HORA', 60);


// =================================================================
//  Archivos de currículum (Fase 3)
// =================================================================

// 3 MB. Un currículum real no pesa más, y cada archivo cuenta contra
// el límite de 30 000 archivos de la cuenta de hosting.
define('CV_TAMANO_MAXIMO_BYTES', 3 * 1024 * 1024);

// Solo estos dos formatos. El tipo real se comprueba con finfo,
// nunca por la extensión ni por lo que diga el navegador.
const CV_TIPOS_PERMITIDOS = [
    'application/pdf' => 'pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];


// =================================================================
//  Restablecimiento asistido de contraseña (decisión D-005)
// =================================================================
define('RESTABLECIMIENTO_VALIDEZ_MINUTOS', 60);
define('RESTABLECIMIENTO_LARGO_CODIGO',    10);


// =================================================================
//  Listados
// =================================================================
define('OFERTAS_POR_PAGINA',  10);
define('BITACORA_POR_PAGINA', 50);
define('REPORTES_POR_PAGINA', 25);


// =================================================================
//  Retención de datos (Fase 6)
//  Cuánto tiempo se conserva cada cosa antes de que la limpieza
//  manual del panel la borre.
// =================================================================
define('RETENCION_INTENTOS_DIAS',  30);   // tabla intentos_acceso
define('RETENCION_BITACORA_DIAS', 730);   // 2 años de auditoría


// =================================================================
//  Consentimiento (Fase 4)
//  Versión del texto que la persona acepta al postularse. Si el texto
//  cambia, se sube esta versión: así queda registrado qué aceptó
//  exactamente cada quien, y cambiar el texto no reescribe el pasado.
// =================================================================
define('CONSENTIMIENTO_VERSION', '1.0');


// =================================================================
//  Rutas del sistema
//  RAIZ_APP la define app/nucleo/inicio.php al arrancar.
// =================================================================
define('RUTA_ALMACEN',   RAIZ_APP . '/almacen');
define('RUTA_CV',        RUTA_ALMACEN . '/cv');
define('RUTA_LOGS',      RUTA_ALMACEN . '/logs');
define('RUTA_RESPALDOS', RUTA_ALMACEN . '/respaldos');
