# Manual para quien administra la plataforma

Este manual es para la persona de la institución que va a cargar y verificar ofertas.
No hace falta saber de computación. Si algo no se entiende, está mal escrito acá y hay que
corregirlo.

---

## Lo primero: qué es esto y qué no es

Esta plataforma **muestra ofertas de trabajo cuyo origen fue verificado**. Nada más y nada menos.

No consigue trabajo, no hace trámites, no cobra dinero y no representa a nadie. Eso está escrito
en el pie de todas las páginas a propósito, y no hay que quitarlo.

Su trabajo acá es el más importante del sistema: **comprobar de dónde sale cada oferta antes de
que la vea la gente**. Si una oferta falsa llega a publicarse, alguien puede perder dinero que no
tenía o caer en algo peor.

---

## Cómo entrar

1. Abrir `https://[la dirección del sitio]/admin/entrar.php`
2. Escribir su correo y su contraseña.

Si se le olvida la contraseña, tiene que pedirle a la persona responsable del sistema
(el superadministrador) que le genere un código.

**Nunca le dé su contraseña a nadie**, ni por teléfono ni por mensaje. Nadie del sistema se la va
a pedir jamás.

---

## Las cinco cosas que va a hacer seguido

### 1. Cargar una oferta

**Panel → Ofertas → Cargar una oferta**

Llene los datos tal como aparecen en la publicación original. **No invente ni redondee nada**: si
la oferta no dice el salario, deje el campo vacío. Un dato inventado es un dato que la persona va
a creer.

Dos campos son obligatorios y conviene entender por qué:

- **De dónde salió la oferta (la fuente).** Se le muestra a la persona. Sin fuente no se puede
  cargar una oferta.
- **Disponible hasta (fecha de vencimiento).** Cuando llega esa fecha, la oferta **desaparece
  sola** del sitio. Sin esa fecha se quedaría publicada para siempre.

La oferta queda como **pendiente**. Todavía no la ve nadie.

### 2. Verificar una oferta

**Panel → Ofertas → Verificar**

Acá está el corazón del trabajo. La pantalla le va a pedir que marque, una por una, las cosas que
comprobó:

- Que entró a la fuente y la oferta está ahí de verdad.
- Que el empleador existe y se puede comprobar.
- Que no le piden dinero al trabajador.
- Que no piden DPI ni pasaporte antes de una entrevista formal.
- Que los datos que cargamos coinciden con la publicación original.

**Marque solo lo que hizo de verdad.** Queda registrado con su nombre y la fecha. Si no comprobó
algo, no lo marque: mejor una oferta menos que una oferta falsa con nuestro sello.

Si la oferta viene por un reclutador cuya autorización está vencida, **el sistema no la va a dejar
verificar**. Eso es a propósito y no es un error.

### 3. Publicar una oferta

Una vez verificada, aparece el botón **Publicada**. Recién ahí la ve la gente.

Si le falta algún dato, el sistema le va a decir cuál y no la va a dejar publicar.

### 4. Revisar los reportes

**Panel → Reportes**

Cuando alguien avisa que una oferta le parece rara, aparece acá. Cada reporte hay que mirarlo.

**Ninguna oferta se retira sola por acumular reportes.** Es una decisión suya. Si fuera automático,
cualquiera podría tumbar ofertas legítimas reportándolas muchas veces.

Si un reporte tiene razón:
1. Vaya a **Ofertas**, busque esa oferta y póngala **En revisión** o **Retirada**, escribiendo el
   motivo.
2. Vuelva a **Reportes** y marque el reporte como **Resuelto**, contando qué comprobó.

Lo que escriba queda guardado. Escríbalo pensando en alguien que lo lea dentro de un año.

### 5. Mantener el registro de reclutadores

**Panel → Reclutadores autorizados**

Este es el dato más valioso de toda la plataforma. Es lo que permite que cualquier persona escriba
el nombre de quien la contactó por WhatsApp y sepa si está autorizado.

- Cárguelo del listado público del Ministerio de Trabajo.
- En **"De dónde sacaste este dato"** escriba de qué documento o página lo sacó, con la fecha. Si
  alguien pregunta "¿y ustedes cómo saben?", la respuesta tiene que estar ahí.
- Si un reclutador opera con otro nombre comercial, agréguelo en **"Otros nombres"**. La gente lo
  va a buscar por el nombre con el que la contactaron.
- **Revise el listado del Ministerio cada tres meses** y actualice lo que haya cambiado.

---

## Una vez por semana

**Panel → Mantenimiento**

Entre y revise dos cosas:

1. **Cómo está el sistema.** Si algo sale en rojo, avise a la persona responsable.
2. **Ofertas que ya pasaron su fecha.** Apriete el botón para ponerlas al día. Esas ofertas ya no
   se ven en el sitio (eso pasa solo), esto es para que el listado interno diga la verdad.

**Panel → Respaldo** → baje una copia de la base de datos y guárdela en una computadora de la
institución. No en la suya personal, y no la mande por WhatsApp: tiene datos de gente.

---

## Cuando alguien no puede entrar a su cuenta

**Panel → Restablecer contraseñas**

1. **Primero asegúrese de que está hablando con quien dice ser.** Un código en manos equivocadas
   es la cuenta de esa persona en manos equivocadas.
2. Escriba el correo de la persona y genere el código.
3. El código se muestra **una sola vez**. Anótelo y dígaselo por teléfono o en persona.
4. Dígale que entre a la página de poner contraseña nueva y escriba su correo y ese código.

El código sirve **una hora y una sola vez**. Si se pierde, genere otro: el anterior deja de servir
en ese momento.

**Usted no puede ver ni cambiar la contraseña de nadie.** Solo puede darle la posibilidad de que
la persona ponga una nueva. Está hecho así a propósito.

---

## Los currículums de la gente

Cuando alguien se postula a una oferta, **autoriza compartir su currículum con esa oferta y solo
con esa**.

- Puede verlos en **Ofertas → Postulaciones**.
- Cada vez que descarga un currículum, **queda registrado con su nombre y la fecha**.
- Usar esos datos para cualquier otra cosa rompe el permiso que la persona dio. No los mande a
  otros empleadores, no los guarde en su computadora personal, no los comparta.

Esta gente confió en nosotros con información que en malas manos la puede perjudicar. Trátela como
trataría la de su familia.

---

## Cosas que nunca hay que hacer

- **Nunca** marcar una comprobación que no hizo.
- **Nunca** publicar una oferta cuya fuente no pudo comprobar.
- **Nunca** compartir su contraseña, ni con un compañero.
- **Nunca** usar la palabra "verificada" en un texto si la oferta no pasó el proceso.
- **Nunca** prometerle trabajo a nadie. La plataforma muestra ofertas, no consigue empleo.
- **Nunca** dejar el respaldo de la base de datos en una carpeta compartida o en un correo.

---

## Si algo no funciona

1. Mire **Panel → Mantenimiento → Cómo está el sistema**. Ahí sale en palabras qué está mal.
2. Si el sitio no abre o algo se ve raro, avise a la persona responsable del sistema. Anote qué
   estaba haciendo cuando pasó: eso es lo que más ayuda a encontrar el problema.
3. **No borre nada** para "arreglarlo". Casi siempre empeora las cosas y hay cosas que no se
   pueden recuperar.
