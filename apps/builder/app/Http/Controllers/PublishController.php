<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Publishing\Plans;
use App\Publishing\PublishSite;
use Illuminate\Http\Request;
use Webparaguay\Provisioning\ProvisioningException;

class PublishController extends Controller
{
    public function show(Project $project)
    {
        $project->load('site', 'payments', 'backofficeTasks');

        return view('publish.show', ['project' => $project, 'plans' => Plans::all()]);
    }

    public function store(Request $request, Project $project, PublishSite $publish)
    {
        $data = $request->validate([
            'plan' => 'required|in:'.implode(',', array_keys(Plans::all())),
            'domain_kind' => 'required|in:subdomain,gtld,compy',
            'domain_value' => 'nullable|string|max:120',
        ]);

        if (in_array($data['domain_kind'], ['gtld', 'compy'], true) && empty($data['domain_value'])) {
            return back()->withErrors(['domain_value' => 'Indicá el dominio.']);
        }

        try {
            $publish->handle($project, $data['plan'], $data['domain_kind'], $data['domain_value'] ?? null);
        } catch (ProvisioningException $e) {
            return back()->withErrors(['publicacion' => $e->getMessage()]);
        }

        return redirect()->route('publish.show', $project)->with('ok', 'Sitio publicado.');
    }
}
