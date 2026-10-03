<?php
/**
 * EL LOGO DE MJOB, DIBUJADO UNA SOLA VEZ POR PÁGINA
 * -----------------------------------------------------------------
 * Lo incluye la cabecera, al principio del <body>. Después, en
 * cualquier lugar de la página, el logo se pone así:
 *
 *     <svg class="marca__logo" viewBox="0 0 120 70" aria-hidden="true">
 *       <use href="#mj-marca" xlink:href="#mj-marca"></use>
 *     </svg>
 *
 * Es un redibujo en vector del logo original (marca/mjob-logo-original.png):
 * la M, el avión con su estela y la persona. El original pesa 495 KB;
 * este, poco más de 1 KB, y no hace falta descargarlo aparte.
 *
 * Los colores NO están escritos acá: salen de las variables --logo-*
 * de estilo.css, así el logo se aclara solo en el modo noche (el azul
 * marino del original desaparecería sobre fondo oscuro).
 *
 * El <svg> de afuera no puede llevar display:none: algunos navegadores
 * dejan de pintar los degradados que están adentro. Por eso mide cero
 * y queda fuera de la vista con la clase .svg-definiciones.
 */
?>
<svg class="svg-definiciones" width="0" height="0" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="mj-degrade" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" class="mj-tono-1"></stop>
      <stop offset=".55" class="mj-tono-2"></stop>
      <stop offset="1" class="mj-tono-3"></stop>
    </linearGradient>
    <linearGradient id="mj-pliegue" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" class="mj-tono-4"></stop>
      <stop offset="1" class="mj-tono-5"></stop>
    </linearGradient>
    <mask id="mj-corte" maskUnits="userSpaceOnUse" x="0" y="0" width="120" height="70">
      <rect width="120" height="70" fill="#fff"></rect>
      <path d="M26 46.5C44 42.5 66 35 95 19.5" fill="none" stroke="#000" stroke-width="3.2" stroke-linecap="round"></path>
    </mask>
  </defs>
  <symbol id="mj-marca" viewBox="0 0 120 70">
    <g mask="url(#mj-corte)">
      <path fill="url(#mj-degrade)" d="M3 10a7 7 0 0 1 7-7h6.5a7 7 0 0 1 5.2 2.3L43 28.5 64.3 5.3A7 7 0 0 1 69.5 3H76a7 7 0 0 1 7 7v53a4 4 0 0 1-4 4H65a4 4 0 0 1-4-4V33.5L43 51 25 33.5V63a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4z"></path>
      <path fill="url(#mj-pliegue)" opacity=".55" d="M14 26.5c0-4 4.8-6 7.6-3.2L25 26.7V63a4 4 0 0 1-4 4h-7z"></path>
    </g>
    <path fill="url(#mj-degrade)" d="M56 39.2C70 34.5 82 28.5 94.5 20.2 83 30 70 36.5 56 39.2z"></path>
    <g class="mj-solido">
      <path d="M43 47.2a4.6 4.6 0 1 1 0 9.2 4.6 4.6 0 0 1 0-9.2zm0 1.9a2.7 2.7 0 1 0 0 5.4 2.7 2.7 0 0 0 0-5.4z"></path>
      <path d="M35 67a8 8.5 0 0 1 16 0h-2.8a5.2 6 0 0 0-10.4 0z"></path>
      <path transform="translate(103 13.5) rotate(58) scale(1.08) translate(-12 -12)" d="M12 2c1 0 1.6 1 1.6 2.2V9.5l7.4 4.3v2l-7.4-2.2v4.6l2.2 1.6v1.7L12 20.6l-3.8.9v-1.7l2.2-1.6v-4.6L3 15.8v-2l7.4-4.3V4.2C10.4 3 11 2 12 2z"></path>
    </g>
  </symbol>
</svg>
