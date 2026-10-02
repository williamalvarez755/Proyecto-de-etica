"""
PRUEBA DE HUMO DEL SITIO PUBLICADO
===================================================================
Se corre en la computadora, contra el sitio ya subido, DESPUÉS DE
CADA SUBIDA POR FTP:

    python herramientas/prueba_de_humo.py https://tusitio.infinityfreeapp.com

Es de SOLO LECTURA: no crea cuentas, no llena formularios, no cambia
nada. Solo pide páginas y mira qué responde el servidor.

Qué comprueba, y por qué cada cosa:

  1. Que las carpetas privadas NO se puedan abrir desde internet
     (app/, vendor/, los currículums). Desde D-044 esa protección
     depende de tres .htaccess, y la única forma de saber que
     funcionan es pedirlas y ver que el servidor diga que no.
  2. Que las páginas públicas respondan, incluida una búsqueda con
     texto: así se habría visto enseguida el error 500 del buscador
     que encontró la auditoría del 2026-10-01.
  3. Que lleguen las cabeceras de seguridad (CSP, nosniff, marcos).
  4. Que se hayan borrado instalar.php y diagnostico.php.

Sobre el sistema anti-robots de InfinityFree: a un programa que no es
un navegador, el hosting le contesta con una página que exige
JavaScript. Si pasa, la prueba lo dice, y hay que pasarle la cookie
"__test" del navegador:

    1. Abrí el sitio en Chrome o Firefox.
    2. F12 -> Aplicación (o Almacenamiento) -> Cookies -> copiá el
       valor de __test.
    3. python herramientas/prueba_de_humo.py https://tusitio... --cookie VALOR
"""

import sys
import urllib.error
import urllib.parse
import urllib.request

fallos = 0


def ok(texto):
    print("  [ok] " + texto)


def mal(texto):
    global fallos
    fallos += 1
    print("  [X]  " + texto)


def aviso(texto):
    print("  [!]  " + texto)


class SinRedirecciones(urllib.request.HTTPRedirectHandler):
    """Queremos ver el código tal cual (301, 403...), sin seguirlo."""

    def redirect_request(self, *argumentos, **nombrados):
        return None


def pedir(base, ruta, cookie):
    url = base.rstrip("/") + ruta
    peticion = urllib.request.Request(url, headers={"User-Agent": "prueba-de-humo/1.0"})
    if cookie:
        peticion.add_header("Cookie", "__test=" + cookie)
    abridor = urllib.request.build_opener(SinRedirecciones)
    try:
        respuesta = abridor.open(peticion, timeout=20)
        return respuesta.status, dict(respuesta.headers), respuesta.read(200000).decode("utf-8", "replace")
    except urllib.error.HTTPError as error:
        return error.code, dict(error.headers), error.read(200000).decode("utf-8", "replace")
    except (urllib.error.URLError, TimeoutError) as error:
        return None, {}, str(error)


def es_desafio_antirrobots(cuerpo):
    return "aes.js" in cuerpo or "slowAES" in cuerpo or "__test=" in cuerpo


def main():
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(2)

    base = sys.argv[1]
    cookie = sys.argv[sys.argv.index("--cookie") + 1] if "--cookie" in sys.argv else ""

    codigo, _, cuerpo = pedir(base, "/", cookie)
    if codigo is None:
        print("No se pudo conectar con " + base + ": " + cuerpo)
        sys.exit(2)
    if es_desafio_antirrobots(cuerpo):
        print("El hosting contestó con su página anti-robots, no con el sitio.")
        print("Pasale la cookie __test del navegador con --cookie (ver arriba de este archivo).")
        sys.exit(2)

    # ------------------------------------------------------------ 1
    print("\n=== 1. Carpetas privadas (D-044) ===")
    for ruta in ["/app/config/config.php", "/app/config/catalogos.php", "/app/almacen/cv/",
                 "/app/almacen/logs/", "/app/nucleo/inicio.php", "/vendor/autoload.php",
                 "/app/.htaccess"]:
        codigo, _, cuerpo = pedir(base, ruta, cookie)
        if codigo in (403, 404):
            ok(ruta + " -> " + str(codigo))
        elif codigo == 200 and cuerpo.strip() == "":
            # PHP ejecutado sin salida: no filtra el código, pero significa
            # que Apache NO está bloqueando la carpeta.
            mal(ruta + " -> 200 (vacío). Apache no está bloqueando la carpeta: revisar los .htaccess YA")
        else:
            mal(ruta + " -> " + str(codigo) + ". ESTO ES GRAVE: la carpeta privada se puede abrir")

    # ------------------------------------------------------------ 2
    print("\n=== 2. Páginas públicas ===")
    for ruta, debe_decir in [
        ("/", "verific"),
        ("/ofertas.php", "Ofertas de trabajo"),
        ("/ofertas.php?texto=" + urllib.parse.quote("albañil"), "Ofertas de trabajo"),
        ("/verificador.php?nombre=" + urllib.parse.quote("prueba de humo"), "registro"),
        ("/alertas.php", "oferta falsa"),
        ("/cuenta/entrar.php", "Entrar"),
        ("/cuenta/registrarse.php", "cuenta"),
        ("/recursos/estilo.css", "--azul"),
        ("/recursos/app.js", "use strict"),
    ]:
        codigo, _, cuerpo = pedir(base, ruta, cookie)
        if codigo == 200 and debe_decir in cuerpo:
            ok(ruta)
        elif codigo == 200:
            mal(ruta + " responde 200 pero no muestra lo esperado (¿página de error?)")
        else:
            mal(ruta + " -> " + str(codigo))

    codigo, _, _ = pedir(base, "/oferta.php?id=999999999", cookie)
    if codigo == 404:
        ok("una oferta que no existe responde 404")
    else:
        mal("/oferta.php?id=999999999 -> " + str(codigo) + " (debería ser 404)")

    # ------------------------------------------------------------ 3
    print("\n=== 3. Cabeceras de seguridad ===")
    _, cabeceras, _ = pedir(base, "/", cookie)
    cabeceras = {clave.lower(): valor for clave, valor in cabeceras.items()}
    for nombre, debe_contener in [
        ("content-security-policy", "script-src 'self'"),
        ("x-content-type-options", "nosniff"),
        ("x-frame-options", "DENY"),
        ("referrer-policy", "same-origin"),
    ]:
        if debe_contener.lower() in cabeceras.get(nombre, "").lower():
            ok(nombre)
        else:
            mal("falta o está mal: " + nombre)
    if base.startswith("https://"):
        if "max-age" in cabeceras.get("strict-transport-security", ""):
            ok("strict-transport-security")
        else:
            aviso("no llegó strict-transport-security (revisar si PHP ve la conexión como HTTPS)")
        codigo, cabeceras_http, _ = pedir("http://" + base[len("https://"):], "/", cookie)
        destino = {k.lower(): v for k, v in cabeceras_http.items()}.get("location", "")
        if codigo in (301, 302, 307, 308) and destino.startswith("https://"):
            ok("http:// redirige a https://")
        else:
            aviso("http:// no redirigió a https:// (" + str(codigo) + ")")

    # ------------------------------------------------------------ 4
    print("\n=== 4. Archivos que se usan una vez y se borran ===")
    for ruta in ["/instalar.php", "/diagnostico.php"]:
        codigo, _, _ = pedir(base, ruta, cookie)
        if codigo == 404:
            ok(ruta + " ya no está")
        else:
            mal(ruta + " sigue en el servidor (" + str(codigo) + "): borralo por FTP")

    print("\n" + "=" * 62)
    print("RESULTADO: " + ("todo en orden." if fallos == 0 else str(fallos) + " problema(s). Revisar antes de seguir."))
    print("=" * 62)
    sys.exit(1 if fallos else 0)


if __name__ == "__main__":
    main()
