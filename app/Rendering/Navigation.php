<?php

namespace App\Rendering;

/**
 * Navegación DERIVADA de las páginas activas y las anclas de sección.
 * Nunca se escribe a mano (regla 12 del CLAUDE.md).
 *
 *  - Un ítem por página activa con show_in_nav.
 *  - Para la página actual, se agregan las anclas de sus secciones
 *    (sección con `anchor` y con `label` o `title`) como sub-ítems.
 *  - Una landing de una sola página queda como los anclas de esa página.
 */
final class Navigation
{
    /**
     * @return array<int,array{label:string,href:string,current:bool,children:array<int,array{label:string,href:string}>}>
     */
    public static function build(SiteConfig $site, SitePage $current, UrlContext $url): array
    {
        $pages = array_values(array_filter($site->pages(), fn (SitePage $p) => $p->showInNav()));

        $anchorItems = static function (SitePage $page) use ($url): array {
            $items = [];
            foreach ($page->sections() as $section) {
                $label = $section->navLabel();
                if ($label !== null) {
                    $items[] = ['label' => $label, 'href' => $url->anchorHref($page, $section->domId())];
                }
            }

            return $items;
        };

        // Landing de una sola página: la navegación son las anclas de esa página.
        if (count($pages) <= 1) {
            $page = $pages[0] ?? $current;

            return array_map(
                fn (array $item) => [...$item, 'current' => false, 'children' => []],
                $anchorItems($page),
            );
        }

        $nav = [];
        foreach ($pages as $page) {
            $isCurrent = $page->slug() === $current->slug();

            $nav[] = [
                'label' => $page->title(),
                'href' => $url->pageHref($page),
                'current' => $isCurrent,
                'children' => $isCurrent ? $anchorItems($page) : [],
            ];
        }

        return $nav;
    }
}
