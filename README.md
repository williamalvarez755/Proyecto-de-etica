# Plataforma de ofertas laborales verificadas

Ofertas de trabajo en el extranjero con **origen verificado**, para personas migrantes
guatemaltecas y personas retornadas del occidente del país.

Proyecto de responsabilidad social del curso de Ética Aplicada,
Universidad Rafael Landívar.

---

## El problema

Cuando una persona guatemalteca busca trabajo en el extranjero se entera de las oportunidades
por publicaciones de Facebook, cadenas de WhatsApp o un número que le pasó un conocido, y no
tiene cómo saber si la oferta es real. Hasta agosto de 2025 el Ministerio de Trabajo había
recibido **330 denuncias por estafas con ofertas de trabajo en el extranjero**, y en el **40 %**
de esos casos la persona ya había entregado dinero. La OIM advierte que las ofertas laborales
falsas son una de las formas de captación de trata de personas que más está creciendo.

Sí existen ofertas legítimas: el Ministerio de Trabajo mantiene un registro público de
reclutadores autorizados. Casi nadie sabe que existe, y es un listado de nombres que no le dice
a la persona qué oportunidad le sirve a ella.

## Qué hace la plataforma

- Muestra ofertas **únicamente de fuentes verificadas**, cada una con su fuente, su fecha de
  publicación, su fecha de última verificación, su empleador y quién la verificó.
- Deja consultar, **sin necesidad de cuenta**, si un reclutador que contactó a alguien por fuera
  está en el registro autorizado.
- A quien sube su currículum, le muestra ofertas que coinciden con lo que sabe hacer, explicando
  **en palabras** por qué coinciden y en qué no.

Y lo que **no** hace, dicho en la propia interfaz: no consigue trabajo, no gestiona trámites,
no mueve dinero y no representa legalmente a nadie.

---

## Cómo está hecho

PHP 8 puro, MySQL con PDO, HTML/CSS/JavaScript sin framework.

El stack no es preferencia: lo impone el hosting. El proyecto arranca en InfinityFree (gratuito,
porque no genera ingresos), que no tiene Node, ni cron, ni envío de correo, ni conexiones
salientes confiables, y tiene tope de 30 000 archivos y 30 000 peticiones diarias. Todo está
escrito de forma portable para poder migrar a un servidor propio sin reescribirlo.

```
app/            fuera de la carpeta pública: nada de esto se sirve por web
  config/       configuración única y catálogos
  nucleo/       primitivas compartidas (permisos, CSRF, escape, bitácora, límites)
  modelos/      consultas SQL por entidad
  vistas/       plantillas
  almacen/      currículums, registros y respaldos
htdocs/         lo único público
sql/            esquema y datos iniciales
```

## Seguridad

Guatemala no tiene ley de protección de datos personales. No hay una ley que obligue a cuidar
estos currículums: el cuidado depende enteramente de cómo esté diseñado el sistema.

- Consultas preparadas en absolutamente todas las consultas.
- Token CSRF validado **automáticamente** en toda petición POST.
- Autorización comprobada siempre en el servidor. Ocultar un botón no es control de acceso.
- Contraseñas con `password_hash()`; cookies de sesión `httponly`, `secure` y `samesite`.
- Cabeceras de seguridad (CSP incluida) enviadas desde PHP y desde `.htaccess`.
- Los currículums se guardan fuera de la carpeta pública y se sirven solo por un script que
  comprueba sesión y permiso.
- Sin puertas traseras: no hay contraseñas maestras, ni usuarios ocultos, ni credenciales en el
  código, ni accesos "solo para pruebas".

Datos que el sistema **no pide ni guarda en ninguna parte**: DPI, pasaporte, situación
migratoria, visa, datos bancarios, fotografía, edad, sexo, departamento de origen y apellido.
Los últimos, además, para que el emparejamiento no pueda discriminar aunque quisiera.

## Instalación

1. Crear la base de datos en el panel del hosting.
2. Importar `sql/esquema.sql` y después `sql/datos_iniciales.sql` en phpMyAdmin.
3. Copiar `app/config/config.ejemplo.php` como `app/config/config.php` y llenarlo.
   **Ese archivo nunca se sube al repositorio.**
4. Subir por FTP la carpeta `app/` a la raíz de la cuenta (al lado de `htdocs`, no adentro) y el
   contenido de `htdocs/` dentro de `htdocs`.
5. Abrir `/diagnostico.php` para comprobar el servidor, y borrarlo después.
6. Crear por FTP `app/config/instalacion.txt` con una frase larga, abrir `/instalar.php` para
   crear la cuenta responsable, y borrar `instalar.php`.

## Estado

En construcción, por fases. El detalle de en qué va, las decisiones tomadas y por qué se
descartaron las alternativas está en [CLAUDE.md](CLAUDE.md).
