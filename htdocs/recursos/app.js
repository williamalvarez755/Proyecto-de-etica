/* ==================================================================
   JavaScript del sitio  (único archivo)
   ------------------------------------------------------------------
   Regla: la página tiene que funcionar completa SIN este archivo.
   Acá solo van comodidades, nunca validaciones de seguridad: eso se
   comprueba siempre en el servidor.

   Está escrito a propósito en JavaScript "viejo" (var, function, sin
   flechas ni clases): mucha gente entra desde teléfonos de gama baja
   con navegadores desactualizados, y un solo error de sintaxis haría
   que no funcionara NADA de este archivo. Cada parte comprueba antes
   que el navegador tenga lo que necesita, y si no lo tiene, no hace
   nada: la página sigue andando como sin JavaScript.

   Se carga al final del HTML, así que el documento ya existe.
   ================================================================== */

(function () {
  'use strict';

  var raiz = document.documentElement;
  raiz.classList.add('js');
  var reducirMovimiento = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function todos(selector, dentro) {
    return Array.prototype.slice.call((dentro || document).querySelectorAll(selector));
  }

  /* ----------------------------------------------------------------
     Modo noche (D-051).
     El tema lo pone PHP en <html data-tema="..."> leyendo una cookie,
     así cada página llega ya con sus colores, sin destello blanco.
     Acá solo vive el botón: cambia el atributo en el momento y guarda
     la elección en esa cookie para las páginas que siguen.

     La cookie dice "claro" u "oscuro" y nada más: no identifica a
     nadie y no viaja a ningún otro sitio.

     Si la persona nunca tocó el botón, se sigue lo que diga su
     teléfono, y el botón se acomoda solo si el teléfono cambia.
     ---------------------------------------------------------------- */
  var botonTema = document.querySelector('[data-tema-boton]');
  if (botonTema) {
    var textoTema = botonTema.querySelector('.boton-tema__texto');
    var temaDelSistema = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var COLOR_BARRA = { claro: '#FFFFFF', oscuro: '#0A1120' };

    var temaActual = function () {
      var elegido = raiz.getAttribute('data-tema');
      if (elegido === 'claro' || elegido === 'oscuro') {
        return elegido;
      }
      return (temaDelSistema && temaDelSistema.matches) ? 'oscuro' : 'claro';
    };

    var pintarBotonTema = function () {
      var oscuro = temaActual() === 'oscuro';
      botonTema.setAttribute('data-estado', oscuro ? 'oscuro' : 'claro');
      if (textoTema) {
        textoTema.textContent = oscuro ? 'Modo día' : 'Modo noche';
      }
      botonTema.setAttribute('title', oscuro ? 'Pasar a colores claros' : 'Pasar a colores oscuros');
    };

    // La barra del navegador del teléfono también cambia de color.
    var pintarBarraDelNavegador = function (tema) {
      todos('meta[name="theme-color"]').forEach(function (meta) {
        meta.parentNode.removeChild(meta);
      });
      var meta = document.createElement('meta');
      meta.name = 'theme-color';
      meta.content = COLOR_BARRA[tema];
      document.head.appendChild(meta);
    };

    botonTema.addEventListener('click', function () {
      var nuevo = temaActual() === 'oscuro' ? 'claro' : 'oscuro';
      raiz.setAttribute('data-tema', nuevo);
      document.cookie = 'tema=' + nuevo + '; path=/; max-age=31536000; samesite=lax'
        + (window.location.protocol === 'https:' ? '; secure' : '');
      pintarBotonTema();
      pintarBarraDelNavegador(nuevo);
    });

    if (temaDelSistema) {
      if (temaDelSistema.addEventListener) {
        temaDelSistema.addEventListener('change', pintarBotonTema);
      } else if (temaDelSistema.addListener) {
        temaDelSistema.addListener(pintarBotonTema);
      }
    }

    pintarBotonTema();
    botonTema.hidden = false;
  }


  function formatoPeso(bytes) {
    if (bytes < 1024 * 1024) {
      return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }
    return (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
  }


  /* ----------------------------------------------------------------
     Mostrar u ocultar la contraseña.
     Importa más de lo que parece: mucha gente va a escribir desde un
     teléfono, con el sol de frente, y no hay recuperación por correo.
     Una contraseña mal tecleada al registrarse deja a la persona
     afuera y tiene que pedirle ayuda a un administrador.
     ---------------------------------------------------------------- */
  todos('input[type="password"][data-ver]').forEach(function (campo) {
    var boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'boton boton--secundario boton--ancho separado-poco';
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
     Antes de enviar un formulario:
       1. Si la acción no se puede deshacer, se pregunta (data-confirmar).
       2. El botón que se apretó muestra que está trabajando, y un
          segundo toque no vuelve a mandar el formulario.

     Lo segundo importa con internet lento: la persona no ve que pase
     nada, vuelve a apretar, y se postula dos veces o sube el archivo
     dos veces. El servidor igual lo controla; esto es para que la
     persona entienda que su toque llegó.

     No se usa "disabled" en el botón: un botón deshabilitado no viaja
     con el formulario, y algunos llevan name y value (accion=quitar).
     ---------------------------------------------------------------- */
  var ultimoBoton = null;
  document.addEventListener('click', function (evento) {
    var boton = evento.target.closest ? evento.target.closest('button[type="submit"], button:not([type])') : null;
    if (boton && boton.form) {
      ultimoBoton = boton;
    }
  });

  todos('form').forEach(function (formulario) {
    // Los que se actualizan en el lugar tienen su propio manejo, abajo.
    if (formulario.hasAttribute('data-en-vivo')) {
      return;
    }

    formulario.addEventListener('submit', function (evento) {
      var pregunta = formulario.getAttribute('data-confirmar');
      if (pregunta && !window.confirm(pregunta)) {
        evento.preventDefault();
        return;
      }

      if (formulario.getAttribute('data-enviando') === 'si') {
        evento.preventDefault();
        return;
      }
      formulario.setAttribute('data-enviando', 'si');

      var boton = (ultimoBoton && ultimoBoton.form === formulario)
        ? ultimoBoton
        : formulario.querySelector('button[type="submit"], button:not([type])');
      if (boton) {
        boton.setAttribute('aria-busy', 'true');
      }

      // Si el formulario descarga un archivo, la página no cambia:
      // a los pocos segundos el botón vuelve a estar disponible.
      if (formulario.hasAttribute('data-descarga')) {
        setTimeout(function () {
          formulario.removeAttribute('data-enviando');
          if (boton) {
            boton.removeAttribute('aria-busy');
          }
        }, 4000);
      }
    });
  });

  // Si la persona vuelve con el botón "atrás", el navegador puede
  // mostrar la página como quedó, con el botón todavía "trabajando".
  window.addEventListener('pageshow', function () {
    todos('form[data-enviando]').forEach(function (formulario) {
      formulario.removeAttribute('data-enviando');
    });
    todos('[aria-busy="true"]').forEach(function (elemento) {
      elemento.removeAttribute('aria-busy');
    });
  });


  /* ----------------------------------------------------------------
     Imprimir el currículum armado.
     Si el JavaScript no carga, la persona puede imprimir igual desde
     el menú del navegador: por eso el botón no es imprescindible.
     ---------------------------------------------------------------- */
  todos('[data-imprimir]').forEach(function (boton) {
    boton.addEventListener('click', function () {
      window.print();
    });
  });


  /* ----------------------------------------------------------------
     La barra de arriba toma sombra cuando la página se despega del
     borde, para que se entienda que sigue ahí encima del contenido.
     ---------------------------------------------------------------- */
  var barra = document.querySelector('.barra');
  if (barra) {
    var pendiente = false;
    var revisarBarra = function () {
      pendiente = false;
      barra.classList.toggle('barra--elevada', window.pageYOffset > 4);
    };
    window.addEventListener('scroll', function () {
      if (!pendiente) {
        pendiente = true;
        (window.requestAnimationFrame || setTimeout)(revisarBarra);
      }
    }, { passive: true });
    revisarBarra();
  }


  /* ----------------------------------------------------------------
     El aviso de "listo, se guardó" se puede cerrar.
     No se cierra solo, a propósito: hay quien lee despacio.
     ---------------------------------------------------------------- */
  todos('.aviso--flotante').forEach(function (aviso) {
    var texto = document.createElement('span');
    texto.className = 'aviso__texto';
    while (aviso.firstChild) {
      texto.appendChild(aviso.firstChild);
    }
    aviso.appendChild(texto);

    var cerrar = document.createElement('button');
    cerrar.type = 'button';
    cerrar.className = 'aviso__cerrar';
    cerrar.setAttribute('aria-label', 'Cerrar este aviso');
    cerrar.textContent = '×';
    cerrar.addEventListener('click', function () {
      var quitar = function () {
        var contenedor = aviso.parentNode;
        aviso.remove();
        if (contenedor && contenedor.children.length === 0) {
          contenedor.remove();
        }
      };
      if (reducirMovimiento) {
        quitar();
        return;
      }
      aviso.classList.add('aviso--cerrandose');
      aviso.addEventListener('animationend', quitar);
      setTimeout(quitar, 400);   // por si la animación no corre
    });
    aviso.appendChild(cerrar);
  });


  /* ----------------------------------------------------------------
     Aparecer al bajar por la página.
     Solo se marca lo que está FUERA de la pantalla al cargar: lo que
     la persona ya está viendo no se toca, así nunca parpadea.
     ---------------------------------------------------------------- */
  function prepararRevelado(dentro) {
    if (reducirMovimiento || !('IntersectionObserver' in window)) {
      return;
    }
    var alto = window.innerHeight || document.documentElement.clientHeight;
    var observador = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (entrada) {
        if (entrada.isIntersecting) {
          entrada.target.classList.add('revelado');
          entrada.target.classList.remove('por-revelar');
          observador.unobserve(entrada.target);
        }
      });
    }, { rootMargin: '0px 0px -40px 0px' });

    var marcados = [];
    todos('.oferta, .paso, .senal, .tarjeta, .cifra, .mosaico', dentro).forEach(function (elemento) {
      if (elemento.getBoundingClientRect().top > alto) {
        elemento.classList.add('por-revelar');
        observador.observe(elemento);
        marcados.push(elemento);
      }
    });

    // Red de seguridad: pase lo que pase, a los pocos segundos todo
    // queda visible. Ningún contenido puede quedar escondido porque
    // el navegador no avisó que la persona bajó por la página.
    setTimeout(function () {
      marcados.forEach(function (elemento) {
        elemento.classList.add('revelado');
        elemento.classList.remove('por-revelar');
        observador.unobserve(elemento);
      });
    }, 2500);
  }
  prepararRevelado(document);


  /* ----------------------------------------------------------------
     Contraseña nueva: cuánto le falta para el largo mínimo, y si la
     confirmación coincide. Se avisa MIENTRAS se escribe, para no
     enterarse recién después de enviar con datos contados.
     ---------------------------------------------------------------- */
  todos('input[data-medir]').forEach(function (campo) {
    var minimo = parseInt(campo.getAttribute('minlength'), 10) || 10;

    var medidor = document.createElement('div');
    medidor.className = 'medidor';
    medidor.innerHTML = '<div class="medidor__barra"><div class="medidor__relleno"></div></div>'
      + '<span class="medidor__texto" aria-live="polite"></span>';
    var relleno = medidor.querySelector('.medidor__relleno');
    var texto = medidor.querySelector('.medidor__texto');

    var actualizar = function () {
      var largo = campo.value.length;
      var listo = largo >= minimo;
      relleno.style.width = Math.min(100, Math.round(largo / minimo * 100)) + '%';
      medidor.classList.toggle('medidor--listo', listo);
      if (largo === 0) {
        texto.textContent = 'Tiene que tener al menos ' + minimo + ' caracteres.';
      } else if (listo) {
        texto.textContent = '✓ Ya tiene el largo suficiente.';
      } else {
        var faltan = minimo - largo;
        texto.textContent = 'Te falta' + (faltan === 1 ? '' : 'n') + ' ' + faltan
          + ' caracter' + (faltan === 1 ? '' : 'es') + '.';
      }
    };

    campo.addEventListener('input', actualizar);
    campo.insertAdjacentElement('afterend', medidor);
    actualizar();
  });

  todos('input[data-igual-a]').forEach(function (campo) {
    var original = document.getElementById(campo.getAttribute('data-igual-a'));
    if (!original) {
      return;
    }
    var aviso = document.createElement('span');
    aviso.className = 'coincide';
    aviso.setAttribute('aria-live', 'polite');

    var actualizar = function () {
      if (campo.value === '') {
        aviso.textContent = '';
        aviso.classList.remove('coincide--si');
      } else if (campo.value === original.value) {
        aviso.textContent = '✓ Las dos son iguales.';
        aviso.classList.add('coincide--si');
      } else {
        aviso.textContent = 'Todavía no son iguales.';
        aviso.classList.remove('coincide--si');
      }
    };
    campo.addEventListener('input', actualizar);
    original.addEventListener('input', actualizar);
    campo.insertAdjacentElement('afterend', aviso);
  });


  /* ----------------------------------------------------------------
     Cuántos caracteres quedan en los textos largos.
     ---------------------------------------------------------------- */
  todos('textarea[maxlength]').forEach(function (campo) {
    var maximo = parseInt(campo.getAttribute('maxlength'), 10);
    if (!maximo) {
      return;
    }
    var contador = document.createElement('span');
    contador.className = 'contador';
    var actualizar = function () {
      var largo = campo.value.length;
      contador.textContent = largo + ' de ' + maximo + ' caracteres';
      contador.classList.toggle('contador--cerca', largo > maximo * 0.9);
    };
    campo.addEventListener('input', actualizar);
    campo.insertAdjacentElement('afterend', contador);
    actualizar();
  });


  /* ----------------------------------------------------------------
     Elegir el archivo del currículum.
     Muestra el nombre y el peso, y avisa ANTES de subir si pesa de
     más o no es PDF ni Word: con datos contados, subir 8 MB para que
     el servidor diga "muy grande" es plata perdida. El servidor lo
     vuelve a revisar todo igual, mirando el contenido real.
     ---------------------------------------------------------------- */
  todos('.zona-archivo').forEach(function (zona) {
    var campo = zona.querySelector('input[type="file"]');
    var elegido = zona.querySelector('.zona-archivo__elegido');
    if (!campo || !elegido) {
      return;
    }
    var maximo = parseInt(campo.getAttribute('data-max-bytes'), 10) || 0;

    campo.addEventListener('change', function () {
      zona.classList.remove('zona-archivo--lista', 'zona-archivo--mal');
      campo.setCustomValidity('');
      var archivo = campo.files && campo.files[0];
      if (!archivo) {
        elegido.textContent = '';
        return;
      }
      var nombre = archivo.name.toLowerCase();
      var problema = '';
      if (!/\.(pdf|docx)$/.test(nombre)) {
        problema = 'Ese archivo no es PDF ni Word (.docx). Elegí otro.';
      } else if (maximo && archivo.size > maximo) {
        problema = 'Pesa ' + formatoPeso(archivo.size) + ' y el máximo es ' + formatoPeso(maximo)
          + '. Probá con uno más liviano.';
      }
      if (problema) {
        zona.classList.add('zona-archivo--mal');
        elegido.textContent = problema;
        campo.setCustomValidity(problema);
      } else {
        zona.classList.add('zona-archivo--lista');
        elegido.textContent = '✓ ' + archivo.name + ' (' + formatoPeso(archivo.size) + ')';
      }
    });

    ['dragenter', 'dragover'].forEach(function (tipo) {
      zona.addEventListener(tipo, function () { zona.classList.add('zona-archivo--encima'); });
    });
    ['dragleave', 'drop'].forEach(function (tipo) {
      zona.addEventListener(tipo, function () { zona.classList.remove('zona-archivo--encima'); });
    });
  });


  /* ----------------------------------------------------------------
     Los filtros "de más" del buscador vienen plegados para que en el
     teléfono los resultados se vean sin bajar tanto. En pantalla
     ancha hay lugar: se abren solos.
     ---------------------------------------------------------------- */
  if (window.matchMedia && window.matchMedia('(min-width: 48rem)').matches) {
    todos('details.filtros__mas').forEach(function (detalle) {
      detalle.open = true;
    });
  }


  /* ----------------------------------------------------------------
     Compartir una oferta.
     El enlace de WhatsApp funciona sin JavaScript. Esto agrega
     "Copiar el enlace" y, si el teléfono lo trae, su propio menú de
     compartir.
     ---------------------------------------------------------------- */
  todos('[data-compartir-url]').forEach(function (bloque) {
    var url = bloque.getAttribute('data-compartir-url');
    var titulo = bloque.getAttribute('data-compartir-titulo') || document.title;
    var estado = bloque.querySelector('.compartir__estado');

    var avisar = function (mensaje) {
      if (estado) {
        estado.textContent = mensaje;
      }
    };

    if (navigator.share) {
      var compartir = document.createElement('button');
      compartir.type = 'button';
      compartir.className = 'boton boton--secundario';
      compartir.textContent = 'Compartir…';
      compartir.addEventListener('click', function () {
        navigator.share({ title: titulo, url: url }).catch(function () {});
      });
      bloque.insertBefore(compartir, estado);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      var copiar = document.createElement('button');
      copiar.type = 'button';
      copiar.className = 'boton boton--secundario';
      copiar.textContent = 'Copiar el enlace';
      copiar.addEventListener('click', function () {
        navigator.clipboard.writeText(url).then(function () {
          avisar('✓ Enlace copiado');
        }, function () {
          avisar('No se pudo copiar. Copiá la dirección de arriba del navegador.');
        });
      });
      bloque.insertBefore(copiar, estado);
    }
  });


  /* ----------------------------------------------------------------
     BÚSQUEDAS QUE SE ACTUALIZAN EN EL LUGAR
     ----------------------------------------------------------------
     Los formularios con data-en-vivo="#algo" (buscador de ofertas,
     verificador) traen la página de resultados por detrás y cambian
     solo la parte de los resultados, sin recargar todo.

     Por qué así y no con una "API":
       - El servidor no cambia en nada: es la MISMA página, con los
         mismos controles de seguridad y los mismos límites de uso.
       - Se pide exactamente lo mismo que al apretar "Buscar": no se
         gastan más peticiones del tope diario del hosting. Por eso
         NO se busca mientras se escribe, letra por letra: eso sí
         multiplicaría las peticiones.
       - La dirección del navegador se actualiza, así el enlace de la
         búsqueda se puede guardar o mandar igual que antes.
       - Si algo falla (sin conexión, error), se navega normal.
     ---------------------------------------------------------------- */
  var anuncio = document.getElementById('anuncio');

  function anunciar(mensaje) {
    if (anuncio) {
      anuncio.textContent = '';
      setTimeout(function () { anuncio.textContent = mensaje; }, 50);
    }
  }

  function cargarEnLugar(url, selector, formulario) {
    var destino = document.querySelector(selector);
    if (!destino || !window.fetch || !window.DOMParser || !window.history || !history.replaceState) {
      window.location.href = url;
      return;
    }

    raiz.classList.add('cargando');
    destino.setAttribute('aria-busy', 'true');

    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
      .then(function (respuesta) {
        if (!respuesta.ok) {
          throw new Error('respuesta ' + respuesta.status);
        }
        return respuesta.text();
      })
      .then(function (html) {
        var nuevo = new DOMParser().parseFromString(html, 'text/html').querySelector(selector);
        if (!nuevo) {
          throw new Error('sin resultados en la respuesta');
        }
        destino.innerHTML = nuevo.innerHTML;
        destino.removeAttribute('aria-busy');
        raiz.classList.remove('cargando');
        history.replaceState(null, '', url);

        var mensaje = destino.querySelector('[data-anuncio]');
        anunciar(mensaje ? mensaje.textContent.replace(/\s+/g, ' ').trim() : 'Resultados actualizados.');

        if (!reducirMovimiento) {
          destino.classList.remove('resultado-nuevo');
          void destino.offsetWidth;   // reinicia la animación
          destino.classList.add('resultado-nuevo');
        }

        // Si los resultados quedaron fuera de la pantalla (filtros
        // largos en el teléfono), se baja hasta ellos.
        var arriba = destino.getBoundingClientRect().top;
        if (arriba > window.innerHeight * 0.6 || arriba < 0) {
          destino.scrollIntoView({ behavior: reducirMovimiento ? 'auto' : 'smooth', block: 'start' });
        }

        if (formulario) {
          var boton = formulario.querySelector('[aria-busy="true"]');
          if (boton) {
            boton.removeAttribute('aria-busy');
          }
        }
      })
      .catch(function () {
        // Cualquier problema: se navega como siempre. La persona llega
        // igual a sus resultados, solo que recargando la página.
        window.location.href = url;
      });
  }

  todos('form[data-en-vivo]').forEach(function (formulario) {
    var selector = formulario.getAttribute('data-en-vivo');

    var urlDelFormulario = function () {
      var partes = [];
      todos('input[name], select[name]', formulario).forEach(function (campo) {
        if (campo.name === 'apellido_secundario' || campo.disabled) {
          return;
        }
        if (campo.value !== '') {
          partes.push(encodeURIComponent(campo.name) + '=' + encodeURIComponent(campo.value));
        }
      });
      var accion = formulario.getAttribute('action') || window.location.pathname;
      return accion + (partes.length ? '?' + partes.join('&') : '');
    };

    formulario.addEventListener('submit', function (evento) {
      evento.preventDefault();
      var boton = formulario.querySelector('button[type="submit"], button:not([type])');
      if (boton) {
        boton.setAttribute('aria-busy', 'true');
      }
      cargarEnLugar(urlDelFormulario(), selector, formulario);
    });

    // Cambiar un menú ya busca: es una petición, igual que apretar
    // "Buscar". El texto se busca solo al apretar Enter o el botón.
    todos('select', formulario).forEach(function (menu) {
      menu.addEventListener('change', function () {
        cargarEnLugar(urlDelFormulario(), selector, formulario);
      });
    });
  });

  // Los enlaces dentro de los resultados (páginas, quitar un filtro)
  // también se cargan en el lugar.
  document.addEventListener('click', function (evento) {
    var enlace = evento.target.closest ? evento.target.closest('a[data-en-vivo-enlace]') : null;
    if (!enlace || evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.button !== 0) {
      return;
    }
    var selector = enlace.getAttribute('data-en-vivo-enlace');
    if (!document.querySelector(selector)) {
      return;
    }
    evento.preventDefault();
    var href = enlace.getAttribute('href');

    // Si el enlace quita un filtro, el formulario tiene que reflejarlo.
    var formulario = document.querySelector('form[data-en-vivo="' + selector + '"]');
    if (formulario && window.URL) {
      var parametros = new URL(href, window.location.href).searchParams;
      todos('input[name], select[name]', formulario).forEach(function (campo) {
        if (campo.type !== 'hidden' && campo.name !== 'apellido_secundario') {
          campo.value = parametros.get(campo.name) || '';
        }
      });
    }
    cargarEnLugar(href, selector, null);
  });

}());
