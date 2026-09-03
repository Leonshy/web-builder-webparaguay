<?php

namespace App\Rendering;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitiza `richtext` contra una allowlist cerrada (§2 del Anexo A).
 *
 * Todo richtext se sanitiza SIEMPRE. Lo genera una IA o lo edita un cliente:
 * ninguno es fuente confiable de HTML.
 *
 * Etiquetas permitidas: p strong em ul ol li a br h3 h4.
 * En <a> solo href (http/https/mailto/tel/ancla) y se fuerza rel/target seguros.
 */
final class HtmlSanitizer
{
    private const ALLOWED = ['p', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'br', 'h3', 'h4'];

    public function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="wp-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('wp-root');
        if (! $root) {
            return '';
        }

        $this->sanitizeChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! in_array($tag, self::ALLOWED, true)) {
                    // Etiqueta no permitida: se conserva el texto, se descarta la etiqueta.
                    $this->sanitizeChildren($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);

                    continue;
                }

                $this->stripAttributes($child, $tag);
                $this->sanitizeChildren($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }

    private function stripAttributes(DOMElement $el, string $tag): void
    {
        $href = $tag === 'a' ? $this->safeHref($el->getAttribute('href')) : null;

        foreach (iterator_to_array($el->attributes ?? []) as $attr) {
            $el->removeAttribute($attr->name);
        }

        if ($tag === 'a' && $href !== null) {
            $el->setAttribute('href', $href);
            if (str_starts_with($href, 'http')) {
                $el->setAttribute('target', '_blank');
                $el->setAttribute('rel', 'noopener noreferrer nofollow');
            }
        }
    }

    private function safeHref(string $href): ?string
    {
        $href = trim($href);
        if ($href === '') {
            return null;
        }

        if (str_starts_with($href, '#') || str_starts_with($href, '/')) {
            return $href;
        }

        $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true) ? $href : null;
    }
}
