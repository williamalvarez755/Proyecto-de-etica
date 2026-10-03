-- ===================================================================
--  MIGRACIÓN 002 · 2026-10-03
--  Empleo en Guatemala: departamento, forma de postularse y un oficio
--  ------------------------------------------------------------------
--
--  QUÉ AGREGA (decisiones D-054, D-057 y D-058 del CLAUDE.md)
--    1. ofertas.departamento_codigo: en qué departamento de Guatemala
--       es el trabajo, para poder buscar por departamento. Es el lugar
--       del TRABAJO; de la persona no se guarda (D-008).
--    2. ofertas.forma_postulacion: 'plataforma' (la persona se postula
--       acá y la institución le pasa el currículum al empleador) o
--       'externa' (la persona se postula en la página oficial de la
--       empresa). Las existentes quedan en 'plataforma', como estaban.
--    3. El oficio "Atención al cliente y call center", uno de los tres
--       sectores que más contratan a personas retornadas.
--
--  CUÁNDO SE CORRE
--    Una sola vez, en una base creada con el esquema.sql anterior al
--    2026-10-03. Si la base se creó con el esquema.sql nuevo, NO hace
--    falta (daría error porque las columnas ya existen).
--    Va DESPUÉS de migracion_001.sql y ANTES de cargar ofertas reales.
--
--  CÓMO SE CORRE
--    phpMyAdmin -> elegir la base -> pestaña "SQL" -> pegar todo este
--    archivo -> Continuar.
--
--  CÓMO SE COMPRUEBA QUE QUEDÓ
--    phpMyAdmin -> tabla ofertas -> Estructura: tienen que aparecer
--    departamento_codigo y forma_postulacion. Y en la tabla rubros,
--    la fila atencion_cliente.
-- ===================================================================

ALTER TABLE ofertas
    ADD COLUMN departamento_codigo VARCHAR(20) NULL AFTER pais_codigo,
    ADD COLUMN forma_postulacion VARCHAR(20) NOT NULL DEFAULT 'plataforma' AFTER url_original,
    ADD KEY idx_ofertas_departamento (departamento_codigo);

INSERT INTO rubros (codigo, nombre, activo, orden)
SELECT 'atencion_cliente', 'Atención al cliente y call center', 1, 145
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM rubros WHERE codigo = 'atencion_cliente');
