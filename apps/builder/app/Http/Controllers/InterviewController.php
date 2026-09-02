<?php

namespace App\Http\Controllers;

use App\Generation\GenerationFailed;
use App\Generation\PaletteProposer;
use App\Generation\SiteGenerator;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Entrevista guiada de tres etapas: marca → propósito → contenido.
 * Cada etapa persiste al confirmar. Volver atrás no pierde nada.
 *
 * MVP: formularios con copy conversacional + propuesta de paletas
 * determinística. La capa de repreguntas con IA es la siguiente iteración;
 * el generador con IA (ClaudeGenerator) ya está detrás de la interfaz.
 */
class InterviewController extends Controller
{
    public function __construct(private PaletteProposer $palettes) {}

    public function show(Project $project, Request $request)
    {
        $draft = $project->draft();
        $stage = $request->query('stage', $draft->stage === 'welcome' ? 'welcome' : $draft->stage);

        return match ($stage) {
            'purpose' => view('interview.purpose', compact('project', 'draft')),
            'content' => view('interview.content', compact('project', 'draft')),
            'review' => view('interview.review', compact('project', 'draft')),
            'done' => redirect()->route('interview.result', $project),
            'brand' => $this->brandView($project),
            default => view('interview.welcome', compact('project', 'draft')),
        };
    }

    private function brandView(Project $project)
    {
        $draft = $project->draft();

        return view('interview.brand', [
            'project' => $project,
            'draft' => $draft,
            'proposals' => $this->palettes->propose(
                $draft->purpose['industry'] ?? null,
                $draft->palette_regenerations,
            ),
        ]);
    }

    public function regeneratePalettes(Project $project)
    {
        $draft = $project->draft();
        abort_if($draft->palette_regenerations >= 3, 422, 'Ya viste varias opciones. Elegí una y la ajustás en el CMS.');
        $draft->increment('palette_regenerations');

        return redirect()->route('interview', ['project' => $project, 'stage' => 'brand']);
    }

    public function saveBrand(Project $project, Request $request)
    {
        $data = $request->validate([
            'primary' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'accent' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'background' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'text' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'pairing' => 'required|string|max:40',
            'source' => 'nullable|in:palette,logo,manual,description',
        ]);

        $assumptions = [];
        if (($data['source'] ?? 'palette') === 'palette') {
            $assumptions[] = 'Colores y tipografía elegidos de una propuesta por rubro (no había manual de marca).';
        }

        $draft = $project->draft();
        $wasConfirmed = $draft->brand_status === 'confirmed';
        $draft->update([
            'brand' => [
                'colors' => ['primary' => $data['primary'], 'accent' => $data['accent'], 'background' => $data['background'], 'text' => $data['text']],
                'typography' => ['pairing' => $data['pairing']],
                'assumptions' => $assumptions,
            ],
            'brand_status' => 'confirmed',
            'stage' => $draft->purpose_status === 'confirmed' ? 'review' : 'purpose',
        ]);
        if ($wasConfirmed) {
            $draft->flagLaterStages('brand');
            $draft->save();
        }

        return redirect()->route('interview', ['project' => $project, 'stage' => $draft->stage]);
    }

    public function savePurpose(Project $project, Request $request)
    {
        $data = $request->validate([
            'industry' => 'required|string|max:60',
            'audience' => 'nullable|string|max:200',
            'goal' => 'required|in:contact,catalog,about',
            'template' => 'required|in:landing,institucional',
        ]);

        $draft = $project->draft();
        $wasConfirmed = $draft->purpose_status === 'confirmed';
        $draft->update([
            'purpose' => $data + ['assumptions' => []],
            'purpose_status' => 'confirmed',
            'stage' => $draft->content_status === 'confirmed' ? 'review' : 'content',
        ]);
        if ($wasConfirmed) {
            $draft->flagLaterStages('purpose');
            $draft->save();
        }

        return redirect()->route('interview', ['project' => $project, 'stage' => $draft->stage]);
    }

    public function saveContent(Project $project, Request $request)
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:80',
            'tagline' => 'nullable|string|max:120',
            'email' => 'nullable|email|max:120',
            'phone' => 'nullable|string|max:40',
            'whatsapp' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:200',
            'schedule' => 'nullable|string|max:200',
            'services_raw' => 'nullable|string',
            'about_text' => 'nullable|string|max:4000',
            'reference_texts' => 'nullable|string|max:8000',
        ]);

        $services = collect(preg_split('/\r?\n/', (string) ($data['services_raw'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(function ($line) {
                [$name, $desc] = array_pad(explode(':', $line, 2), 2, null);

                return array_filter(['name' => trim($name), 'description' => $desc ? trim($desc) : null], fn ($v) => $v !== null && $v !== '');
            })
            ->values()->all();

        $assumptions = [];
        if (empty($data['whatsapp']) && empty($data['phone'])) {
            $assumptions[] = 'Sin teléfono ni WhatsApp: el sitio no muestra botón de contacto directo.';
        }

        $draft = $project->draft();
        $draft->update([
            'content' => array_filter([
                'business_name' => $data['business_name'],
                'tagline' => $data['tagline'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'address' => $data['address'] ?? null,
                'schedule' => $data['schedule'] ?? null,
                'services' => $services,
                'about_text' => $data['about_text'] ?? null,
                'reference_texts' => array_filter([$data['reference_texts'] ?? null]),
                'assumptions' => $assumptions,
            ], fn ($v) => $v !== null && $v !== [] && $v !== ''),
            'content_status' => 'confirmed',
            'stage' => 'review',
        ]);

        return redirect()->route('interview', ['project' => $project, 'stage' => 'review']);
    }

    public function reopen(Project $project, string $stage)
    {
        abort_unless(in_array($stage, ['brand', 'purpose', 'content'], true), 404);
        $project->draft()->update(['stage' => $stage]);

        return redirect()->route('interview', ['project' => $project, 'stage' => $stage]);
    }

    public function generate(Project $project, SiteGenerator $generator)
    {
        $draft = $project->draft();
        abort_unless($draft->allConfirmed(), 422, 'Faltan etapas por confirmar.');

        try {
            $generator->run($project);
        } catch (GenerationFailed $e) {
            return redirect()->route('interview', ['project' => $project, 'stage' => 'review'])
                ->withErrors(['generacion' => $e->errors]);
        }

        return redirect()->route('interview.result', $project);
    }

    public function result(Project $project)
    {
        $project->load('site');

        return view('interview.result', ['project' => $project]);
    }
}
