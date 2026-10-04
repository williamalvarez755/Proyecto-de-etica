-- ===================================================================
--  SEGUNDO LOTE DE OFERTAS REALES: OFICIOS · comprobado el 2026-10-03
--  ------------------------------------------------------------------
--  Doce vacantes reales de oficios en Guatemala (bodega, pilotos,
--  ayudantes de albañil, maquinaria, mecánica, ventas y campo), varias
--  en el occidente: Huehuetenango, San Marcos, Quetzaltenango, Quiché,
--  Sololá y Coatepeque. Decisión D-060 del CLAUDE.md.
--
--  DE DÓNDE SALEN
--    Portal oficial de empleo del grupo Progreso ("Trabaja con
--    nosotros"): https://cementosprogreso.pandape.computrabajo.com
--    Ahí la propia empresa publica sus vacantes (Cementos Progreso,
--    tiendas Construfácil, Mixto Listo y Agreca). Está alojado en
--    Pandapé, el sistema de reclutamiento que usa la empresa; no es la
--    bolsa pública de Computrabajo. Se leyó cada publicación una por
--    una, como lo haría una persona, sin programas automáticos.
--
--  QUÉ SE COMPROBÓ EN CADA UNA (las cinco comprobaciones de D-017):
--    1. Está publicada en el portal oficial de la empresa.
--    2. El empleador está identificado (empresa guatemalteca desde 1899).
--    3. No le pide dinero a la persona.
--    4. No pide fotos del DPI, pasaporte ni datos bancarios.
--    5. Los datos cargados coinciden con la publicación (las
--       descripciones están escritas con palabras propias).
--  Las hizo el asistente de desarrollo. Igual que el primer lote
--  (D-056), las aprueba quien corre este archivo, y queda escrito en
--  la bitácora.
--
--  CÓMO SE POSTULA LA PERSONA
--    En el portal de la empresa (botón "Aplicar a este proceso").
--    forma_postulacion 'externa' (D-058).
--
--  VENCEN SOLAS
--    Las publicadas en septiembre y octubre, el 2026-11-02 (30 días).
--    Las dos publicadas en agosto, el 2026-10-17: llevan más tiempo
--    abiertas y es más probable que se cierren antes.
--    Para renovarlas hay que volver a comprobar que siguen en el portal.
--
--  CUÁNDO SE CORRE
--    Después de migracion_002.sql y del superadministrador. Se puede
--    correr más de una vez: no duplica nada. Después de la fecha de
--    vencimiento de cada oferta, esa oferta ya no se carga.
-- ===================================================================

SET NAMES utf8mb4;


-- -------------------------------------------------------------------
--  La fuente
-- -------------------------------------------------------------------
INSERT INTO fuentes (nombre, tipo, url, descripcion, activa, notas, creado_en)
SELECT 'Progreso — página oficial de empleos', 'empleador_directo',
       'https://cementosprogreso.pandape.computrabajo.com',
       'Vacantes que publica el grupo Progreso (Cementos Progreso, Construfácil, Mixto Listo, Agreca) en su portal de empleo', 1,
       'Revisada el 2026-10-03 para el segundo lote de ofertas reales (D-060).', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos');

-- 1. Ayudante de bodega en tienda de materiales de construcción · San Marcos
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Ayudante de bodega en tienda de materiales de construcción',
    'Cargar, descargar y revisar la mercadería dentro y fuera de la tienda Construfácil de San Marcos, para que cada cliente reciba sus materiales completos y en buen estado. Es trabajo presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Construfácil (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'san_marcos', 'San Marcos',
    (SELECT id FROM rubros WHERE codigo = 'empaque_bodega'),
    'Sexto primaria. 1 año de experiencia como ayudante de albañil, ayudante de entrega o cargador. Poder cargar y descargar materiales de construcción. Vivir en San Marcos o cerca.',
    1, 'primaria', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531766',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13531766')
ORDER BY u.id LIMIT 1;

-- 2. Piloto de camión de entrega de materiales · Aldea Caxaque (km 252)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Piloto de camión de entrega de materiales',
    'Manejar el camión y entregar materiales de construcción a los clientes, revisando la carga antes, durante y después de cada entrega. La tienda está en el kilómetro 252, aldea Caxaque, salida a la costa, después del INTECAP. La empresa busca 3 personas. Es trabajo presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Construfácil (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'san_marcos', 'Aldea Caxaque (km 252)',
    (SELECT id FROM rubros WHERE codigo = 'transporte'),
    'Sexto primaria. 1 año de experiencia manejando camiones de 5 toneladas o más. Licencia tipo A vigente.',
    1, 'primaria', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531725',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13531725')
ORDER BY u.id LIMIT 1;

-- 3. Ayudante de bodega en tienda de materiales de construcción · Zona 8 de Xela
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Ayudante de bodega en tienda de materiales de construcción',
    'Recibir, acomodar y despachar productos en la bodega de la tienda Construfácil de la zona 8 de Xela (1a. calle 36-20), y cargar y descargar la mercadería que piden los clientes. Es trabajo presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Construfácil (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'quetzaltenango', 'Zona 8 de Xela',
    (SELECT id FROM rubros WHERE codigo = 'empaque_bodega'),
    'Sexto primaria. 1 año de experiencia como ayudante de albañil, ayudante de bodega o cargador. Poder cargar y descargar materiales de construcción.',
    1, 'primaria', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13631511',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13631511')
ORDER BY u.id LIMIT 1;

-- 4. Vendedor temporal en tienda de materiales de construcción · Zona 8 de Xela
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Vendedor temporal en tienda de materiales de construcción',
    'Atender a los clientes de la tienda Construfácil de la zona 8 de Xela, entender qué necesitan y ayudarles a comprar. Es un contrato por tiempo determinado (temporal), presencial y de tiempo completo. La publicación no dice el salario.',
    'Construfácil (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'quetzaltenango', 'Zona 8 de Xela',
    (SELECT id FROM rubros WHERE codigo = 'comercio'),
    'Título de diversificado (perito contador, bachiller o parecido). 1 año de experiencia como vendedor en tienda o atendiendo clientes. Vivir en la zona 8 de Xela o cerca.',
    1, 'diversificado', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13797188',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13797188')
ORDER BY u.id LIMIT 1;

-- 5. Vendedor en tienda de materiales de construcción · Santa Cruz del Quiché
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Vendedor en tienda de materiales de construcción',
    'Atender a los clientes de la tienda Construfácil de Santa Cruz del Quiché (2a. calle final, zona 3, salida a Totonicapán, a la par del hospital Santa Elena) y ayudarles a comprar lo que necesitan. Es trabajo presencial, de tiempo completo, de lunes a sábado, con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Construfácil (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'quiche', 'Santa Cruz del Quiché',
    (SELECT id FROM rubros WHERE codigo = 'comercio'),
    'Título de diversificado (perito contador, bachiller o parecido). 1 año de experiencia como vendedor en tienda, atendiendo clientes o como asesor comercial (la empresa valora 2 años). Disponibilidad de lunes a sábado.',
    1, 'diversificado', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531870',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13531870')
ORDER BY u.id LIMIT 1;

-- 6. Chequeador de producto en bodega · Aldea Chimusinique (zona 12)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Chequeador de producto en bodega',
    'Revisar que todo lo que sale de la bodega lleve la cantidad y la calidad correctas según la factura o la orden de traslado. Es trabajo presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Progreso',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'huehuetenango', 'Aldea Chimusinique (zona 12)',
    (SELECT id FROM rubros WHERE codigo = 'empaque_bodega'),
    'Título de diversificado. 1 año de experiencia como verificador, chequeador o auxiliar de bodega. Es una ventaja conocer materiales de ferretería y construcción. Vivir en la zona 12, aldea Chimusinique, o cerca.',
    1, 'diversificado', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13681017',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13681017')
ORDER BY u.id LIMIT 1;

-- 7. Ayudante general en planta de cemento · Aldea Pujujil
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Ayudante general en planta de cemento',
    'Apoyar en el despacho y la recepción de producto en la sede de aldea Pujujil, Sololá. Es trabajo pesado, en turnos rotativos. La empresa busca 2 personas. Es presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Cementos Progreso',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'solola', 'Aldea Pujujil',
    (SELECT id FROM rubros WHERE codigo = 'construccion'),
    'Sexto primaria. 1 año de experiencia como albañil, ayudante de albañil o ayudante de mantenimiento. Poder hacer trabajo pesado y trabajar en turnos rotativos. Tener transporte propio (licencia de moto o de carro). Vivir cerca de aldea Pujujil.',
    1, 'primaria', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13632834',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13632834')
ORDER BY u.id LIMIT 1;

-- 8. Ayudante general en planta de agregados · Coatepeque (aldea Las Palmas)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Ayudante general en planta de agregados',
    'Limpieza, mantenimiento, reparaciones y trabajos de construcción en la planta de Coatepeque, además de ayudar al operador de la trituradora y en el montaje de equipos. Es trabajo de campo, en turnos rotativos y con viajes ocasionales entre sedes. Es presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Agreca (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'quetzaltenango', 'Coatepeque (aldea Las Palmas)',
    (SELECT id FROM rubros WHERE codigo = 'construccion'),
    'Tercero básico. 1 año de experiencia como albañil, maestro de obra o ayudante de albañil. Trabajar en el campo, en turnos rotativos y viajar de vez en cuando entre sedes. Vivir cerca de aldea Las Palmas, planta Coatepeque. La empresa valora tener licencia de moto.',
    1, 'basicos', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13234989',
    'externa', 'publicada', CURDATE(), '2026-10-17', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-10-17'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13234989')
ORDER BY u.id LIMIT 1;

-- 9. Piloto de camión mezclador de concreto · Ciudad de Guatemala (Planta Norte, zona 6)
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Piloto de camión mezclador de concreto',
    'Manejar el camión mezclador y entregar el concreto a los clientes siguiendo las normas de seguridad y de calidad. La empresa busca 2 personas, en turnos rotativos y con traslados entre sedes. Es presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Mixto Listo (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'guatemala', 'Ciudad de Guatemala (Planta Norte, zona 6)',
    (SELECT id FROM rubros WHERE codigo = 'transporte'),
    'Sexto primaria. 3 años de experiencia como piloto de transporte pesado (caja de 9 velocidades). Licencia tipo A. Turnos rotativos. Tener cómo transportarse.',
    3, 'primaria', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13789686',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13789686')
ORDER BY u.id LIMIT 1;

-- 10. Mecánico de planta · Palín
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Mecánico de planta',
    'Mantenimiento preventivo y correctivo de la maquinaria y el equipo de la planta de Palín. La empresa busca 2 personas, en turnos rotativos y con traslados entre sedes. Es presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Agreca (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'escuintla', 'Palín',
    (SELECT id FROM rubros WHERE codigo = 'mecanica'),
    'Bachiller industrial y perito en mecánica general. Es ideal ser técnico en mecánica industrial, automotriz o diésel. Licencia tipo C (obligatoria) y carro o moto. Turnos rotativos. La empresa valora 2 años de experiencia, pero no la pide como obligatoria.',
    0, 'diversificado', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13578882',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13578882')
ORDER BY u.id LIMIT 1;

-- 11. Operador de cargador frontal · Palín
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Operador de cargador frontal',
    'Manejar maquinaria pesada (cargador frontal, excavadora, tractor de orugas, camión de minería y motoniveladora) para mantener caminos y llevar materiales a las trituradoras. Incluye fines de semana y turnos rotativos. Es presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Agreca (grupo Progreso)',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'escuintla', 'Palín',
    (SELECT id FROM rubros WHERE codigo = 'construccion'),
    'Tercero básico. 2 años de experiencia manejando cargador frontal. Licencia tipo E y transporte propio. Trabajar fines de semana y en turnos rotativos. Vivir cerca de Amatitlán, Palín o Escuintla.',
    2, 'basicos', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13290830',
    'externa', 'publicada', CURDATE(), '2026-10-17', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-10-17'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13290830')
ORDER BY u.id LIMIT 1;

-- 12. Peón de campo (jardinería y áreas verdes) · San Juan Sacatepéquez
INSERT INTO ofertas
    (titulo, descripcion, empleador, fuente_id, pais_codigo, departamento_codigo, ciudad,
     rubro_id, requisitos, experiencia_anios_min, estudios_min, disponibilidad_requerida,
     salario_texto, url_original, forma_postulacion, estado, fecha_publicacion,
     fecha_vencimiento, verificada_en, verificada_por, creada_por, creado_en)
SELECT
    'Peón de campo (jardinería y áreas verdes)',
    'Revisar terrenos, limpiar y mantener despejados los caminos de abastecimiento y producción, y cuidar instalaciones. La empresa busca 3 personas, en turnos rotativos. Es presencial, de tiempo completo y con contrato por tiempo indefinido. La publicación no dice el salario.',
    'Cementos Progreso',
    (SELECT id FROM fuentes WHERE nombre = 'Progreso — página oficial de empleos'),
    'gt', 'guatemala', 'San Juan Sacatepéquez',
    (SELECT id FROM rubros WHERE codigo = 'agricultura'),
    'Sexto primaria. 2 años de experiencia en el campo como agricultor, jardinero, en áreas verdes o podando árboles. Vivir cerca de San Juan Sacatepéquez. Turnos rotativos.',
    2, 'primaria', 'a_convenir',
    NULL,
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13730131',
    'externa', 'publicada', CURDATE(), '2026-11-02', '2026-10-03 12:00:00', u.id, u.id, NOW()
FROM usuarios u
INNER JOIN roles r ON r.id = u.rol_id
WHERE r.codigo = 'superadministrador'
  AND CURDATE() <= '2026-11-02'
  AND NOT EXISTS (SELECT 1 FROM ofertas WHERE url_original = 'https://cementosprogreso.pandape.computrabajo.com/Detail/13730131')
ORDER BY u.id LIMIT 1;


-- -------------------------------------------------------------------
--  Idioma: todas piden español
-- -------------------------------------------------------------------
INSERT IGNORE INTO ofertas_idiomas (oferta_id, idioma_codigo, obligatorio)
SELECT id, 'espanol', 1 FROM ofertas WHERE url_original IN (
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531766',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531725',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13631511',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13797188',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531870',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13681017',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13632834',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13234989',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13789686',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13578882',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13290830',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13730131'
);


-- -------------------------------------------------------------------
--  La bitácora: qué se comprobó, cuándo y quién la aprobó
-- -------------------------------------------------------------------
INSERT INTO bitacora_admin (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en)
SELECT o.verificada_por, 'oferta_verificada', 'oferta', o.id,
       'Lote oficios 2026-10-03: el asistente la comprobó en el portal oficial de empleo de Progreso (publicada ahí, empleador identificado, no pide dinero ni fotos de documentos, datos iguales). Aprobada al importar.',
       'phpMyAdmin (lote)', NOW()
FROM ofertas o
WHERE o.url_original IN (
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531766',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531725',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13631511',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13797188',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531870',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13681017',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13632834',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13234989',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13789686',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13578882',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13290830',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13730131'
)
  AND NOT EXISTS (
    SELECT 1 FROM bitacora_admin b
    WHERE b.entidad = 'oferta' AND b.entidad_id = o.id AND b.accion = 'oferta_verificada'
  );

INSERT INTO bitacora_admin (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en)
SELECT o.verificada_por, 'oferta_publicada', 'oferta', o.id,
       CONCAT('Lote oficios 2026-10-03: publicada al importar. Se postula en el portal de la empresa. Vence sola el ', o.fecha_vencimiento, '.'),
       'phpMyAdmin (lote)', NOW()
FROM ofertas o
WHERE o.url_original IN (
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531766',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531725',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13631511',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13797188',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531870',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13681017',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13632834',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13234989',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13789686',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13578882',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13290830',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13730131'
)
  AND NOT EXISTS (
    SELECT 1 FROM bitacora_admin b
    WHERE b.entidad = 'oferta' AND b.entidad_id = o.id AND b.accion = 'oferta_publicada'
  );


-- -------------------------------------------------------------------
--  Para comprobar: tienen que aparecer las 12 (menos las que ya
--  hayan vencido, si el archivo se corre tarde).
-- -------------------------------------------------------------------
SELECT o.id, o.titulo, o.ciudad, o.estado, o.fecha_vencimiento
FROM ofertas o
WHERE o.url_original IN (
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531766',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531725',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13631511',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13797188',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13531870',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13681017',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13632834',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13234989',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13789686',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13578882',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13290830',
    'https://cementosprogreso.pandape.computrabajo.com/Detail/13730131'
)
ORDER BY o.id;
