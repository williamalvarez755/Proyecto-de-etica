<?php
/**
 * CATÁLOGOS DEL SISTEMA
 * -----------------------------------------------------------------
 * Listas cerradas de valores válidos. Sirven para dos cosas:
 *
 *   1. Llenar los menús desplegables de los formularios.
 *   2. VALIDAR en el servidor: si un valor no está en la lista, se
 *      rechaza. Nunca se guarda en la base algo que el usuario mandó
 *      sin que esté en una de estas listas.
 *
 * Están en PHP y no en la base de datos porque casi nunca cambian y
 * así no cuesta una consulta cada vez. Los rubros (oficios) sí están
 * en la base, porque la institución sí va a necesitar agregar más.
 */

// =================================================================
//  Países de destino
//  Solo los del programa de trabajo temporal y los destinos reales.
// =================================================================
const PAISES = [
    'gt' => 'Guatemala',
    'mx' => 'México',
    'us' => 'Estados Unidos',
    'ca' => 'Canadá',
    'bz' => 'Belice',
    'cr' => 'Costa Rica',
    'pa' => 'Panamá',
    'es' => 'España',
    'otro' => 'Otro país',
];

// =================================================================
//  Idiomas
//  Se guarda qué idiomas HABLA la persona, porque es una capacidad
//  laboral y la regla 7 lo permite. NO se guarda su idioma materno.
//  Los idiomas mayas están incluidos porque para muchos trabajos son
//  una ventaja real, y dejarlos fuera sería la verdadera omisión.
// =================================================================
const IDIOMAS = [
    'espanol'    => 'Español',
    'ingles'     => 'Inglés',
    'frances'    => 'Francés',
    'kiche'      => "K'iche'",
    'qeqchi'     => "Q'eqchi'",
    'mam'        => 'Mam',
    'kaqchikel'  => 'Kaqchikel',
    'otro'       => 'Otro idioma',
];

// =================================================================
//  Nivel de estudios
// =================================================================
const NIVELES_ESTUDIO = [
    'ninguno'      => 'Sin estudios formales',
    'primaria'     => 'Primaria',
    'basicos'      => 'Básicos',
    'diversificado'=> 'Diversificado',
    'tecnico'      => 'Carrera técnica',
    'universitario'=> 'Universidad',
];

// Orden de menor a mayor, para poder comparar en el emparejamiento.
const ORDEN_ESTUDIOS = [
    'ninguno' => 0, 'primaria' => 1, 'basicos' => 2,
    'diversificado' => 3, 'tecnico' => 4, 'universitario' => 5,
];

// =================================================================
//  Disponibilidad
// =================================================================
const DISPONIBILIDAD = [
    'inmediata'   => 'Puedo viajar de inmediato',
    'un_mes'      => 'En un mes',
    'tres_meses'  => 'En tres meses',
    'a_convenir'  => 'A convenir',
];

// =================================================================
//  Estados de una oferta (Fase 2)
// =================================================================
const ESTADOS_OFERTA = [
    'pendiente'   => 'Pendiente de verificar',
    'verificada'  => 'Verificada, sin publicar',
    'publicada'   => 'Publicada',
    'vencida'     => 'Vencida',
    'retirada'    => 'Retirada',
    'en_revision' => 'En revisión',
];

// El ÚNICO estado que se muestra al público. Además, para verse tiene
// que estar verificada_por alguien y no haber pasado su vencimiento.
const ESTADO_OFERTA_PUBLICO = 'publicada';

// Transiciones permitidas: de qué estado se puede pasar a cuál.
// Cualquier cambio que no esté acá se rechaza en el servidor.
const TRANSICIONES_OFERTA = [
    'pendiente'   => ['verificada', 'retirada'],
    'verificada'  => ['publicada', 'pendiente', 'retirada'],
    'publicada'   => ['en_revision', 'vencida', 'retirada'],
    'en_revision' => ['publicada', 'retirada'],
    'vencida'     => ['verificada', 'retirada'],
    'retirada'    => [],
];

// =================================================================
//  Reportes de usuarios (Fase 5)
// =================================================================
const MOTIVOS_REPORTE = [
    'posible_estafa'         => 'Creo que es una estafa',
    'solicita_dinero'        => 'Me pidieron dinero',
    'informacion_incorrecta' => 'La información está equivocada',
    'empresa_inexistente'    => 'La empresa no existe',
    'reclutador_no_autorizado' => 'El reclutador no está autorizado',
    'oferta_vencida'         => 'La oferta ya no está disponible',
    'datos_sospechosos'      => 'Me pidieron datos que no debían',
    'otro'                   => 'Otro motivo',
];

const ESTADOS_REPORTE = [
    'pendiente'   => 'Pendiente',
    'en_revision' => 'En revisión',
    'resuelto'    => 'Resuelto',
    'descartado'  => 'Descartado',
];

// =================================================================
//  Tipos de fuente de una oferta (Fase 2)
// =================================================================
const TIPOS_FUENTE = [
    'institucion_publica'    => 'Institución pública',
    'reclutador_autorizado'  => 'Reclutador autorizado',
    'empleador_directo'      => 'Empleador directo',
    'importacion_csv'        => 'Importación desde CSV',
];

// =================================================================
//  Tipos de intento que registran los límites de uso
// =================================================================
const TIPOS_INTENTO = [
    'login_usuario',
    'login_admin',
    'registro',
    'subida_cv',
    'postulacion',
    'verificador',
    'restablecimiento',
];

// =================================================================
//  Cómo se lee cada acción de la bitácora
//  La bitácora la va a leer alguien de la institución que no es
//  informático: "login_admin_fallido" no le dice nada.
// =================================================================
const ACCIONES_BITACORA = [
    'instalacion_inicial'        => 'Se creó la primera cuenta responsable del sistema',
    'login_admin_exitoso'        => 'Entró al panel',
    'login_admin_fallido'        => 'Intento fallido de entrar al panel',
    'login_admin_bloqueado'      => 'Cuenta bloqueada por intentos fallidos',
    'logout_admin'               => 'Cerró la sesión del panel',
    'acceso_denegado'            => 'Intentó entrar a una sección que no le corresponde',
    'permiso_denegado'           => 'Intentó una acción sin tener el permiso',
    'administrador_creado'       => 'Creó una cuenta administrativa',
    'administrador_desactivado'  => 'Desactivó una cuenta administrativa',
    'administrador_reactivado'   => 'Reactivó una cuenta administrativa',
    'contrasena_cambiada'        => 'Cambió su contraseña',
];

// =================================================================
//  Roles
//  Se escriben acá como constantes para no andar repartiendo el texto
//  'superadministrador' por todo el código y que un error de dedo
//  termine dando permisos de más.
// =================================================================
const ROL_USUARIO            = 'usuario';
const ROL_ADMINISTRADOR      = 'administrador';
const ROL_SUPERADMINISTRADOR = 'superadministrador';

// Roles que pueden entrar al panel de administración.
const ROLES_ADMINISTRATIVOS = [ROL_ADMINISTRADOR, ROL_SUPERADMINISTRADOR];
