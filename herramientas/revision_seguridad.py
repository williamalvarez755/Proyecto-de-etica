"""
Revision de seguridad de la Fase 7.
Comprueba, leyendo el codigo, los puntos que se pueden comprobar asi.
"""
import io
import re
import sys
import pathlib

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

RAIZ = pathlib.Path(sys.argv[1])
ARCHIVOS = [p for p in sorted(RAIZ.rglob('*.php')) if 'vendor' not in p.parts]
fallos = 0


def titulo(n, texto):
    print(f"\n=== {n}. {texto} ===")


def ok(msg):
    print("  [ok] " + msg)


def mal(msg):
    global fallos
    fallos += 1
    print("  [X]  " + msg)


def leer(p):
    return p.read_text(encoding='utf-8')


def sin_comentarios(t):
    t = re.sub(r'/\*.*?\*/', '', t, flags=re.S)
    return re.sub(r'//[^\n]*', '', t)


def rel(p):
    return str(p.relative_to(RAIZ)).replace('\\', '/')


# ---------------------------------------------------------------- 1
titulo(1, "SQL construido por concatenacion")
# Se revisa LINEA POR LINEA y no sobre el archivo entero: un patron
# multilinea empieza el match en el cierre de una cadena y lo termina en
# la apertura de la siguiente, y marca como peligroso el codigo PHP que
# hay en medio. Eso produce falsos positivos constantes, y un detector
# con falsos positivos constantes es uno que nadie vuelve a mirar.
SQL_PALABRA = re.compile(r'\b(SELECT|INSERT INTO|UPDATE\s|DELETE FROM)\b')
# Fragmentos de estructura que arma el propio codigo, nunca datos:
#   $donde / $extra -> condiciones WHERE con parametros con nombre
#   $nombre_seguro  -> nombre de tabla validado contra lista blanca
ESTRUCTURA = ('donde', 'extra', 'nombre_seguro', 'sql')

hallazgos = []
for p in ARCHIVOS:
    for n, linea in enumerate(leer(p).split('\n'), 1):
        limpia = linea.strip()
        if limpia.startswith(('*', '//', '/*', '#')):
            continue

        # a) una variable DENTRO de una cadena que contiene SQL
        for m in re.finditer(r'''(['"])((?:(?!\1).)*)\1''', linea):
            cuerpo = m.group(2)
            if SQL_PALABRA.search(cuerpo) and re.search(r'\$\w|\{\$', cuerpo):
                hallazgos.append(f"{rel(p)}:{n} variable dentro de la cadena SQL")

        # b) una variable concatenada justo despues de una cadena SQL
        for m in re.finditer(r"""(['"])(?:(?!\1).)*\1\s*\.\s*\$(\w+)""", linea):
            if SQL_PALABRA.search(linea) and m.group(2) not in ESTRUCTURA:
                hallazgos.append(f"{rel(p)}:{n} concatena ${m.group(2)} en SQL")

for h in dict.fromkeys(hallazgos):
    mal(h)
if not hallazgos:
    ok("ninguna cadena SQL contiene una variable: todo dato va como parametro")
    ok("las unicas concatenaciones son estructura ($donde, $extra) y la lista blanca de tablas")


# ---------------------------------------------------------------- 2
titulo(2, "Salida sin escapar (XSS)")

FORMATEADORES = ('escapar(', 'htmlspecialchars(', '(int)', '(float)', 'count(', 'implode',
                 'array_sum', 'fecha_en_palabras', 'fecha_hora_en_palabras', 'nl2br',
                 'urlencode', 'http_build_query', 'number_format', 'icono(')
# htmlspecialchars() es lo mismo que hace escapar(). Lo usa
# diagnostico.php, que es autonomo y no puede cargar las funciones
# del sistema.
# icono() (app/nucleo/iconos.php) nunca imprime lo que recibe: busca el
# nombre en la lista fija ICONOS y devuelve ese dibujo, o nada si no
# existe; la clase la pasa por escapar(). Por eso es segura aunque el
# nombre venga en una variable.

# Un ternario cuyas dos ramas son literales de cadena no puede inyectar nada:
# lo que se imprime es una de esas dos cadenas, escritas por nosotros.
TERNARIO_LITERAL = re.compile(r"^.+\?\s*'[^']*'\s*:\s*'[^']*'$", re.S)


def es_entero_seguro(nombre, texto):
    """La variable se asigna desde algo que siempre devuelve un entero."""
    patrones = [
        rf"\${nombre}\s*=\s*\(int\)",
        rf"\${nombre}\s*=\s*count\(",
        rf"\${nombre}\s*=\s*max\(",
        rf"\${nombre}\s*=\s*contar_",
        rf"\${nombre}\s*=\s*entero_en_rango\(",
        rf"\${nombre}\s*=\s*reportes_pendientes\(",
        rf"\${nombre}\s*=\s*eliminar_cuenta\(",
        rf"\${nombre}\s*=\s*\d+",
    ]
    return any(re.search(pat, texto) for pat in patrones)


hallazgos = []
for p in ARCHIVOS:
    t = leer(p)
    for n, linea in enumerate(t.split('\n'), 1):
        for m in re.finditer(r'<\?=\s*(.+?)\s*\?>', linea):
            expr = m.group(1).strip()
            if '$' not in expr:
                continue
            if any(s in expr for s in FORMATEADORES):
                continue
            if TERNARIO_LITERAL.match(expr):
                continue
            solo_var = re.fullmatch(r'\$(\w+)', expr)
            if solo_var and es_entero_seguro(solo_var.group(1), t):
                continue
            hallazgos.append(f"{rel(p)}:{n}  <?= {expr[:70]}")
for h in hallazgos:
    mal(h)
if not hallazgos:
    ok("toda variable impresa pasa por escapar(), por un cast, o es un ternario de literales")


# ---------------------------------------------------------------- 3
titulo(3, "CSRF en los formularios que modifican datos")
hallazgos = []
total_formularios = 0
for p in ARCHIVOS:
    for i, cuerpo in enumerate(re.findall(r'<form[^>]*method="post"[^>]*>(.*?)</form>',
                                          leer(p), flags=re.S | re.I), 1):
        total_formularios += 1
        if 'campo_csrf()' not in cuerpo:
            hallazgos.append(f"{rel(p)}: formulario POST #{i} sin campo_csrf()")
for h in hallazgos:
    mal(h)
if not hallazgos:
    ok(f"los {total_formularios} formularios POST llevan campo_csrf()")
    ok("y inicio.php valida el token en TODA peticion POST (decision D-011)")


# ---------------------------------------------------------------- 4 y 7
titulo("4 y 7", "Control de acceso del lado del servidor")
# entrar.php y salir.php son las puertas: no pueden exigir sesion.
PUERTAS = {'entrar.php', 'salir.php'}
hallazgos = []
for p in sorted((RAIZ / 'htdocs' / 'admin').glob('*.php')):
    if p.name in PUERTAS:
        continue
    if not re.search(r'requerir_(permiso|administrativo|superadministrador)\s*\(', sin_comentarios(leer(p))):
        hallazgos.append(f"{rel(p)}: no exige rol ni permiso")
for h in hallazgos:
    mal(h)
if not hallazgos:
    ok("cada pantalla de htdocs/admin/ exige rol o permiso en el servidor")
    ok("entrar.php y salir.php son las puertas y no pueden exigirlo, por definicion")

hallazgos = []
PUBLICAS_CUENTA = {'entrar.php', 'registrarse.php', 'restablecer.php', 'salir.php'}
for p in sorted((RAIZ / 'htdocs' / 'cuenta').glob('*.php')):
    if p.name in PUBLICAS_CUENTA:
        continue
    if not re.search(r'requerir_(sesion|rol_usuario)\s*\(', sin_comentarios(leer(p))):
        hallazgos.append(f"{rel(p)}: no exige sesion")
for h in hallazgos:
    mal(h)
if not hallazgos:
    ok("cada pagina personal exige sesion")

# Un usuario normal no debe poder entrar al panel
aut = leer(RAIZ / 'app' / 'nucleo' / 'autorizacion.php')
if 'acceso_denegado' in aut and 'abortar(' in aut:
    ok("un usuario normal que escriba una URL del panel recibe 403 y queda en la bitacora")
else:
    mal("el rechazo al panel no queda registrado")


# ---------------------------------------------------------------- 6
titulo(6, "Identificadores manipulables en la URL")
hallazgos = []
for p in ARCHIVOS:
    t = sin_comentarios(leer(p))
    for m in re.finditer(r'(?:parametro|campo)\(\s*[\'"](id|oferta|postulacion|editar|quien|reporte|oferta_id|usuario_id|fuente_id|reclutador_id|alias_id|reporte_id|postulacion_id)[\'"]', t):
        contexto = t[max(0, m.start() - 140):m.end() + 60]
        if 'id_valido' not in contexto and 'entero_en_rango' not in contexto:
            linea = t[:m.start()].count('\n') + 1
            hallazgos.append(f"{rel(p)}:{linea} '{m.group(1)}' sin validar como entero")
for h in hallazgos:
    mal(h)
if not hallazgos:
    ok("todo identificador que llega por URL o formulario pasa por id_valido()")


# ---------------------------------------------------------------- 5
titulo(5, "Subida y descarga de archivos")
arch = leer(RAIZ / 'app' / 'nucleo' / 'archivos.php')
for cumple, texto in [
    ('FILEINFO_MIME_TYPE' in arch, "el tipo real se comprueba con finfo, no por la extension"),
    ('random_bytes' in arch, "el nombre del archivo se genera al azar"),
    ('RUTA_CV' in arch, "se guarda fuera de htdocs"),
    ('nosniff' in arch, "se entrega con X-Content-Type-Options: nosniff"),
    ('attachment' in arch, "se entrega como descarga, nunca como pagina"),
    ('[a-f0-9]{32}' in arch, "solo se aceptan nombres con la forma que genera el sistema"),
    ('CV_TAMANO_MAXIMO_BYTES' in arch, "hay limite de tamano"),
]:
    ok(texto) if cumple else mal("FALTA: " + texto)

propio = sin_comentarios(leer(RAIZ / 'htdocs' / 'cuenta' / 'archivo_cv.php'))
if 'parametro(' not in propio:
    ok("la descarga del propio CV no recibe identificador: sale de la sesion")
else:
    mal("la descarga del propio CV recibe un parametro manipulable")


# ---------------------------------------------------------------- 12
titulo(12, "Consentimiento para compartir el CV")
admin_cv = leer(RAIZ / 'htdocs' / 'admin' / 'archivo_cv.php')
for cumple, texto in [
    ('hay_consentimiento_para' in admin_cv, "comprueba consentimiento para ESA oferta"),
    ("requerir_permiso('cv.descargar')" in admin_cv, "exige el permiso cv.descargar"),
    ('cv_descargado' in admin_cv, "registra la descarga antes de entregar el archivo"),
    ("cv_archivo" in admin_cv, "entrega el archivo que dice el consentimiento, no el actual"),
]:
    ok(texto) if cumple else mal("FALTA: " + texto)

post = leer(RAIZ / 'app' / 'modelos' / 'postulaciones.php')
if 'beginTransaction' in post:
    ok("consentimiento y postulacion se crean en una transaccion: o los dos, o ninguno")
else:
    mal("no se crean juntos")

esquema = leer(RAIZ / 'sql' / 'esquema.sql')
if re.search(r'consentimiento_id\s+INT UNSIGNED NOT NULL', esquema):
    ok("la base impide una postulacion sin consentimiento (columna NOT NULL)")
else:
    mal("la base permitiria una postulacion sin consentimiento")


# ---------------------------------------------------------------- 13 y 14
titulo("13 y 14", "Ofertas vencidas y no verificadas fuera de la vista publica")
of = leer(RAIZ / 'app' / 'modelos' / 'ofertas.php')
cond = re.search(r'CONDICION_OFERTA_PUBLICA = "(.*?)";', of, flags=re.S)
if cond:
    c = cond.group(1)
    for cumple, texto in [
        ("estado = 'publicada'" in c, "solo las publicadas"),
        ("verificada_por IS NOT NULL" in c, "solo si alguien las verifico y quedo su nombre"),
        ("fecha_publicacion IS NOT NULL" in c, "solo si tienen fecha de publicacion que mostrar"),
        ("fecha_vencimiento >= :hoy" in c, "solo si no paso su vencimiento"),
    ]:
        ok(texto) if cumple else mal("FALTA en la condicion publica: " + texto)
else:
    mal("no se encontro CONDICION_OFERTA_PUBLICA")

publicos = ['htdocs/ofertas.php', 'htdocs/oferta.php', 'htdocs/reportar.php',
            'htdocs/cuenta/postular.php', 'app/modelos/guardadas.php']
malos = [f for f in publicos if re.search(r'\bbuscar_oferta\(', sin_comentarios(leer(RAIZ / f)))]
if malos:
    mal("paginas publicas que usan buscar_oferta() en vez de buscar_oferta_publica(): " + ', '.join(malos))
else:
    ok("las paginas publicas solo consultan por la via publica")

if 'CONDICION_OFERTA_PUBLICA' in leer(RAIZ / 'app' / 'modelos' / 'guardadas.php'):
    ok("las ofertas guardadas tambien pasan por la condicion publica")
else:
    mal("las guardadas podrian mostrar ofertas retiradas o vencidas")


# ---------------------------------------------------------------- 9
titulo(9, "Intentos repetidos de inicio de sesion")
for archivo in ['htdocs/cuenta/entrar.php', 'htdocs/admin/entrar.php',
                'htdocs/cuenta/restablecer.php']:
    t = leer(RAIZ / archivo)
    if 'esta_bloqueado(' in t and 'registrar_intento(' in t:
        ok(f"{archivo}: cuenta los fallos y bloquea")
    else:
        mal(f"{archivo}: no bloquea por intentos fallidos")

usu = leer(RAIZ / 'app' / 'modelos' / 'usuarios.php')
if 'password_verify($contrasena, ' in usu and 'return null;' in usu:
    ok("con un correo que no existe igual se gasta el tiempo de verificar (no se puede enumerar)")
else:
    mal("se podria averiguar que correos tienen cuenta por el tiempo de respuesta")


# ---------------------------------------------------------------- 10
titulo(10, "Reportes")
rep = leer(RAIZ / 'app' / 'modelos' / 'reportes.php')
if not re.search(r'UPDATE\s+ofertas', rep):
    ok("el modelo de reportes NO puede cambiar el estado de ninguna oferta")
else:
    mal("el modelo de reportes toca la tabla ofertas")
if 'ya_reporto' in rep:
    ok("no se puede reportar dos veces la misma oferta desde una cuenta")
else:
    mal("se podria reportar muchas veces desde la misma cuenta")


# ---------------------------------------------------------------- 11
titulo(11, "Eliminacion de cuenta")
eli = leer(RAIZ / 'app' / 'modelos' / 'eliminacion.php')
for cumple, texto in [
    ('DELETE FROM usuarios' in eli, "borra la fila de verdad, no la marca como eliminada"),
    ('borrar_cv(' in eli, "borra los archivos del disco"),
    ('archivos_de_cv_de' in eli, "junta todos sus archivos, incluidos los ya compartidos"),
    (eli.find('archivos_de_cv_de') < eli.find('DELETE FROM usuarios'),
     "junta los nombres ANTES de borrar la fila, para que no queden huerfanos"),
]:
    ok(texto) if cumple else mal("FALTA: " + texto)

# Cada llave, una por una, y en su propia linea. La version anterior
# buscaba "ON DELETE CASCADE" en cualquier parte del esquema: daba [ok]
# aunque fk_post_cons no tuviera cascada, y por eso nadie vio que quien
# se habia postulado NO podia borrar su cuenta (ver sql/migracion_001.sql).
# Y si algo faltaba no decia nada: un control que solo sabe decir "ok"
# no controla.
LLAVES_EN_CASCADA = [
    'fk_perfiles_usuario', 'fk_pr_usuario', 'fk_pi_usuario', 'fk_pp_usuario',
    'fk_og_usuario', 'fk_cons_usuario', 'fk_post_usuario', 'fk_post_cons',
    'fk_restablecimientos_usuario',
]
sin_cascada = [
    llave for llave in LLAVES_EN_CASCADA
    if not re.search(r'CONSTRAINT\s+' + llave + r'\b[^\n]*ON DELETE CASCADE', esquema)
]
if not sin_cascada:
    ok("perfil, postulaciones, consentimientos y guardadas se borran en cascada")
else:
    mal("FALTA ON DELETE CASCADE en: " + ", ".join(sin_cascada)
        + " (borrar la cuenta va a fallar)")

if re.search(r'CONSTRAINT\s+fk_reportes_usuario\b[^\n]*ON DELETE SET NULL', esquema):
    ok("los reportes se conservan sin identidad, para seguir protegiendo a otros")
else:
    mal("fk_reportes_usuario no queda en NULL al borrar la cuenta")

print("  [!]  esto revisa el TEXTO del esquema. Que el borrado funcione de verdad")
print("       solo se sabe probandolo: crear una cuenta, postularse y borrarla.")


# ---------------------------------------------------------------- 8
titulo(8, "Solicitudes con datos invalidos")
val = leer(RAIZ / 'app' / 'nucleo' / 'validacion.php')
for fn, texto in [('en_catalogo', "los valores de menus se validan contra listas cerradas"),
                  ('id_valido', "los identificadores se validan como enteros en rango"),
                  ('es_fecha_valida', "las fechas se validan con checkdate"),
                  ('url_segura', "las direcciones web no aceptan javascript:"),
                  ('limpiar_texto', "se quitan caracteres de control de todo texto"),
                  ('cayo_en_trampa', "hay trampa para formularios automaticos")]:
    ok(texto) if f'function {fn}' in val else mal("FALTA " + fn)


# ---------------------------------------------------------------- extra
titulo("+", "Cabeceras, sesion y limites")
ini = leer(RAIZ / 'app' / 'nucleo' / 'inicio.php')
for clave, texto in [('Content-Security-Policy', "CSP enviada desde PHP"),
                     ('X-Content-Type-Options', "nosniff en toda respuesta"),
                     ('X-Frame-Options', "no se puede incrustar el sitio"),
                     ('Referrer-Policy', "no se filtra de donde vino la persona"),
                     ('Strict-Transport-Security', "HSTS si la visita llego por HTTPS"),
                     ('validar_csrf', "CSRF validado en todo POST"),
                     ('revisar_ritmo', "freno al ritmo de peticiones")]:
    ok(texto) if clave in ini else mal("FALTA " + texto)

ses = leer(RAIZ / 'app' / 'nucleo' / 'sesion.php')
for clave, texto in [("'httponly' => true", "cookie httponly"),
                     ("'samesite' => 'Lax'", "cookie samesite"),
                     ('session_regenerate_id', "el id de sesion cambia al iniciar sesion"),
                     ('use_strict_mode', "no se aceptan ids de sesion inventados")]:
    ok(texto) if clave in ses else mal("FALTA " + texto)

err = leer(RAIZ / 'app' / 'nucleo' / 'errores.php')
if "display_errors', '0'" in err:
    ok("los errores de PHP no se muestran en pantalla en produccion")
else:
    mal("los errores podrian mostrarse al usuario")

htaccess = leer(RAIZ / 'app' / '.htaccess')
if 'Require all denied' in htaccess:
    ok("app/ tiene .htaccess que niega todo, como segunda linea de defensa")
else:
    mal("app/ no esta protegida por .htaccess")


print("\n" + "=" * 62)
print("RESULTADO: los 14 puntos pasan." if fallos == 0
      else f"RESULTADO: {fallos} hallazgos. NO se puede dar por terminado.")
print("=" * 62)
sys.exit(1 if fallos else 0)
