<?php

namespace App\Http\Controllers;

use App\Ai\AiUsageRecorder;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function __construct(private AiUsageRecorder $usage) {}

    private function org(): Organization
    {
        return Auth::user()->organization;
    }

    public function index()
    {
        $org = $this->org();

        return view('projects.index', [
            'organization' => $org,
            'projects' => $org->projects()->with('site')->latest()->get(),
            'aiCostUsd' => $this->usage->totalUsd($org),
            'canStart' => $org->canStartNewProject(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120']);
        $org = $this->org();

        abort_unless($org->canStartNewProject(), 403,
            'El plan gratuito permite un proyecto activo. Publicá el actual o eliminálo para empezar otro.');

        $project = $org->projects()->create([
            'user_id' => Auth::id(),
            'name' => $data['name'],
            'status' => 'draft',
        ]);

        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project)
    {
        $this->authorizeProject($project);
        $project->load('site', 'organization', 'aiUsages', 'interviewDraft');

        return view('projects.show', ['project' => $project]);
    }

    private function authorizeProject(Project $project): void
    {
        abort_unless($project->organization_id === $this->org()->id, 404);
    }
}
