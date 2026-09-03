/* ==================================================================
   JavaScript del sitio  (único archivo)
   ------------------------------------------------------------------
   Regla: la página tiene que funcionar completa SIN este archivo.
   Acá solo van comodidades, nunca validaciones de seguridad: eso se
   comprueba siempre en el servidor.

   Se carga al final del HTML, así que el documento ya existe.
   ================================================================== */

(function () {
  'use strict';

  /* ----------------------------------------------------------------
     Mostrar u ocultar la contraseña.
     Importa más de lo que parece: mucha gente va a escribir desde un
     teléfono, con el sol de frente, y no hay recuperación por correo.
     Una contraseña mal tecleada al registrarse deja a la persona
     afuera y tiene que pedirle ayuda a un administrador.
     ---------------------------------------------------------------- */
  var campos = document.querySelectorAll('input[type="password"][data-ver]');

  Array.prototype.forEach.call(campos, function (campo) {
    var boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'boton boton--secundario boton--ancho';
    boton.textContent = 'Mostrar la contraseña';
    boton.setAttribute('aria-pressed', 'false');

    boton.addEventListener('click', function () {
      var oculta = campo.type === 'password';
      campo.type = oculta ? 'text' : 'password';
      boton.textContent = oculta ? 'Ocultar la contraseña' : 'Mostrar la contraseña';
      boton.setAttribute('aria-pressed', oculta ? 'true' : 'false');
      campo.focus();
    });

    campo.insertAdjacentElement('afterend', boton);
  });


  /* ----------------------------------------------------------------
     Confirmar antes de una acción que no se puede deshacer.
     El servidor no depende de esto: es solo para no borrar algo de
     un dedazo.
     ---------------------------------------------------------------- */
  var formularios = document.querySelectorAll('form[data-confirmar]');

  Array.prototype.forEach.call(formularios, function (formulario) {
    formulario.addEventListener('submit', function (evento) {
      if (!window.confirm(formulario.getAttribute('data-confirmar'))) {
        evento.preventDefault();
      }
    });
  });

}());
