<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDeployToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (empty($request->hasHeader('X-Deploy-Token')) || $request->header('X-Deploy-Token') !== config('app.deploy_token')) {
            abort(403);
        }

        return $next($request);
    }
}
