<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Publishing\Plans;
use App\Publishing\PublishSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webparaguay\Provisioning\ProvisioningException;

class PublishController extends Controller
{
    public function show(Project $project)
    {
        $project->load('site', 'payments', 'backofficeTasks', 'organization');

        return view('publish.show', [
            'project' => $project,
            'plans' => Plans::all(),
            'organization' => $project->organization,
        ]);
    }

    public function saveBilling(Request $request, Project $project)
    {
        $data = $request->validate([
            'billing_phone' => 'required|string|max:40',
            'billing_address' => 'required|string|max:200',
            'billing_city' => 'required|string|max:80',
            'billing_state' => 'nullable|string|max:80',
            'billing_postcode' => 'nullable|string|max:20',
            'billing_country' => 'required|string|size:2',
        ]);

        Auth::user()->organization->update($data);

        return redirect()->route('publish.show', $project)->with('ok', 'Datos de facturación guardados.');
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

        if (! $project->organization->billingComplete()) {
            return back()->withErrors(['billing' => 'Primero completá y guardá los datos de facturación.']);
        }

        try {
            $publish->handle($project, $data['plan'], $data['domain_kind'], $data['domain_value'] ?? null);
        } catch (ProvisioningException $e) {
            return back()->withErrors(['publicacion' => $e->getMessage()]);
        }

        return redirect()->route('publish.show', $project)->with('ok', 'Publicación iniciada.');
    }
}
