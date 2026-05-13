<?php
/**
 * Mater-Natura — Helpers globales
 *
 * Funciones de utilidad disponibles en todas las plantillas.
 */

declare(strict_types=1);

if (!function_exists('svg_icon')) {
    /**
     * Renderiza un icono SVG de CoreUI inline.
     *
     * Los SVGs inline heredan el color CSS del texto padre, lo que permite
     * colorearlos con la clase del contenedor.
     *
     * @param string $name   Nombre del icono (sin prefijo cil- ni extensión)
     * @param string $class  Clases CSS adicionales para el SVG
     * @param string $title  Texto accesible (aria-label / title)
     * @return string SVG inline o marcador de posición si no existe
     */
    function svg_icon(string $name, string $class = '', string $title = ''): string
    {
        $path = MATER_PUBLIC_DIR . '/assets/icons/cil-' . $name . '.svg';
        if (!file_exists($path)) {
            return '<span class="icon-missing" style="color:red">[!' . $name . ']</span>';
        }

        $svg = file_get_contents($path);
        // Eliminar declaración XML y DOCTYPE
        $svg = preg_replace('/^<\?xml[^>]+\?>\s*/', '', $svg);
        $svg = preg_replace('/^<!DOCTYPE[^>]+>\s*/i', '', $svg);

        // Limpiar atributos que interfieren
        $svg = preg_replace('/\s+(width|height)="[^"]*"/', '', $svg);
        $svg = preg_replace('/\s+fill="[^"]*"/', '', $svg);

        // Insertar class y atributos accesibles
        $attrs = ' class="icon icon-' . $name . ' ' . $class . '"'
               . ' aria-hidden="true"'
               . ' focusable="false"';
        if ($title) {
            $attrs .= ' role="img" aria-label="' . $title . '"';
            $svg = str_replace('</svg>', '<title>' . $title . '</title></svg>', $svg);
        }
        $svg = str_replace('<svg', '<svg' . $attrs, $svg);

        return "\n" . $svg . "\n";
    }
}

if (!function_exists('renderHtml')) {
    /**
     * Renderiza HTML seguro permitiendo solo etiquetas básicas.
     *
     * Útil para contenido generado por el editor WYSIWYG.
     * Elimina etiquetas peligrosas (script, iframe, etc.) y
     * permite solo las etiquetas de formato seguro.
     *
     * @param string $html El HTML a sanitizar y renderizar.
     * @return string HTML seguro listo para imprimir.
     */
    function renderHtml(string $html): string
    {
        // Permitir solo etiquetas de formato seguro y contenido inline
        $allowed = '<p><br><strong><em><b><i><u><s><sub><sup>'
                 . '<h1><h2><h3><h4><h5><h6>'
                 . '<ul><ol><li><blockquote><pre><code>'
                 . '<hr><div><span>'
                 . '<a><img><figure><figcaption>'
                 . '<table><thead><tbody><tr><td><th>';

        // Eliminar etiquetas no permitidas y su contenido
        $html = strip_tags($html, $allowed);

        // Decodificar entidades HTML para evitar doble escape
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Limpiar atributos peligrosos de enlaces e imágenes
        $html = preg_replace('/<a\s[^>]*href=["\']javascript:[^>]*>/i', '<a>', $html);
        $html = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        return $html;
    }
}
