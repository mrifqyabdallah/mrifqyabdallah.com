<?php

namespace App\Http\Controllers;

use App\Jobs\DeployApp;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeployController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        DeployApp::dispatch();

        return response()->noContent();
    }
}
