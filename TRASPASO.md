# Documento de traspaso

Para quien reciba este proyecto y tenga que mantenerlo, cambiarlo o moverlo de servidor.
Asume conocimientos técnicos básicos. La guía para quien solo va a cargar ofertas está en
[MANUAL.md](MANUAL.md); las decisiones de diseño y por qué se tomaron, en [CLAUDE.md](CLAUDE.md).

---

## 1. Qué es y por qué está hecho así

Plataforma de ofertas laborales con origen verificado, para personas migrantes guatemaltecas y
retornadas. PHP 8 puro y MySQL, sin framework.

**Antes de proponer un cambio de stack, leé el CLAUDE.md.** El stack no es una preferencia: lo
impone el hosting gratuito con el que arrancó el proyecto. Si el hosting cambia, varias decisiones
se pueden revisar, y están todas anotadas con su razón.

---

## 2. Cómo se levanta desde cero

### 2.1. Base de datos

1. Crear una base de datos MySQL.
2. Importar en este orden:
   - `sql/esquema.sql` — las 21 tablas
   - `sql/datos_iniciales.sql` — roles, permisos y rubros
   - `sql/datos_prueba.sql` — **opcional**, solo para probar en una copia local. Sus ofertas de
     EJEMPLO son en el extranjero: no se usa en el sitio público desde que es de empleo en
     Guatemala (D-054). Trae instrucciones para borrarlo.
3. **Si la base ya existía antes del 2026-10-01:** correr también `sql/migracion_001.sql`. Sin
   ella, borrar una oferta con postulaciones falla. (Borrar una cuenta funciona igual: el código
   ya no depende de la migración.)
4. **Si la base ya existía antes del 2026-10-03:** correr también `sql/migracion_002.sql`
   (departamento, forma de postularse y el oficio "Atención al cliente y call center").
5. **Ofertas reales:** `sql/ofertas_reales_2026-10-03.sql` carga 6 vacantes comprobadas en las
   páginas oficiales de las empresas. Vencen solas el 2026-11-02 y después de esa fecha el archivo
   ya no carga nada (D-056). Las siguientes se cargan desde el panel.

### 2.2. Configuración

Copiar `app/config/config.ejemplo.php` como `app/config/config.php` y llenar:

| Constante | Qué es |
|---|---|
| `BD_SERVIDOR`, `BD_NOMBRE`, `BD_USUARIO`, `BD_CLAVE` | Credenciales de MySQL |
| `SITIO_URL` | Dirección pública, sin barra final |
| `SITIO_NOMBRE` | Nombre visible (hoy `Mjob for all`). La primera palabra va en negrita con la M en el azul del logo; el resto, más delgado |
| `SITIO_LEMA` | El lema debajo del nombre (hoy `Trabajo sin fronteras`). Si falta, el sitio anda igual, sin lema |
| `ENTORNO` | `produccion` en el servidor. **Nunca `desarrollo` en público** |

**`config.php` no está en el repositorio y no debe estarlo nunca.** Está en `.gitignore`.

### 2.3. Archivos

| Se sube | A dónde |
|---|---|
| Contenido de `htdocs/` | Dentro de `htdocs/` |
| `app/` completo | **Dentro de `htdocs/`** (ver la advertencia de abajo) |
| `vendor/` completo | **Dentro de `htdocs/`** |

> ⚠️ **Por qué `app/` va dentro de `htdocs` y no afuera, que sería lo correcto**
>
> InfinityFree encierra a PHP dentro de `htdocs` con `open_basedir`. Con `app/` afuera, ninguna
> página carga: todo da error 500. Es la decisión **D-044** del `CLAUDE.md`.
>
> La consecuencia es que los currículums de las personas quedan dentro de la carpeta pública, y
> lo único que impide descargarlos son tres archivos `.htaccess`. **Si alguno se borra, se
> exponen.**
>
> Después de cualquier cambio en los `.htaccess`, comprobá en el navegador que
> `/app/config/config.php` devuelva 403 o 404. Es la única prueba que confirma que la protección
> sigue en pie. **`herramientas/prueba_de_humo.py` la hace sola** junto con otras seis rutas
> privadas (sección 3.1): corrélo después de cada subida por FTP.
>
> **En un servidor propio esto se revierte:** `app/` y `vendor/` vuelven afuera de la carpeta
> pública, los `require` recuperan su `../`, y la protección deja de depender de un archivo de
> configuración. Es lo primero que hay que hacer al migrar.

**No se suben:** `sql/`, `herramientas/`, `*.md`, `.gitignore`, `composer.json`.

Comprobar que existan y sean escribibles: `app/almacen/cv`, `app/almacen/logs`,
`app/almacen/respaldos`. Muchos clientes FTP no suben carpetas que solo tienen archivos ocultos.

### 2.4. Comprobación del servidor

Subir `htdocs/diagnostico.php`, abrirlo, leerlo y **borrarlo**. Contesta:

- Versión de PHP (necesita 8.0+) y de MySQL.
- Extensiones: `pdo_mysql`, `mbstring`, `fileinfo`, `zip`, `openssl`.
- `upload_max_filesize` y `post_max_size`.
- Si se puede escribir en la carpeta de currículums y si los `.htaccess` que la protegen están
  puestos.

**En InfinityFree (verificado el 2026-09-09):** PHP 8.3.19, todas las extensiones disponibles,
512 MB de memoria, subida hasta 20 MB, y `open_basedir` limitado a `htdocs` — que es lo que
obligó a la decisión D-044.

### 2.5. Primera cuenta

No hay cuenta de fábrica ni contraseña por defecto (regla 10).

1. Crear por FTP `app/config/instalacion.txt` con una frase de 12 caracteres o más.
2. Abrir `/instalar.php`, llenar los datos y escribir esa frase.
3. **Borrar `instalar.php` por FTP.** El instalador además se niega a correr si ya existe una
   cuenta administrativa, pero no hay razón para dejarlo.

---

## 3. Dónde está cada cosa

```
app/config/      configuración (config.php con credenciales, limites.php, catalogos.php)
app/nucleo/      primitivas: permisos, CSRF, sesión, bitácora, límites, archivos, emparejamiento
app/modelos/     todas las consultas SQL, agrupadas por entidad
app/vistas/      plantillas compartidas
app/almacen/     currículums, registros de error y respaldos
htdocs/          lo único público
herramientas/    scripts que corren en la máquina local, nunca en el servidor
sql/             esquema, datos iniciales y datos de prueba
```

**Toda página empieza con un solo require** de `app/nucleo/inicio.php`, y con eso ya tiene sesión
segura, cabeceras de seguridad, manejo de errores, CSRF validado y funciones de permisos.

La tabla completa de funciones compartidas está en `CLAUDE.md`, sección 7. **Consultala antes de
escribir una función nueva**: tener dos maneras de comprobar lo mismo es como aparecen los huecos.

---

## 3.1. Herramientas de revisión

En `herramientas/` hay dos scripts de Python que **no forman parte del sitio** y no se suben al
servidor. Corren en la máquina de quien desarrolla:

```bash
python herramientas/revision_seguridad.py "C:/proyecto etica"
```

Comprueba, leyendo el código, los 14 puntos de la revisión de seguridad: SQL sin concatenación,
salida escapada, CSRF en todos los formularios, control de acceso en cada pantalla, manejo de
archivos, consentimiento, ofertas vencidas y no verificadas fuera de la vista pública, y más.
**Corrélo antes de cada entrega y después de cualquier cambio grande.** Si sale un `[X]`, hay
que revisarlo a mano: puede ser un problema real o un límite del detector, pero no se ignora.

```bash
python herramientas/revisar_php.py "C:/proyecto etica"
```

Comprueba que los bloques `{}`, `()` y `[]` estén balanceados en todos los archivos. Útil cuando
no hay PHP instalado para correr `php -l`.

```bash
python herramientas/prueba_de_humo.py https://tusitio.infinityfreeapp.com
```

La única que mira **el sitio publicado** en lugar del código. Es de solo lectura: no crea cuentas
ni cambia nada. Comprueba que las carpetas privadas (`app/`, `vendor/`, los currículums) no se
puedan abrir, que las páginas públicas respondan (incluida una búsqueda con texto), que lleguen las
cabeceras de seguridad y que `instalar.php` y `diagnostico.php` ya no estén. **Corrélo después de
cada subida por FTP.** Si el hosting contesta con su página anti-robots, el propio archivo explica
cómo pasarle la cookie `__test` del navegador.

Las otras dos herramientas leen el código, y leer no alcanza: las dos fallas críticas de la
auditoría del 2026-10-01 pasaban la revisión de los 14 puntos (ver D-049 en `CLAUDE.md` y
`AUDITORIA.md`).

**Cuando haya PHP en la máquina, además:**

```bash
php -l archivo.php
```

---

## 4. Mantenimiento

### Cada semana
- **Panel → Mantenimiento**: revisar el estado del sistema y marcar las ofertas vencidas.
- **Panel → Respaldo**: bajar una copia. Guardar las últimas tres.

### Cada mes
- Copiar por FTP la carpeta `app/almacen/cv`. **El respaldo de la base no incluye los archivos de
  currículum**: un respaldo completo son las dos cosas.
- Revisar `app/almacen/logs/` por errores repetidos.
- Limpiar registros viejos desde Mantenimiento.

### Cada tres meses
- Actualizar el registro de reclutadores contra el listado del Ministerio de Trabajo.
- **Verificar los teléfonos de denuncia de `htdocs/alertas.php`.** Un número equivocado ahí es
  alguien en problemas llamando a un teléfono que no contesta.
- Revisar el consumo de archivos (inodes) en el panel del hosting.

### Una vez al año
- Probar una restauración completa en una base de prueba. Un respaldo que nunca se restauró no es
  un respaldo.

---

## 5. Los límites que hay que vigilar

InfinityFree da 30 000 archivos y 30 000 peticiones diarias en toda la cuenta.

**Archivos:** el código son unos 100. Cada currículum es 1 archivo más. En la práctica el techo
está en unos **25 000 currículums**, que este proyecto no va a alcanzar en años. Igual conviene
mirarlo en el panel del hosting cada tanto.

**Peticiones:** cada archivo que carga una página cuenta por separado. El sitio está hecho con un
solo CSS, un solo JS e íconos dibujados dentro del HTML, así que una visita cuesta unas 3
peticiones en vez de 10. Eso da margen para unas **7 000 a 10 000 visitas diarias**. Si el sitio se
acerca a ese número, es señal de que hay que mudarse a un servidor propio, no de que algo esté mal.

---

## 6. Lo que este hosting no permite cumplir

| Requisito | Por qué no se cumple | Cómo se resuelve al migrar |
|---|---|---|
| **Currículums fuera de la carpeta pública** | `open_basedir` encierra a PHP en `htdocs` | Mover `app/` y `vendor/` afuera y devolverle un nivel a los `require` (D-044) |
| Usuario de MySQL con permisos mínimos | InfinityFree da un solo usuario con todos los privilegios y no deja crear otros | `GRANT` con solo SELECT, INSERT, UPDATE, DELETE sobre esta base |
| Tareas programadas | No hay cron | Ver sección 7 |
| Envío de correo | `mail()` deshabilitado y SMTP bloqueado | Ver sección 7 |

---

## 7. El día que se migre a un servidor propio

Estas son las cosas que se pueden mejorar, en orden de importancia. **Ninguna requiere reescribir
la aplicación**: todo está escrito en PHP y SQL estándar a propósito.

1. **Sacar `app/` y `vendor/` de la carpeta pública.** Es lo más importante de esta lista.
   En InfinityFree tuvieron que quedar adentro porque `open_basedir` encierra a PHP en `htdocs`
   (decisión D-044), y eso dejó la protección de los currículums dependiendo de unos `.htaccess`.
   En un servidor propio:
   - Mover `app/` y `vendor/` fuera de la raíz pública.
   - Devolverle un nivel a los `require`: `__DIR__ . '/app/...'` → `__DIR__ . '/../app/...'` en
     las 8 páginas de la raíz, y `'/../app/...'` → `'/../../app/...'` en las 33 de subcarpetas.
   - Comprobar que el sitio carga y que `/app/` ya no existe como dirección web.

2. **Usuario de MySQL restringido.** Crear uno con solo los permisos necesarios y cambiar
   `config.php`. Cinco minutos, y cierra el hueco más grande que deja el hosting gratuito.

3. **Cron para lo periódico.** Hoy el vencimiento de ofertas y la limpieza se disparan a mano
   desde el panel (decisión D-003). Las funciones ya existen y están separadas:
   `vencer_ofertas_publicadas()`, `limpiar_intentos_viejos()`, `limpiar_restablecimientos_viejos()`.
   Basta un script que las llame y un cron diario. **Ojo:** el vencimiento *ya* protege a la
   persona sin cron, porque la consulta pública filtra por fecha (decisión D-018). El cron es para
   que el panel diga la verdad, no para la seguridad.

4. **Correo.** Habilitaría la recuperación de contraseña por email y quitaría la carga de trabajo
   del restablecimiento asistido (decisión D-005). El flujo actual seguiría sirviendo para quien
   no tiene correo, que no es poca gente en la población objetivo: **conviene conservarlo**, no
   reemplazarlo.

5. **Automatizar la importación de fuentes externas.** `herramientas/generar_csv_adzuna.php` ya
   hace la consulta y arma el CSV; hoy se corre a mano porque las conexiones salientes del hosting
   no son confiables (decisión D-004). En un servidor propio ese mismo script se puede automatizar
   sin tocarle una línea. **Lo que no cambia:** todo lo importado sigue entrando como `pendiente`
   y necesita verificación humana (decisión D-021). Eso no es una limitación del hosting, es una
   regla del proyecto.

   **Actualización del 2026-10-03:** Adzuna no cubre Guatemala, y desde D-054 las ofertas son de
   empleo en Guatemala. No hay una fuente legal que se pueda conectar sola (sección 6 del
   `CLAUDE.md`). Lo que sí se puede automatizar es lo que una institución acepte compartir: si el
   Ministerio de Trabajo o la OIM firman el convenio (`documentos/carta_convenio.md`) y mandan un
   archivo periódico, se arma un script que lo convierta al CSV de `admin/importar_csv.php` (con
   las columnas `departamento_codigo` y `forma_postulacion`), y en un servidor propio corre solo.

6. **Cabecera CSP.** Si el hosting deja de inyectar JavaScript en las respuestas, se puede
   endurecer todavía más la línea de CSP en `app/nucleo/inicio.php`.

7. **Pantalla para gestionar rubros.** Hoy agregar un oficio requiere una consulta en phpMyAdmin
   (decisión D-022). Para que la institución no dependa de alguien con acceso a la base, conviene
   construirla.

---

## 8. Las reglas que no se tocan

Están completas en `CLAUDE.md`, sección 3. Las cuatro que más fácil se rompen sin querer al
agregar una función nueva:

- **Regla 5:** toda autorización se comprueba en el servidor. Ocultar un botón no es control de
  acceso. Cada archivo de `htdocs/admin/` empieza con `requerir_permiso()`.
- **Regla 6:** el consentimiento es por oferta y no se reutiliza. No existe ninguna consulta que
  busque un consentimiento sin decir de qué oferta.
- **Regla 7:** el emparejamiento solo puede usar seis campos. Se comprueba leyendo la firma de
  `evaluar_coincidencia()`.
- **Regla 12:** no prometer lo que no se cumple. Cualquier texto que sugiera que la plataforma
  consigue trabajo está mal escrito y hay que corregirlo.

---

## 9. Credenciales: dónde están y dónde no

| Qué | Dónde está | Dónde NO está |
|---|---|---|
| Base de datos | `app/config/config.php`, solo en el servidor | No en el repositorio (`.gitignore`) |
| Clave de instalación | `app/config/instalacion.txt`, se borra sola al instalar | No en el repositorio |
| Credenciales de Adzuna | `herramientas/adzuna_credenciales.php`, solo en la máquina local | No en el repositorio, no en el servidor |
| Contraseñas de cuentas | Hasheadas con `password_hash()` en la base | En ningún lado en texto plano, ni en la bitácora |
| Códigos de restablecimiento | Hasheados en la base | En ningún lado en texto plano |

**Ningún acceso al hosting ni al FTP está guardado en el proyecto.** Quien entregue el proyecto
tiene que traspasar esas credenciales por fuera, en persona o por un canal seguro, y quien las
reciba debería cambiarlas.

---

## 10. Antes de entregar a la institución

- [ ] Borrar los datos de prueba (instrucciones al final de `sql/datos_prueba.sql`).
- [ ] Revisar que las ofertas del primer lote (`sql/ofertas_reales_2026-10-03.sql`) ya vencieron
      o reemplazarlas por vacantes comprobadas de nuevo en las páginas de las empresas.
- [ ] Mandar la carta de convenio (`documentos/carta_convenio.md`) al Ministerio de Trabajo y a
      la OIM: sin convenio, alguien tiene que buscar y comprobar las ofertas a mano cada semana.
- [ ] Comprobar que `instalar.php` y `diagnostico.php` no están en el servidor.
- [ ] Comprobar que `ENTORNO` dice `produccion` en `config.php`.
- [ ] Cargar el registro real de reclutadores del Ministerio de Trabajo.
- [ ] Verificar los teléfonos de denuncia de `htdocs/alertas.php`. Ahora se pueden tocar para
      llamar: uno equivocado es alguien en problemas llamando a un número que no contesta.
- [ ] Mirar la pregunta 5 de `diagnostico.php` (la IP) y decidir los límites por conexión
      (D1 en `AUDITORIA.md`).
- [ ] `python herramientas/prueba_de_humo.py https://...` termina en "todo en orden".
- [ ] Resolver o descartar con razón los seis puntos de la sección 4 de `AUDITORIA.md`.
- [ ] Crear la cuenta de superadministrador de la institución y desactivar las de prueba.
- [ ] Hacer un respaldo y **probar restaurarlo**.
- [ ] Entregar `MANUAL.md` impreso o en PDF a quien vaya a cargar ofertas.
- [ ] Traspasar las credenciales del hosting y del FTP, y que las cambien.
