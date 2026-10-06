-- ===================================================================
--  MIGRACIÓN 003 · 2026-10-06
--  Una cuenta no puede reportar dos veces la misma oferta
--  ------------------------------------------------------------------
--
--  QUÉ ARREGLA
--    reportar.php comprueba con ya_reporto() si la cuenta ya reportó la
--    oferta, pero esa comprobación y el guardado son dos pasos: dos
--    envíos a la vez (recargar rápido, doble toque con internet lento)
--    pasaban los dos antes de que el primero guardara, y quedaban dos
--    o tres reportes iguales. No es grave —ninguna oferta se retira
--    sola por reportes (D-033)—, pero infla el conteo que ve quien
--    revisa. Esta llave única lo impide en la base.
--
--    En MySQL dos valores NULL no se consideran iguales, así que los
--    reportes sin identidad (de cuentas borradas, usuario_id = NULL,
--    D-041) NO chocan entre sí: se conservan todos, que es lo correcto.
--
--  CUÁNDO SE CORRE
--    Una sola vez, en una base creada con el esquema.sql anterior al
--    2026-10-06. Si la base se creó con el esquema nuevo, NO hace falta
--    (la llave ya existe y daría error).
--    Va después de migracion_002.sql.
--
--  CÓMO SE CORRE
--    phpMyAdmin -> elegir la base -> pestaña "SQL" -> pegar todo este
--    archivo -> Continuar.
--
--  CÓMO SE COMPRUEBA
--    phpMyAdmin -> tabla reportes -> Estructura -> Índices: tiene que
--    aparecer uk_reporte_cuenta_oferta como UNIQUE sobre
--    (usuario_id, oferta_id).
-- ===================================================================

-- 1. Primero se quitan los duplicados que pudiera haber de antes, si no
--    la llave única no se puede crear. De cada grupo repetido
--    (misma cuenta, misma oferta) se conserva el reporte más viejo (el
--    id más chico) y se borran los demás. Los reportes sin identidad
--    (usuario_id NULL) no entran acá: no se tocan.
DELETE r FROM reportes r
INNER JOIN reportes mas_viejo
        ON mas_viejo.usuario_id = r.usuario_id
       AND mas_viejo.oferta_id  = r.oferta_id
       AND mas_viejo.id         < r.id
WHERE r.usuario_id IS NOT NULL;

-- 2. La llave única.
ALTER TABLE reportes
    ADD UNIQUE KEY uk_reporte_cuenta_oferta (usuario_id, oferta_id);
