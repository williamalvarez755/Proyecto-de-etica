-- ===================================================================
--  ESQUEMA DE LA BASE DE DATOS
--  Plataforma de ofertas laborales verificadas
--  Fase 1 - Cimientos, roles y primitivas de seguridad
-- ===================================================================
--
--  CÓMO SE CORRE:
--    Panel de InfinityFree -> phpMyAdmin -> pestaña "Importar"
--    -> seleccionar este archivo -> Continuar.
--
--  NOTAS DE COMPATIBILIDAD (a propósito, para que corra en el MySQL
--  viejo de InfinityFree y también en un servidor propio moderno):
--    - Motor InnoDB en todas las tablas, para tener llaves foráneas.
--    - Codificación utf8mb4 (soporta tildes, ñ y emojis).
--    - Las columnas de texto que llevan índice son VARCHAR(191) y no
--      VARCHAR(255): MySQL 5.5/5.6 no admite índices más largos en utf8mb4.
--    - No se usa DEFAULT CURRENT_TIMESTAMP: todas las fechas las genera
--      PHP con la zona horaria de Guatemala, para que nunca haya dos
--      relojes distintos en el sistema.
--    - No se usan CTEs, funciones de ventana, CHECK ni columnas JSON.
-- ===================================================================

SET NAMES utf8mb4;


-- ===================================================================
--  1. SEGURIDAD Y CUENTAS
-- ===================================================================

-- Los tres roles del sistema. Se cargan en datos_iniciales.sql.
CREATE TABLE roles (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo      VARCHAR(30)  NOT NULL,
    nombre      VARCHAR(60)  NOT NULL,
    descripcion VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_roles_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Cada acción administrativa concreta que se puede permitir o negar.
-- El código es lo que se escribe en el PHP: requerir_permiso('ofertas.verificar')
CREATE TABLE permisos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo      VARCHAR(60)  NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_permisos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Qué permisos tiene cada rol.
CREATE TABLE roles_permisos (
    rol_id     INT UNSIGNED NOT NULL,
    permiso_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    KEY idx_roles_permisos_permiso (permiso_id),
    CONSTRAINT fk_rp_rol     FOREIGN KEY (rol_id)     REFERENCES roles (id)    ON DELETE CASCADE,
    CONSTRAINT fk_rp_permiso FOREIGN KEY (permiso_id) REFERENCES permisos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Una sola tabla para todas las cuentas (usuario, administrador,
-- superadministrador). El rol se decide SOLO desde el servidor:
-- el registro público escribe el rol de usuario fijo en el código.
--
-- Lo que NO se guarda acá, a propósito (regla 4 y regla 7):
--   apellido aparte, edad, sexo, DPI, pasaporte, situación migratoria,
--   visa, datos bancarios, fotografía, departamento de origen.
CREATE TABLE usuarios (
    id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rol_id                   INT UNSIGNED NOT NULL,
    correo                   VARCHAR(191) NOT NULL,
    contrasena_hash          VARCHAR(255) NOT NULL,
    nombre                   VARCHAR(100) NOT NULL,
    activo                   TINYINT(1)   NOT NULL DEFAULT 1,
    debe_cambiar_contrasena  TINYINT(1)   NOT NULL DEFAULT 0,
    creado_en                DATETIME     NOT NULL,
    actualizado_en           DATETIME     NULL,
    ultimo_acceso_en         DATETIME     NULL,
    desactivado_en           DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_correo (correo),
    KEY idx_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Restablecimiento de contraseña asistido por el administrador (decisión D-005).
-- El código NUNCA se guarda en texto plano: se guarda su hash.
CREATE TABLE restablecimientos (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id    INT UNSIGNED NOT NULL,
    codigo_hash   VARCHAR(255) NOT NULL,
    creado_por    INT UNSIGNED NULL,
    creado_en     DATETIME     NOT NULL,
    expira_en     DATETIME     NOT NULL,
    usado_en      DATETIME     NULL,
    invalidado_en DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_restablecimientos_usuario (usuario_id, creado_en),
    CONSTRAINT fk_restablecimientos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_restablecimientos_admin   FOREIGN KEY (creado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Base de los límites de uso: cada intento de una acción sensible.
-- Sin llave foránea a propósito: también registra intentos con correos
-- que no existen, que es justamente lo que hay que contar.
CREATE TABLE intentos_acceso (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo          VARCHAR(30)  NOT NULL,
    identificador VARCHAR(191) NOT NULL,
    ip            VARCHAR(45)  NOT NULL,
    exito         TINYINT(1)   NOT NULL,
    creado_en     DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_intentos_identificador (tipo, identificador, creado_en),
    KEY idx_intentos_ip (tipo, ip, creado_en),
    KEY idx_intentos_limpieza (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Bitácora de auditoría. Se empieza a escribir desde la Fase 1.
-- PROHIBIDO guardar acá: contraseñas, códigos, tokens, contenido de
-- currículums o datos personales que no hagan falta.
CREATE TABLE bitacora_admin (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NULL,
    accion     VARCHAR(60)  NOT NULL,
    entidad    VARCHAR(40)  NULL,
    entidad_id INT UNSIGNED NULL,
    detalle    VARCHAR(255) NULL,
    ip         VARCHAR(45)  NOT NULL,
    creado_en  DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_bitacora_fecha (creado_en),
    KEY idx_bitacora_usuario (usuario_id, creado_en),
    KEY idx_bitacora_entidad (entidad, entidad_id),
    CONSTRAINT fk_bitacora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===================================================================
--  2. CATÁLOGO, FUENTES Y RECLUTADORES
-- ===================================================================

-- Los oficios / rubros. En base de datos y no en un archivo PHP para que
-- la institución pueda agregar uno desde el panel, sin FTP.
CREATE TABLE rubros (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(40)  NOT NULL,
    nombre VARCHAR(80)  NOT NULL,
    activo TINYINT(1)   NOT NULL DEFAULT 1,
    orden  SMALLINT     NOT NULL DEFAULT 100,
    PRIMARY KEY (id),
    UNIQUE KEY uk_rubros_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- De dónde viene cada oferta. Regla 1: ninguna oferta sin fuente.
CREATE TABLE fuentes (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre         VARCHAR(120) NOT NULL,
    tipo           VARCHAR(30)  NOT NULL,
    url            VARCHAR(255) NULL,
    descripcion    VARCHAR(255) NULL,
    activa         TINYINT(1)   NOT NULL DEFAULT 1,
    notas          TEXT         NULL,
    creado_en      DATETIME     NOT NULL,
    actualizado_en DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_fuentes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Registro público de reclutadores autorizados del Ministerio de Trabajo.
-- nombre_normalizado = el nombre en mayúsculas y sin tildes, para que el
-- verificador de la Fase 5 encuentre a alguien aunque lo escriban distinto.
CREATE TABLE reclutadores_autorizados (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre             VARCHAR(191) NOT NULL,
    nombre_normalizado VARCHAR(191) NOT NULL,
    numero_registro    VARCHAR(60)  NULL,
    estado             VARCHAR(20)  NOT NULL,
    vigencia_desde     DATE         NULL,
    vigencia_hasta     DATE         NULL,
    fuente_registro    VARCHAR(191) NOT NULL,
    verificado_en      DATETIME     NULL,
    notas              TEXT         NULL,
    creado_por         INT UNSIGNED NULL,
    creado_en          DATETIME     NOT NULL,
    actualizado_en     DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_reclutadores_normalizado (nombre_normalizado),
    KEY idx_reclutadores_estado (estado),
    CONSTRAINT fk_reclutadores_creador FOREIGN KEY (creado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Otros nombres con los que opera un mismo reclutador (nombre comercial).
CREATE TABLE reclutadores_alias (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reclutador_id     INT UNSIGNED NOT NULL,
    alias             VARCHAR(191) NOT NULL,
    alias_normalizado VARCHAR(191) NOT NULL,
    creado_en         DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_alias_normalizado (alias_normalizado),
    KEY idx_alias_reclutador (reclutador_id),
    CONSTRAINT fk_alias_reclutador FOREIGN KEY (reclutador_id) REFERENCES reclutadores_autorizados (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===================================================================
--  3. OFERTAS
-- ===================================================================

-- Estados válidos (se validan en PHP, ver app/config/catalogos.php):
--   pendiente | verificada | publicada | vencida | retirada | en_revision
--
-- verificada_por es obligatorio para que una oferta se muestre como
-- verificada: la regla 1 queda amarrada al esquema, no a la buena memoria.
CREATE TABLE ofertas (
    id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo                   VARCHAR(200) NOT NULL,
    descripcion              TEXT         NOT NULL,
    empleador                VARCHAR(150) NOT NULL,
    reclutador_id            INT UNSIGNED NULL,
    fuente_id                INT UNSIGNED NOT NULL,
    pais_codigo              VARCHAR(20)  NOT NULL,
    ciudad                   VARCHAR(100) NULL,
    rubro_id                 INT UNSIGNED NOT NULL,
    requisitos               TEXT         NULL,
    experiencia_anios_min    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    estudios_min             VARCHAR(30)  NOT NULL,
    disponibilidad_requerida VARCHAR(30)  NOT NULL,
    salario_texto            VARCHAR(120) NULL,
    url_original             VARCHAR(255) NULL,
    estado                   VARCHAR(20)  NOT NULL,
    fecha_publicacion        DATE         NULL,
    fecha_vencimiento        DATE         NULL,
    verificada_en            DATETIME     NULL,
    verificada_por           INT UNSIGNED NULL,
    motivo_retiro            VARCHAR(255) NULL,
    creada_por               INT UNSIGNED NULL,
    creado_en                DATETIME     NOT NULL,
    actualizado_en           DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_ofertas_publicas (estado, fecha_vencimiento),
    KEY idx_ofertas_rubro (rubro_id),
    KEY idx_ofertas_pais (pais_codigo),
    KEY idx_ofertas_fuente (fuente_id),
    KEY idx_ofertas_reclutador (reclutador_id),
    CONSTRAINT fk_ofertas_fuente     FOREIGN KEY (fuente_id)      REFERENCES fuentes (id),
    CONSTRAINT fk_ofertas_rubro      FOREIGN KEY (rubro_id)       REFERENCES rubros (id),
    CONSTRAINT fk_ofertas_reclutador FOREIGN KEY (reclutador_id)  REFERENCES reclutadores_autorizados (id) ON DELETE SET NULL,
    CONSTRAINT fk_ofertas_verifica   FOREIGN KEY (verificada_por) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_ofertas_creador    FOREIGN KEY (creada_por)     REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Idiomas que pide una oferta.
CREATE TABLE ofertas_idiomas (
    oferta_id     INT UNSIGNED NOT NULL,
    idioma_codigo VARCHAR(20)  NOT NULL,
    obligatorio   TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (oferta_id, idioma_codigo),
    CONSTRAINT fk_ofertas_idiomas_oferta FOREIGN KEY (oferta_id) REFERENCES ofertas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===================================================================
--  4. PERFIL DE LA PERSONA
-- ===================================================================

-- Los únicos campos que puede leer el emparejamiento (regla 7):
--   rubros, anios_experiencia, nivel_estudios, idiomas, países, disponibilidad.
-- Ubicación = a dónde QUIERE ir (tabla perfiles_paises), nunca de dónde viene.
CREATE TABLE perfiles (
    usuario_id        INT UNSIGNED NOT NULL,
    anios_experiencia TINYINT UNSIGNED NOT NULL DEFAULT 0,
    nivel_estudios    VARCHAR(30)  NULL,
    disponibilidad    VARCHAR(30)  NULL,
    disponible_desde  DATE         NULL,
    cv_archivo        VARCHAR(80)  NULL,
    cv_extension      VARCHAR(10)  NULL,
    cv_tamano         INT UNSIGNED NULL,
    cv_origen         VARCHAR(20)  NULL,
    cv_subido_en      DATETIME     NULL,
    confirmado_en     DATETIME     NULL,
    actualizado_en    DATETIME     NULL,
    PRIMARY KEY (usuario_id),
    CONSTRAINT fk_perfiles_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE perfiles_rubros (
    usuario_id INT UNSIGNED NOT NULL,
    rubro_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (usuario_id, rubro_id),
    KEY idx_perfiles_rubros_rubro (rubro_id),
    CONSTRAINT fk_pr_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_rubro   FOREIGN KEY (rubro_id)   REFERENCES rubros (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE perfiles_idiomas (
    usuario_id    INT UNSIGNED NOT NULL,
    idioma_codigo VARCHAR(20)  NOT NULL,
    PRIMARY KEY (usuario_id, idioma_codigo),
    CONSTRAINT fk_pi_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Países donde la persona está dispuesta a trabajar.
CREATE TABLE perfiles_paises (
    usuario_id  INT UNSIGNED NOT NULL,
    pais_codigo VARCHAR(20)  NOT NULL,
    PRIMARY KEY (usuario_id, pais_codigo),
    CONSTRAINT fk_pp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE ofertas_guardadas (
    usuario_id INT UNSIGNED NOT NULL,
    oferta_id  INT UNSIGNED NOT NULL,
    creado_en  DATETIME     NOT NULL,
    PRIMARY KEY (usuario_id, oferta_id),
    KEY idx_guardadas_oferta (oferta_id),
    CONSTRAINT fk_og_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_og_oferta  FOREIGN KEY (oferta_id)  REFERENCES ofertas (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===================================================================
--  5. CONSENTIMIENTO Y POSTULACIÓN
-- ===================================================================

-- El consentimiento se crea PRIMERO y la postulación apunta a él.
-- Como postulaciones.consentimiento_id es NOT NULL, no existe forma de
-- crear una postulación sin su propio consentimiento (regla 6).
-- Y al revés: si el consentimiento se borra (porque se borró la cuenta
-- o la oferta), la postulación se va con él. Sin ese ON DELETE CASCADE,
-- MySQL se negaba a borrar la cuenta de cualquiera que se hubiera
-- postulado (ver sql/migracion_001.sql).
-- texto_version guarda qué texto exacto aceptó la persona, para que
-- cambiar el texto después no reescriba la historia.
CREATE TABLE consentimientos (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id    INT UNSIGNED NOT NULL,
    oferta_id     INT UNSIGNED NOT NULL,
    texto_version VARCHAR(20)  NOT NULL,
    cv_archivo    VARCHAR(80)  NOT NULL,
    otorgado_en   DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_consentimientos_usuario (usuario_id, otorgado_en),
    KEY idx_consentimientos_oferta (oferta_id),
    CONSTRAINT fk_cons_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_cons_oferta  FOREIGN KEY (oferta_id)  REFERENCES ofertas (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE postulaciones (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id        INT UNSIGNED NOT NULL,
    oferta_id         INT UNSIGNED NOT NULL,
    consentimiento_id INT UNSIGNED NOT NULL,
    estado            VARCHAR(20)  NOT NULL,
    creado_en         DATETIME     NOT NULL,
    actualizado_en    DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_postulacion_unica (usuario_id, oferta_id),
    KEY idx_postulaciones_oferta (oferta_id),
    KEY idx_postulaciones_consentimiento (consentimiento_id),
    CONSTRAINT fk_post_usuario FOREIGN KEY (usuario_id)        REFERENCES usuarios (id)        ON DELETE CASCADE,
    CONSTRAINT fk_post_oferta  FOREIGN KEY (oferta_id)         REFERENCES ofertas (id)         ON DELETE CASCADE,
    CONSTRAINT fk_post_cons    FOREIGN KEY (consentimiento_id) REFERENCES consentimientos (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===================================================================
--  6. REPORTES DE USUARIOS
-- ===================================================================

-- usuario_id queda en NULL si la persona borra su cuenta: el reporte
-- sigue sirviéndole al administrador, sin el dato de quién lo hizo.
CREATE TABLE reportes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    oferta_id   INT UNSIGNED NOT NULL,
    usuario_id  INT UNSIGNED NULL,
    motivo      VARCHAR(40)  NOT NULL,
    descripcion TEXT         NULL,
    estado      VARCHAR(20)  NOT NULL,
    creado_en   DATETIME     NOT NULL,
    revisado_por INT UNSIGNED NULL,
    revisado_en  DATETIME    NULL,
    resolucion   TEXT        NULL,
    PRIMARY KEY (id),
    KEY idx_reportes_estado (estado, creado_en),
    KEY idx_reportes_oferta (oferta_id),
    CONSTRAINT fk_reportes_oferta  FOREIGN KEY (oferta_id)    REFERENCES ofertas (id)  ON DELETE CASCADE,
    CONSTRAINT fk_reportes_usuario FOREIGN KEY (usuario_id)   REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_reportes_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
