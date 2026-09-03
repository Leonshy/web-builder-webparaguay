<?php

namespace App\Cms;

use App\Models\Cms\Page;
use App\Models\Cms\Section;
use App\Models\Cms\Site;

/**
 * Arma el documento JSON del sitio a partir de los modelos, y lo importa
 * de vuelta. El renderer y el validador siempre trabajan sobre ESE documento:
 * el esquema es el contrato, la base es sólo cómo lo guardamos.
 */
final class SiteAssembler
{
    /** @return array<string,mixed> documento listo para validar y renderizar */
    public function toArray(Site $site): array
    {
        $doc = [
            'schema_version' => $site->schema_version ?: '0.1',
            'template' => $site->template ?: 'landing',
            'theme' => $site->theme ?: [],
            'settings' => $site->settings ?: [],
        ];

        if (! empty($site->layout)) {
            $doc['layout'] = $site->layout;
        }

        $doc['pages'] = $site->pages
            ->sortBy('position')
            ->values()
            ->map(fn (Page $page) => $this->page($page))
            ->all();

        return $doc;
    }

    /** @return array<string,mixed> */
    private function page(Page $page): array
    {
        $out = [
            'slug' => $page->slug,
            'title' => $page->title,
            'is_home' => (bool) $page->is_home,
            'is_active' => (bool) $page->is_active,
            'show_in_nav' => (bool) $page->show_in_nav,
            'order' => (int) $page->position,
        ];

        if (! empty($page->seo)) {
            $out['seo'] = $page->seo;
        }

        $out['sections'] = $page->sections
            ->sortBy('position')
            ->values()
            ->map(fn (Section $section) => $this->section($section))
            ->all();

        return $out;
    }

    /** @return array<string,mixed> */
    private function section(Section $section): array
    {
        $out = [
            'id' => 'sec_'.$section->id,
            'type' => $section->type,
            'variant' => $section->variant,
            'order' => (int) $section->position,
            'is_active' => (bool) $section->is_active,
            'background' => $section->background ?: 'default',
            'content' => $section->content ?: new \stdClass(),
        ];

        foreach (['anchor', 'label', 'title', 'subtitle'] as $field) {
            if (! empty($section->{$field})) {
                $out[$field] = $section->{$field};
            }
        }

        if (! empty($section->background_image)) {
            $out['background_image'] = $section->background_image;
        }

        return $out;
    }

    /**
     * Importa un documento JSON a modelos. Usado para sembrar el ejemplo.
     * No valida (de eso se ocupa quien llama).
     *
     * @param  array<string,mixed>  $doc
     */
    public function importInto(Site $site, array $doc): Site
    {
        $site->fill([
            'template' => $doc['template'] ?? 'landing',
            'schema_version' => $doc['schema_version'] ?? '0.1',
            'theme' => $doc['theme'] ?? [],
            'settings' => $doc['settings'] ?? [],
            'layout' => $doc['layout'] ?? null,
        ])->save();

        $site->pages()->delete();

        foreach ($doc['pages'] ?? [] as $pi => $pageData) {
            $page = $site->pages()->create([
                'slug' => $pageData['slug'],
                'title' => $pageData['title'],
                'is_home' => $pageData['is_home'] ?? false,
                'is_active' => $pageData['is_active'] ?? true,
                'show_in_nav' => $pageData['show_in_nav'] ?? true,
                'position' => $pageData['order'] ?? $pi,
                'seo' => $pageData['seo'] ?? null,
            ]);

            foreach ($pageData['sections'] ?? [] as $si => $sectionData) {
                $page->sections()->create([
                    'type' => $sectionData['type'],
                    'variant' => $sectionData['variant'],
                    'position' => $sectionData['order'] ?? $si,
                    'is_active' => $sectionData['is_active'] ?? true,
                    'anchor' => $sectionData['anchor'] ?? null,
                    'label' => $sectionData['label'] ?? null,
                    'title' => $sectionData['title'] ?? null,
                    'subtitle' => $sectionData['subtitle'] ?? null,
                    'background' => $sectionData['background'] ?? 'default',
                    'background_image' => $sectionData['background_image'] ?? null,
                    'content' => $sectionData['content'] ?? [],
                ]);
            }
        }

        return $site->refresh();
    }
}
