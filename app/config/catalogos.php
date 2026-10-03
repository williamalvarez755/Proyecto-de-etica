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
//  Países
//  Desde el 2026-10-03 las ofertas son de empleo en Guatemala (D-054),
//  por eso Guatemala va primero. Los demás quedan para las ofertas de
//  trabajo temporal en el extranjero que pueda traer una fuente
//  oficial (el Programa de Trabajo Temporal del Ministerio).
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
//  Departamentos de Guatemala
//  Dónde es el TRABAJO, nunca de dónde es la persona (D-008, D-057):
//  el departamento de origen ni siquiera se pregunta (regla 7).
// =================================================================
const DEPARTAMENTOS = [
    'alta_verapaz'   => 'Alta Verapaz',
    'baja_verapaz'   => 'Baja Verapaz',
    'chimaltenango'  => 'Chimaltenango',
    'chiquimula'     => 'Chiquimula',
    'el_progreso'    => 'El Progreso',
    'escuintla'      => 'Escuintla',
    'guatemala'      => 'Guatemala',
    'huehuetenango'  => 'Huehuetenango',
    'izabal'         => 'Izabal',
    'jalapa'         => 'Jalapa',
    'jutiapa'        => 'Jutiapa',
    'peten'          => 'Petén',
    'quetzaltenango' => 'Quetzaltenango',
    'quiche'         => 'Quiché',
    'retalhuleu'     => 'Retalhuleu',
    'sacatepequez'   => 'Sacatepéquez',
    'san_marcos'     => 'San Marcos',
    'santa_rosa'     => 'Santa Rosa',
    'solola'         => 'Sololá',
    'suchitepequez'  => 'Suchitepéquez',
    'totonicapan'    => 'Totonicapán',
    'zacapa'         => 'Zacapa',
];

// =================================================================
//  Cómo se postula la persona a una oferta (D-058)
//  'plataforma': acá, con su consentimiento, y la institución le pasa
//  el currículum al empleador. 'externa': en la página oficial de la
//  empresa; la plataforma no recibe ni guarda nada. Si una oferta
//  tomada de la página de una empresa se postulara por acá, el
//  currículum quedaría guardado sin que nadie se lo mande a la empresa
//  (regla 12).
// =================================================================
const FORMAS_POSTULACION = [
    'plataforma' => 'Por esta plataforma (la institución le pasa el currículum al empleador)',
    'externa'    => 'En la página oficial de la empresa (el enlace de la publicación original)',
];

// =================================================================
//  Disponibilidad
//  Dicho sin "viajar": sirve igual para la oferta ("cuándo hay que
//  empezar") y para la persona ("desde cuándo podés empezar").
// =================================================================
const DISPONIBILIDAD = [
    'inmediata'   => 'De inmediato',
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
//  Estados de una postulación (Fase 4)
//
//  Retirar no borra nada: el consentimiento que la persona dio queda
//  registrado igual, porque es cierto que lo dio y tiene derecho a
//  poder consultarlo después.
// =================================================================
const ESTADOS_POSTULACION = [
    'enviada'  => 'Enviada',
    'retirada' => 'Retirada por vos',
];

// =================================================================
//  Señales de alerta de una estafa laboral (Fase 5)
//
//  Están acá, en un solo lugar, porque se muestran en varias
//  pantallas y tienen que decir siempre exactamente lo mismo.
//
//  Cada una está redactada como algo CONCRETO que le puede estar
//  pasando a la persona en este momento, no como consejo general.
//  "Desconfíe de ofertas sospechosas" no le sirve a nadie; "si te
//  piden dinero por adelantado, es ilegal" sí.
// =================================================================
const SENALES_ALERTA = [
    'dinero' => [
        'titulo' => 'Te piden dinero por adelantado',
        'texto'  => 'La ley prohíbe que un reclutador le cobre al trabajador. Ni por el trámite, '
                  . 'ni por el viaje, ni por "apartar el cupo", ni por la papelería. '
                  . 'Si te piden dinero, es una estafa. No importa lo convincentes que sean.',
    ],
    'documentos' => [
        'titulo' => 'Te piden fotos de tu DPI o tu pasaporte antes de una entrevista formal',
        'texto'  => 'Con una foto de tu DPI se pueden sacar préstamos a tu nombre o abrir cuentas. '
                  . 'Un empleador real pide tus documentos cuando ya hay un proceso formal, '
                  . 'no en el primer mensaje.',
    ],
    'empresa' => [
        'titulo' => 'No hay una empresa que puedas comprobar',
        'texto'  => 'Si no te dicen el nombre completo de la empresa, ni dónde queda, ni un '
                  . 'teléfono que puedas llamar, no hay forma de saber si existe. '
                  . 'Una oferta real no tiene por qué esconder quién es.',
    ],
    'whatsapp' => [
        'titulo' => 'Solo te hablan por WhatsApp y con apuro',
        'texto'  => 'Cuando todo pasa por un número de teléfono, sin correo de la empresa ni '
                  . 'oficina, y encima te apuran diciendo que quedan pocos cupos, esa prisa '
                  . 'es la herramienta: es para que no te dé tiempo de comprobar nada.',
    ],
    'demasiado_bueno' => [
        'titulo' => 'Ofrecen mucho dinero por un trabajo sencillo',
        'texto'  => 'Si el sueldo que prometen es muy superior a lo normal para ese trabajo, '
                  . 'y encima no piden experiencia ni requisitos, conviene desconfiar.',
    ],
    'visa' => [
        'titulo' => 'Te prometen que ellos consiguen la visa',
        'texto'  => 'Nadie puede garantizarte una visa. Las visas las dan los consulados y no hay '
                  . 'gestor que las asegure. Quien te lo promete, te está mintiendo.',
    ],
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
//  Estado de la autorización de un reclutador
//
//  Ojo: que el estado diga "vigente" no alcanza. Si la fecha de
//  vigencia ya pasó, el sistema lo trata como vencido igual. El estado
//  lo escribe una persona; la fecha no se equivoca.
// =================================================================
const ESTADOS_RECLUTADOR = [
    'vigente'   => 'Autorización vigente',
    'vencido'   => 'Autorización vencida',
    'suspendido'=> 'Autorización suspendida',
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
    'oferta_creada'              => 'Cargó una oferta',
    'oferta_editada'             => 'Editó una oferta',
    'oferta_verificada'          => 'Verificó una oferta',
    'oferta_publicada'           => 'Publicó una oferta',
    'oferta_retirada'            => 'Retiró una oferta',
    'oferta_vencida'             => 'Marcó una oferta como vencida',
    'oferta_en_revision'         => 'Puso una oferta en revisión',
    'oferta_estado_cambiado'     => 'Cambió el estado de una oferta',
    'ofertas_vencidas_en_lote'   => 'Marcó como vencidas las ofertas que ya pasaron su fecha',
    'importacion_csv'            => 'Importó ofertas desde un archivo CSV',
    'fuente_creada'              => 'Creó una fuente',
    'fuente_editada'             => 'Editó una fuente',
    'fuente_estado_cambiado'     => 'Activó o desactivó una fuente',
    'reclutador_creado'          => 'Agregó un reclutador al registro',
    'reclutador_editado'         => 'Editó un reclutador del registro',
    'reclutador_alias_agregado'  => 'Agregó otro nombre a un reclutador',
    'reclutador_alias_borrado'   => 'Quitó un nombre de un reclutador',
    'restablecimiento_generado'  => 'Generó un código de contraseña para alguien',
    'restablecimiento_usado'     => 'Una persona puso contraseña nueva con su código',
    'cv_descargado'              => 'Descargó el currículum de una persona que se postuló',
    'postulaciones_vistas'       => 'Vio quiénes se postularon a una oferta',
    'reporte_revisado'           => 'Revisó un reporte de un usuario',
    'limpieza_ejecutada'         => 'Borró registros viejos desde mantenimiento',
    'respaldo_descargado'        => 'Descargó un respaldo de la base de datos',
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
