<?php
/**
 * PÁGINA NO ENCONTRADA
 * -----------------------------------------------------------------
 * La usa Apache (ErrorDocument en htdocs/.htaccess) cuando alguien
 * pide una dirección que no existe. En español, sin tecnicismos y con
 * una salida clara.
 */

require __DIR__ . '/app/nucleo/inicio.php';

http_response_code(404);

$titulo_pagina = 'No encontramos esa página';
$texto = 'La dirección que abriste no existe o cambió de lugar. '
       . 'Puede ser que el enlace esté incompleto.';

require RAIZ_APP . '/vistas/pagina_aviso.php';
