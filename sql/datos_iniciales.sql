-- ===================================================================
--  DATOS INICIALES
--  Se corre UNA sola vez, después de esquema.sql, en phpMyAdmin.
--
--  Acá NO se crea ninguna cuenta (regla 10: nada de puertas traseras
--  ni credenciales escritas en el código). La primera cuenta de
--  superadministrador se crea desde htdocs/instalar.php, que la persona
--  dueña del hosting ejecuta una vez y después borra por FTP.
-- ===================================================================

SET NAMES utf8mb4;


-- -------------------------------------------------------------------
--  Roles
-- -------------------------------------------------------------------
INSERT INTO roles (codigo, nombre, descripcion) VALUES
('usuario',            'Usuario',             'Persona que busca trabajo. Ve ofertas, sube su currículum y se postula.'),
('administrador',      'Administrador',       'Personal de la institución. Carga, verifica y publica ofertas; revisa reportes.'),
('superadministrador', 'Superadministrador',  'Responsable del sistema. Además crea y desactiva administradores.');


-- -------------------------------------------------------------------
--  Permisos
--  El código es exactamente lo que se escribe en el PHP:
--      requerir_permiso('ofertas.verificar');
-- -------------------------------------------------------------------
INSERT INTO permisos (codigo, descripcion) VALUES
('ofertas.ver',                 'Ver todas las ofertas en el panel, incluidas las no publicadas'),
('ofertas.crear',               'Cargar una oferta nueva'),
('ofertas.editar',              'Modificar los datos de una oferta'),
('ofertas.verificar',           'Marcar una oferta como verificada'),
('ofertas.publicar',            'Publicar o despublicar una oferta verificada'),
('ofertas.retirar',             'Retirar o vencer una oferta'),
('fuentes.gestionar',           'Crear y editar fuentes de ofertas'),
('reclutadores.gestionar',      'Crear y editar el registro de reclutadores autorizados'),
('importacion.csv',             'Importar ofertas desde un archivo CSV'),
('reportes.revisar',            'Ver y resolver los reportes enviados por los usuarios'),
('cv.descargar',                'Descargar el currículum de una persona que se postuló'),
('usuarios.restablecer',        'Generar códigos de restablecimiento de contraseña'),
('bitacora.ver',                'Consultar la bitácora de auditoría'),
('mantenimiento.ejecutar',      'Ejecutar respaldos, limpieza y vencimiento de ofertas'),
('administradores.gestionar',   'Crear, desactivar y editar cuentas administrativas'),
('permisos.gestionar',          'Cambiar qué permisos tiene cada rol'),
('configuracion.sensible',      'Ver y modificar la configuración sensible del sistema');


-- -------------------------------------------------------------------
--  Qué permisos tiene cada rol
--
--  Administrador: todo lo operativo.
--  Superadministrador: todo, incluyendo la gestión de administradores.
--  Usuario: ningún permiso administrativo. Lo que puede hacer una
--  persona con su propia cuenta se comprueba por rol y por dueño del
--  dato, no con esta tabla.
-- -------------------------------------------------------------------

-- Administrador: todos los permisos MENOS los tres reservados.
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r, permisos p
WHERE r.codigo = 'administrador'
  AND p.codigo NOT IN ('administradores.gestionar', 'permisos.gestionar', 'configuracion.sensible');

-- Superadministrador: todos.
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r, permisos p
WHERE r.codigo = 'superadministrador';


-- -------------------------------------------------------------------
--  Rubros (oficios)
--  Pensados para el trabajo que realmente se ofrece en los programas
--  de trabajo temporal. Se pueden agregar más desde el panel.
-- -------------------------------------------------------------------
INSERT INTO rubros (codigo, nombre, activo, orden) VALUES
('agricultura',            'Agricultura y cosecha',              1, 10),
('construccion',           'Construcción y albañilería',         1, 20),
('carpinteria',            'Carpintería',                        1, 30),
('electricidad',           'Electricidad',                       1, 40),
('mecanica',               'Mecánica y automotriz',              1, 50),
('manufactura',            'Manufactura y maquila',              1, 60),
('empaque_bodega',         'Empaque y bodega',                   1, 70),
('hoteleria_restaurantes', 'Hotelería y restaurantes',           1, 80),
('alimentos',              'Panadería y procesamiento de alimentos', 1, 90),
('limpieza',               'Limpieza y mantenimiento',           1, 100),
('jardineria',             'Jardinería y áreas verdes',          1, 110),
('cuidado_personas',       'Cuidado de personas',                1, 120),
('transporte',             'Transporte y pilotaje',              1, 130),
('comercio',               'Comercio y ventas',                  1, 140),
('seguridad',              'Seguridad',                          1, 150),
('otros',                  'Otro oficio',                        1, 900);
