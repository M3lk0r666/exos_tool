<?php

namespace App\Services\Parser;

/**
 * Divide un archivo "show tech-support all" en secciones {comando => contenido}.
 *
 * Reconoce los tres estilos de delimitador documentados en el Anexo A:
 *   1. "->comando"  (EXOS 22.x, sin espacio)
 *   2. "-> comando" (EXOS 12.x, con espacio)
 *   3. "!  comando" enmarcado entre líneas de "======" (bloques agregados manualmente)
 *
 * Las secciones repetidas se concatenan. El sufijo de pipes ("| exclude ...")
 * se conserva en la clave; el matching por prefijo lo ignora (ver ExosParser).
 */
class SectionSplitter
{
    /**
     * @return array<string, string>
     */
    public function split(string $text): array
    {
        $sections = [];
        $current = null;
        $buffer = [];

        $flush = function () use (&$sections, &$current, &$buffer) {
            if ($current !== null) {
                $content = implode("\n", $buffer);
                $sections[$current] = isset($sections[$current])
                    ? $sections[$current]."\n".$content
                    : $content;
            }
            $buffer = [];
        };

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (str_starts_with($line, '->')) {
                $flush();
                $current = trim(substr($line, 2));
                continue;
            }

            // Estilo "!  comando" (bloques enmarcados en ======)
            if (preg_match('/^!\s+(\S.*)$/', $line, $m)) {
                $flush();
                $current = trim($m[1]);
                continue;
            }

            // Las líneas de marco "======" no forman parte del contenido si
            // preceden/siguen inmediatamente a un encabezado "!".
            $buffer[] = $line;
        }

        $flush();

        return $sections;
    }
}
