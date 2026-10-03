-- ===================================================================
--  PRIMER LOTE DE OFERTAS REALES · comprobado el 2026-10-03
--  ------------------------------------------------------------------
--  Seis vacantes REALES de empleo en Guatemala, tomadas de las páginas
--  oficiales de empleo de dos empresas que contratan a personas
--  retornadas (los call centers son uno de los tres sectores que más
--  las reciben). Decisión D-056 del CLAUDE.md.
--
--  DE DÓNDE SALEN
--    - Allied Global: https://jobs.alliedglobal.com/open-positions
--    - IntouchCX (antes 24-7 Intouch): https://apply.24-7intouch.com/
--      Sede de Guatemala confirmada en su página oficial de sedes:
--      15 Av. 5-00 zona 13, edificio WTC, torre 1, Ciudad de Guatemala.
--
--  QUÉ SE COMPROBÓ EN CADA UNA (las mismas cinco comprobaciones de
--  admin/oferta_verificar.php, D-017), el 2026-10-03:
--    1. La vacante está publicada en la página oficial del empleador.
--    2. El empleador está identificado: empresa con sede y página propias.
--    3. No le pide dinero a la persona por ningún concepto.
--    4. No pide fotos del DPI, pasaporte ni datos bancarios antes de
--       una entrevista (el DPI original se presenta al contratar).
--    5. Los datos cargados coinciden con la publicación original.
--  Las hizo el asistente de desarrollo leyendo cada publicación oficial.
--  Las descripciones están escritas con palabras propias, no copiadas.
--
--  QUIÉN LAS APRUEBA
--    Quien corre este archivo con su cuenta de phpMyAdmin. Quedan
--    atribuidas al primer superadministrador, y en la bitácora queda
--    escrito qué se comprobó, cuándo y quién.
--
--  CÓMO SE POSTULA LA PERSONA
--    En la página oficial de la empresa (forma_postulacion 'externa',
--    D-058). La plataforma no recibe ni guarda currículums de estas.
--
--  VENCEN SOLAS EL 2026-11-02
--    30 días después de comprobarlas. La fecha está fija a propósito:
--    si este archivo se corre más tarde, NO se presentan como recién
--    revisadas, y si se corre después del 2026-11-02 no carga nada.
--    Para renovarlas hay que volver a comprobarlas en la página de la
--    empresa: una vacante de hace un mes puede ya no existir.
--
--  CUÁNDO SE CORRE
--    Después de migracion_002.sql (necesita las columnas nuevas y el
--    oficio "Atención al cliente y call center") y después de crear el
--    superadministrador con instalar.php.
--    Se puede correr más de una vez: no duplica nada.
--
--  CÓMO SE CORRE
--    phpMyAdmin -> elegir la base -> pestaña "Importar" -> este
--    archivo -> Continuar. Al final muestra una tabla con las 6.
-- ===================================================================

SET NAMES utf8mb4;


-- -------------------------------------------------------------------
--  Las fuentes: las páginas oficiales de empleo de cada empresa
-- -------------------------------------------------------------------
INSERT INTO fuentes (nombre, tipo, url, descripcion, activa, notas, creado_en)
SELECT 'Allied Global — página oficial de empleos', 'empleador_directo',
       'https://jobs.alliedglobal.com/open-positions',
       'Vacantes publicadas por la propia empresa en su portal de empleo', 1,
       'Revisada el 2026-10-03 para el primer lote de ofertas reales (D-056).', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fuentes WHERE nombre = 'Allied Global — página oficial de empleos');

INSERT INTO fuentes (nombre, tipo, url, descripcion, activa, notas, creado_en)
SELECT 'IntouchCX — página oficial de postulación', 'empleador_directo',
       'https://apply.24-7intouch.com/',
       'Vacantes publicadas por la propia empresa en su portal de postulación', 1,
       'Revisada el 2026-10-03 para el primer lote de ofertas reales (D-056).', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fuentes WHERE nombre = 'IntouchCX — página oficial de postulación');


-- -------------------------------------------------------------------
--  Las ofertas
--  Cada INSERT carga UNA oferta, solo si todavía no está (se reconoce
--  por su dirección original) y solo si no venció.
-- -------------------------------------------------------------------

-- 1. Allied Global · Asesor de call center en idioma maya
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Asesor de call center en idioma maya',
    'Atender llamadas de clientes en un idioma maya y en español, en las oficinas de la empresa en la Ciudad de Guatemala. Es trabajo presencial, de tiempo completo y con contrato permanente. La capacitación es pagada. La empresa ofrece transporte nocturno gratuito, bonos por productividad y por referidos, descuentos para empleados y atención médica y psicológica gratuita. Hay turnos de mañana, de tarde o a elección.',
    'Allied Global',
    (SELECT id FROM fuentes WHERE nombre = 'Allied Global — página oficial de empleos'),
    'gt', 'guatemala', 'Ciudad de Guatemala',
    (SELECT id FROM rubros WHERE codigo = 'atencion_cliente'),
    'Hablar al menos un idioma maya: K''iche'', Q''eqchi'', Mam, Kaqchikel, Q''anjob''al, Ixil, Poqomchi'', Poqomam u otro. Título de diversificado. Tener 18 años o más. Buen récord crediticio. Saber usar la computadora y escribir rápido. Disponibilidad de horario. No se pide experiencia (es ideal en el sector financiero).',
    0, 'diversificado', 'a_convenir',
    'Entre Q3,900 y Q5,000 al mes',
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-idiomas-mayas',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://jobs.alliedglobal.com/o/asesor-de-call-center-idiomas-mayas')
ORDER BY u.id LIMIT 1;

-- 2. Allied Global · Atención al cliente en inglés
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Agente de servicio al cliente en inglés',
    'Atender por teléfono a clientes que hablan inglés, en las oficinas de la empresa en la Ciudad de Guatemala. Es trabajo presencial, de tiempo completo y con contrato permanente. No se pide experiencia. La empresa ofrece transporte gratuito dentro de la ciudad, seguro médico, clínica y atención de salud mental gratuitas, bono por asistencia, bono por referidos y descuentos para empleados. En la empresa el puesto se llama "Customer Service Representative GT".',
    'Allied Global',
    (SELECT id FROM fuentes WHERE nombre = 'Allied Global — página oficial de empleos'),
    'gt', 'guatemala', 'Ciudad de Guatemala',
    (SELECT id FROM rubros WHERE codigo = 'atencion_cliente'),
    'Inglés al 80 %, hablado y escrito. DPI vigente. Tener 18 años o más. La empresa puede pedir revisión de antecedentes o prueba de drogas.',
    0, 'ninguno', 'a_convenir',
    'Entre Q4,400 y Q6,700 al mes',
    'https://jobs.alliedglobal.com/o/customer-service-representative-gt',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://jobs.alliedglobal.com/o/customer-service-representative-gt')
ORDER BY u.id LIMIT 1;

-- 3. Allied Global · Asesor de call center en español
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Asesor de call center en español',
    'Atender llamadas en español en las oficinas de la empresa en la Ciudad de Guatemala. Es trabajo presencial, de tiempo completo y con contrato permanente. La capacitación es pagada y se puede elegir turno de mañana o de tarde. La empresa ofrece transporte nocturno gratuito, bonos por productividad y servicios médicos y psicológicos gratuitos. La publicación no dice el salario.',
    'Allied Global',
    (SELECT id FROM fuentes WHERE nombre = 'Allied Global — página oficial de empleos'),
    'gt', 'guatemala', 'Ciudad de Guatemala',
    (SELECT id FROM rubros WHERE codigo = 'atencion_cliente'),
    'Título de diversificado. Tener 18 años o más. Récord crediticio sin moras. Saber usar la computadora y escribir rápido. Facilidad para negociar y comunicarse. No se pide experiencia.',
    0, 'diversificado', 'a_convenir',
    NULL,
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-espanol-2',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://jobs.alliedglobal.com/o/asesor-de-call-center-espanol-2')
ORDER BY u.id LIMIT 1;

-- 4. Allied Global · Ventas por teléfono en inglés, en Petén
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Representante de ventas por teléfono en inglés',
    'Vender por teléfono a clientes que hablan inglés, en la sede de la empresa en Melchor de Mencos, Petén. Es trabajo presencial, de tiempo completo y con contrato permanente. La empresa ofrece transporte gratuito dentro de la ciudad, seguro médico, atención médica y de salud mental gratuitas, bono por asistencia, bono por referidos y posibilidades de ascenso. La publicación no dice el salario. En la empresa el puesto se llama "Sales Representative PTN".',
    'Allied Global',
    (SELECT id FROM fuentes WHERE nombre = 'Allied Global — página oficial de empleos'),
    'gt', 'peten', 'Melchor de Mencos',
    (SELECT id FROM rubros WHERE codigo = 'atencion_cliente'),
    'Inglés intermedio (entre 65 % y 80 %) o avanzado. Gusto por atender clientes y resolver problemas. La empresa pregunta por experiencia en ventas, pero acepta a quien no la tiene.',
    0, 'ninguno', 'a_convenir',
    NULL,
    'https://jobs.alliedglobal.com/o/sales-representative-peten-gt',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://jobs.alliedglobal.com/o/sales-representative-peten-gt')
ORDER BY u.id LIMIT 1;

-- 5. IntouchCX · Atención al cliente en inglés, por temporada (inglés B1)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Agente de servicio al cliente en inglés, por temporada',
    'Atender a clientes que hablan inglés por teléfono, correo o chat, en el campus de la empresa en la Ciudad de Guatemala (15 avenida 5-00 zona 13, edificio WTC). Es un puesto de temporada: algunos turnos dependen de la disponibilidad. La empresa ofrece horarios flexibles, salario competitivo, seguro médico y de vida, clínica en el lugar y descuentos para empleados. La publicación no dice el salario exacto.',
    'IntouchCX (antes 24-7 Intouch)',
    (SELECT id FROM fuentes WHERE nombre = 'IntouchCX — página oficial de postulación'),
    'gt', 'guatemala', 'Ciudad de Guatemala',
    (SELECT id FROM rubros WHERE codigo = 'atencion_cliente'),
    'Inglés nivel B1 o más. Poder trabajar legalmente en Guatemala (DPI original). Título de diversificado o equivalente. Antecedentes penales y policiacos vigentes. Trabajar presencial en el campus.',
    0, 'diversificado', 'a_convenir',
    NULL,
    'https://apply.24-7intouch.com/57',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://apply.24-7intouch.com/57')
ORDER BY u.id LIMIT 1;

-- 6. IntouchCX · Atención al cliente en inglés (inglés B2)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Representante de servicio al cliente en inglés',
    'Atender a clientes que hablan inglés por llamadas, correos o chat, en el campus de la empresa en la Ciudad de Guatemala (15 avenida 5-00 zona 13, edificio WTC). La empresa ofrece horarios flexibles, salario competitivo, posibilidades de ascenso, clínica en el lugar, seguro médico y de vida y descuentos para empleados. La publicación no dice el salario exacto.',
    'IntouchCX (antes 24-7 Intouch)',
    (SELECT id FROM fuentes WHERE nombre = 'IntouchCX — página oficial de postulación'),
    'gt', 'guatemala', 'Ciudad de Guatemala',
    (SELECT id FROM rubros WHERE codigo = 'atencion_cliente'),
    'Inglés nivel B2 o más. Poder trabajar legalmente en Guatemala (DPI original). Título de diversificado o equivalente. Antecedentes penales y policiacos vigentes. Trabajar presencial en el campus.',
    0, 'diversificado', 'a_convenir',
    NULL,
    'https://apply.24-7intouch.com/52/6',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://apply.24-7intouch.com/52/6')
ORDER BY u.id LIMIT 1;


-- -------------------------------------------------------------------
--  Idiomas que pide cada una
--  La de idiomas mayas pide español; el idioma maya va en palabras en
--  los requisitos, porque sirve CUALQUIERA de ellos y el emparejamiento
--  le marcaría a la persona que le faltan todos los que no habla.
-- -------------------------------------------------------------------
INSERT IGNORE INTO ofertas_idiomas (oferta_id, idioma_codigo, obligatorio)
SELECT id, 'espanol', 1 FROM ofertas WHERE url_original IN (
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-idiomas-mayas',
    'https://jobs.alliedglobal.com/o/customer-service-representative-gt',
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-espanol-2',
    'https://jobs.alliedglobal.com/o/sales-representative-peten-gt',
    'https://apply.24-7intouch.com/57',
    'https://apply.24-7intouch.com/52/6'
);

INSERT IGNORE INTO ofertas_idiomas (oferta_id, idioma_codigo, obligatorio)
SELECT id, 'ingles', 1 FROM ofertas WHERE url_original IN (
    'https://jobs.alliedglobal.com/o/customer-service-representative-gt',
    'https://jobs.alliedglobal.com/o/sales-representative-peten-gt',
    'https://apply.24-7intouch.com/57',
    'https://apply.24-7intouch.com/52/6'
);


-- -------------------------------------------------------------------
--  La bitácora: qué se comprobó, cuándo y quién la aprobó
-- -------------------------------------------------------------------
INSERT INTO bitacora_admin (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en)
SELECT o.verificada_por, 'oferta_verificada', 'oferta', o.id,
       'Lote 2026-10-03: el asistente la comprobó en la página oficial del empleador (publicada ahí, empleador identificado, no pide dinero ni fotos de documentos, datos iguales). Aprobada al importar con esta cuenta.',
       'phpMyAdmin (lote)', NOW()
FROM ofertas o
WHERE o.url_original IN (
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-idiomas-mayas',
    'https://jobs.alliedglobal.com/o/customer-service-representative-gt',
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-espanol-2',
    'https://jobs.alliedglobal.com/o/sales-representative-peten-gt',
    'https://apply.24-7intouch.com/57',
    'https://apply.24-7intouch.com/52/6'
)
  AND NOT EXISTS (
    SELECT 1 FROM bitacora_admin b
    WHERE b.entidad = 'oferta' AND b.entidad_id = o.id AND b.accion = 'oferta_verificada'
  );

INSERT INTO bitacora_admin (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en)
SELECT o.verificada_por, 'oferta_publicada', 'oferta', o.id,
       'Lote 2026-10-03: publicada al importar. Se postula en la página de la empresa. Vence sola el 2026-11-02.',
       'phpMyAdmin (lote)', NOW()
FROM ofertas o
WHERE o.url_original IN (
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-idiomas-mayas',
    'https://jobs.alliedglobal.com/o/customer-service-representative-gt',
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-espanol-2',
    'https://jobs.alliedglobal.com/o/sales-representative-peten-gt',
    'https://apply.24-7intouch.com/57',
    'https://apply.24-7intouch.com/52/6'
)
  AND NOT EXISTS (
    SELECT 1 FROM bitacora_admin b
    WHERE b.entidad = 'oferta' AND b.entidad_id = o.id AND b.accion = 'oferta_publicada'
  );


-- -------------------------------------------------------------------
--  Para comprobar: tienen que aparecer las 6, en estado "publicada".
--  Si no aparece ninguna: falta correr migracion_002.sql, falta el
--  superadministrador, o ya pasó el 2026-11-02.
-- -------------------------------------------------------------------
SELECT o.id, o.titulo, o.empleador, o.ciudad, o.estado, o.fecha_vencimiento
FROM ofertas o
WHERE o.url_original IN (
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-idiomas-mayas',
    'https://jobs.alliedglobal.com/o/customer-service-representative-gt',
    'https://jobs.alliedglobal.com/o/asesor-de-call-center-espanol-2',
    'https://jobs.alliedglobal.com/o/sales-representative-peten-gt',
    'https://apply.24-7intouch.com/57',
    'https://apply.24-7intouch.com/52/6'
)
ORDER BY o.id;
