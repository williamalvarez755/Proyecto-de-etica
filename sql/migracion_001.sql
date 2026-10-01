-- ===================================================================
--  MIGRACIÓN 001 · 2026-10-01
--  Las postulaciones se borran junto con su consentimiento
--  ------------------------------------------------------------------
--
--  QUÉ PROBLEMA ARREGLA
--    En el esquema original, postulaciones.consentimiento_id apuntaba
--    a consentimientos SIN "ON DELETE CASCADE". Al borrar una cuenta,
--    MySQL intentaba borrar los consentimientos mientras la
--    postulación todavía los apuntaba, y rechazaba el borrado entero.
--
--    Consecuencia: cualquier persona que se hubiera postulado a una
--    oferta NO podía borrar su cuenta (veía "Algo salió mal"). Eso
--    rompe la regla 4. Lo mismo pasaba al borrar ofertas con
--    postulaciones, por ejemplo con las instrucciones de limpieza de
--    sql/datos_prueba.sql.
--
--    El código ya se corrigió para que el borrado de cuenta funcione
--    aunque esta migración no se haya corrido (borra las postulaciones
--    antes). Esta migración arregla la causa en la base.
--
--  CUÁNDO SE CORRE
--    Una sola vez, en una base que se creó con el esquema.sql anterior
--    al 2026-10-01. Si la base se creó con el esquema.sql nuevo, NO
--    hace falta (y daría error porque la llave ya está bien).
--
--  CÓMO SE CORRE
--    phpMyAdmin -> elegir la base -> pestaña "SQL" -> pegar las dos
--    instrucciones de abajo -> Continuar.
--
--  CÓMO SE COMPRUEBA QUE QUEDÓ
--    phpMyAdmin -> tabla postulaciones -> Estructura -> Vista de
--    relaciones: fk_post_cons tiene que decir ON DELETE: CASCADE.
-- ===================================================================

ALTER TABLE postulaciones DROP FOREIGN KEY fk_post_cons;

ALTER TABLE postulaciones
    ADD CONSTRAINT fk_post_cons FOREIGN KEY (consentimiento_id)
        REFERENCES consentimientos (id) ON DELETE CASCADE;
