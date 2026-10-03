# CLAUDE.md — Mjob for all · Trabajo sin fronteras

**Plataforma de ofertas laborales verificadas para personas migrantes y retornadas.**
El nombre visible es **Mjob for all**, con el lema **Trabajo sin fronteras** (desde el 2026-10-03, D-050).

> Este archivo es la memoria del proyecto. Se lee al inicio de **cada** sesión.
> Si una decisión ya está acá, no se vuelve a discutir: se consulta.
> Se actualiza al cerrar cada fase y cada vez que se toma una decisión no trivial.

---

## 1. Qué es el proyecto y para quién

Cuando una persona guatemalteca busca trabajo en el extranjero, se entera de las oportunidades
por publicaciones de Facebook, cadenas de WhatsApp o un número de teléfono que le pasó un
conocido. No tiene forma de saber si la oferta es real. Hasta agosto de 2025 el Ministerio de
Trabajo había recibido **330 denuncias por estafas con ofertas de trabajo en el extranjero**, y
en el **40 %** de esos casos la persona ya había entregado dinero. La OIM advierte que las ofertas
laborales falsas son una de las formas de captación de trata de personas que más está creciendo.

Al mismo tiempo sí existen ofertas legítimas: el Ministerio de Trabajo tiene el Programa de
Trabajo Temporal y mantiene un **registro público de reclutadores autorizados** (31 a la fecha).
Casi nadie sabe que ese registro existe, y aunque lo encuentre, es un listado de nombres que no
le dice qué oportunidad le sirve a él.

**La plataforma hace esto:** la persona sube su currículum, el sistema extrae su experiencia y le
muestra ofertas que coinciden con lo que sabe hacer, tomadas **únicamente de fuentes verificadas**.
Además, cualquiera —sin cuenta— puede consultar si un reclutador que lo contactó por fuera está en
el registro autorizado, y ver las señales de alerta de una estafa.

> **Cambio de rumbo del 2026-10-03 (D-054).** Las ofertas son de **empleo en Guatemala**, para
> personas que **regresaron** al país (muchas deportadas). El estudiante lo dijo así: "los
> migrantes ya están acá". El verificador de reclutadores y las señales de estafa se quedan,
> porque a quien regresa lo vuelven a buscar con ofertas falsas para irse otra vez. El texto de
> arriba describe el planteamiento original y se deja como historia.

**Población:** personas migrantes guatemaltecas y personas retornadas del occidente del país
(Huehuetenango, San Marcos, Quiché, Quetzaltenango). Muchas entran desde un celular de gama baja,
con internet lento y datos limitados, con poca práctica usando páginas web, y para varias el
español es su segunda lengua.

**Por qué importa que esté bien hecho:** acá un error no es un bug. Puede terminar en que alguien
pierda dinero que no tenía, o que caiga en una red de trata. Además Guatemala **no tiene ley de
protección de datos personales**: no hay una ley que nos obligue a cuidar estos currículums, el
cuidado depende enteramente de cómo esté diseñado el sistema.

**Contexto académico:** curso de Ética Aplicada, Universidad Rafael Landívar. Pero **no es una
tarea desechable**: la intención es entregarlo a una institución que lo siga operando con usuarios
reales después de que termine el curso.

### Orden de prioridad cuando algo entra en conflicto

```
seguridad > privacidad > funcionamiento > mantenibilidad > estética > funciones adicionales
```

### El estándar

- **Muy funcional:** nada de botones que no hacen nada ni secciones "para después". Cinco cosas
  terminadas antes que quince empezadas. Cada flujo cerrado de punta a punta, incluyendo qué pasa
  cuando algo sale mal, cuando no hay resultados y cuando el usuario hace las cosas en desorden.
- **Muy estético:** la calidad visual **es** un mecanismo de confianza. Una página que se ve
  improvisada se ve igual que las páginas falsas de las que estamos protegiendo a la gente, y la
  persona la descarta en tres segundos sin leer nada.
- **Sobre todo seguro:** se diseña desde la primera línea, no se "endurece al final".

---

## 2. El stack y por qué es ese

El stack **no es preferencia técnica: lo impone el hosting.**

El proyecto arranca en **InfinityFree** (hosting gratuito) porque no genera ingresos y tiene que
empezar sin costo. Límites duros ya verificados:

| Límite de InfinityFree | Consecuencia de diseño |
|---|---|
| Solo PHP y MySQL. No hay Node, Python, SSH ni terminal | PHP 8 puro; nada que requiera instalar o compilar en el servidor |
| No hay tareas programadas (cron) | Todo lo periódico se dispara **manualmente desde el panel** |
| Conexiones salientes poco confiables (DNS y restricciones) | **Ninguna función depende de llamar una API desde el servidor** |
| Envío de correo deshabilitado (`mail()` y SMTP bloqueados) | No hay recuperación de contraseña por correo ni avisos por email |
| Límite de 30 000 archivos (inodes) en toda la cuenta | Sin frameworks (miles de archivos); los CV cuentan contra este límite |
| Límite de 30 000 peticiones diarias | Un abuso deja el sitio caído para todos: los límites de uso son función de seguridad |
| HTTPS gratis con Let's Encrypt, sin publicidad inyectada | HTTPS forzado desde `.htaccess` |

**Stack definitivo:**

- **Backend:** PHP 8 puro, sin framework. Nada de Laravel ni Symfony (no hay Composer en el
  servidor y un framework se come el límite de archivos).
- **Base de datos:** MySQL con **PDO**, siempre con consultas preparadas.
- **Frontend:** HTML, CSS y JavaScript vanilla. Sin React, sin Vue, sin Bootstrap.
- **Sesiones y contraseñas:** sesiones nativas de PHP; `password_hash()` y `password_verify()`.
- **Lectura de currículums:**
  - `.docx` — es un ZIP con XML adentro; se lee con `ZipArchive` nativo de PHP, sin instalar nada.
  - `.pdf` — `smalot/pdfparser`, instalado con Composer **en la máquina local** y subido por FTP.
    **Es la única dependencia externa del proyecto.**
- **SQL conservador:** sin CTEs, sin funciones de ventana, sin tipos JSON (la versión de MySQL de
  InfinityFree suele ser vieja). Todo se escribe portable, para poder migrar a un servidor propio
  sin reescribirlo.

**Prohibido en este proyecto:** Redis, Node.js, Python, cron, workers, colas y servicios externos.
Todo se resuelve con PHP y MySQL.

---

## 3. Reglas no negociables

Son invariantes del sistema, no sugerencias. Si una decisión técnica choca con una de estas, se
cambia la decisión técnica, no la regla.

1. **Ninguna oferta sin fuente verificable** — toda oferta muestra fuente, fecha de publicación, fecha de última verificación, empleador o reclutador y estado; si un dato no se puede verificar, la oferta no se muestra.
2. **La palabra "verificada" se gana** — no se usa si la oferta no pasó el proceso de verificación, y ningún elemento visual puede parecer una garantía de empleo.
3. **No se scrapea LinkedIn ni ningún sitio que lo prohíba** — antes de integrar una fuente nueva se verifica que sus términos lo permitan.
4. **Datos mínimos** — ofertas visibles sin registro; nunca se piden DPI, pasaporte, situación migratoria, visa, datos bancarios ni fotografía; borrar la cuenta borra de verdad (base de datos y archivo en disco).
5. **Toda autorización se comprueba en el servidor** — ocultar un botón no es control de acceso; cada acción verifica rol y permiso del lado del servidor, siempre.
6. **El consentimiento es por oferta y no se reutiliza** — cada postulación pide su propia confirmación explícita y queda registrada.
7. **El emparejamiento no puede discriminar** — solo puede usar oficio o rubro, años de experiencia, estudios, idiomas, ubicación y disponibilidad; edad, sexo, departamento de origen, idioma materno y apellido **ni siquiera se recolectan**.
8. **El emparejamiento tiene que ser explicable** — razones concretas en palabras, y discrepancias cuando sirvan; nunca solo un porcentaje.
9. **La máquina propone, la persona decide** — la persona revisa y corrige lo que el sistema entendió **antes** de ver cualquier oferta.
10. **Nada de puertas traseras** — sin contraseñas maestras, usuarios secretos, credenciales escritas en el código ni accesos "solo para pruebas".
11. **La caída de un servicio externo no puede tumbar la plataforma** — el sitio sigue con los datos locales, el administrador recibe un aviso claro y el usuario final nunca ve un error técnico.
12. **No prometer lo que no se cumple** — la plataforma no consigue trabajo, no gestiona trámites, no mueve dinero y no representa legalmente a nadie.

---

## 4. Estado actual

**Fase actual: 3 — Cuentas, currículum y perfil.**
**Situación: Fases 1, 2 y 3 escritas y revisadas en local el 2026-09-03. Ninguna se ha probado
todavía en el servidor real. Las pruebas del servidor siguen pendientes y son la condición para
dar las tres fases por cerradas.**

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Cimientos, roles y primitivas de seguridad | Escrita, falta probar en el servidor |
| 2 | Ofertas, estados y panel de administración | Escrita, falta probar en el servidor |
| 3 | Cuentas, currículum y perfil | Escrita, falta probar en el servidor |
| 4 | Emparejamiento, postulación y consentimiento | Escrita, falta probar en el servidor |
| 5 | Prevención de estafas y reportes | Escrita, falta probar en el servidor |
| 6 | Abuso, auditoría, resiliencia y retención | Escrita, falta probar en el servidor |
| 7 | Revisión de seguridad y puesta en producción | Revisión hecha; falta instalar y probar |

**Las siete fases están escritas.** La revisión de seguridad de los 14 puntos pasa
(`herramientas/revision_seguridad.py`). Lo que queda es instalar en el servidor y probar,
que es lo único que no se puede hacer desde la computadora.

**Auditoría del 2026-10-01** (detalle en [AUDITORIA.md](AUDITORIA.md)): se ejecutó todo el sitio
contra MariaDB, PHP 8.3 y Apache. Aparecieron dos fallas críticas que la revisión de los 14
puntos no veía (el buscador daba error 500 al escribir, y quien se había postulado no podía
borrar su cuenta), cinco altas y varias medias. Todas corregidas. La interfaz se volvió más
dinámica sin tocar la CSP ni el funcionamiento sin JavaScript (D-048). Quedan seis puntos que
necesitan una decisión del equipo: sección 4 de `AUDITORIA.md`.

**Rediseño del 2026-10-03** (D-050 a D-053): el sitio pasa a llamarse **Mjob for all · Trabajo
sin fronteras**, con el logo nuevo redibujado en vector; colores sacados del logo; **modo noche**;
portada con dos "puertas" (busco trabajo / me ofrecieron un trabajo); "Inicio" en la barra de
abajo del teléfono; íconos con palabra en toda la interfaz; "Mi cuenta" con accesos que se tocan
enteros. Se probó **ejecutando** en una copia local con XAMPP (MariaDB 10.4, PHP 8.4): teléfono
y computadora, claro y oscuro, persona usuaria y administración, sin errores en la consola ni
choques con la CSP, y la prueba de humo pasa las páginas y cabeceras. En el servidor real todavía
no se subió: hay que subir los archivos y agregar `SITIO_LEMA` al `config.php` del servidor.

**Empleo en Guatemala, mismo día** (D-054 a D-058): las ofertas pasan a ser de empleo en
Guatemala para personas retornadas; solo de fuentes oficiales; departamento en cada oferta y
filtro por departamento; postulación en la página de la empresa cuando la oferta se tomó de ahí;
y un **primer lote de 6 vacantes reales** (Allied Global e IntouchCX) comprobadas el 2026-10-03
en sus páginas oficiales, en `sql/ofertas_reales_2026-10-03.sql`, que **vencen solas el
2026-11-02**. Se probó ejecutando: migración sobre la base vieja y esquema nuevo desde cero, el
lote corrido dos veces sin duplicar, el filtro, la página de la oferta, el bloqueo de la
postulación interna, el formulario del panel y la importación por CSV con el formato nuevo.

**Lo que esto NO resuelve, dicho claro:** no existe una fuente legal y automática de vacantes en
Guatemala (ver la sección 6). Mientras no haya un convenio (`documentos/carta_convenio.md`), las
ofertas nuevas las tiene que buscar y comprobar una persona, y el lote actual se vence el
2026-11-02.

### Pendientes de verificar en el servidor real (bloquean decisiones)

- [ ] Versión de **MySQL / MariaDB** que reporta el panel de InfinityFree.
- [ ] Versión de **PHP** y extensiones disponibles: `pdo_mysql`, `zip` (ZipArchive), `fileinfo` (finfo), `mbstring`, `openssl`.
- [ ] `upload_max_filesize` y `post_max_size` reales.
- [ ] Que PHP pueda **leer y escribir fuera de `htdocs`** (que `open_basedir` no lo impida).
      De esto depende dónde se guardan los currículums.
- [ ] Que la **CSP no choque con el sistema anti-robots de InfinityFree**. InfinityFree inyecta
      JavaScript en la respuesta para poner una cookie de verificación. Nuestra cabecera
      `script-src 'self'` bloquea el JavaScript escrito dentro del HTML. Si al abrir el sitio la
      página queda dando vueltas o sale en blanco, es esto: se resuelve ajustando la línea de la
      CSP en `app/nucleo/inicio.php`. **No cambiar nada antes de comprobarlo.**

- [ ] **Qué IP ve PHP de cada visita** (pregunta 5 de `diagnostico.php`). Si es la del proxy del
      hosting, los límites "por conexión" son para todo el sitio junto: con
      `REGISTRO_MAX_POR_IP = 3` se cierra el registro para todos a la cuarta cuenta del día.
      En la auditoría se comprobó que la cuarta se rechaza. Ver D1 en `AUDITORIA.md`.
- [ ] Correr `sql/migracion_001.sql` si la base del servidor es anterior al 2026-10-01.
- [ ] Correr `sql/migracion_002.sql` (departamento, forma de postularse y el oficio "Atención al
      cliente y call center") si la base es anterior al 2026-10-03.
- [ ] Importar `sql/ofertas_reales_2026-10-03.sql` **antes del 2026-11-02** (después no carga
      nada, a propósito). Y no importar `datos_prueba.sql` en el servidor: sus ofertas de EJEMPLO
      son en el extranjero y confundirían ahora que el sitio es de empleo en Guatemala.
- [ ] Mandar la carta de `documentos/carta_convenio.md` al Ministerio de Trabajo y a la OIM.
- [x] ~~Para la demostración: importar `sql/datos_prueba.sql`~~ **Superado el mismo día** por el
      lote de ofertas reales (abajo). El archivo sigue sirviendo para probar en una copia local
      (se puede correr más de una vez: borra lo de EJEMPLO y lo vuelve a cargar). Si ya se importó
      en el servidor, se borra con las tres líneas del final del archivo.
- [ ] `python herramientas/prueba_de_humo.py https://...` termina en "todo en orden".

Las cuatro primeras y la de la IP las contesta `htdocs/diagnostico.php`, que se sube, se lee una
vez y se borra. La prueba de humo se repite después de cada subida por FTP.

### Lo que no se va a poder cumplir en InfinityFree

- **Usuario de MySQL con permisos mínimos.** InfinityFree entrega un solo usuario con todos los
  privilegios sobre la base y no deja crear otros. La lista de seguridad pide lo contrario. Queda
  anotado para el día de la migración a servidor propio, donde se resuelve en dos minutos.

---

## 5. Registro de decisiones

Formato: qué se decidió, por qué, y qué alternativas se descartaron con su razón.
**Las entradas viejas no se borran.** Si una queda superada se marca como tal y se anota por qué
cambió: el historial de por qué algo cambió vale tanto como la decisión actual.

---

### D-001 · 2026-09-01 · No se usa LinkedIn como fuente de ofertas · VIGENTE

- **Decisión:** LinkedIn queda excluido como fuente de datos, en cualquier forma.
- **Por qué:** su contrato de usuario, sección 8.2, prohíbe expresamente usar programas
  automáticos para copiar datos del sitio, y su API oficial no da acceso público a ofertas de
  empleo. Sería incoherente construir algo que protege a migrantes de prácticas irregulares
  usando una práctica irregular para conseguir los datos.
- **Alternativas descartadas:** scraping con navegador automatizado (viola los términos igual);
  API oficial (no expone ofertas públicas).
- **Nota:** el descarte es **ético, no técnico**. Que se pueda hacer no significa que se haga.

### D-002 · 2026-09-01 · PHP y no Node.js · VIGENTE

- **Decisión:** todo el backend en PHP 8 puro.
- **Por qué:** InfinityFree no tiene runtime de Node. **No es preferencia de lenguaje.**
- **Alternativas descartadas:** Node/Express (no hay runtime); Python/Flask (tampoco).

### D-003 · 2026-09-01 · Sin cron ni procesos en segundo plano · VIGENTE

- **Decisión:** nada del sistema depende de un proceso corriendo solo. Todo lo periódico
  (vencimiento de ofertas, limpieza, respaldos, importaciones) se dispara manualmente desde el
  panel o al ingresar el administrador.
- **Por qué:** el hosting no ofrece tareas programadas.
- **Alternativas descartadas:** cron externo (tipo cron-job.org) llamando una URL: depende de un
  tercero, expone un endpoint disparable desde fuera y agrega un secreto más que proteger.

### D-004 · 2026-09-01 · Las ofertas externas entran por CSV generado localmente · VIGENTE

- **Decisión:** las fuentes externas (tipo Adzuna) se consultan con un script que se corre **en la
  computadora local**, que genera un CSV; ese CSV se sube por el panel de administración. El
  servidor nunca llama una API externa.
- **Por qué:** las conexiones salientes de InfinityFree fallan seguido por DNS y restricciones del
  hosting. Una función que depende de ellas no va a correr en el servidor real.
- **Alternativas descartadas:** llamar la API desde PHP con cURL (no confiable); un proxy
  intermedio (agrega infraestructura y un punto más de falla).
- **Ventaja adicional:** el día que se migre a servidor propio, ese mismo script se automatiza sin
  reescribirlo.

### D-005 · 2026-09-01 · No hay recuperación de contraseña por correo · VIGENTE

- **Decisión:** no existe "olvidé mi contraseña" por email. Se resuelve con **restablecimiento
  asistido por el administrador**: código de un solo uso, con expiración, guardado con hash
  (nunca en texto plano) e invalidado apenas se usa.
- **Por qué:** `mail()` está deshabilitado y el SMTP saliente está bloqueado en el hosting.
- **Alternativas descartadas:** SMTP externo tipo SendGrid (puerto bloqueado y depende de conexión
  saliente); preguntas de seguridad (débiles y recolectan datos personales de más).

### D-006 · 2026-09-01 · La verificación del empleador es el centro del proyecto · VIGENTE

- **Decisión:** la verificación del origen de cada oferta y el registro de reclutadores
  autorizados no son un detalle del panel: son la función principal del sistema.
- **Por qué:** sin verificación, la plataforma se convierte en un canal más de ofertas falsas, con
  el agravante de que se vería confiable.
- **Consecuencia:** ninguna función nueva puede debilitar el proceso de verificación por comodidad
  o por velocidad de carga de ofertas.

### D-007 · 2026-09-03 · Una sola tabla `usuarios` con columna de rol · VIGENTE

- **Decisión:** las tres clases de cuenta (usuario, administrador, superadministrador) viven en la
  misma tabla `usuarios`, distinguidas por `rol_id`.
- **Por qué:** con tablas separadas habría dos funciones de login, dos bloqueos por intentos y dos
  flujos de contraseña, que se desincronizan con el tiempo; el hueco aparece en la copia que nadie
  actualizó. El riesgo típico de una sola tabla —que alguien se ascienda solo— no aplica acá:
  en PHP puro no hay asignación masiva de campos, cada `INSERT` se escribe a mano, el registro
  público pone `ROL_USUARIO` fijo en el código y el único lugar que escribe `rol_id` es la
  pantalla del superadministrador.
- **Alternativas descartadas:** tabla `administradores` aparte (más aislamiento, pero duplica todo
  el mecanismo de cuentas).

### D-008 · 2026-09-03 · "Ubicación" es a dónde quiere ir, no de dónde viene · VIGENTE

- **Decisión:** del lugar de la persona se guarda únicamente `perfiles_paises`, los países donde
  está dispuesta a trabajar. Su municipio y su departamento de origen **no se piden en ninguna
  pantalla ni existen como columna**.
- **Por qué:** la regla 7 permite usar "ubicación" y prohíbe "departamento de origen". Guardando
  solo el destino, la regla deja de depender de que el algoritmo se porte bien: el dato con el que
  se podría discriminar no existe en la base.
- **Consecuencia:** tampoco hay campo de apellido. El campo `nombre` es libre y de un solo trozo;
  sirve para saludar y para el currículum que genera la Fase 3, y nunca entra al emparejamiento.

### D-009 · 2026-09-03 · Todas las fechas las genera PHP, nunca `NOW()` de MySQL · VIGENTE

- **Decisión:** ninguna consulta usa `NOW()` ni `DEFAULT CURRENT_TIMESTAMP`. Toda fecha sale de
  `ahora()` en `app/nucleo/salida.php`, con la zona horaria fijada una sola vez.
- **Por qué:** el servidor de base de datos del hosting puede estar en otra zona horaria que PHP.
  Con dos relojes, un consentimiento puede quedar fechado horas antes de que la persona lo diera,
  y ese registro es justamente la prueba de la regla 6.

### D-010 · 2026-09-03 · Los estados son `VARCHAR` validados en PHP, no `ENUM` · VIGENTE

- **Decisión:** `ofertas.estado`, `reportes.estado` y demás son texto, validados contra los
  catálogos de `app/config/catalogos.php`.
- **Por qué:** falla del lado seguro. La consulta pública filtra por `estado = 'publicada'`, así
  que un valor mal escrito hace que la oferta **no** aparezca, en vez de aparecer de más. Y agregar
  un estado no obliga a un `ALTER TABLE` en el phpMyAdmin lento del hosting.

### D-011 · 2026-09-03 · El token CSRF se valida solo, en toda petición POST · VIGENTE

- **Decisión:** `app/nucleo/inicio.php` valida el token en **cada** POST del sitio. Ninguna página
  lo comprueba por su cuenta.
- **Por qué:** el agujero clásico no es equivocarse en la comprobación, es olvidarla en la página
  nueva que se agregó tres semanas después. Así, un formulario nuevo de la Fase 5 ya nace
  protegido; lo único que hay que recordar es poner `campo_csrf()` dentro del formulario.

### D-012 · 2026-09-03 · La primera cuenta se crea con `instalar.php` + clave por FTP · VIGENTE

- **Decisión:** no hay cuenta de fábrica. `htdocs/instalar.php` crea el primer
  superadministrador, y para funcionar exige que exista `app/config/instalacion.txt` (fuera de la
  carpeta pública) y que se escriba su contenido exacto en el formulario. Después se borra por FTP.
- **Por qué:** la regla 10 prohíbe credenciales en el código, pero un instalador sin llave deja una
  ventana peligrosa entre que se suben los archivos y se crea la cuenta: los robots prueban
  `/instalar.php` todo el tiempo. Pedir un archivo que solo puede crear quien tiene el FTP cierra
  esa ventana. Además el instalador se niega a correr si ya existe una cuenta administrativa.
- **Alternativas descartadas:** un script local que genere el `INSERT` (es más seguro todavía, pero
  requiere PHP instalado en la computadora y hoy no lo hay); un instalador sin llave (ventana
  abierta); dejar un usuario y contraseña por defecto (viola la regla 10).

### D-013 · 2026-09-03 · Registro e inicio de sesión se adelantan de la Fase 3 a la Fase 1 · VIGENTE

- **Decisión:** crear cuenta, entrar, salir y cambiar contraseña se construyeron en la Fase 1.
- **Por qué:** el criterio de cierre de la Fase 1 es "puedo iniciar sesión con cada rol". Sin el
  registro, la única forma de tener una cuenta de usuario para probarlo sería insertarla a mano en
  phpMyAdmin, que es exactamente "dar la fase por terminada con algo a medias".
- **Consecuencia:** la Fase 3 ya no construye cuentas; construye currículum y perfil encima de esto.

### D-014 · 2026-09-03 · Los permisos se leen de la base en cada petición · VIGENTE

- **Decisión:** el rol y los permisos no se guardan en la sesión; se consultan en cada carga de
  página (una consulta, cacheada dentro de la misma petición).
- **Por qué:** si estuvieran en la sesión, desactivar a un administrador no tendría efecto hasta
  que a esa persona se le ocurriera salir. Cuesta una consulta y compra que las bajas sean
  inmediatas.

### D-015 · 2026-09-03 · Dirección visual: "institucional cálido" · PARCIALMENTE SUPERADA POR D-050

> **Nota del 2026-10-03:** la paleta (papel cálido, azul `#12496B`) se reemplazó por la del logo
> de Mjob (D-050). Lo demás sigue vigente sin cambios: verde solo para "origen verificado", cero
> urgencia, tipografías del sistema, cuerpo de 17px, botones de 48px, todo desde variables.

- **Decisión:** papel claro cálido, azul profundo de documento oficial, verde reservado
  **únicamente** para el sello de verificación, tipografías del sistema, cuerpo de 17px, botones de
  48px de alto. Todo sale de las variables CSS al inicio de `htdocs/recursos/estilo.css`.
- **Por qué:** la página tiene que verse creíble para alguien que ya fue engañado antes. El
  lenguaje visual de la estafa es el contrario: colores encendidos, urgencia, promesas. Se evita a
  propósito. El verde se reserva porque si se usa de adorno deja de significar algo, y es el
  elemento que más peso carga en toda la interfaz.
- **Consecuencia:** cambiar la identidad del proyecto es cambiar un bloque de variables, no
  repasar las pantallas una por una.

### D-016 · 2026-09-03 · Los CV iban fuera de `htdocs` · SUPERADA POR D-044

- **Decisión provisional que se tomó:** guardar los currículums en `app/almacen/cv/`, fuera de la
  carpeta pública, servidos solo por un script que comprueba sesión y permiso.
- **Por qué era lo mejor:** es la única forma de que un currículum no se pueda descargar
  escribiendo su dirección, sin depender de ninguna configuración de Apache.
- **Por qué se cayó:** el diagnóstico en el servidor real (2026-09-09) mostró que InfinityFree
  encierra a PHP dentro de `htdocs` con `open_basedir`. Ver D-044.

### D-044 · 2026-09-09 · `app/` y `vendor/` van DENTRO de `htdocs`, protegidas por `.htaccess` · VIGENTE

- **Qué obligó al cambio:** el `open_basedir` del servidor termina en `/htdocs`. PHP no puede leer
  ni un archivo fuera de esa carpeta. Con `app/` afuera, **ninguna página del sitio cargaba**:
  el `require` de `inicio.php` fallaba y todo daba error 500.

  ```
  open_basedir = ... :/home/vol18_1/infinityfree.com/if0_42870094/htdocs
  ```

- **Decisión:** `app/` y `vendor/` se mueven dentro de `htdocs`, y la protección pasa a ser el
  `.htaccess`. Los `require` de las 41 páginas pierden un nivel (`/../app/` → `/app/`).

- **Esto es peor que el diseño original y hay que decirlo claro.** Antes, la protección era
  estructural: no existía dirección web que llegara a un currículum. Ahora depende de que Apache
  lea un archivo de configuración. Es la diferencia entre "no se puede" y "está prohibido".

- **Qué se hizo para compensar,** tres barreras independientes:
  1. `htdocs/.htaccess` corta las rutas `/app/` y `/vendor/` con `[F]`, antes que cualquier otra
     regla, incluso antes del redirect a HTTPS.
  2. `app/.htaccess` y `vendor/.htaccess` niegan todo, con `FilesMatch ".*"` por si los módulos
     de autorización no estuvieran.
  3. `app/almacen/.htaccess` vuelve a negar todo, redundante a propósito: si alguien borrara el
     de `app/`, este sigue en pie.

  Más lo que ya existía: los nombres de archivo son 32 caracteres al azar, así que ni sabiendo
  que la protección falló se puede adivinar un currículum.

- **Cómo se comprueba que sigue funcionando:** abrir `/app/config/config.php` en el navegador.
  Tiene que dar 403 o 404. `htdocs/diagnostico.php` avisa si falta alguno de los `.htaccess`, pero
  esa prueba en el navegador es la única que confirma el resultado de verdad. **Hay que repetirla
  después de cada cambio en los `.htaccess`.**

- **Qué se descartó:**
  - *Guardar los currículums en la base de datos como BLOB:* los sacaría del sistema de archivos
    y sería más seguro contra este riesgo, pero el respaldo de la base pasaría a pesar cientos de
    megas y en este hosting se cortaría a la mitad, dejando al proyecto sin respaldo utilizable.
    Se cambiaría un riesgo por otro peor.
  - *Usar `/home/uploads`, que sí está en el `open_basedir`:* es una carpeta del sistema
    compartida con otras cuentas del hosting. Poner ahí currículums de personas migrantes sería
    peor que la solución actual.

- **Lo primero que hay que revertir al migrar a servidor propio.** Está anotado en `TRASPASO.md`
  como prioridad. En un servidor propio, `app/` vuelve afuera y la protección deja de depender de
  un archivo de configuración.

### D-017 · 2026-09-03 · Verificar no es un botón: es una lista de comprobaciones · VIGENTE

- **Decisión:** para marcar una oferta como verificada hay que marcar, una por una, cinco
  comprobaciones (seis si hay reclutador de por medio). Todas son obligatorias, y cuáles se
  marcaron queda escrito en la bitácora con el nombre de quien las marcó.
- **Por qué:** la regla 2 dice que la palabra "verificada" se gana. Si verificar fuera un botón en
  una lista, en tres meses sería un clic de trámite y la palabra dejaría de significar algo — y esa
  palabra es lo único que esta plataforma le ofrece a alguien que ya fue estafado antes.
- **Alternativas descartadas:** un botón "verificar" con confirmación (no obliga a mirar nada);
  un campo de notas libre (nadie lo llena, y no se puede exigir).

### D-018 · 2026-09-03 · La fecha de vencimiento es obligatoria para publicar · VIGENTE

- **Decisión:** una oferta no se puede publicar sin fecha de vencimiento, y la consulta pública
  exige que esa fecha no haya pasado.
- **Por qué:** sin fecha, una oferta se quedaría publicada para siempre. Y como el hosting no
  tiene tareas programadas (D-003), no hay nadie que la baje. Poniendo la condición en la consulta,
  **una oferta vencida deja de verse sola**, aunque nadie entre al panel en seis meses.
- **Consecuencia:** el botón de mantenimiento que marca las vencidas es para que el panel diga la
  verdad, no para proteger a la persona. Esa protección ya está en la consulta.

### D-019 · 2026-09-03 · Editar una oferta verificada le quita la verificación · VIGENTE

- **Decisión:** guardar cambios en una oferta que estaba verificada o publicada la devuelve a
  'pendiente' y borra quién la verificó.
- **Por qué:** si cambió el empleador, la fuente o el vencimiento, lo que alguien comprobó antes
  ya no es lo que dice la oferta ahora. Sin esto, "verificada" se convertiría en una etiqueta que
  alguien puso una vez y que después dejó de ser cierta sin que nadie se enterara.

### D-020 · 2026-09-03 · Un reclutador sin autorización vigente bloquea la verificación · VIGENTE

- **Decisión:** si una oferta viene por un reclutador cuya autorización está vencida o suspendida,
  el sistema no deja verificarla. No es una advertencia: es un bloqueo.
- **Por qué:** verificar significa que comprobamos el origen. Si el reclutador no está autorizado,
  lo que comprobamos es justamente lo contrario.
- **Detalle:** la vigencia se comprueba con el estado **y** con la fecha. El estado lo escribe una
  persona y se puede quedar viejo; la fecha no se equivoca.

### D-021 · 2026-09-03 · Todo lo que se importa entra como 'pendiente' · VIGENTE

- **Decisión:** las ofertas importadas por CSV entran siempre en 'pendiente'. No existe ningún
  camino en el sistema para crear una oferta ya verificada o ya publicada.
- **Por qué:** si lo hubiera, bastaría con subir un archivo para publicar cualquier cosa con el
  sello de verificada. El CSV es una comodidad para cargar; no es una fuente de confianza.
- **Detalle:** el archivo se lee y se descarta, nunca se guarda en el servidor. Un archivo menos
  que cuidar y que cuente contra el límite de archivos del hosting.

### D-022 · 2026-09-03 · Sin pantalla para gestionar rubros (por ahora) · VIGENTE

- **Decisión:** los 16 rubros iniciales se cargan con `datos_iniciales.sql` y no hay pantalla para
  crear más. Agregar uno hoy requiere una consulta en phpMyAdmin.
- **Por qué:** no estaba en el alcance de la fase y los 16 cubren el trabajo que se ofrece en los
  programas de trabajo temporal. Se prefirió terminar bien lo pedido antes que agregar de más.
- **Pendiente conocido:** para el traspaso a la institución conviene construir esa pantalla, para
  que no dependan de alguien con acceso a la base. Anotado para la Fase 7.

### D-023 · 2026-09-03 · El texto del currículum no se guarda en ninguna parte · VIGENTE

- **Decisión:** el texto extraído del archivo vive solo durante la petición que arma la pantalla
  de confirmación. No se guarda en la base ni en la sesión. Lo único que queda son los campos
  estructurados que la persona confirmó.
- **Por qué:** datos mínimos (regla 4). El texto completo de un currículum tiene nombres,
  teléfonos, direcciones y nombres de empleadores anteriores. Nada de eso lo necesita el
  emparejamiento, así que no hay razón para tenerlo guardado y sí para no tenerlo.
- **Consecuencia:** si la persona recarga la pantalla de confirmación, el archivo se vuelve a
  leer. Cuesta un poco de procesador y ahorra un depósito de datos personales.

### D-024 · 2026-09-03 · El currículum armado se imprime desde el navegador · VIGENTE

- **Decisión:** para quien no tiene currículum, el sistema arma una hoja ordenada en HTML con
  estilos de impresión. La persona la guarda como PDF desde su propio navegador.
- **Por qué:** generar PDF en el servidor obligaría a una segunda dependencia externa, y cada
  librería es superficie de ataque y archivos que cuentan contra el límite del hosting. Cualquier
  navegador de teléfono ya sabe imprimir a PDF: el problema real se resuelve sin agregar nada.
- **Alternativas descartadas:** una librería de PDF (dependencia y peso); generar `.docx` a mano
  con ZipArchive (se puede, pero es bastante código para un beneficio pequeño).

### D-025 · 2026-09-03 · Sin `pdfparser` el sistema sigue funcionando · VIGENTE

- **Decisión:** la lectura de PDF se intenta solo si `vendor/autoload.php` existe. Si no está, o
  si el PDF es un escaneo sin texto, la persona ve un aviso en español y llena el formulario a
  mano. El archivo igual queda guardado.
- **Por qué:** regla 11. La única dependencia externa del proyecto no puede ser un punto de falla
  que deje a alguien sin poder usar la plataforma. El `.docx` se lee siempre, con `ZipArchive`,
  que viene con PHP.

### D-026 · 2026-09-03 · El archivo del CV no se pide por su nombre · VIGENTE

- **Decisión:** `htdocs/cuenta/archivo_cv.php` no recibe ningún parámetro. Busca el currículum que
  corresponde a la sesión abierta.
- **Por qué:** si recibiera un identificador, existiría un número que alguien podría cambiar en la
  dirección para pedir el currículum de otra persona. Es el error más común de estas pantallas, y
  acá el dato que se filtraría es el más sensible de todo el sistema.

### D-027 · 2026-09-08 · El puntaje del emparejamiento ordena, pero nunca se muestra · VIGENTE

- **Decisión:** el algoritmo calcula un puntaje para ordenar la lista, y ese número **no aparece
  en ninguna pantalla**. Lo que ve la persona son frases: "Sabés albañilería", "Piden 3 años de
  experiencia y vos tenés 5".
- **Por qué:** la regla 8. Un "87 % de coincidencia" no le dice nada a nadie y encima da una falsa
  sensación de precisión que la persona no puede discutir. Las frases sí las puede juzgar por su
  cuenta, sin saber nada de tecnología.

### D-028 · 2026-09-08 · El emparejamiento recibe seis campos y ninguno más · VIGENTE

- **Decisión:** `evaluar_coincidencia()` no recibe el usuario completo. Recibe un arreglo armado
  por `datos_para_emparejar()` con exactamente seis claves: rubros, años, estudios, idiomas,
  países y disponibilidad.
- **Por qué:** la regla 7 pide que la no discriminación sea **verificable**. Con esta firma se
  comprueba leyendo dos funciones que el algoritmo no puede usar edad, sexo, apellido, idioma
  materno ni origen, aunque alguien quisiera. Y esos datos, además, no existen en la base.

### D-029 · 2026-09-08 · Rubro y país excluyen; lo demás solo advierte · VIGENTE

- **Decisión:** si la oferta no es de un oficio que la persona sabe hacer, o es en un país al que
  dijo que no iría, no se recomienda. Todo lo demás (experiencia, estudios, idiomas,
  disponibilidad) **no excluye**: se muestra como advertencia y la persona decide.
- **Por qué:** que a alguien le falte un año de experiencia no significa que no deba intentarlo, y
  decidir eso por él sería exactamente el paternalismo que la regla 8 quiere evitar. En cambio,
  recomendarle un trabajo que no sabe hacer o en un país al que no quiere ir es ruido.

### D-030 · 2026-09-08 · Se puede postular con archivo o con el perfil · VIGENTE

- **Decisión:** si la persona tiene un archivo de currículum, se comparte el archivo. Si no tiene,
  se comparten los datos de su perfil. La pantalla de consentimiento dice cuál de los dos.
- **Por qué:** exigir un archivo dejaría fuera precisamente a quien no tiene currículum, que es
  buena parte de nuestra población y para quien se construyó el formulario alterno.
- **Detalle:** el consentimiento guarda `'perfil'` o el nombre del archivo, así que siempre queda
  registrado qué se compartió exactamente.

### D-031 · 2026-09-08 · Un archivo ya consentido no se borra al reemplazarlo · VIGENTE

- **Decisión:** cuando alguien sube un currículum nuevo o borra el suyo, el archivo anterior se
  borra del disco **salvo** que ya lo haya compartido con alguna oferta. En ese caso se conserva.
- **Por qué:** el consentimiento dice "se compartió este archivo". Si el archivo desapareciera, el
  registro estaría mintiendo, y el empleador que sí tenía autorización se quedaría sin lo que se
  le autorizó ver.
- **Consecuencia para la Fase 6:** al **eliminar la cuenta** sí se borra todo, sin excepción. Esta
  regla vale para el reemplazo, no para el borrado de cuenta (regla 4).

### D-032 · 2026-09-08 · "No aparece en el registro" NO significa "es una estafa" · VIGENTE

- **Decisión:** cuando el verificador no encuentra un nombre, dice exactamente esto: que no lo
  encontramos, que el registro es **solo de reclutadores autorizados** (intermediarios), que una
  empresa que contrata directo no tiene por qué estar ahí, y que por lo tanto **no podemos
  confirmar nada**. Nunca dice ni sugiere que sea falso.
- **Por qué:** es el punto donde más fácil sería hacer daño. Decir "no está, es estafa" acusaría
  a empleadores legítimos y, peor, le enseñaría a la gente a confiar en un sello que no significa
  lo que cree. La plataforma solo puede afirmar lo que sabe.
- **En la dirección contraria:** cuando sí aparece, tampoco se dice "es seguro". Se dice que está
  inscrito, y se recuerda que **aunque esté autorizado sigue sin poder cobrarle al trabajador.**

### D-033 · 2026-09-08 · Ninguna oferta se retira sola por acumular reportes · VIGENTE

- **Decisión:** en `app/modelos/reportes.php` no existe ninguna función que cambie el estado de una
  oferta. Resolver un reporte y retirar una oferta son dos acciones separadas, en dos pantallas
  distintas, y la segunda la decide una persona escribiendo el motivo.
- **Por qué:** si bastara con reportes para tumbar una oferta, un competidor o un reclutador
  irregular podría sacar del aire a los que sí cumplen con diez cuentas de correo desechable. El
  costo de revisar a mano es mucho menor que ese riesgo.
- **Se le dice a la persona que reporta**, para no prometerle algo que no va a pasar.

### D-034 · 2026-09-08 · El verificador no guarda qué nombres busca la gente · VIGENTE

- **Decisión:** se registra que hubo una consulta (para el límite de uso), con la conexión como
  identificador, pero **no el texto que la persona buscó**.
- **Por qué:** datos mínimos (regla 4). Saber qué empresas consulta la gente no nos hace falta
  para nada, y sería un registro de quién sospecha de quién. Lo que no se guarda no se filtra.

### D-035 · 2026-09-08 · Coincidencia exacta y coincidencia parecida se muestran distinto · VIGENTE

- **Decisión:** el verificador separa las coincidencias exactas de las parecidas. Con una exacta
  se puede afirmar "está en el registro". Con una parecida solo se dice "hay un nombre parecido,
  fijate bien si es el mismo".
- **Por qué:** usar un nombre casi igual al de una empresa real es una técnica de estafa conocida.
  Tratar las dos cosas igual sería peligroso en las dos direcciones.

### D-036 · 2026-09-08 · Los teléfonos de denuncia hay que verificarlos antes de publicar · PENDIENTE

- **Situación:** `htdocs/alertas.php` lista dónde denunciar. Los nombres de las instituciones son
  correctos, pero **los números de teléfono deben verificarse antes de publicar el sitio** y
  revisarse cada cierto tiempo.
- **Por qué importa:** un número equivocado acá no es un detalle de contenido. Es alguien en
  problemas llamando a un teléfono que no contesta.
- **Criterio:** si un número no se puede verificar, se deja solo el nombre de la institución. Es
  mejor decir menos que decir algo dudoso.

### D-037 · 2026-09-08 · La limpieza se dispara con un botón, no al entrar el administrador · VIGENTE

- **Decisión:** el borrado de registros viejos está en Mantenimiento, como botón. No se ejecuta
  solo cuando un administrador inicia sesión.
- **Por qué:** un borrado que ocurre sin que nadie lo pida es un borrado que nadie revisó. Con el
  botón hay una persona que apretó y una línea en la bitácora con su nombre; si algún día la
  limpieza tiene un error, hay a quién preguntarle. Además, colgarle trabajo pesado al inicio de
  sesión hace lento justo el momento en que alguien entra a trabajar.
- **Para que no se olvide:** el panel avisa cuando hay algo pendiente de limpiar.

### D-038 · 2026-09-08 · Trampa oculta en vez de captcha · VIGENTE

- **Decisión:** contra formularios automáticos se usa un campo escondido que las personas no ven
  y los programas llenan. No hay captcha.
- **Por qué:** un captcha necesita un servicio externo (regla 11: no dependemos de nadie) y carga
  imágenes pesadas, justo lo que no le sirve a alguien con datos contados. Y sobre todo: los
  captchas son una barrera real para gente con poca práctica usando páginas web, que es
  exactamente nuestra población. Sería ponerle el obstáculo a la persona equivocada.
- **Honestamente:** para el ruido de fondo. Contra alguien decidido están los límites por acción.

### D-039 · 2026-09-08 · El freno al ritmo se cuenta en la sesión, no en la base · VIGENTE

- **Decisión:** el límite de peticiones por minuto se lleva en la sesión, sin consultar la base.
- **Por qué:** agregarle una consulta a cada visita sería empeorar el problema que se quiere
  resolver, que es justamente consumir de más.
- **Lo que NO hace, dicho claro:** no frena a quien pida páginas sin cookies. Eso no se puede
  resolver desde PHP en un hosting compartido. Los límites que sí son infranqueables son los de
  cada acción, porque tocan la base: login, registro, subida, postulación y verificador.

### D-040 · 2026-09-08 · Al eliminar la cuenta se borran también los archivos ya compartidos · VIGENTE

- **Decisión:** `eliminar_cuenta()` borra del disco todos los currículums de esa persona,
  incluidos los que ya había compartido con alguna oferta.
- **Por qué:** contradice a propósito la decisión D-031, que los conserva cuando la persona solo
  **reemplaza** su currículum. Al eliminar la cuenta manda la regla 4. Que un empleador se quede
  sin un archivo que ya podía ver es un costo menor que incumplirle a alguien el borrado de sus
  datos.
- **Detalle de implementación:** los nombres de archivo se juntan **antes** de borrar la fila.
  Al revés quedarían huérfanos en el disco para siempre, sin forma de saber de quién eran.

### D-041 · 2026-09-08 · Los reportes sobreviven al borrado de cuenta, sin identidad · VIGENTE

- **Decisión:** al eliminar una cuenta, sus reportes de ofertas sospechosas se conservan con
  `usuario_id` en NULL.
- **Por qué:** el aviso de que una oferta puede ser una estafa le sirve a la institución para
  proteger a otras personas, y sin la identidad ya no apunta a nadie. Se le dice a la persona en
  la pantalla de borrado, antes de que confirme: no es una letra chica.

### D-042 · 2026-09-08 · El respaldo no incluye los currículums · VIGENTE

- **Decisión:** `admin/respaldo.php` exporta las 21 tablas, no los archivos. Un respaldo completo
  son **dos cosas**: ese archivo .sql y una copia por FTP de `app/almacen/cv`.
- **Por qué:** meter archivos binarios dentro del .sql lo haría enorme y frágil, y en este hosting
  se cortaría a la mitad. Está dicho en la propia pantalla para que nadie crea que con bajar el
  .sql ya respaldó todo.

### D-043 · 2026-09-08 · La lista blanca de tablas es la protección del respaldo · VIGENTE

- **Situación:** el respaldo es el único lugar del proyecto donde algo que no es un parámetro
  entra al texto de una consulta, y no se puede evitar: **PDO no permite parametrizar nombres de
  tabla**, solo valores.
- **Decisión:** existe `tabla_permitida()`, que comprueba el nombre contra `TABLAS_RESPALDO` **y**
  contra un patrón de solo letras minúsculas, y corta la ejecución si algo no cuadra. Se comprueba
  explícitamente en cada uso, aunque el nombre venga de una constante nuestra.
- **Por qué así:** para que la protección esté en la función y no en la confianza de que quien
  llame use la constante correcta.

### D-045 · 2026-10-01 · Una oferta con reclutador sin autorización vigente deja de verse sola · VIGENTE

- **Decisión:** `CONDICION_OFERTA_PUBLICA` exige que, si la oferta vino por un reclutador, su
  autorización esté vigente hoy (estado `vigente` **y** fecha). `motivo_para_no_publicar()` lo
  comprueba también, y el panel avisa qué ofertas dejaron de verse por esto.
- **Por qué:** D-020 bloqueaba *verificar*, pero nada lo volvía a mirar después. Si la
  autorización se vencía o se suspendía con la oferta publicada, seguía apareciendo con el sello
  y el número de registro del Ministerio: justo la señal en la que le pedimos a la gente confiar.
  Es el mismo razonamiento de D-018: la condición pública protege aunque nadie entre al panel.
- **Detalle:** la condición usa dos parámetros (`:hoy` y `:hoy_reclutador`) porque PDO no deja
  repetir uno con nombre. Los arma `parametros_oferta_publica()`; no se escriben a mano.
- **Alternativa descartada:** mostrar la oferta con una advertencia. Sería un sello de
  "verificada" acompañado de un "pero ya no": confunde más de lo que informa.

### D-046 · 2026-10-01 · Retirar la postulación revoca el permiso desde ese momento · VIGENTE

- **Decisión:** si la postulación está `retirada`, `admin/archivo_cv.php` se niega a entregar el
  currículum (y lo deja en la bitácora), y el panel no muestra sus datos.
- **Por qué:** consentir incluye poder retirar el consentimiento. Antes se comprobaba solo que
  *existiera* un consentimiento, así que retirarse no cambiaba nada del lado del panel.
- **Lo que no se puede prometer, y se dice:** lo que ya se descargó o se le pasó al empleador no
  vuelve. La pantalla de la persona lo explica con esas palabras.

### D-047 · 2026-10-01 · La sesión se ata a la contraseña con la que se abrió · VIGENTE

- **Decisión:** al iniciar sesión se guarda `sello_de_clave()` (un SHA-256 del hash de la
  contraseña). `usuario_actual()` lo compara en cada petición: si la contraseña cambió desde
  cualquier lado, la sesión se cierra.
- **Por qué:** cambiar la contraseña o pedir un restablecimiento es lo que hace alguien que dejó
  la sesión abierta en un café internet. `session_regenerate_id()` solo renovaba la propia.
- **Por qué así y no con una columna nueva:** no hace falta tocar la base, y la consulta ya se
  hacía en cada petición (D-014). Costo conocido: si PHP re-cifra la contraseña al entrar
  (`password_needs_rehash`), las otras sesiones de esa cuenta se cierran. Pasa una vez por versión.

### D-048 · 2026-10-01 · Interfaz dinámica por mejora progresiva · VIGENTE

- **Decisión:** todo lo dinámico es una capa de `app.js` encima de páginas que funcionan sin
  JavaScript. Los formularios con `data-en-vivo="#zona"` piden **la misma página** por detrás y
  reemplazan solo esa zona; la dirección del navegador se actualiza.
- **Por qué no una API:** la misma página trae los mismos controles de seguridad y de límites; no
  hay un segundo camino al dato que haya que proteger.
- **Por qué no buscar mientras se escribe:** multiplicaría las peticiones contra el tope de
  30 000 diarias. Se busca al apretar Enter o cambiar un menú: igual que antes.
- **Otras reglas:** JavaScript sin sintaxis nueva (un teléfono viejo con un error de sintaxis se
  queda sin nada); nada de `style=` en el HTML; movimiento corto y ninguno con
  `prefers-reduced-motion`; lo que aparece al bajar por la página se revela solo a los 2,5 s pase
  lo que pase (nada puede quedar invisible). En el teléfono, pestañas abajo con ícono y palabra,
  no menú escondido: el verificador tiene que estar a la vista.
- **D-015 aplicado de nuevo:** los avisos de éxito y "te aparece porque" usaban verde; pasaron a
  azul. El verde significa solo "origen verificado".

### D-049 · 2026-10-01 · Probar ejecutando, no solo leyendo · VIGENTE

- **Decisión:** además de `revision_seguridad.py` (que lee el código), existe
  `herramientas/prueba_de_humo.py`, que pide el sitio publicado y comprueba carpetas privadas,
  páginas, búsqueda con texto y cabeceras. Se corre después de cada subida por FTP. Es de solo
  lectura.
- **Por qué:** las dos fallas críticas de la auditoría pasaban la revisión de los 14 puntos. No
  se ven leyendo; solo al ejecutar. Y la prueba de D-044 ("abrí `/app/config/config.php` en el
  navegador") dependía de que alguien se acordara.
- **Lo siguiente:** pruebas de flujo completas en GitHub Actions (idea 3 de `AUDITORIA.md`).

### D-050 · 2026-10-03 · Identidad Mjob: nombre, logo y colores del logo · VIGENTE

- **Decisión:** el sitio se llama **Mjob for all**, con el lema **Trabajo sin fronteras**. Los dos
  salen de `SITIO_NOMBRE` y `SITIO_LEMA` en `config.php`; si falta `SITIO_LEMA`, `inicio.php` lo
  define vacío y el sitio arranca igual. `app/vistas/marca.php` (una sola pieza para la barra y el
  pie) lo dibuja como el logo: la primera palabra en negrita con la M en el azul del logo
  (`::first-letter`), y el resto ("for all") más delgado. El nombre sigue siendo uno solo y
  editable.
- **Corrección del mismo día:** primero se había puesto solo "Mjob". El nombre correcto, confirmado
  por el estudiante, es "Mjob for all". Al ser más largo, en teléfonos de menos de 360 px el botón
  "Crear cuenta" de la barra queda solo con su dibujo (la palabra sigue para el lector de
  pantalla); se midió sin desborde en 320, 360, 768 y 1024 px.
- **El logo:** el original (`marca/mjob-logo-original.png`, 1254×1254, 495 KB) se redibujó en
  vector en `app/vistas/logo.php`: la M, la estela con el avión y la persona. Pesa poco más de
  1 KB y va dentro del HTML (cero peticiones). Se dibuja una vez por página como `<symbol>` y se
  usa con `<use href="#mj-marca">` en la barra, el pie y las pantallas de entrar y crear cuenta.
  Sus colores son variables CSS (`--logo-*`), así que en modo noche se aclara (el azul marino del
  original desaparecería sobre fondo oscuro). El favicon (`icono.svg`) es la M blanca sobre el
  degradado; `icono-180.png` (17 KB, para "agregar a la pantalla de inicio" en iPhone) se generó
  del dibujo original.
- **Colores:** los del logo. El azul vivo de la M (`#0A74EC`) es `--marca`; para botones y
  enlaces se usa un punto más oscuro (`#0A66D8`), porque el de la M sobre blanco no llega al
  contraste mínimo para letra chica (4,45:1 contra 4,5:1). Fondo azul grisáceo muy claro en
  lugar del papel cálido, porque el logo es frío y los dos juntos peleaban.
- **Lo que NO cambió de D-015, a propósito:** el verde sigue significando SOLO "origen
  verificado"; nada de rojo de urgencia, contadores ni animaciones que apuren.
- **Un riesgo que hay que tener presente:** un avión y "sin fronteras" se parecen a la estética
  de los anuncios que prometen viajes. Se compensa así: el lema nunca va solo; en la portada va
  justo encima de "Antes de creerle a una oferta de trabajo, revisá de dónde viene", y en el pie
  junto a "no consigue trabajo, no gestiona trámites, no cobra dinero" (regla 12). Si la
  institución que reciba el proyecto ve que la gente lo lee como promesa de viaje, el lema se
  cambia en `config.php` sin tocar código.
- **Alternativas descartadas:** usar el PNG original (495 KB por página para una persona con
  datos contados, y no se puede aclarar en modo noche); una versión PNG chica (sigue sin
  adaptarse al modo noche y suma una petición por página); cargar una fuente parecida a la del
  logo (dependería de un servidor externo, regla 11, o sumaría archivos pesados).

### D-051 · 2026-10-03 · Modo noche: lo decide PHP con una cookie, no JavaScript al cargar · VIGENTE

- **Decisión:** si la persona nunca tocó el botón, se sigue lo que diga su teléfono
  (`prefers-color-scheme`). Si lo tocó, `app.js` guarda una cookie `tema=claro|oscuro` y en las
  páginas siguientes PHP pone `<html data-tema="...">` (`tema_elegido()` en `salida.php`).
- **Por qué PHP y no `localStorage`:** la CSP prohíbe JavaScript dentro del HTML, así que el
  tema solo se podría aplicar cuando carga `app.js`, al final de la página: cada página
  aparecería blanca y después se oscurecería. Un destello de luz es justo lo que no quiere quien
  eligió el modo noche. Con la cookie, la página llega ya con sus colores.
- **La cookie:** dice "claro" u "oscuro" y nada más. No identifica a nadie, es `SameSite=Lax`,
  `Secure` en HTTPS, dura un año, y PHP solo acepta esos dos valores (cualquier otro se ignora).
  Se le cuenta a la persona en "Qué guardamos de vos".
- **El botón** existe en el HTML con `hidden`, y solo `app.js` lo muestra: sin JavaScript no
  podría funcionar, y la regla del proyecto es que no haya botones que no hacen nada. Muestra la
  luna en claro y el sol en oscuro (a dónde te lleva); la palabra queda para el lector de
  pantalla y como globito, porque en la barra del teléfono no entra.
- **Los colores del modo noche** están DOS veces en `estilo.css` (una para el teléfono, otra para
  la elección con el botón), porque CSS no deja compartir un bloque entre una consulta de medios
  y un selector. Si se cambia un color, se cambia en los dos. Ningún componente escribe un color
  a mano; la hoja del currículum armado es la excepción a propósito: es papel, sale blanca.

### D-052 · 2026-10-03 · Los íconos son SVG dentro del HTML, desde una sola función · VIGENTE

- **Decisión:** `icono('nombre')` en `app/nucleo/iconos.php` devuelve el dibujo desde una lista
  fija (`ICONOS`). Ninguna página escribe un `<svg>` de ícono a mano.
- **Por qué:** sin descargas aparte (cuidan datos y el tope de peticiones), toman el color del
  texto (cambian solos con el modo noche) y hay un solo lugar donde están. Siempre son
  decorativos (`aria-hidden`): al lado va la palabra.
- **Seguridad:** el nombre lo escribe el código; si no existe, devuelve vacío. Por eso
  `revision_seguridad.py` acepta `icono(` como salida segura aunque reciba una variable.
- **Descartado:** una fuente de íconos o una librería externa (regla 11, y más archivos); un
  archivo `.svg` con todos los dibujos (una petición más por página).

### D-053 · 2026-10-03 · La portada abre con dos puertas, cada una con su buscador · VIGENTE

- **Decisión:** arriba de todo, dos tarjetas con palabras de todos los días: **"Busco trabajo"**
  (buscador de ofertas) y **"Me ofrecieron un trabajo"** (el verificador). Cada una trae su caja
  de búsqueda adentro.
- **Qué cambió:** antes el verificador iba primero y solo, porque quien llega asustada por un
  mensaje de WhatsApp no tiene que pasar por otra pantalla para comprobarlo. Eso se mantiene: el
  verificador sigue en la portada, con su caja, sin un clic de más. Lo que se agregó es la otra
  mitad: la persona que entra a buscar trabajo ahora también ve su camino de entrada, en lugar
  de un párrafo y un botón.
- **Navegación:** en el teléfono la barra de abajo suma "Inicio" (cinco pestañas con ícono y
  palabra). En pantalla ancha "Inicio" no va, porque el logo lleva al inicio y sin él entra todo;
  las palabras largas ("Verificar reclutador", "Señales de estafa") solo desde 1280 px.
- **"Mi cuenta":** los accesos son mosaicos con ícono que se tocan enteros, en lugar de tarjetas
  con párrafo y botón. Cada pantalla de la cuenta abre con el mismo ícono que tiene su mosaico,
  para que la persona reconozca dónde está.

### D-054 · 2026-10-03 · Las ofertas son de empleo en Guatemala, para quien regresó · VIGENTE

- **Decisión:** el centro de la plataforma pasa a ser el **empleo en Guatemala** para personas
  retornadas. Las ofertas en el extranjero no se eliminan del sistema (el país sigue siendo un
  campo), pero ya no son el objetivo.
- **Por qué:** lo pidió el estudiante con una razón que pesa: la población a la que apunta el
  proyecto ya está acá, deportada o de regreso. Mostrarle ofertas para irse otra vez no es lo
  que necesita, y es justo el terreno donde operan las estafas.
- **Qué se mantiene:** el verificador de reclutadores y las señales de estafa. A quien regresa lo
  buscan con ofertas falsas para volver a migrar; comprobar antes sigue siendo la función que
  más protege.
- **Qué cambió en pantalla:** portada ("Si regresaste a Guatemala y buscás trabajo…", sin la
  palabra "deportado", que estigmatiza), buscador, perfil (Guatemala ya viene marcada; "¿Dónde
  podrías trabajar?" y "¿Desde cuándo podrías empezar?" en lugar de "ir" y "viajar") y el
  emparejamiento ("Es en Guatemala, donde dijiste que podés trabajar").
- **Lo que se dejó de usar:** Adzuna (D-004) no cubre Guatemala, y las ofertas H-2A/H-2B del
  Departamento de Trabajo de EE. UU. (que se investigaron ese mismo día y tienen datos abiertos
  oficiales) son para irse. El mecanismo de D-004 (CSV preparado afuera e importado) sigue
  vigente para cualquier fuente oficial.

### D-055 · 2026-10-03 · Solo fuentes oficiales; nada se publica solo · VIGENTE

- **Lo que se pidió:** que las ofertas se publicaran y actualizaran solas, sin un empleado que
  las revise, conectando LinkedIn u otras plataformas.
- **Lo que se le explicó al estudiante antes de decidir:** (1) LinkedIn queda descartado (D-001);
  (2) Computrabajo, Tecoloco, Indeed y Jooble prohíben copiar sus ofertas y en Guatemala obtener
  datos de una base sin autorización es delito (Código Penal, art. 274F); (3) si las empresas
  publicaran solas sin que nadie las revise, cualquier estafador podría crear una "empresa" y su
  oferta saldría con nuestro sello, frente a personas recién deportadas; (4) en este hosting no
  hay forma automática de comprobar que una empresa existe (sin correo, sin SAT, sin conexiones
  salientes confiables).
- **Opciones que se le ofrecieron:** verificar cada empresa una sola vez y después publicar sus
  ofertas solas; publicar sin verificar con una etiqueta "no verificada"; o solo fuentes
  oficiales.
- **Lo que decidió el estudiante:** **solo fuentes oficiales**: ofertas que ya verificó el
  Ministerio de Trabajo o la OIM (por convenio), o que publica la propia empresa en su página
  oficial y alguien comprobó. **No se construyó** el formulario para que las empresas publiquen.
- **Consecuencia que hay que tener presente:** sin convenio, las ofertas nuevas las busca y
  comprueba una persona. Por eso existe `documentos/carta_convenio.md`. La actualización
  automática de verdad necesita dos cosas que hoy no hay: un convenio con una fuente oficial y un
  servidor con tareas programadas (ver `TRASPASO.md`).

### D-056 · 2026-10-03 · El primer lote lo comprobó el asistente y se aprueba al importarlo · VIGENTE

- **Decisión:** 6 vacantes reales (Allied Global e IntouchCX), comprobadas el 2026-10-03 en las
  páginas oficiales de cada empresa con las mismas cinco comprobaciones de D-017, entran
  **publicadas** con `sql/ofertas_reales_2026-10-03.sql`. Quedan atribuidas al primer
  superadministrador, que las aprueba al correr el archivo, y en la bitácora queda escrito qué se
  comprobó, cuándo y que lo hizo el asistente.
- **Por qué es una excepción a D-017 y D-021, y por qué se aceptó:** la verificación sí se hizo,
  una por una, contra la fuente oficial; lo distinto es quién la hizo. El estudiante lo pidió
  así, sabiendo que no tiene quién revise. Queda registrado para que nadie lo confunda con un
  atajo general: el panel y la importación por CSV siguen sin poder crear ofertas verificadas.
- **Fechas fijas a propósito:** "se revisó el" dice 2026-10-03 aunque se importe después, y todas
  vencen el 2026-11-02. Si el archivo se corre después de esa fecha, no carga nada. **No se
  renueva cambiando la fecha:** una vacante de hace un mes puede ya no existir; hay que volver a
  comprobarla.
- **Lo que se descartó en la búsqueda:** Concentrix (su portal no deja filtrar Guatemala de forma
  confiable), Walmart "Martes de Oportunidades" (la página oficial de la campaña da error 404 y el
  anuncio es de julio de 2025: no se pudo confirmar que siga), la "Academia" de Allied Global (es
  un curso, no un trabajo), y las vacantes que piden "6 meses de experiencia" (el sistema cuenta
  años: cargarlas diría "no se pide experiencia" o "1 año", y las dos cosas son falsas).

### D-057 · 2026-10-03 · Departamento en las ofertas, no en el perfil · VIGENTE

- **Decisión:** `ofertas.departamento_codigo` (los 22 departamentos, en `DEPARTAMENTOS`).
  Obligatorio si el trabajo es en Guatemala. El buscador filtra por departamento, y la tarjeta dice
  "Melchor de Mencos, Petén" (`lugar_de_oferta()`).
- **Por qué no en el perfil:** se le preguntó al estudiante y eligió "solo en las ofertas". Para
  mucha gente, el departamento donde podría trabajar coincide con el de origen, que la regla 7
  prohíbe recolectar (D-008). La persona filtra por departamento cuando busca; el sistema no
  guarda nada de eso sobre ella.

### D-058 · 2026-10-03 · Postulación en la página de la empresa · VIGENTE

- **Decisión:** `ofertas.forma_postulacion`: `plataforma` (como hasta ahora: consentimiento y la
  institución le pasa el currículum al empleador) o `externa` (el botón lleva a la página oficial
  de la empresa y dice a qué dirección lleva).
- **Por qué:** si una oferta tomada de la página de una empresa se postulara por acá, el
  currículum quedaría guardado y nadie se lo mandaría a la empresa: prometer algo que no se
  cumple (regla 12) y guardar datos sin razón (regla 4).
- **Se comprueba en el servidor** (regla 5): `cuenta/postular.php` rechaza las externas aunque se
  escriba la dirección a mano, y una externa no se puede publicar sin una dirección válida.

### D-059 · 2026-10-03 · Oficio "Atención al cliente y call center" y disponibilidad sin "viajar" · VIGENTE

- **Decisión:** se agrega el rubro `atencion_cliente` (es uno de los tres sectores que más
  contratan a personas retornadas, junto con construcción y carpintería) y la disponibilidad
  pasa a decir "De inmediato" en lugar de "Puedo viajar de inmediato", porque sirve igual para la
  oferta y para la persona.
- **Detalle:** `fgetcsv()` y `fputcsv()` llevan sus cuatro parámetros escritos: en PHP 8.4 omitir
  `$escape` da un aviso que el manejador de errores convierte en error. En el servidor (8.3) no
  pasaba todavía; en la copia local (8.4) rompía la importación.

---

## 6. Cosas que ya se intentaron y no funcionaron

### 2026-09-08 · Detectar SQL peligroso con un patrón multilínea

- **Qué se intentó:** en `herramientas/revision_seguridad.py`, buscar cadenas con SQL y variables
  usando una expresión regular sobre el archivo completo, con `re.S`.
- **Por qué no sirve:** el patrón empieza el match en la comilla que **cierra** una cadena y lo
  termina en la que **abre** la siguiente, así que marca como peligroso el código PHP que hay en
  medio. Daba 128 hallazgos, de los cuales uno era real.
- **Qué se hizo en su lugar:** revisar línea por línea, donde una cadena no puede cruzarse con
  otra. Bajó a cero falsos positivos.
- **La lección, que vale más que el detalle técnico:** un detector con falsos positivos crónicos
  es un detector que nadie vuelve a mirar, y entonces no protege nada. Si vuelve a dar ruido, hay
  que afinarlo, no acostumbrarse a ignorarlo.

### 2026-10-01 · Repetir un parámetro con nombre en la misma consulta

- **Qué se intentó:** `titulo LIKE :texto OR empleador LIKE :texto`, con el mismo `:texto`.
- **Por qué no sirve:** con `EMULATE_PREPARES` en `false` (que es lo correcto, ver `bd.php`),
  PDO no lo permite y la consulta revienta con `Invalid parameter number`. El buscador entero daba
  error 500 en cuanto alguien escribía algo, y nadie lo vio porque sin texto andaba.
- **Qué se hace:** un nombre por aparición (`:texto1`, `:texto2`), armados por
  `parametros_de_texto()`. Lo mismo con la fecha: `parametros_oferta_publica()`.

### 2026-10-01 · Comprobar el esquema buscando un texto en cualquier parte

- **Qué se intentó:** en `revision_seguridad.py`, dar por buena la cascada si `ON DELETE CASCADE`
  aparecía en algún lugar de `esquema.sql`.
- **Por qué no sirve:** aparecía en muchas llaves, no en `fk_post_cons`, y el detector decía
  `[ok]`. Encima, si faltaba no decía nada. Resultado: quien se había postulado no podía borrar su
  cuenta, con la revisión en verde.
- **Qué se hace:** revisar cada llave por su nombre, y que todo control sepa decir `[X]`. Y la
  lección de fondo: leer el código no reemplaza ejecutarlo (D-049).

### 2026-10-03 · Buscar una fuente automática de empleos en Guatemala

- **Qué se intentó:** encontrar una API o un conjunto de datos abiertos con vacantes en Guatemala,
  para conectarlo y que se actualice solo.
- **Lo que se encontró, fuente por fuente:**
  - *LinkedIn:* descartado desde D-001.
  - *Tu Empleo (Ministerio de Trabajo):* la mejor fuente posible, pero está detrás de una
    verificación anti-robots de Cloudflare. No se intentó saltarla: hacerlo viola la regla 3.
  - *Computrabajo:* responde 403 a los programas. *Tecoloco, Indeed:* sus términos prohíben
    copiar. *Jooble:* sus términos prohíben republicar sin permiso escrito; su API es para socios
    y no se pudo confirmar que cubra Guatemala.
  - *Adzuna:* no cubre Guatemala.
  - *Departamento de Trabajo de EE. UU. (seasonaljobs.dol.gov):* tiene datos abiertos oficiales
    y diarios, pero son ofertas para irse (H-2A, H-2B). Útil si algún día se vuelven a mostrar
    ofertas en el extranjero: el formato está en seasonaljobs.dol.gov/feeds.
  - *Quioscos y ferias del Ministerio:* oficiales, pero son eventos de un día que la prensa
    publica casi siempre después.
  - *Páginas oficiales de las empresas:* sí sirven, una por una. De ahí sale el primer lote.
- **La lección:** en Guatemala no hay una fuente legal que se pueda conectar sola. Lo que se
  puede automatizar es lo que una institución acepte compartir: de ahí la carta de convenio.

### 2026-10-03 · El vidrio esmerilado en la barra de arriba

- **Qué se intentó:** `backdrop-filter: blur()` en `.barra`, para que el contenido se vea
  borroso detrás de la barra al bajar.
- **Por qué no sirve:** un `backdrop-filter` convierte al elemento en el marco de referencia de
  todo lo que tiene adentro con `position: fixed`. La barra de pestañas del teléfono vive
  dentro de `.barra`, y en lugar de quedar pegada abajo de la pantalla quedó pegada arriba,
  tapando el logo. Solo se vio al probar en el navegador.
- **Qué se hace:** el efecto existe solo desde 48rem, donde la navegación no es fija. En el
  teléfono la barra es de color sólido (que además es más barato para un teléfono de gama baja).

### 2026-10-03 · Poner las palabras largas del menú en cualquier pantalla ancha

- **Qué se intentó:** "Verificar reclutador" y "Señales de estafa" desde 1024 px.
- **Por qué no sirve:** a 1024 px la barra no entraba y aparecía desplazamiento horizontal en
  todo el sitio.
- **Qué se hace:** palabras cortas hasta 1280 px, y se midió con JavaScript que el contenido no
  sea más ancho que la pantalla a 768, 1024 y 1280 px. Si se agrega una pestaña, hay que volver
  a medir.

### 2026-09-08 · Escribir archivos largos con heredoc desde la terminal

- **Qué se intentó:** crear el `CLAUDE.md` inicial con `cat > archivo <<'FIN'`.
- **Por qué no sirve:** con contenido largo y acentuado, la terminal rompía la sintaxis antes de
  terminar el archivo.
- **Qué se hizo en su lugar:** escribir los archivos directamente. Sin consecuencias para el
  proyecto, pero para no volver a perder tiempo en lo mismo.

---

## 7. Convenciones del código

*(Confirmadas con la Fase 1.)*

- **Idioma del código:** todo en español — tablas, columnas, funciones, archivos y variables. El
  dominio es en español y quien lo mantenga después también.
- **Nombres de tablas:** minúsculas, plural, `snake_case` (`ofertas`, `reclutadores_autorizados`).
- **Nombres de columnas:** `snake_case`, singular. Llaves foráneas: `entidad_id` (`oferta_id`).
- **Fechas:** columnas `creado_en`, `actualizado_en`, `<accion>_en` (`verificada_en`).
- **Archivos PHP:** minúsculas con guion bajo (`ver_oferta.php`, `subir_cv.php`).
- **Funciones:** verbo + sustantivo en español (`requerir_permiso()`, `escapar()`).
- **Constantes de configuración:** MAYÚSCULAS, definidas **solo** en la zona única de
  configuración.
- **Nada de números sueltos** repartidos por el código: todo límite sale de la configuración.

### Dónde va cada cosa

```
app/config/     configuración y catálogos      app/vistas/    plantillas de pantalla
app/nucleo/     primitivas compartidas         app/almacen/   datos (CV, logs, respaldos)
app/modelos/    consultas SQL por entidad      htdocs/        lo único público
marca/          el logo original (no se sube al servidor)
```

**Toda página empieza igual**, y con eso ya tiene sesión segura, cabeceras, manejo de errores,
CSRF validado y las funciones de permisos:

```php
require __DIR__ . '/app/nucleo/inicio.php';      // desde htdocs/
require __DIR__ . '/../app/nucleo/inicio.php';   // desde htdocs/cuenta/ o htdocs/admin/
```

### Funciones compartidas: qué existe y para qué

Antes de escribir una función nueva, revisar esta lista. Si ya hay una que hace eso, se usa esa.
Tener dos maneras de comprobar lo mismo es como aparecen los huecos.

| Archivo | Funciones | Para qué |
|---|---|---|
| `nucleo/autorizacion.php` | `requerir_sesion()`, `requerir_administrativo()`, `requerir_permiso()`, `requerir_superadministrador()`, `tiene_permiso()`, `usuario_actual()` | **Regla 5.** Único lugar donde se decide quién puede hacer qué |
| `nucleo/csrf.php` | `campo_csrf()`, `validar_csrf()` | Token de formularios. La validación es automática (D-011); en el formulario solo hay que poner `campo_csrf()` |
| `nucleo/salida.php` | `escapar()`, `ahora()`, `fecha_en_palabras()`, `guardar_mensaje()`, `guardar_mensaje_si_no_hay()`, `recurso()`, `tema_elegido()` | Imprimir sin XSS, fechas del sistema, avisos después de redirigir, CSS y JS con versión, modo noche elegido (D-051) |
| `nucleo/iconos.php` | `icono()` | Todos los íconos de la interfaz, de una lista fija (D-052). Nunca un `<svg>` de ícono escrito a mano |
| `vistas/logo.php` | *(lo incluye la cabecera)* | El logo de Mjob en vector, una vez por página. Se usa con `<use href="#mj-marca">` (D-050) |
| `vistas/marca.php` | *(se incluye)* | Logo + nombre + lema, igual en la barra y en el pie. El nombre se escribe solo acá |
| `nucleo/bd.php` | `consultar()`, `consultar_una()`, `consultar_todas()`, `consultar_valor()`, `consultar_paginado()`, `parametros_de_texto()` | Todas las consultas, siempre preparadas. Ni una concatenación de SQL. **Nunca repetir un parámetro con nombre** |
| `nucleo/validacion.php` | `es_correo_valido()`, `en_catalogo()`, `id_valido()`, `revisar_contrasena()`, `normalizar_nombre()` | Validar en el servidor lo que llega del usuario |
| `nucleo/peticion.php` | `es_post()`, `campo()`, `campo_crudo()`, `ip_cliente()`, `redirigir()`, `abortar()` | Lo que viene del navegador y a dónde se manda después |
| `nucleo/bitacora.php` | `registrar_accion()`, `leer_bitacora()` | Auditoría. Nunca contraseñas, códigos ni datos personales de más |
| `nucleo/limites_uso.php` | `registrar_intento()`, `esta_bloqueado()`, `limpiar_fallos()` | Control de abuso, con los números de `config/limites.php` |
| `nucleo/sesion.php` | `iniciar_sesion_de_usuario()`, `cerrar_sesion()`, `renovar_sello_de_clave()` | Sesión segura, expiración, inicio de sesión, y cierre de las otras sesiones al cambiar la contraseña (D-047) |
| `nucleo/codigos.php` | `generar_codigo()`, `generar_contrasena_temporal()` | Códigos aleatorios seguros y legibles al dictarlos |
| `modelos/usuarios.php` | `autenticar()`, `crear_usuario()`, `cambiar_contrasena()`, `desactivar_usuario()`, `es_correo_administrativo()`, `hash_de_relleno()` | Todo lo que toca la tabla `usuarios` |
| `modelos/ofertas.php` | `listar_ofertas_publicas()`, `buscar_oferta_publica()`, `crear_oferta()`, `transicion_permitida()`, `motivo_para_no_publicar()`, `marcar_verificada()`, `parametros_oferta_publica()`, `ofertas_publicadas_con_reclutador_no_vigente()`, `lugar_de_oferta()` | Ofertas y su ciclo de vida. Contiene `CONDICION_OFERTA_PUBLICA`, que es la regla 1 escrita una sola vez. Toda consulta que la use pasa `parametros_oferta_publica()` |
| `modelos/fuentes.php` | `listar_fuentes()`, `crear_fuente()`, `cambiar_estado_fuente()` | De dónde salió cada oferta |
| `modelos/reclutadores.php` | `listar_reclutadores()`, `reclutador_vigente_hoy()`, `agregar_alias()` | El registro del Ministerio. Alimenta el verificador de la Fase 5 |
| `modelos/rubros.php` | `listar_rubros()`, `rubro_valido()`, `id_de_rubro()` | Los oficios |
| `vistas/sello_verificacion.php` | *(se incluye, no es función)* | El sello + los cinco datos obligatorios, juntos y en un solo lugar |
| `nucleo/archivos.php` | `revisar_cv_subido()`, `guardar_cv()`, `borrar_cv()`, `entregar_cv()` | Currículums: tipo real con finfo, nombre aleatorio, en `app/almacen/cv` cerrada por `.htaccess` (D-044) |
| `nucleo/extraccion.php` | `leer_texto_de_cv()`, `extraer_datos_del_cv()`, `se_puede_leer()`, `contiene_palabra()` | Leer `.docx` y `.pdf` y proponer qué entendió. Lo que sale de acá es propuesta, no dato. Con tope contra la bomba ZIP |
| `modelos/perfiles.php` | `buscar_perfil()`, `guardar_perfil()`, `rubros_de_perfil()`, `paises_de_perfil()` | El perfil laboral. Solo los campos que permite la regla 7 |
| `modelos/guardadas.php` | `guardar_oferta()`, `listar_ofertas_guardadas()` | Ofertas apartadas para después |
| `modelos/restablecimientos.php` | `crear_restablecimiento()`, `validar_restablecimiento()` | Códigos de un solo uso, guardados con hash |
| `nucleo/emparejamiento.php` | `evaluar_coincidencia()`, `datos_para_emparejar()`, `ofertas_recomendadas()` | **Reglas 7 y 8.** Recibe seis campos y ninguno más, y devuelve razones en palabras |
| `modelos/postulaciones.php` | `crear_postulacion()`, `hay_consentimiento_para()`, `archivo_esta_en_algun_consentimiento()` | **Regla 6.** Consentimiento y postulación se crean juntos o no se crean |
| `modelos/reportes.php` | `crear_reporte()`, `resolver_reporte()`, `ofertas_con_reportes_pendientes()` | Reportes. **No tiene ninguna función que cambie el estado de una oferta**, a propósito |
| `vistas/texto_consentimiento.php` | *(se incluye)* | El texto que la persona acepta, en un solo lugar y versionado |

### Reglas de estilo que ya están amarradas al código

- **Nada de `style="..."` ni `onclick="..."` en el HTML.** La CSP del sitio los bloquea. Todo lo
  visual sale de una clase del CSS y todo el JavaScript vive en `htdocs/recursos/app.js`.
- **La página tiene que funcionar sin JavaScript.** El `app.js` solo agrega comodidades
  (ver la contraseña, confirmar antes de borrar); ninguna comprobación de seguridad depende de él.
- **Cada pantalla resuelve sus estados**: qué se ve cuando no hay datos (`.vacio`), cuando algo
  falla (`.aviso--error`) y cuando salió bien (`.aviso--exito`).
- **Los ganchos de `app.js` son atributos, no código en el HTML** (D-048):

  | Atributo | Qué hace |
  |---|---|
  | `data-confirmar="pregunta"` en un `<form>` | Pregunta antes de enviar |
  | `data-descarga` en un `<form>` | El formulario baja un archivo: el botón no queda "trabajando" |
  | `data-en-vivo="#zona"` en un `<form method="get">` | Actualiza solo `#zona` sin recargar. La página tiene que tener `#zona` |
  | `data-en-vivo-enlace="#zona"` en un `<a>` | Lo mismo para un enlace (páginas, quitar un filtro) |
  | `data-anuncio` dentro de la zona | Ese texto se le lee al lector de pantalla al actualizar |
  | `data-ver` en un campo de contraseña | Botón "Mostrar la contraseña" |
  | `data-medir` + `minlength` en la contraseña nueva | Cuántos caracteres faltan |
  | `data-igual-a="id"` en la confirmación | Si coincide con el campo `id` |
  | `data-max-bytes` en el `<input type="file">` de `.zona-archivo` | Avisa antes de subir si pesa de más |
  | `data-compartir-url` en `.compartir` | Agrega "Copiar el enlace" y el menú de compartir del teléfono |
  | `data-tema-boton` en el botón de la cabecera | Lo muestra y lo hace cambiar entre modo día y modo noche (D-051) |

- **Cada pantalla principal abre con `.cabeza`**: un `.cabeza__icono` con el mismo ícono que
  tiene esa sección en el menú o en "Mi cuenta", y al lado el título y la guía. Así la persona
  reconoce dónde está sin leer.
- **Componentes del rediseño** (D-050 a D-053): `.portada` y `.puerta` (portada), `.chip`
  (país, oficio), `.oferta__datos` (datos con ícono en la tarjeta), `.detalle` (página de una
  oferta en dos columnas), `.datos-oferta` y `.dato`, `.mosaicos` y `.mosaico` (accesos de "Mi
  cuenta"), `.acceso__cabeza` (entrar y crear cuenta), `.lista-iconos`, `.vacio__icono`.
- **Ningún color escrito a mano en un componente**: siempre una variable del bloque de arriba de
  `estilo.css`, o el modo noche deja de funcionar ahí.

---

## 8. Cómo trabajamos

**Antes de tocar código en cada fase:** leer este archivo, inspeccionar la estructura actual, decir
qué existe y qué falta, indicar qué archivos se van a crear o modificar y explicar cómo se integra
lo nuevo sin romper lo que ya funciona. Después se implementa.

**Al terminar cada fase:** decir qué se agregó, qué archivos se modificaron, qué cambió en la base
de datos, cómo probar cada función, qué casos de error se probaron y qué quedó pendiente. Y
actualizar este archivo: estado de la fase, decisiones nuevas con su razón, y lo que se descartó.

**Reglas de trabajo:**

- Fase por fase. Al terminar una, se para y se espera confirmación.
- Explicar lo que se va haciendo: el objetivo es que el estudiante entienda su propio código, no
  solo que funcione.
- Con Git, dar los comandos exactos paso a paso.
- No hay terminal en el servidor: todo se sube por FTP, así que hay que decir exactamente qué
  archivos subir en cada paso.
- Si algo que se pide es mala idea, decirlo antes de hacerlo.
- Si una decisión tiene dos caminos razonables, preguntar en vez de asumir.
- No dar por terminada una fase con algo a medias.
