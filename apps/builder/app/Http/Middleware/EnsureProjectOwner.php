<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;

/**
 * Un usuario sólo ve los proyectos de su organización.
 */
class EnsureProjectOwner
{
    public function handle(Request $request, Closure $next)
    {
        $project = $request->route('project');

        if ($project instanceof Project && $project->organization_id !== $request->user()?->organization_id) {
            abort(404);
        }

        return $next($request);
    }
}
