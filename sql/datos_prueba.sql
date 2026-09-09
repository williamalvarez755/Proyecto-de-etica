-- ===================================================================
--  DATOS DE PRUEBA
--  ------------------------------------------------------------------
--  Sirve para enseñar la plataforma funcionando SIN usar datos de
--  personas reales.
--
--  CUÁNDO SE CORRE:
--    Después de esquema.sql y datos_iniciales.sql, y DESPUÉS de haber
--    creado la primera cuenta con instalar.php (las ofertas verificadas
--    necesitan un administrador al cual atribuir la verificación).
--
--  QUÉ CREA:
--    2 fuentes, 3 reclutadores (uno vencido a propósito) y 6 ofertas
--    en distintos estados, para poder mostrar el ciclo completo.
--
--  QUÉ NO CREA:
--    Ninguna cuenta de persona, ningún currículum, ninguna
--    postulación. Esos se prueban a mano creando una cuenta.
--
--  CÓMO SE BORRA DESPUÉS (antes de entregar el sitio de verdad):
--    Al final de este archivo están las instrucciones.
--
--  NOTA sobre las fechas: acá sí se usa CURDATE(), a diferencia del
--  resto del sistema (decisión D-009). Es un script que se corre a
--  mano una vez, y así las ofertas de prueba nunca nacen vencidas
--  aunque el archivo se use dentro de seis meses.
-- ===================================================================

SET NAMES utf8mb4;


-- -------------------------------------------------------------------
--  Fuentes
-- -------------------------------------------------------------------
INSERT INTO fuentes (nombre, tipo, url, descripcion, activa, notas, creado_en) VALUES
('EJEMPLO - Programa de Trabajo Temporal', 'institucion_publica',
 'https://www.mintrabajo.gob.gt/', 'Datos de prueba, no son ofertas reales', 1,
 'DATOS DE PRUEBA. Borrar antes de usar el sitio de verdad.', NOW()),
('EJEMPLO - Empleador directo', 'empleador_directo',
 NULL, 'Datos de prueba, no son ofertas reales', 1,
 'DATOS DE PRUEBA. Borrar antes de usar el sitio de verdad.', NOW());


-- -------------------------------------------------------------------
--  Reclutadores
--  El tercero está vencido a propósito: sirve para mostrar que el
--  sistema NO deja verificar una oferta suya (decisión D-020).
-- -------------------------------------------------------------------
INSERT INTO reclutadores_autorizados
    (nombre, nombre_normalizado, numero_registro, estado, vigencia_desde, vigencia_hasta,
     fuente_registro, verificado_en, notas, creado_en)
VALUES
('EJEMPLO Reclutadora del Valle, S.A.', 'EJEMPLO RECLUTADORA DEL VALLE S A', 'PRUEBA-001',
 'vigente', DATE_SUB(CURDATE(), INTERVAL 180 DAY), DATE_ADD(CURDATE(), INTERVAL 365 DAY),
 'DATOS DE PRUEBA', NOW(), 'DATOS DE PRUEBA. Borrar antes de usar el sitio de verdad.', NOW()),

('EJEMPLO Agencia Cosecha Norte', 'EJEMPLO AGENCIA COSECHA NORTE', 'PRUEBA-002',
 'vigente', DATE_SUB(CURDATE(), INTERVAL 90 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY),
 'DATOS DE PRUEBA', NOW(), 'DATOS DE PRUEBA. Borrar antes de usar el sitio de verdad.', NOW()),

('EJEMPLO Intermediarios Unidos', 'EJEMPLO INTERMEDIARIOS UNIDOS', 'PRUEBA-003',
 'vencido', DATE_SUB(CURDATE(), INTERVAL 800 DAY), DATE_SUB(CURDATE(), INTERVAL 60 DAY),
 'DATOS DE PRUEBA', NOW(),
 'DATOS DE PRUEBA. Vencido a propósito: sirve para probar que no deja verificar.', NOW());


-- Un nombre comercial alterno, para probar el verificador.
INSERT INTO reclutadores_alias (reclutador_id, alias, alias_normalizado, creado_en)
SELECT id, 'EJEMPLO Del Valle Reclutamientos', 'EJEMPLO DEL VALLE RECLUTAMIENTOS', NOW()
FROM reclutadores_autorizados WHERE numero_registro = 'PRUEBA-001';


-- -------------------------------------------------------------------
--  Ofertas
--  Se crean en varios estados para poder mostrar el ciclo completo.
--  Las publicadas quedan atribuidas al primer superadministrador.
-- -------------------------------------------------------------------

-- 1 y 2: PUBLICADAS (se ven en el sitio público)
INSERT INTO ofertas
    (titulo, descripcion, empleador, reclutador_id, fuente_id, pais_codigo, ciudad, rubro_id,
     requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida, salario_texto,
     estado, fecha_publicacion, fecha_vencimiento, verificada_en, verificada_por, creado_en)
SELECT
    'EJEMPLO - Cosecha de manzana temporada 2026',
    'Datos de prueba. Recolección de manzana en huerto. El empleador cubre hospedaje y transporte desde el aeropuerto. Contrato temporal de seis meses.',
    'EJEMPLO Huertos del Valle',
    (SELECT id FROM reclutadores_autorizados WHERE numero_registro = 'PRUEBA-002'),
    (SELECT id FROM fuentes WHERE nombre = 'EJEMPLO - Programa de Trabajo Temporal'),
    'ca', 'Kelowna',
    (SELECT id FROM rubros WHERE codigo = 'agricultura'),
    'Poder trabajar de pie durante la jornada', 1, 'ninguno', 'tres_meses',
    '18 dólares canadienses por hora',
    'publicada', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), NOW(),
    u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador' ORDER BY u.id LIMIT 1;

INSERT INTO ofertas
    (titulo, descripcion, empleador, reclutador_id, fuente_id, pais_codigo, ciudad, rubro_id,
     requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida, salario_texto,
     estado, fecha_publicacion, fecha_vencimiento, verificada_en, verificada_por, creado_en)
SELECT
    'EJEMPLO - Albañil para obra residencial',
    'Datos de prueba. Trabajo de albañilería en construcción de casas. Se requiere experiencia comprobable en levantado de block y repello.',
    'EJEMPLO Constructora del Norte',
    NULL,
    (SELECT id FROM fuentes WHERE nombre = 'EJEMPLO - Empleador directo'),
    'mx', 'Monterrey',
    (SELECT id FROM rubros WHERE codigo = 'construccion'),
    'Herramienta básica propia', 3, 'ninguno', 'un_mes',
    'A convenir según experiencia',
    'publicada', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 40 DAY), NOW(),
    u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador' ORDER BY u.id LIMIT 1;

-- 3: VERIFICADA pero sin publicar (no se ve en el sitio)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, ciudad, rubro_id,
     experiencia_anios_min, estudios_min, disponibilidad_requerida,
     estado, fecha_publicacion, fecha_vencimiento, verificada_en, verificada_por, creado_en)
SELECT
    'EJEMPLO - Ayudante de cocina en hotel',
    'Datos de prueba. Apoyo en cocina de hotel: preparación de alimentos, limpieza de área y lavado de loza. Turnos rotativos.',
    'EJEMPLO Hotel Costa Serena',
    (SELECT id FROM fuentes WHERE nombre = 'EJEMPLO - Empleador directo'),
    'cr', 'Guanacaste',
    (SELECT id FROM rubros WHERE codigo = 'hoteleria_restaurantes'),
    0, 'primaria', 'inmediata',
    'verificada', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 45 DAY), NOW(),
    u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador' ORDER BY u.id LIMIT 1;

-- 4: PENDIENTE (recién cargada, no se ve en ningún lado público)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, rubro_id,
     experiencia_anios_min, estudios_min, disponibilidad_requerida,
     estado, fecha_publicacion, fecha_vencimiento, creado_en)
VALUES
('EJEMPLO - Operario de empaque',
 'Datos de prueba. Empaque de producto terminado en planta. Turnos de ocho horas.',
 'EJEMPLO Empacadora Central',
 (SELECT id FROM fuentes WHERE nombre = 'EJEMPLO - Empleador directo'),
 'us',
 (SELECT id FROM rubros WHERE codigo = 'empaque_bodega'),
 0, 'ninguno', 'a_convenir',
 'pendiente', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW());

-- 5: PENDIENTE con reclutador VENCIDO
--    Sirve para probar que el sistema no deja verificarla.
INSERT INTO ofertas
    (titulo, descripcion, empleador, reclutador_id, fuente_id, pais_codigo, rubro_id,
     experiencia_anios_min, estudios_min, disponibilidad_requerida,
     estado, fecha_publicacion, fecha_vencimiento, creado_en)
VALUES
('EJEMPLO - Jardinería en condominio',
 'Datos de prueba. Mantenimiento de áreas verdes en condominio residencial. Esta oferta viene por un reclutador con autorización vencida: el sistema no debe dejar verificarla.',
 'EJEMPLO Condominios del Sur',
 (SELECT id FROM reclutadores_autorizados WHERE numero_registro = 'PRUEBA-003'),
 (SELECT id FROM fuentes WHERE nombre = 'EJEMPLO - Programa de Trabajo Temporal'),
 'es',
 (SELECT id FROM rubros WHERE codigo = 'jardineria'),
 2, 'ninguno', 'tres_meses',
 'pendiente', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 50 DAY), NOW());

-- 6: PUBLICADA pero YA VENCIDA
--    Sirve para probar que NO aparece en el buscador público, y que
--    el panel avisa que hay que ponerla al día.
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, rubro_id,
     experiencia_anios_min, estudios_min, disponibilidad_requerida,
     estado, fecha_publicacion, fecha_vencimiento, verificada_en, verificada_por, creado_en)
SELECT
    'EJEMPLO - Oferta que ya venció',
    'Datos de prueba. Esta oferta tiene fecha de vencimiento pasada: NO debe aparecer en el buscador público, aunque su estado siga diciendo publicada.',
    'EJEMPLO Empleador Anterior',
    (SELECT id FROM fuentes WHERE nombre = 'EJEMPLO - Empleador directo'),
    'mx',
    (SELECT id FROM rubros WHERE codigo = 'manufactura'),
    0, 'ninguno', 'a_convenir',
    'publicada', DATE_SUB(CURDATE(), INTERVAL 90 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), NOW(),
    u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador' ORDER BY u.id LIMIT 1;


-- Idiomas de algunas ofertas
INSERT INTO ofertas_idiomas (oferta_id, idioma_codigo, obligatorio)
SELECT id, 'espanol', 1 FROM ofertas WHERE titulo LIKE 'EJEMPLO -%';

INSERT INTO ofertas_idiomas (oferta_id, idioma_codigo, obligatorio)
SELECT id, 'ingles', 1 FROM ofertas WHERE titulo = 'EJEMPLO - Cosecha de manzana temporada 2026';


-- ===================================================================
--  CÓMO BORRAR TODOS LOS DATOS DE PRUEBA
--  ------------------------------------------------------------------
--  Antes de entregar el sitio con datos reales, corré estas tres
--  instrucciones en phpMyAdmin, en este orden:
--
--    DELETE FROM ofertas WHERE titulo LIKE 'EJEMPLO -%';
--    DELETE FROM reclutadores_autorizados WHERE nombre LIKE 'EJEMPLO %';
--    DELETE FROM fuentes WHERE nombre LIKE 'EJEMPLO -%';
--
--  Todo lo de prueba empieza con la palabra EJEMPLO justamente para
--  que se pueda borrar así y para que nadie lo confunda con una oferta
--  real mientras esté cargado.
-- ===================================================================
