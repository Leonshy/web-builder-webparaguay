<?php

namespace App\Http\Controllers\Cms;

use App\Cms\SchemaForm;
use App\Cms\SiteValidator;
use App\Http\Controllers\Controller;
use App\Models\Cms\Page;
use App\Models\Cms\Section;
use Illuminate\Http\Request;

class CmsSectionController extends Controller
{
    public function __construct(private SiteValidator $validator, private SchemaForm $form) {}

    public function store(Request $request, Page $page)
    {
        $data = $request->validate([
            'type' => 'required|in:'.implode(',', $this->form->sectionTypes()),
            'variant' => 'required|string',
        ]);

        abort_unless(in_array($data['variant'], $this->form->variantsFor($data['type']), true), 422, 'Variante inválida.');

        $section = $this->validator->persistOrRollback($page->site, fn () => $page->sections()->create([
            'type' => $data['type'],
            'variant' => $data['variant'],
            'position' => ($page->sections()->max('position') ?? -1) + 1,
            'content' => $this->stubContent($data['type']),
        ]));

        return redirect()->route('cms.section', $section)->with('ok', 'Sección agregada.');
    }

    public function show(Section $section)
    {
        $section->load('page.site');

        return view('cms.section', [
            'section' => $section,
            'envelopeFields' => $this->form->envelopeFields(),
            'contentFields' => $this->form->contentFields($section->type),
            'variants' => $this->form->variantsFor($section->type),
            'iconKeys' => $this->form->iconKeys(),
        ]);
    }

    public function update(Request $request, Section $section)
    {
        $envelope = (array) $request->input('envelope', []);
        $rawContent = (array) $request->input('content', []);

        $content = [];
        foreach ($this->form->contentFields($section->type) as $field) {
            if (! array_key_exists($field['name'], $rawContent)) {
                continue;
            }
            $value = $this->form->coerce($rawContent[$field['name']], $field);
            if ($value !== null && $value !== '' && $value !== []) {
                $content[$field['name']] = $value;
            }
        }

        $this->validator->persistOrRollback($section->page->site, fn () => $section->update([
            'is_active' => $request->boolean('is_active'),
            'anchor' => $this->clean($envelope['anchor'] ?? null),
            'label' => $this->clean($envelope['label'] ?? null),
            'title' => $this->clean($envelope['title'] ?? null),
            'subtitle' => $this->clean($envelope['subtitle'] ?? null),
            'background' => $envelope['background'] ?? 'default',
            'background_image' => $this->form->coerce($envelope['background_image'] ?? null, ['kind' => 'image']),
            'content' => $content,
        ]));

        return redirect()->route('cms.section', $section)->with('ok', 'Sección guardada.');
    }

    /**
     * Cambiar de variante NUNCA pierde contenido. Sólo cambia `variant`;
     * los campos que la variante nueva no use quedan guardados en `content`.
     */
    public function changeVariant(Request $request, Section $section)
    {
        $variant = $request->string('variant')->toString();
        abort_unless(in_array($variant, $this->form->variantsFor($section->type), true), 422, 'Variante inválida.');

        $this->validator->persistOrRollback($section->page->site, fn () => $section->update(['variant' => $variant]));

        return redirect()->route('cms.section', $section)->with('ok', "Variante cambiada a «{$variant}». El contenido se conservó.");
    }

    public function move(Request $request, Section $section)
    {
        $direction = $request->string('direction')->toString();
        $siblings = $section->page->sections()->orderBy('position')->get();
        $index = $siblings->search(fn ($s) => $s->id === $section->id);
        $swapWith = $direction === 'up' ? $siblings->get($index - 1) : $siblings->get($index + 1);

        if ($swapWith) {
            [$section->position, $swapWith->position] = [$swapWith->position, $section->position];
            $section->save();
            $swapWith->save();
        }

        return redirect()->route('cms.page', $section->page)->with('ok', 'Orden actualizado.');
    }

    public function destroy(Section $section)
    {
        $page = $section->page;
        abort_if($page->sections()->count() <= 1, 422, 'Una página necesita al menos una sección.');

        $this->validator->persistOrRollback($page->site, fn () => $section->delete());

        return redirect()->route('cms.page', $page)->with('ok', 'Sección eliminada.');
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @return array<string,mixed> */
    private function stubContent(string $type): array
    {
        return match ($type) {
            'hero' => ['headline' => 'Título del hero'],
            'page_header' => ['heading' => 'Encabezado'],
            'media_text' => ['body' => '<p>Texto de ejemplo.</p>'],
            'feature_list' => ['items' => [['title' => 'Ítem 1'], ['title' => 'Ítem 2']]],
            'cta_banner' => ['primary_button' => ['label' => 'Contactar', 'action' => 'anchor', 'anchor' => 'contacto']],
            'contact_form' => ['fields' => ['name', 'email', 'phone', 'message']],
            'stats' => ['items' => [['value' => 10, 'label' => 'Uno'], ['value' => 20, 'label' => 'Dos']]],
            'rich_text' => ['body' => '<p>Contenido.</p>'],
            'gallery' => ['items' => [['image' => ['src' => '', 'alt' => 'Imagen 1']], ['image' => ['src' => '', 'alt' => 'Imagen 2']], ['image' => ['src' => '', 'alt' => 'Imagen 3']]]],
            'entity_grid' => ['source' => 'manual', 'items' => [['title' => 'Elemento 1']]],
            'testimonials' => ['items' => [['quote' => 'Muy conformes.']]],
            'faq' => ['items' => [['question' => '¿Pregunta 1?', 'answer' => '<p>Respuesta.</p>'], ['question' => '¿Pregunta 2?', 'answer' => '<p>Respuesta.</p>']]],
            'pricing_plans' => ['items' => [['name' => 'Plan']]],
            'video' => ['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            default => [],
        };
    }
}
