<?php

namespace App\Http\Controllers;

use App\Jobs\DeployApp;
use Illuminate\Http\Request;

class DeployController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        DeployApp::dispatch();

        return response()->json(['ok' => true]);
    }
}
