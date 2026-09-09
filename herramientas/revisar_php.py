"""
Verificador estático simple de archivos PHP.

No es un parser de PHP. Hace lo que atrapa la mayoría de errores reales
de sintaxis cuando no se puede correr `php -l`:

  1. Recorre solo las zonas de código (entre <?php y ?>), saltándose el
     HTML de afuera.
  2. Ignora comentarios y el contenido de las cadenas de texto.
  3. Comprueba que {} () [] queden balanceados.
  4. Comprueba las estructuras alternativas (if: ... endif;).
  5. Avisa si un archivo no empieza con <?php.
"""

import sys
import re
from pathlib import Path

APERTURA = {'{': '}', '(': ')', '[': ']'}
CIERRE = {v: k for k, v in APERTURA.items()}


def analizar(texto):
    """Devuelve (errores, codigo_limpio) del archivo."""
    errores = []
    pila = []
    limpio = []

    i = 0
    n = len(texto)
    en_php = False
    linea = 1

    while i < n:
        c = texto[i]

        if c == '\n':
            linea += 1

        if not en_php:
            if texto.startswith('<?php', i) or texto.startswith('<?=', i):
                en_php = True
                i += 5 if texto.startswith('<?php', i) else 3
                continue
            i += 1
            continue

        # dentro de PHP
        if texto.startswith('?>', i):
            en_php = False
            i += 2
            continue

        # comentario de una linea
        if texto.startswith('//', i) or c == '#':
            fin = texto.find('\n', i)
            i = n if fin == -1 else fin
            continue

        # comentario de bloque
        if texto.startswith('/*', i):
            fin = texto.find('*/', i + 2)
            if fin == -1:
                errores.append(f'linea {linea}: comentario /* sin cerrar')
                break
            linea += texto.count('\n', i, fin)
            i = fin + 2
            continue

        # cadenas
        if c in ('"', "'"):
            comilla = c
            j = i + 1
            while j < n:
                if texto[j] == '\\':
                    j += 2
                    continue
                if texto[j] == comilla:
                    break
                if texto[j] == '\n':
                    linea += 1
                j += 1
            if j >= n:
                errores.append(f'linea {linea}: cadena {comilla} sin cerrar')
                break
            i = j + 1
            continue

        # delimitadores
        if c in APERTURA:
            pila.append((c, linea))
            limpio.append(c)
        elif c in CIERRE:
            if not pila:
                errores.append(f'linea {linea}: sobra un "{c}"')
            else:
                abierto, ln = pila.pop()
                if APERTURA[abierto] != c:
                    errores.append(
                        f'linea {linea}: se esperaba "{APERTURA[abierto]}" '
                        f'(abierto en linea {ln}) y vino "{c}"'
                    )
            limpio.append(c)
        else:
            limpio.append(c)

        i += 1

    for abierto, ln in pila:
        errores.append(f'linea {ln}: quedo sin cerrar un "{abierto}"')

    return errores


def revisar_alternativas(texto):
    """Compara la cantidad de aperturas y cierres de la sintaxis if: ... endif;"""
    errores = []
    sin_comentarios = re.sub(r'/\*.*?\*/', '', texto, flags=re.S)

    pares = [
        ('endif', r'\bif\s*\(.*?\)\s*:', r'\bendif\b'),
        ('endforeach', r'\bforeach\s*\(.*?\)\s*:', r'\bendforeach\b'),
        ('endfor', r'\bfor\s*\(.*?\)\s*:', r'\bendfor\b'),
        ('endwhile', r'\bwhile\s*\(.*?\)\s*:', r'\bendwhile\b'),
        ('endswitch', r'\bswitch\s*\(.*?\)\s*:', r'\bendswitch\b'),
    ]

    for nombre, abre, cierra in pares:
        n_abre = len(re.findall(abre, sin_comentarios, flags=re.S))
        n_cierra = len(re.findall(cierra, sin_comentarios))
        if n_cierra > n_abre:
            errores.append(f'hay {n_cierra} "{nombre}" pero solo {n_abre} aperturas')
        if n_abre > n_cierra and nombre == 'endif':
            # los if con llaves tambien matchean el patron, asi que solo
            # avisamos cuando faltan cierres habiendo alguno
            if n_cierra > 0:
                errores.append(
                    f'ojo: {n_abre} posibles "if(...):" y {n_cierra} "{nombre}" '
                    '(revisar a mano si son de bloque alternativo)'
                )
    return errores


def main(raiz):
    archivos = sorted(Path(raiz).rglob('*.php'))
    total_errores = 0

    for archivo in archivos:
        texto = archivo.read_text(encoding='utf-8')
        rel = archivo.relative_to(raiz)

        errores = analizar(texto)
        errores += revisar_alternativas(texto)

        if not texto.lstrip().startswith('<?php'):
            errores.append('el archivo no empieza con <?php')

        if errores:
            total_errores += len(errores)
            print(f'\n[X] {rel}')
            for e in errores:
                print(f'    - {e}')
        else:
            print(f'[ok] {rel}')

    print(f'\n{len(archivos)} archivos revisados, {total_errores} avisos.')
    return 1 if total_errores else 0


if __name__ == '__main__':
    sys.exit(main(Path(sys.argv[1])))
