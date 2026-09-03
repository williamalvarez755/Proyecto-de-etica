# CLAUDE.md — Plataforma de ofertas laborales verificadas para personas migrantes y retornadas

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

**Fase actual: 1 — Cimientos, roles y primitivas de seguridad.**
**Situación: código escrito y revisado en local el 2026-09-03. Falta subirlo por FTP y probarlo
en el servidor real. La Fase 2 no empieza hasta que las pruebas del servidor pasen.**

| Fase | Contenido | Estado |
|---|---|---|
| 1 | Cimientos, roles y primitivas de seguridad | Escrita, falta probar en el servidor |
| 2 | Ofertas, estados y panel de administración | Pendiente |
| 3 | Cuentas, currículum y perfil | Pendiente |
| 4 | Emparejamiento, postulación y consentimiento | Pendiente |
| 5 | Prevención de estafas y reportes | Pendiente |
| 6 | Abuso, auditoría, resiliencia y retención | Pendiente |
| 7 | Revisión de seguridad y puesta en producción | Pendiente |

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

Las cuatro primeras las contesta `htdocs/diagnostico.php`, que se sube, se lee una vez y se borra.

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

### D-015 · 2026-09-03 · Dirección visual: "institucional cálido" · VIGENTE

- **Decisión:** papel claro cálido, azul profundo de documento oficial, verde reservado
  **únicamente** para el sello de verificación, tipografías del sistema, cuerpo de 17px, botones de
  48px de alto. Todo sale de las variables CSS al inicio de `htdocs/recursos/estilo.css`.
- **Por qué:** la página tiene que verse creíble para alguien que ya fue engañado antes. El
  lenguaje visual de la estafa es el contrario: colores encendidos, urgencia, promesas. Se evita a
  propósito. El verde se reserva porque si se usa de adorno deja de significar algo, y es el
  elemento que más peso carga en toda la interfaz.
- **Consecuencia:** cambiar la identidad del proyecto es cambiar un bloque de variables, no
  repasar las pantallas una por una.

### D-016 · 2026-09-03 · Sin `open_basedir` confirmado, los CV van fuera de `htdocs` · EN PRUEBA

- **Decisión provisional:** los currículums se guardarán en `app/almacen/cv/`, fuera de la carpeta
  pública, servidos solo por un script que comprueba sesión y permiso.
- **Por qué:** es la única forma de que un currículum no se pueda descargar escribiendo su
  dirección.
- **Pendiente:** que `htdocs/diagnostico.php` confirme en el servidor real que PHP puede leer y
  escribir ahí. Si no pudiera, el plan B es `htdocs/_privado/` con `.htaccess` que niegue todo, que
  es más débil (un error en un `.htaccess` expone los archivos) y solo se usaría sin más remedio.

---

## 6. Cosas que ya se intentaron y no funcionaron

*(Vacío por ahora. Se llena en cuanto algo se pruebe y se descarte, con la razón concreta, para no
volver a intentarlo dentro de tres semanas.)*

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
```

**Toda página empieza igual**, y con eso ya tiene sesión segura, cabeceras, manejo de errores,
CSRF validado y las funciones de permisos:

```php
require __DIR__ . '/../app/nucleo/inicio.php';   // una carpeta menos desde htdocs/
```

### Funciones compartidas: qué existe y para qué

Antes de escribir una función nueva, revisar esta lista. Si ya hay una que hace eso, se usa esa.
Tener dos maneras de comprobar lo mismo es como aparecen los huecos.

| Archivo | Funciones | Para qué |
|---|---|---|
| `nucleo/autorizacion.php` | `requerir_sesion()`, `requerir_administrativo()`, `requerir_permiso()`, `requerir_superadministrador()`, `tiene_permiso()`, `usuario_actual()` | **Regla 5.** Único lugar donde se decide quién puede hacer qué |
| `nucleo/csrf.php` | `campo_csrf()`, `validar_csrf()` | Token de formularios. La validación es automática (D-011); en el formulario solo hay que poner `campo_csrf()` |
| `nucleo/salida.php` | `escapar()`, `ahora()`, `fecha_en_palabras()`, `guardar_mensaje()` | Imprimir sin XSS, fechas del sistema, avisos después de redirigir |
| `nucleo/bd.php` | `consultar()`, `consultar_una()`, `consultar_todas()`, `consultar_valor()`, `consultar_paginado()` | Todas las consultas, siempre preparadas. Ni una concatenación de SQL |
| `nucleo/validacion.php` | `es_correo_valido()`, `en_catalogo()`, `id_valido()`, `revisar_contrasena()`, `normalizar_nombre()` | Validar en el servidor lo que llega del usuario |
| `nucleo/peticion.php` | `es_post()`, `campo()`, `campo_crudo()`, `ip_cliente()`, `redirigir()`, `abortar()` | Lo que viene del navegador y a dónde se manda después |
| `nucleo/bitacora.php` | `registrar_accion()`, `leer_bitacora()` | Auditoría. Nunca contraseñas, códigos ni datos personales de más |
| `nucleo/limites_uso.php` | `registrar_intento()`, `esta_bloqueado()`, `limpiar_fallos()` | Control de abuso, con los números de `config/limites.php` |
| `nucleo/sesion.php` | `iniciar_sesion_de_usuario()`, `cerrar_sesion()` | Sesión segura, expiración e inicio de sesión |
| `nucleo/codigos.php` | `generar_codigo()`, `generar_contrasena_temporal()` | Códigos aleatorios seguros y legibles al dictarlos |
| `modelos/usuarios.php` | `autenticar()`, `crear_usuario()`, `cambiar_contrasena()`, `desactivar_usuario()` | Todo lo que toca la tabla `usuarios` |

### Reglas de estilo que ya están amarradas al código

- **Nada de `style="..."` ni `onclick="..."` en el HTML.** La CSP del sitio los bloquea. Todo lo
  visual sale de una clase del CSS y todo el JavaScript vive en `htdocs/recursos/app.js`.
- **La página tiene que funcionar sin JavaScript.** El `app.js` solo agrega comodidades
  (ver la contraseña, confirmar antes de borrar); ninguna comprobación de seguridad depende de él.
- **Cada pantalla resuelve sus estados**: qué se ve cuando no hay datos (`.vacio`), cuando algo
  falla (`.aviso--error`) y cuando salió bien (`.aviso--exito`).

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
