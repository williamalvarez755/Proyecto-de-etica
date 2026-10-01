<?php
/**
 * CONEXIÓN A LA BASE DE DATOS
 * -----------------------------------------------------------------
 * Una sola función, bd(), que devuelve la conexión PDO.
 *
 * Dos cosas importantes:
 *
 * 1. La conexión es PEREZOSA: no se abre al cargar la página, se abre
 *    la primera vez que alguien pide bd(). Una página que no consulta
 *    nada no gasta una conexión. En un hosting compartido y con tope
 *    de peticiones diarias, eso se nota.
 *
 * 2. EMULATE_PREPARES en false. Con la emulación activada, PDO arma la
 *    consulta como texto antes de mandarla, y eso ha tenido agujeros de
 *    inyección conocidos. Con false, los datos viajan aparte de la
 *    consulta y el motor nunca los interpreta como SQL.
 */

function bd(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        BD_SERVIDOR,
        BD_NOMBRE
    );

    $opciones = [
        // Los errores llegan como excepciones y los atrapa errores.php.
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Los resultados vienen como arreglos con nombre: $fila['correo'].
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Consultas preparadas de verdad, no emuladas.
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Sin conexiones persistentes: en hosting compartido dan más
        // problemas de los que resuelven.
        PDO::ATTR_PERSISTENT         => false,
    ];

    $conexion = new PDO($dsn, BD_USUARIO, BD_CLAVE, $opciones);

    return $conexion;
}


/**
 * Atajo para las consultas que se repiten en todo el proyecto.
 * SIEMPRE con parámetros: en este proyecto no existe una sola consulta
 * armada pegando texto.
 *
 *   consultar('SELECT * FROM ofertas WHERE estado = ?', ['publicada']);
 */
function consultar(string $sql, array $parametros = []): PDOStatement
{
    $sentencia = bd()->prepare($sql);
    $sentencia->execute($parametros);
    return $sentencia;
}

/** Devuelve la primera fila, o null si no hay ninguna. */
function consultar_una(string $sql, array $parametros = []): ?array
{
    $fila = consultar($sql, $parametros)->fetch();
    return $fila === false ? null : $fila;
}

/** Devuelve todas las filas. */
function consultar_todas(string $sql, array $parametros = []): array
{
    return consultar($sql, $parametros)->fetchAll();
}

/** Devuelve el primer valor de la primera fila (para contar, por ejemplo). */
function consultar_valor(string $sql, array $parametros = [])
{
    $valor = consultar($sql, $parametros)->fetchColumn();
    return $valor === false ? null : $valor;
}

/**
 * Consulta con paginación.
 *
 * Existe porque LIMIT y OFFSET no se pueden pegar como texto sin
 * romper la regla de "ni una concatenación de SQL". Acá van como
 * parámetros de tipo entero, igual que cualquier otro dato.
 *
 * Los parámetros de esta consulta van con NOMBRE, no con signos de
 * pregunta: PDO no deja mezclar los dos estilos.
 *
 *   consultar_paginado(
 *       'SELECT * FROM ofertas WHERE estado = :estado ORDER BY id DESC',
 *       [':estado' => 'publicada'], 10, 0
 *   );
 */
/**
 * Los parámetros de una búsqueda de texto que se usa en VARIAS columnas.
 *
 * Con consultas preparadas de verdad (EMULATE_PREPARES en false) PDO no
 * deja usar el mismo parámetro con nombre dos veces en una consulta: si
 * se escribe "titulo LIKE :texto OR empleador LIKE :texto", la consulta
 * revienta con "Invalid parameter number". Por eso cada columna lleva
 * su propio nombre (:texto1, :texto2...) con el mismo valor.
 *
 *   '(titulo LIKE :texto1 OR empleador LIKE :texto2)'
 *   $parametros += parametros_de_texto($texto, 2);
 */
function parametros_de_texto(string $texto, int $cuantas): array
{
    $parametros = [];
    for ($i = 1; $i <= $cuantas; $i++) {
        $parametros[':texto' . $i] = '%' . $texto . '%';
    }
    return $parametros;
}


function consultar_paginado(string $sql, array $parametros, int $limite, int $desde = 0): array
{
    $sentencia = bd()->prepare($sql . ' LIMIT :limite OFFSET :desde');

    foreach ($parametros as $nombre => $valor) {
        $sentencia->bindValue(':' . ltrim((string) $nombre, ':'), $valor);
    }
    $sentencia->bindValue(':limite', max(1, $limite), PDO::PARAM_INT);
    $sentencia->bindValue(':desde',  max(0, $desde),  PDO::PARAM_INT);

    $sentencia->execute();
    return $sentencia->fetchAll();
}
