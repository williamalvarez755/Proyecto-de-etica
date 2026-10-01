# Auditoría del código · 2026-10-01

Revisión completa de las siete fases, corrección de lo que se encontró, mejora de la interfaz y
lista de lo que le falta a la plataforma. Las decisiones nuevas quedaron anotadas en
[CLAUDE.md](CLAUDE.md) (D-045 a D-049).

---

## 1. Resumen

| | Cuántos | Estado |
|---|---|---|
| Críticos | 2 | Corregidos y probados |
| Altos | 5 | Corregidos y probados |
| Medios | 7 | Corregidos y probados |
| Bajos | 6 | Corregidos |
| Necesitan una decisión tuya | 6 | **Sin tocar**: ver sección 4 |

Los dos críticos tienen algo en común: **la revisión de seguridad de los 14 puntos pasaba igual**.
`revision_seguridad.py` lee el código buscando patrones; ninguno de los dos errores se ve leyendo,
solo aparece al ejecutar. Esa es la lección más importante de esta auditoría (sección 5, idea 3).

---

## 2. Cómo se hizo

No se dio nada por bueno sin ejecutarlo. En el contenedor de trabajo se instalaron:

- **MariaDB 10.11** con `sql/esquema.sql`, `datos_iniciales.sql` y `datos_prueba.sql`, en modo
  estricto (el que trae por defecto).
- **PHP 8.3** (el servidor real tiene 8.3.19) con el sitio en `ENTORNO = 'desarrollo'`.
- **Apache 2.4** con una copia armada igual que en InfinityFree: `app/` como carpeta real dentro
  de `htdocs`, para probar los tres `.htaccess` de verdad.
- **Chromium** para recorrer el sitio como una persona, en tamaño de teléfono (390 px) y de
  escritorio, con JavaScript y sin JavaScript.

Se recorrieron de punta a punta: instalación, registro, subida de currículum, confirmación de
perfil, recomendadas, postulación, reporte, ofertas guardadas, cambio de contraseña,
restablecimiento, borrado de cuenta, y en el panel: carga, edición, verificación, publicación,
importación y descarga de currículums.

---

## 3. Hallazgos corregidos

### Críticos

**C1 · El buscador daba error 500 en cuanto alguien escribía una palabra.**
Público (`/ofertas.php?texto=...`) y del panel. La consulta usaba `:texto` tres veces, y con
`EMULATE_PREPARES` en `false` (que está bien y se mantiene) PDO no permite repetir un parámetro
con nombre. La búsqueda sin texto andaba, por eso pasaba desapercibido.
*Corrección:* `parametros_de_texto()` en `bd.php` arma `:texto1`, `:texto2`... con el mismo valor.
Archivos: `app/nucleo/bd.php`, `app/modelos/ofertas.php`.

**C2 · Quien se había postulado a una oferta NO podía borrar su cuenta.**
Error 500 y sus datos se quedaban. Rompe la regla 4 justo para quienes más usaron la plataforma.
La llave `fk_post_cons` (postulaciones → consentimientos) no tenía `ON DELETE CASCADE`: MySQL
rechazaba borrar el consentimiento mientras la postulación lo apuntaba. Lo mismo hacía fallar las
instrucciones de limpieza de `datos_prueba.sql` si alguien se postuló a una oferta de ejemplo.
*Corrección:* `eliminar_cuenta()` borra las postulaciones primero, dentro de una transacción (anda
**sin** migrar la base), y `sql/migracion_001.sql` arregla la llave en la base que ya existe.

### Altos

**A1 · Retirar una postulación no revocaba nada.** (regla 6)
Después de que la persona se retiraba, el panel seguía mostrando "Descargar su currículum" y
`admin/archivo_cv.php` lo entregaba: solo comprobaba que *existiera* un consentimiento.
*Corrección:* la descarga se niega y queda en la bitácora; el panel ya no muestra sus datos; el
texto que ve la persona dice exactamente qué se corta y qué no se puede deshacer.

**A2 · Una oferta seguía pública con "Origen verificado" aunque a su reclutador le vencieran o
suspendieran la autorización.**
D-020 bloquea *verificar*, pero nada lo volvía a mirar después. La oferta seguía mostrando el sello
y el número de registro del Ministerio.
*Corrección (D-045):* la condición pública exige reclutador vigente (estado **y** fecha), igual que
D-018 hizo con el vencimiento. El panel avisa qué ofertas dejaron de verse y por qué.

**A3 · Se podía dejar una oferta "verificada" sin la lista de comprobaciones.**
La pantalla escondía el botón, pero `admin/ofertas.php` aceptaba `nuevo_estado=verificada` si el
envío se armaba a mano: la oferta quedaba verificada sin comprobaciones y sin nadie que respondiera
por ella. Es exactamente lo que la regla 5 advierte.
*Corrección:* el servidor lo rechaza y manda a la lista de comprobaciones.

**A4 · El bloqueo del panel se evitaba entrando por el formulario público.**
Las cuentas administrativas también entran por `/cuenta/entrar.php`, que tiene su propio contador
(5 intentos / 15 min en vez de 3 / 30) y no dejaba los fallos en la bitácora.
*Corrección:* ese formulario respeta los dos bloqueos y registra el fallo, sin revelar en pantalla
que el correo es de una cuenta administrativa.

**A5 · Cambiar o restablecer la contraseña no cerraba las otras sesiones.**
El comentario decía que sí, pero `session_regenerate_id()` solo renueva la sesión propia. La
sesión olvidada en un café internet seguía viva hasta 8 horas.
*Corrección (D-047):* la sesión guarda una huella del hash de la contraseña; si cambió desde
cualquier lado, se cierra con el aviso "La contraseña de esta cuenta cambió". Sin columnas nuevas.

### Medios

| | Qué pasaba | Corrección |
|---|---|---|
| M1 | Un `.docx` de 600 KB que se descomprime en 600 MB ("bomba ZIP") agotaba la memoria. Como la pantalla de confirmación relee el archivo (D-023), la cuenta quedaba trabada en un error | Tope de 5 MB de texto (`CV_TEXTO_MAXIMO_BYTES`), mirando el tamaño declarado **y** cortando la lectura |
| M2 | Un texto largo en ciudad, pago o dirección daba error 500 al guardar una oferta o importar un CSV | Validación de largo con mensaje en el campo |
| M3 | El panel contaba como "visibles en el sitio" ofertas vencidas, y decía que el emparejamiento y el verificador "se construyen en las siguientes etapas" | Se cuenta con la misma condición pública; el texto falso se quitó |
| M4 | La extracción del CV leía "Edad: 32 años" como 32 años de experiencia (o sea, leía la edad: regla 7), y encontraba "bar" en el apellido Barrios | Se ignora el número pegado a "edad"; las palabras se buscan enteras |
| M5 | El hash de relleno del login tenía costo fijo 10. Con PHP 8.4 (costo 12) un correo inexistente responde 4 veces más rápido y se puede averiguar quién tiene cuenta. **Latente**: el servidor tiene 8.3.19 | `hash_de_relleno()` usa el costo de la versión de PHP instalada |
| M6 | `revision_seguridad.py` daba `[ok]` a la cascada si `ON DELETE CASCADE` aparecía en *cualquier* parte, y si faltaba no decía nada | Revisa cada llave por nombre y marca `[X]` si falta. Probado contra el esquema viejo: detecta C2 |
| M7 | El navegador guarda el CSS 7 días: después de cada cambio, quien ya había entrado vería el estilo viejo una semana | `recurso()` agrega `?v=fecha del archivo`; el JS también se guarda ahora |

### Bajos

- `oferta_editar.php` buscaba la oferta antes de pedir sesión: un visitante sin cuenta podía saber
  qué números de oferta existen, incluidas las no publicadas.
- Los avisos "cerramos tu sesión por inactividad" quedaban tapados por "Necesitás entrar".
- "Qué guardamos de vos" no mencionaba postulaciones ni reportes, y `cv.php` no decía la excepción
  de D-031. En un país sin ley de datos, esa lista es la garantía: tiene que estar completa.
- Varios comentarios y el README decían que `app/` está *fuera* de `htdocs`. Después de D-044 es
  falso, y quien lo crea podría borrar un `.htaccess` pensando que sobra.
- El verde se usaba en los avisos de "listo" y en "te aparece porque", contra D-015.
- Sin ícono propio, cada visita pedía `/favicon.ico`, caía en `404.php` y gastaba una petición
  de PHP del tope diario.

### Lo que se comprobó que está bien

Para que no quede la idea de que todo estaba mal: las consultas preparadas, el CSRF automático,
el escape de la salida, la CSP, la sesión, el currículum propio sin parámetro (D-026), la
transacción de consentimiento + postulación, el bloqueo por reclutador vencido al verificar y la
lista de comprobaciones funcionan como dice el `CLAUDE.md`.

Y lo más importante de D-044: **en Apache, cada uno de los tres `.htaccess` protege los
currículums por sí solo.** Se probó quitándolos de a uno; recién sin ninguno el archivo se
descarga. Los intentos de evasión (`/APP/`, `/%61pp/`, `/recursos/../app/`) también rebotan.
Falta confirmarlo en InfinityFree (sección 6).

---

## 4. Sin corregir: necesitan una decisión tuya

Son caminos con dos opciones razonables, así que no se tocaron (sección 8 del `CLAUDE.md`).

**D1 · Los límites "por conexión" y la gente que comparte IP.** ⚠️ *El más urgente.*
`REGISTRO_MAX_POR_IP = 3` por día. En las pruebas, **la cuarta cuenta se rechazó**. En la vida
real comparten IP: un café internet, una familia con el mismo wifi y, sobre todo, las operadoras
móviles de Guatemala, que ponen a cientos de clientes detrás de una misma IP (CGNAT). Y si
InfinityFree le muestra a PHP la IP de su propio proxy, **el límite es para todo el sitio junto**:
tres cuentas al día y se cierra el registro. Lo mismo con el verificador (60 consultas por hora).
*Primero:* abrir `diagnostico.php` (agregué la pregunta 5, con instrucciones) desde dos conexiones.
*Después, elegir:* subir los números (por ejemplo, 20 cuentas por día), o contar por sesión además
de por IP. Es un cambio de una línea en `limites.php`.

**D2 · Cualquiera puede bloquear la cuenta de otra persona.** Basta con equivocarse 5 veces con
su correo (15 minutos), o 3 con el de un administrador (30 minutos), y repetirlo. Es el costo
conocido del bloqueo por correo. Alternativa: contar por correo **y** conexión juntos, o hacer
esperar unos segundos cada vez más en lugar de bloquear.

**D3 · Los idiomas mayas en el perfil.** La regla 7 prohíbe recolectar el idioma materno, pero el
perfil pregunta "qué idiomas hablás" con K'iche', Q'eqchi', Mam y Kaqchikel en la lista. Para el
emparejamiento sirve poco (casi ninguna oferta los pide) y en la base es un dato que delata origen
étnico, en un país donde eso se usa para discriminar. Es una discusión para el curso: ¿se quitan,
se dejan porque la persona los quiere mostrar, o se dejan pero sin que entren al emparejamiento?

**D4 · El respaldo `.sql` va sin cifrar.** Tiene todos los correos, los hashes de las contraseñas
y las IPs de la tabla de intentos, y queda en la computadora de quien lo bajó. Se podría cifrar con
una frase al descargarlo (`openssl_encrypt`, que viene con PHP) y no incluir `intentos_acceso`.

**D5 · Los teléfonos de denuncia (D-036 sigue PENDIENTE).** Ahora se pueden tocar para llamar,
lo que los vuelve más útiles y también aumenta el daño si alguno está mal. Hay que verificarlos
antes de publicar el sitio, como ya dice D-036.

**D6 · El panel muestra el perfil *actual* de quien se postuló.** Si la persona cambia su perfil
después de postularse, el panel ve los datos nuevos y no los que autorizó compartir. Para el
archivo ya se resolvió (se guarda el nombre exacto); para el perfil habría que guardar una copia en
el consentimiento. ¿Vale la pena?

---

## 5. Qué le hace falta (ideas, en orden de prioridad)

### Antes de abrir al público

1. **Probar en el servidor real.** Lo único que no se puede hacer desde acá. Pasos en la sección 6.
2. **Correr `sql/migracion_001.sql`** si la base del servidor se creó antes del 2026-10-01.
3. **Pruebas automáticas que ejecuten el código, en GitHub Actions.** Es gratis, corre PHP y
   MySQL sin instalar nada en tu computadora, y se dispara solo en cada `git push`. Las pruebas
   que se hicieron a mano en esta auditoría (registrarse, postularse, buscar, borrar la cuenta)
   habrían detectado C1 y C2 el mismo día en que se escribieron. Es lo que más reduce el riesgo
   por hora invertida.
4. **Verificación en dos pasos para las cuentas del panel.** Las cuentas administrativas son las
   que ponen el sello de "verificada": robarle la contraseña a una es poder publicar una estafa con
   nuestro sello. Un código de 6 dígitos de una app (TOTP, el de Google Authenticator) se calcula
   con `hash_hmac` de PHP en unas 60 líneas, sin servicio externo ni correo.
5. **Re-verificación periódica.** Hoy una oferta verificada hace 50 días muestra esa fecha y nada
   más. Una cola en el panel de "ofertas verificadas hace más de N días" y un aviso cuando el
   registro de reclutadores no se actualiza hace más de un mes. Sin cron: se calcula al abrir el
   panel, como los demás avisos.

### Para proteger mejor

6. **Importar el registro de reclutadores por CSV**, igual que las ofertas. Hoy se carga a mano, y
   un error de tipeo produce un "no aparece en el registro" falso en el verificador, que es la
   función más delicada.
7. **Verificar por número de teléfono**, si el registro del Ministerio publica los teléfonos de
   los reclutadores. Muchas estafas llegan como un número de WhatsApp sin nombre de empresa.
8. **Cerrar el ciclo de los reportes:** que la persona vea en su panel "tu reporte fue revisado".
   Sin detalles; solo que alguien lo miró. Hoy reporta y no sabe nunca nada más.
9. **La página de alertas disponible sin conexión.** Un *service worker* (un archivo de
   JavaScript, permitido por la CSP) puede guardar solo `/alertas.php` y el CSS: quien tiene datos
   contados la vuelve a leer sin gastar nada.
10. **Probarlo con la población real y en su lengua.** Cinco personas del occidente del país, cada
    una con su propio teléfono, intentando tres tareas (verificar un nombre, encontrar una oferta,
    crear su cuenta) sin ayuda. Enseña más que cualquier revisión. Y las señales de alerta en
    K'iche' y Mam, traducidas por personas de la comunidad, no por un traductor automático.

### Para el traspaso a la institución

11. **Pantalla de rubros** (D-022, pendiente desde la Fase 2).
12. **"Descargar mis datos"**: todo lo que la plataforma tiene de la persona en una hoja
    imprimible. Complementa "Qué guardamos de vos" y "Borrar mi cuenta".
13. **Limpieza de cuentas inactivas**: un botón en Mantenimiento (D-037) para las que no entran
    hace más de dos años, con aviso previo en el panel de la persona.
14. **Indicadores de impacto sin vigilar a nadie**: consultas al verificador por semana (ya se
    registran, sin el texto buscado: D-034), ofertas publicadas y reportes resueltos. Le sirven al
    informe del curso y a la institución para pedir fondos, sin agregar ningún rastreo.

### Lo que conviene NO hacer, aunque lo pidan

- **Google Analytics o cualquier contador externo**: le cuenta a un tercero quién busca trabajo
  afuera, y la CSP lo bloquea por buena razón.
- **Buscar mientras se escribe, letra por letra**: multiplica las peticiones contra el tope de
  30 000 diarias. La búsqueda nueva actualiza los resultados en el lugar, pero solo al apretar
  Enter o cambiar un menú.
- **"Vence en 3 días", contadores o porcentajes de coincidencia**: es el lenguaje de la urgencia
  y la falsa precisión (D-015, D-027).
- **Captcha**: D-038 ya lo explica.

---

## 6. Qué subir y cómo probarlo en el servidor

### 6.1. En phpMyAdmin (una sola vez)

Si la base del servidor se creó **antes del 2026-10-01**: pestaña SQL → pegar el contenido de
`sql/migracion_001.sql` → Continuar.

### 6.2. Por FTP

Subir, respetando las carpetas, todo lo de esta lista. Los de `app/` van **dentro de `htdocs/app/`**
(D-044). Los tres `.htaccess` también cambiaron (solo comentarios, salvo el de la raíz).

```
htdocs/.htaccess                 htdocs/admin/archivo_cv.php      app/.htaccess
htdocs/index.php                 htdocs/admin/importar_csv.php    app/config/limites.php
htdocs/ofertas.php               htdocs/admin/index.php           app/modelos/eliminacion.php
htdocs/oferta.php                htdocs/admin/oferta_editar.php   app/modelos/guardadas.php
htdocs/verificador.php           htdocs/admin/ofertas.php         app/modelos/ofertas.php
htdocs/alertas.php               htdocs/admin/postulaciones.php   app/modelos/postulaciones.php
htdocs/reportar.php              htdocs/admin/respaldo.php        app/modelos/usuarios.php
htdocs/instalar.php *            htdocs/cuenta/archivo_cv.php     app/nucleo/archivos.php
htdocs/diagnostico.php *         htdocs/cuenta/cambiar_contrasena.php  app/nucleo/autorizacion.php
htdocs/recursos/app.js           htdocs/cuenta/cv.php             app/nucleo/bd.php
htdocs/recursos/estilo.css       htdocs/cuenta/entrar.php         app/nucleo/extraccion.php
htdocs/recursos/icono.svg   (nuevo)  htdocs/cuenta/panel.php      app/nucleo/inicio.php
htdocs/recursos/escudo.svg  (nuevo)  htdocs/cuenta/postulaciones.php  app/nucleo/salida.php
                                 htdocs/cuenta/registrarse.php    app/nucleo/sesion.php
                                 htdocs/cuenta/restablecer.php    app/vistas/cabecera.php
                                                                  app/vistas/pie.php
                                                                  app/vistas/tarjeta_oferta.php
                                                                  app/vistas/tarjeta_recomendada.php
```

\* `instalar.php` y `diagnostico.php` **solo** si todavía los necesitás; después se borran.

### 6.3. Comprobar

1. **`diagnostico.php`, pregunta 5**, desde el teléfono con datos y desde otra conexión. Si la IP
   sale igual, ver D1 en la sección 4 **antes** de abrir el sitio. Después, borrarlo.
2. **La prueba de humo**, en tu computadora (solo lectura, no cambia nada):
   ```
   python herramientas/prueba_de_humo.py https://tusitio.infinityfreeapp.com
   ```
   Si contesta que el hosting mostró su página anti-robots, el archivo explica cómo pasarle la
   cookie `__test` del navegador. Tiene que terminar en **"todo en orden"**. Repetila después de
   cada subida: es la prueba de D-044 hecha sola.
3. **A mano, en el teléfono:** buscar "albañil" en Ofertas (antes daba error), crear una cuenta,
   postularse a una oferta, retirar la postulación y **borrar la cuenta** (antes daba error).
4. **La CSP y el anti-robots** (pendiente del `CLAUDE.md`): si alguna página queda en blanco o
   dando vueltas, es eso, no estos cambios.

---

## 7. La interfaz nueva

El pedido era una interfaz más dinámica. Las reglas que se respetaron:

- **Funciona igual sin JavaScript.** Todo lo nuevo es una capa encima de páginas que ya andaban.
  Se probó con JavaScript apagado.
- **Ni un `style=` ni un `onclick=`**: la CSP no se tocó. Cero errores de consola.
- **Sin librerías y en JavaScript "viejo"**, para que un teléfono de gama baja no se quede sin
  nada por un error de sintaxis.
- **Movimiento corto y tranquilo** (D-015), y nada para quien pidió menos movimiento en su
  teléfono.
- **El verde significa una sola cosa**: origen verificado.

Qué cambió:

| Dónde | Qué |
|---|---|
| Todo el sitio | Barra de arriba fija. En el teléfono, pestañas abajo con ícono **y** palabra (el verificador sigue a un toque, sin menú escondido). La sección actual se marca |
| Portada | El buscador del verificador a la vista, sin un clic de más. Cifras reales: ofertas disponibles, reclutadores y fecha del registro. Tres pasos de cómo sirve |
| Ofertas | Resultados que se actualizan sin recargar, con la misma cantidad de peticiones. Fichas para quitar cada filtro. Toda la tarjeta se puede tocar |
| Verificador | El resultado aparece en su lugar y el lector de pantalla lo anuncia |
| Una oferta | Compartir por WhatsApp o copiar el enlace, con aviso de cómo reconocer nuestra dirección |
| Mi cuenta | Camino de tres pasos: qué hiciste y qué sigue. Conteos en cada tarjeta |
| Currículum | Zona grande para elegir el archivo; avisa **antes de subir** si pesa de más |
| Contraseñas | Cuántos caracteres faltan y si la confirmación coincide, mientras se escribe |
| Botones | Muestran que están trabajando y no se envían dos veces con internet lento |
| Alertas | Señales numeradas en ámbar en vez de una pared de cajas rojas; teléfonos tocables |
