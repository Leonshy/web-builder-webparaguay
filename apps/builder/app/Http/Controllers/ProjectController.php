<?php

namespace App\Http\Controllers;

use App\Ai\AiUsageRecorder;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;

/**
 * Vista mínima de proyectos (Tarea 3). Sin auth todavía: en el MVP se opera
 * sobre la primera organización. La entrevista guiada y la orquestación de
 * agentes son la Tarea 4.
 */
class ProjectController extends Controller
{
    public function __construct(private AiUsageRecorder $usage) {}

    private function org(): Organization
    {
        return Organization::firstOrCreate(['name' => 'Organización de prueba']);
    }

    public function index()
    {
        $org = $this->org();

        return view('projects.index', [
            'organization' => $org,
            'projects' => $org->projects()->with('site')->latest()->get(),
            'aiCostUsd' => $this->usage->totalUsd($org),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120']);
        $org = $this->org();

        $user = $org->owner() ?? $org->users()->create([
            'name' => 'Titular',
            'email' => 'titular+'.$org->id.'@ejemplo.com.py',
            'password' => bcrypt(str()->random(32)),
        ]);

        $project = $org->projects()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'status' => 'draft',
        ]);

        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project)
    {
        $project->load('site', 'organization', 'aiUsages');

        return view('projects.show', ['project' => $project]);
    }
}
